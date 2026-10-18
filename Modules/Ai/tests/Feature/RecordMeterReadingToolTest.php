<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Tools\Request;
use Modules\Address\Models\Address;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Ai\Tools\RecordMeterReading;
use Modules\Auth\Models\User;
use Modules\Meter\Actions\CreateBatchReadings;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

mutates(RecordMeterReading::class);

describe('RecordMeterReading tool', function (): void {
    beforeEach(function (): void {
        $this->travelTo('2026-06-15 12:00:00');
        Storage::fake(AssistantMeterPhoto::DISK);
        $this->user = User::factory()->create();
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => true]);
        $this->meter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => UtilityType::factory()->create(['slug' => 'cold-water'])->getKey(),
            'initial_reading' => 10,
        ]);
        $image = new Imagick;
        $image->newImage(40, 40, new ImagickPixel('white'));
        $image->setImageFormat('jpeg');
        $this->photo = AssistantMeterPhoto::factory()->for($this->user)->create(['path' => 'assistant-photos/test.jpg']);
        Storage::disk(AssistantMeterPhoto::DISK)->put('assistant-photos/test.jpg', $image->getImageBlob());
        $this->tool = app()->make(RecordMeterReading::class, ['user' => $this->user]);
        $this->call = fn (array $overrides = []): array => json_decode((string) $this->tool->handle(new Request([
            'photo_id' => $this->photo->getKey(),
            'meter_id' => $this->meter->getKey(),
            'reading_value' => 25.5,
            'reading_date' => '2026-06-15',
            ...$overrides,
        ])), true, 512, JSON_THROW_ON_ERROR);
    });

    it('is an approvable tool with a stable name', function (): void {
        expect($this->tool)->toBeInstanceOf(Approvable::class)
            ->and($this->tool->name())->toBe('record_meter_reading');
    });

    it('saves the reading, attaches the photo and consumes it', function (): void {
        $result = ($this->call)();

        $reading = MeterReading::query()->where('meter_id', $this->meter->getKey())->firstOrFail();
        expect($result['status'])->toBe('saved')
            ->and($result['reading']['id'])->toBe($reading->getKey())
            ->and($result['reading']['consumption'])->toEqual(15.5)
            ->and($result['reading']['reading_date'])->toBe('2026-06-15')
            ->and((float) $reading->reading_value)->toBe(25.5)
            ->and($reading->getMedia('photos')->count())->toBe(1)
            ->and($this->photo->fresh()->consumed_at)->not->toBeNull();
    });

    it('does not record the same photo twice', function (): void {
        ($this->call)();
        $second = ($this->call)(['reading_value' => 30]);

        expect($second['status'])->toBe('failed')
            ->and($second['message'])->toContain('Фото вже використано')
            ->and(MeterReading::query()->count())->toBe(1);
    });

    it('returns a failed status and saves nothing for invalid values', function (): void {
        $result = ($this->call)(['reading_value' => 5]);

        expect($result)->toBe(['status' => 'failed', 'message' => 'Значення показника не може бути менше попереднього.'])
            ->and(MeterReading::query()->count())->toBe(0)
            ->and($this->photo->fresh()->consumed_at)->toBeNull();
    });

    it('refuses a photo owned by another user', function (): void {
        $foreign = AssistantMeterPhoto::factory()->create(['path' => 'assistant-photos/test.jpg']);

        $result = ($this->call)(['photo_id' => $foreign->getKey()]);

        expect($result['status'])->toBe('failed')
            ->and(MeterReading::query()->count())->toBe(0)
            ->and($foreign->fresh()->consumed_at)->toBeNull();
    });

    it('refuses a meter owned by another user', function (): void {
        $foreign = Meter::factory()->create();

        $result = ($this->call)(['meter_id' => $foreign->getKey()]);

        expect($result['status'])->toBe('failed')
            ->and(MeterReading::query()->count())->toBe(0);
    });

    it('refuses an expired photo', function (): void {
        $this->photo->update(['expires_at' => now()->subMinute()]);

        expect(($this->call)()['status'])->toBe('failed')
            ->and(MeterReading::query()->count())->toBe(0);
    });

    it('fails without consuming the photo when the stored file is missing', function (): void {
        Storage::disk(AssistantMeterPhoto::DISK)->delete('assistant-photos/test.jpg');

        $result = ($this->call)();

        expect($result['status'])->toBe('failed')
            ->and(MeterReading::query()->count())->toBe(0)
            ->and($this->photo->fresh()->consumed_at)->toBeNull();
    });

    it('rejects malformed arguments without leaking exceptions', function (): void {
        $result = ($this->call)(['photo_id' => 'not-a-uuid']);

        expect($result['status'])->toBe('failed');
    });

    it('rejects non-finite or absurdly large values', function (mixed $value): void {
        expect(($this->call)(['reading_value' => $value])['status'])->toBe('failed')
            ->and(MeterReading::query()->count())->toBe(0);
    })->with([
        'overflow string' => ['1e400'],
        'infinity' => [INF],
        'nan' => [NAN],
        'above bound' => [100_000_000],
    ]);

    it('hides unexpected exception details from the model', function (): void {
        $this->mock(CreateBatchReadings::class)
            ->shouldReceive('handle')
            ->andThrow(new RuntimeException('SQLSTATE[23505] /tmp/secret.jpg'));
        $this->tool = app()->make(RecordMeterReading::class, ['user' => $this->user]);

        $result = ($this->call)();

        expect($result['status'])->toBe('failed')
            ->and($result['message'])->not->toContain('SQLSTATE')
            ->and($result['message'])->not->toContain('/tmp')
            ->and($this->photo->fresh()->consumed_at)->toBeNull();
    });

    it('describes the required schema fields', function (): void {
        $schema = $this->tool->schema(new JsonSchemaTypeFactory);

        expect(array_keys($schema))->toBe(['photo_id', 'meter_id', 'reading_value', 'reading_date', 'tariff_id']);
    });
});
