<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Ai\Actions\StoreAssistantMeterPhoto;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

mutates(StoreAssistantMeterPhoto::class);

function storePhotoUpload(int $width = 100, int $height = 60, string $format = 'jpeg', ?string $exifDate = null): UploadedFile
{
    $image = new Imagick;
    $image->newImage($width, $height, new ImagickPixel('gray'));
    $image->setImageFormat($format);
    $blob = $image->getImageBlob();
    if ($exifDate !== null) {
        $tiff = 'II*'."\0".pack('V', 8)
            .pack('vvvVV', 1, 0x8769, 4, 1, 26).pack('V', 0)
            .pack('vvvVV', 1, 0x9003, 2, 20, 44).pack('V', 0)
            .$exifDate."\0";
        $segment = "Exif\0\0".$tiff;
        $blob = substr($blob, 0, 2)."\xFF\xE1".pack('n', strlen($segment) + 2).$segment.substr($blob, 2);
    }
    $path = tempnam(sys_get_temp_dir(), 'qa').'.'.$format;
    file_put_contents($path, $blob);

    return new UploadedFile($path, 'meter.'.$format, 'image/'.$format, null, true);
}

describe('StoreAssistantMeterPhoto', function (): void {
    beforeEach(function (): void {
        $this->travelTo('2026-06-15 12:00:00');
        Storage::fake(AssistantMeterPhoto::DISK);
        $this->user = User::factory()->create();
        $this->action = app(StoreAssistantMeterPhoto::class);
    });

    it('stores a sanitized jpeg and records an expiring photo', function (): void {
        $photo = $this->action->handle($this->user, storePhotoUpload(format: 'png'), null);

        expect($photo->user_id)->toBe($this->user->getKey())
            ->and($photo->disk)->toBe(AssistantMeterPhoto::DISK)
            ->and($photo->path)->toStartWith('assistant-photos/'.$this->user->getKey().'/')->toEndWith('.jpg')
            ->and($photo->consumed_at)->toBeNull()
            ->and($photo->expires_at->equalTo(now()->addHours(AssistantMeterPhoto::TTL_HOURS)))->toBeTrue()
            ->and(Storage::disk(AssistantMeterPhoto::DISK)->exists($photo->path))->toBeTrue()
            ->and(substr(Storage::disk(AssistantMeterPhoto::DISK)->get($photo->path), 0, 3))->toBe("\xFF\xD8\xFF");
    });

    it('limits the longer side to 2048 pixels', function (): void {
        $photo = $this->action->handle($this->user, storePhotoUpload(3000, 1000));

        $stored = new Imagick;
        $stored->readImageBlob(Storage::disk(AssistantMeterPhoto::DISK)->get($photo->path));

        expect($stored->getImageWidth())->toBe(2048)
            ->and($stored->getImageHeight())->toBeLessThanOrEqual(1000);
    });

    it('keeps small images unscaled', function (): void {
        $photo = $this->action->handle($this->user, storePhotoUpload(100, 60));

        $stored = new Imagick;
        $stored->readImageBlob(Storage::disk(AssistantMeterPhoto::DISK)->get($photo->path));

        expect([$stored->getImageWidth(), $stored->getImageHeight()])->toBe([100, 60]);
    });

    it('reads the exif capture date and strips exif from the stored copy', function (): void {
        $photo = $this->action->handle($this->user, storePhotoUpload(exifDate: '2026:06:10 08:30:00'));

        $stored = new Imagick;
        $stored->readImageBlob(Storage::disk(AssistantMeterPhoto::DISK)->get($photo->path));

        expect($photo->taken_at?->toDateString())->toBe('2026-06-10')
            ->and(trim((string) $stored->getImageProperty('exif:DateTimeOriginal')))->toBe('');
    });

    it('leaves taken_at empty when the image has no exif', function (): void {
        expect($this->action->handle($this->user, storePhotoUpload())->taken_at)->toBeNull();
    });

    it('ignores implausible exif dates', function (): void {
        expect($this->action->handle($this->user, storePhotoUpload(exifDate: '1970:01:01 00:00:00'))->taken_at)->toBeNull();
    });

    it('rejects a file that is not an image', function (): void {
        $path = tempnam(sys_get_temp_dir(), 'qa');
        file_put_contents($path, 'not an image');
        $file = new UploadedFile($path, 'x.jpg', 'image/jpeg', null, true);

        expect(fn () => $this->action->handle($this->user, $file))->toThrow(ValidationException::class, 'Не вдалося прочитати зображення');
        expect(AssistantMeterPhoto::query()->count())->toBe(0)
            ->and(Storage::disk(AssistantMeterPhoto::DISK)->allFiles())->toBe([]);
    });

    it('rejects an image with oversized dimensions before decoding it', function (): void {
        $file = storePhotoUpload(13000, 10, 'png');

        expect(fn () => $this->action->handle($this->user, $file))->toThrow(ValidationException::class, 'завелике за роздільною здатністю');
        expect(AssistantMeterPhoto::query()->count())->toBe(0)
            ->and(Storage::disk(AssistantMeterPhoto::DISK)->allFiles())->toBe([]);
    });

    it('replaces previous unconsumed photos of the same conversation only', function (): void {
        $conversation = '11111111-1111-4111-8111-111111111111';
        Storage::disk(AssistantMeterPhoto::DISK)->put('assistant-photos/old.jpg', 'x');
        Storage::disk(AssistantMeterPhoto::DISK)->put('assistant-photos/other.jpg', 'x');
        $old = AssistantMeterPhoto::factory()->for($this->user)->create(['conversation_id' => $conversation, 'path' => 'assistant-photos/old.jpg']);
        $otherConversation = AssistantMeterPhoto::factory()->for($this->user)->create(['conversation_id' => '22222222-2222-4222-8222-222222222222', 'path' => 'assistant-photos/other.jpg']);
        $foreignUser = AssistantMeterPhoto::factory()->create(['conversation_id' => $conversation]);

        $new = $this->action->handle($this->user, storePhotoUpload(), $conversation);

        expect(AssistantMeterPhoto::query()->find($old->getKey()))->toBeNull()
            ->and(Storage::disk(AssistantMeterPhoto::DISK)->exists('assistant-photos/old.jpg'))->toBeFalse()
            ->and(AssistantMeterPhoto::query()->find($otherConversation->getKey()))->not->toBeNull()
            ->and(AssistantMeterPhoto::query()->find($foreignUser->getKey()))->not->toBeNull()
            ->and(AssistantMeterPhoto::query()->find($new->getKey()))->not->toBeNull();
    });

    it('uses a public conversation to replace a photo before an SDK conversation exists', function (): void {
        $conversation = HelpConversation::factory()->for($this->user)->create();
        $other = HelpConversation::factory()->for($this->user)->create();
        $old = $this->action->handle($this->user, storePhotoUpload(), null, $conversation);
        $unrelated = $this->action->handle($this->user, storePhotoUpload(), null, $other);

        $new = $this->action->handle($this->user, storePhotoUpload(), null, $conversation);

        expect(AssistantMeterPhoto::query()->find($old->id))->toBeNull()
            ->and(AssistantMeterPhoto::query()->find($unrelated->id))->not->toBeNull()
            ->and($new->help_conversation_id)->toBe($conversation->id)
            ->and($new->conversation_id)->toBeNull();
    });

    it('rejects a conversation owned by another user before storing the file', function (): void {
        $conversation = HelpConversation::factory()->create();

        expect(fn () => $this->action->handle($this->user, storePhotoUpload(), null, $conversation))
            ->toThrow(HttpException::class);
        expect(AssistantMeterPhoto::query()->count())->toBe(0);
    });
});
