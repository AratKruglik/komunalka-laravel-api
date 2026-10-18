<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Address\Models\Address;
use Modules\Ai\Actions\ValidateMeterReadingProposal;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Models\Tariff;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

mutates(ValidateMeterReadingProposal::class);

function proposalMeterFor(User $user, string $slug = 'electricity', array $attributes = []): Meter
{
    $address = Address::factory()->create();
    $user->addresses()->attach($address->getKey(), ['is_primary' => true]);
    $type = UtilityType::query()->where('slug', $slug)->first() ?? UtilityType::factory()->create(['slug' => $slug]);

    return Meter::factory()->create([
        'address_id' => $address->getKey(),
        'utility_type_id' => $type->getKey(),
        'initial_reading' => 100,
        ...$attributes,
    ]);
}

describe('ValidateMeterReadingProposal', function (): void {
    beforeEach(function (): void {
        $this->travelTo('2026-06-15 12:00:00');
        $this->user = User::factory()->create();
        $this->meter = proposalMeterFor($this->user);
        $this->photo = AssistantMeterPhoto::factory()->for($this->user)->create();
        $this->action = app(ValidateMeterReadingProposal::class);
        $this->arguments = fn (array $overrides = []): array => [
            'photo_id' => $this->photo->getKey(),
            'meter_id' => $this->meter->getKey(),
            'reading_value' => 150.5,
            'reading_date' => '2026-06-15',
            ...$overrides,
        ];
    });

    it('builds a proposal using the initial reading when there is no history', function (): void {
        $proposal = $this->action->handle($this->user, ($this->arguments)());

        expect($proposal->meter->getKey())->toBe($this->meter->getKey())
            ->and($proposal->previousValue)->toBe(100.0)
            ->and($proposal->previousDate)->toBeNull()
            ->and($proposal->readingValue)->toBe(150.5)
            ->and($proposal->consumption())->toBe(50.5)
            ->and($proposal->tariff)->toBeNull()
            ->and($proposal->tariffOptions)->toBe([]);
    });

    it('uses the latest reading as the previous value', function (): void {
        MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
            'reading_value' => 120,
            'reading_date' => '2026-05-01',
        ]);

        $proposal = $this->action->handle($this->user, ($this->arguments)());

        expect($proposal->previousValue)->toBe(120.0)
            ->and($proposal->previousDate)->toBe('2026-05-01');
    });

    it('accepts a value equal to the previous one and a date equal to the previous date', function (): void {
        MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
            'reading_value' => 150.5,
            'reading_date' => '2026-06-15',
        ]);

        $proposal = $this->action->handle($this->user, ($this->arguments)());

        expect($proposal->consumption())->toBe(0.0);
    });

    it('marks the date source as exif when it equals the photo exif date', function (): void {
        $photo = AssistantMeterPhoto::factory()->for($this->user)->create(['taken_at' => '2026-06-14 10:00:00']);

        $proposal = $this->action->handle($this->user, ($this->arguments)(['photo_id' => $photo->getKey(), 'reading_date' => '2026-06-14']));

        expect($proposal->dateSource)->toBe('exif')
            ->and($proposal->exifDate)->toBe('2026-06-14');
    });

    it('marks the date source as manual when it differs from the exif date', function (): void {
        $photo = AssistantMeterPhoto::factory()->for($this->user)->create(['taken_at' => '2026-06-14 10:00:00']);

        $proposal = $this->action->handle($this->user, ($this->arguments)(['photo_id' => $photo->getKey()]));

        expect($proposal->dateSource)->toBe('manual')
            ->and($proposal->exifDate)->toBe('2026-06-14');
    });

    it('marks the date source as manual without exif', function (): void {
        $proposal = $this->action->handle($this->user, ($this->arguments)());

        expect($proposal->dateSource)->toBe('manual')
            ->and($proposal->exifDate)->toBeNull();
    });

    it('accepts today as the reading date', function (): void {
        $proposal = $this->action->handle($this->user, ($this->arguments)(['reading_date' => '2026-06-15']));

        expect($proposal->readingDate)->toBe('2026-06-15');
    });

    it('rejects invalid input', function (array $overrides, string $field): void {
        try {
            $this->action->handle($this->user, ($this->arguments)($overrides));
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            expect($exception->errors())->toHaveKey($field);
        }
    })->with([
        'non uuid photo' => [['photo_id' => 'abc'], 'photo_id'],
        'negative value' => [['reading_value' => -1], 'reading_value'],
        'non numeric value' => [['reading_value' => 'abc'], 'reading_value'],
        'wrong date format' => [['reading_date' => '15.06.2026'], 'reading_date'],
        'impossible date' => [['reading_date' => '2026-02-31'], 'reading_date'],
        'future date' => [['reading_date' => '2026-06-16'], 'reading_date'],
        'non integer meter' => [['meter_id' => 'x'], 'meter_id'],
    ]);

    it('rejects a value lower than the previous reading', function (): void {
        expect(fn () => $this->action->handle($this->user, ($this->arguments)(['reading_value' => 99])))
            ->toThrow(ValidationException::class, 'Значення показника не може бути менше попереднього.');
    });

    it('rejects a date earlier than the previous reading date', function (): void {
        MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
            'reading_value' => 120,
            'reading_date' => '2026-06-10',
        ]);

        expect(fn () => $this->action->handle($this->user, ($this->arguments)(['reading_date' => '2026-06-09'])))
            ->toThrow(ValidationException::class, '10.06.2026');
    });

    it('rejects a photo that does not exist', function (): void {
        expect(fn () => $this->action->handle($this->user, ($this->arguments)(['photo_id' => (string) Str::uuid()])))
            ->toThrow(ValidationException::class, 'Фото вже використано');
    });

    it('rejects a photo that belongs to another user', function (): void {
        $foreign = AssistantMeterPhoto::factory()->create();

        expect(fn () => $this->action->handle($this->user, ($this->arguments)(['photo_id' => $foreign->getKey()])))
            ->toThrow(ValidationException::class, 'Фото вже використано');
    });

    it('rejects consumed and expired photos', function (string $state): void {
        $photo = AssistantMeterPhoto::factory()->for($this->user)->{$state}()->create();

        expect(fn () => $this->action->handle($this->user, ($this->arguments)(['photo_id' => $photo->getKey()])))
            ->toThrow(ValidationException::class, 'Фото вже використано');
    })->with(['consumed', 'expired']);

    it('rejects a meter owned by another user', function (): void {
        $foreign = proposalMeterFor(User::factory()->create());

        expect(fn () => $this->action->handle($this->user, ($this->arguments)(['meter_id' => $foreign->getKey()])))
            ->toThrow(ValidationException::class, 'Лічильник не знайдено');
    });

    it('rejects an inactive meter', function (): void {
        $this->meter->update(['is_active' => false]);

        expect(fn () => $this->action->handle($this->user, ($this->arguments)()))
            ->toThrow(ValidationException::class, 'Лічильник не знайдено');
    });

    it('rejects a meter of an unsupported utility type', function (): void {
        $other = proposalMeterFor($this->user, 'internet');

        expect(fn () => $this->action->handle($this->user, ($this->arguments)(['meter_id' => $other->getKey()])))
            ->toThrow(ValidationException::class, 'Лічильник не знайдено');
    });

    describe('tariffs', function (): void {
        beforeEach(function (): void {
            $this->provider = ServiceProvider::factory()->create(['utility_type_id' => $this->meter->utility_type_id]);
            $this->meter->update(['service_provider_id' => $this->provider->getKey()]);
            $this->makeTariff = fn (string $name, array $overrides = []): Tariff => Tariff::factory()->create([
                'service_provider_id' => $this->provider->getKey(),
                'utility_type_id' => $this->meter->utility_type_id,
                'name' => $name,
                'effective_from' => '2026-01-01',
                'effective_to' => null,
                ...$overrides,
            ]);
        });

        it('picks the only effective tariff automatically', function (): void {
            $tariff = ($this->makeTariff)('T1');

            $proposal = $this->action->handle($this->user, ($this->arguments)());

            expect($proposal->tariff?->getKey())->toBe($tariff->getKey())
                ->and($proposal->tariffOptions)->toHaveCount(1);
        });

        it('requires a tariff when several zones are effective', function (): void {
            ($this->makeTariff)('T1');
            ($this->makeTariff)('T2');

            expect(fn () => $this->action->handle($this->user, ($this->arguments)()))
                ->toThrow(ValidationException::class, 'тарифну зону');
        });

        it('accepts a chosen tariff among several zones', function (): void {
            ($this->makeTariff)('T1');
            $t2 = ($this->makeTariff)('T2');

            $proposal = $this->action->handle($this->user, ($this->arguments)(['tariff_id' => $t2->getKey()]));

            expect($proposal->tariff?->getKey())->toBe($t2->getKey())
                ->and($proposal->tariffOptions)->toHaveCount(2);
        });

        it('rejects a tariff outside the effective options', function (): void {
            ($this->makeTariff)('T1');
            $foreign = Tariff::factory()->create();

            expect(fn () => $this->action->handle($this->user, ($this->arguments)(['tariff_id' => $foreign->getKey()])))
                ->toThrow(ValidationException::class, 'не відповідає');
        });

        it('ignores tariffs that are not effective at the reading date', function (): void {
            ($this->makeTariff)('Old', ['effective_from' => '2025-01-01', 'effective_to' => '2025-12-31']);

            $proposal = $this->action->handle($this->user, ($this->arguments)());

            expect($proposal->tariffOptions)->toBe([])
                ->and($proposal->tariff)->toBeNull();
        });
    });

    it('rejects a tariff for a meter without a provider', function (): void {
        $tariff = Tariff::factory()->create(['utility_type_id' => $this->meter->utility_type_id]);

        expect(fn () => $this->action->handle($this->user, ($this->arguments)(['tariff_id' => $tariff->getKey()])))
            ->toThrow(ValidationException::class, 'не відповідає');
    });
});
