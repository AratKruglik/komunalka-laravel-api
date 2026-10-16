<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Imagick;
use ImagickException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Auth\Models\User;
use Throwable;

class StoreAssistantMeterPhoto
{
    use AsAction;

    private const int MAX_SIDE = 2048;

    private const int JPEG_QUALITY = 85;

    private const int MAX_PIXELS = 50_000_000;

    private const int MAX_DIMENSION = 12_000;

    private const array ALLOWED_FORMATS = ['JPEG', 'PNG', 'WEBP', 'HEIC', 'HEIF'];

    public function __construct(private readonly ForgetAssistantMeterPhoto $forget) {}

    public function handle(User $user, UploadedFile $file, ?string $conversationId = null): AssistantMeterPhoto
    {
        $this->limitImagickResources();

        try {
            $this->assertSafeImage($file->getRealPath().'[0]');
            $image = new Imagick($file->getRealPath().'[0]');
            $takenAt = $this->readTakenAt($image, $file);
            $blob = $this->sanitize($image);
        } catch (ImagickException) {
            throw ValidationException::withMessages(['photo' => ['Не вдалося прочитати зображення. Спробуйте інше фото.']]);
        }

        $path = 'assistant-photos/'.$user->getKey().'/'.Str::uuid().'.jpg';
        Storage::disk(AssistantMeterPhoto::DISK)->put($path, $blob);

        if ($conversationId !== null) {
            $this->discardPreviousPhotos($user, $conversationId);
        }

        return AssistantMeterPhoto::query()->create([
            'user_id' => $user->getKey(),
            'conversation_id' => $conversationId,
            'disk' => AssistantMeterPhoto::DISK,
            'path' => $path,
            'taken_at' => $takenAt,
            'expires_at' => now()->addHours(AssistantMeterPhoto::TTL_HOURS),
        ]);
    }

    /** @throws ImagickException */
    private function limitImagickResources(): void
    {
        Imagick::setResourceLimit(Imagick::RESOURCETYPE_MEMORY, 256 * 1024 * 1024);
        Imagick::setResourceLimit(Imagick::RESOURCETYPE_MAP, 512 * 1024 * 1024);
        Imagick::setResourceLimit(Imagick::RESOURCETYPE_DISK, 1024 * 1024 * 1024);
        Imagick::setResourceLimit(Imagick::RESOURCETYPE_AREA, self::MAX_PIXELS);
        Imagick::setResourceLimit(Imagick::RESOURCETYPE_TIME, 15);
    }

    /** @throws ImagickException */
    private function assertSafeImage(string $source): void
    {
        $probe = new Imagick;
        $probe->pingImage($source);
        $format = strtoupper($probe->getImageFormat());
        $width = $probe->getImageWidth();
        $height = $probe->getImageHeight();
        $probe->clear();

        if (! in_array($format, self::ALLOWED_FORMATS, true)) {
            throw ValidationException::withMessages(['photo' => ['Фото має бути у форматі JPG, PNG, WebP або HEIC.']]);
        }

        if ($width === 0 || $height === 0 || $width * $height > self::MAX_PIXELS || max($width, $height) > self::MAX_DIMENSION) {
            throw ValidationException::withMessages(['photo' => ['Фото завелике за роздільною здатністю. Надішліть інше фото.']]);
        }
    }

    private function discardPreviousPhotos(User $user, string $conversationId): void
    {
        AssistantMeterPhoto::query()
            ->where('user_id', $user->getKey())
            ->where('conversation_id', $conversationId)
            ->whereNull('consumed_at')
            ->get()
            ->each(fn (AssistantMeterPhoto $photo) => $this->forget->handle($photo));
    }

    /** @throws ImagickException */
    private function sanitize(Imagick $image): string
    {
        $image->autoOrient();
        $image->setImageBackgroundColor('white');
        $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);

        if (max($image->getImageWidth(), $image->getImageHeight()) > self::MAX_SIDE) {
            $image->thumbnailImage(self::MAX_SIDE, self::MAX_SIDE, true);
        }

        $image->stripImage();
        $image->setImageFormat('jpeg');
        $image->setImageCompressionQuality(self::JPEG_QUALITY);

        return $image->getImageBlob();
    }

    private function readTakenAt(Imagick $image, UploadedFile $file): ?CarbonImmutable
    {
        $raw = $this->imageProperty($image, 'exif:DateTimeOriginal')
            ?? $this->imageProperty($image, 'exif:DateTimeDigitized');
        $offset = $this->imageProperty($image, 'exif:OffsetTimeOriginal');

        if ($raw === null) {
            [$raw, $offset] = $this->readNativeExif($file);
        }

        return $raw === null ? null : $this->parseExifDate($raw, $offset);
    }

    private function imageProperty(Imagick $image, string $key): ?string
    {
        try {
            $value = trim((string) $image->getImageProperty($key));
        } catch (ImagickException) {
            return null;
        }

        return $value === '' ? null : $value;
    }

    /** @return array{0: ?string, 1: ?string} */
    private function readNativeExif(UploadedFile $file): array
    {
        if (! function_exists('exif_read_data') || ! in_array($file->getMimeType(), ['image/jpeg', 'image/tiff'], true)) {
            return [null, null];
        }

        try {
            $exif = @exif_read_data($file->getRealPath(), 'EXIF');
        } catch (Throwable) {
            return [null, null];
        }

        if (! is_array($exif)) {
            return [null, null];
        }

        $raw = $exif['DateTimeOriginal'] ?? $exif['DateTimeDigitized'] ?? null;
        $offset = $exif['UndefinedTag:0x9011'] ?? null;

        return [is_string($raw) ? $raw : null, is_string($offset) ? $offset : null];
    }

    private function parseExifDate(string $raw, ?string $offset): ?CarbonImmutable
    {
        $appTimezone = (string) config('app.timezone');
        $timezone = $offset !== null && preg_match('/^[+-]\d{2}:\d{2}$/', trim($offset)) === 1
            ? trim($offset)
            : $appTimezone;

        try {
            $date = CarbonImmutable::createFromFormat('!Y:m:d H:i:s', trim($raw), $timezone);
        } catch (Throwable) {
            return null;
        }

        if ($date === null || $date->year < 1990) {
            return null;
        }

        return $date->setTimezone($appTimezone);
    }
}
