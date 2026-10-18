<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ai\DTO\MeterReadingProposal;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Auth\Models\User;
use Modules\Billing\Models\Tariff;
use Modules\Meter\Models\Meter;
use Modules\Meter\Repositories\Contracts\MeterReadingRepositoryInterface;
use Throwable;

class ValidateMeterReadingProposal
{
    use AsAction;

    public function __construct(private readonly MeterReadingRepositoryInterface $readings) {}

    /**
     * @param  array<string, mixed>  $arguments
     *
     * @throws ValidationException
     */
    public function handle(User $user, array $arguments): MeterReadingProposal
    {
        $input = $this->validateShape($arguments);
        $photo = $this->findPhoto($user, $input['photo_id']);
        $meter = $this->findMeter($user, (int) $input['meter_id']);
        $date = $this->parseDate($input['reading_date']);
        $options = $this->tariffOptions($meter, $date);
        $tariff = $this->resolveTariff($options, isset($input['tariff_id']) ? (int) $input['tariff_id'] : null);

        $previous = $this->readings->getLatestForMeterAndTariff($meter->getKey(), $tariff?->getKey());
        $previousValue = $previous !== null ? $previous->reading_value : $meter->initial_reading;
        $previousDate = $previous?->reading_date->toDateString();

        if ($previousDate !== null && $date->toDateString() < $previousDate) {
            $this->fail('reading_date', 'Дата показання не може бути раніше за дату останнього показання ('.CarbonImmutable::parse($previousDate)->format('d.m.Y').').');
        }

        $value = (float) $input['reading_value'];
        if ($value < $previousValue) {
            $this->fail('reading_value', 'Значення показника не може бути менше попереднього.');
        }

        $readingDate = $date->toDateString();

        return new MeterReadingProposal(
            meter: $meter,
            tariff: $tariff,
            tariffOptions: $options,
            previousValue: $previousValue,
            previousDate: $previousDate,
            readingValue: $value,
            readingDate: $readingDate,
            dateSource: $photo->exifDate() === $readingDate ? 'exif' : 'manual',
            exifDate: $photo->exifDate(),
            photo: $photo,
        );
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validateShape(array $arguments): array
    {
        return Validator::make($arguments, [
            'photo_id' => ['required', 'uuid'],
            'meter_id' => ['required', 'integer'],
            'reading_value' => ['required', 'numeric', 'min:0', 'max:99999999.999', fn (string $attribute, mixed $value, Closure $fail) => is_finite((float) $value) ?: $fail($attribute)],
            'reading_date' => ['required', 'date_format:Y-m-d'],
            'tariff_id' => ['nullable', 'integer'],
        ], [
            'photo_id.*' => 'Фото не знайдено. Надішліть фото ще раз.',
            'meter_id.*' => 'Лічильник не знайдено серед ваших активних.',
            'reading_value.*' => 'Вкажіть коректне значення показника (число, не менше нуля).',
            'reading_date.*' => 'Вкажіть дату показання у форматі РРРР-ММ-ДД.',
            'tariff_id.*' => 'Вкажіть коректну тарифну зону.',
        ])->validate();
    }

    /** @throws ValidationException */
    private function findPhoto(User $user, string $photoId): AssistantMeterPhoto
    {
        $photo = AssistantMeterPhoto::query()
            ->active()
            ->where('user_id', $user->getKey())
            ->find($photoId);

        return $photo ?? $this->fail('photo_id', 'Фото вже використано або строк його дії минув. Надішліть фото ще раз.');
    }

    /** @throws ValidationException */
    private function findMeter(User $user, int $meterId): Meter
    {
        $meter = Meter::query()
            ->active()
            ->whereKey($meterId)
            ->whereIn('address_id', $user->addresses()->select('addresses.id'))
            ->whereHas('utilityType', fn ($query) => $query->whereIn('slug', DescribeUserMeters::SUPPORTED_UTILITY_SLUGS))
            ->with(['address', 'utilityType'])
            ->first();

        return $meter ?? $this->fail('meter_id', 'Лічильник не знайдено серед ваших активних.');
    }

    /** @throws ValidationException */
    private function parseDate(string $value): CarbonImmutable
    {
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, (string) config('app.timezone'));
        } catch (Throwable) {
            $date = null;
        }

        if ($date === null) {
            $this->fail('reading_date', 'Вкажіть дату показання у форматі РРРР-ММ-ДД.');
        }

        if ($date->isAfter(CarbonImmutable::now((string) config('app.timezone'))->endOfDay())) {
            $this->fail('reading_date', 'Дата показання не може бути в майбутньому.');
        }

        return $date;
    }

    /** @return list<Tariff> */
    private function tariffOptions(Meter $meter, CarbonImmutable $date): array
    {
        if ($meter->service_provider_id === null) {
            return [];
        }

        return Tariff::query()
            ->where('service_provider_id', $meter->service_provider_id)
            ->where('utility_type_id', $meter->utility_type_id)
            ->effectiveAt($date)
            ->orderBy('name')
            ->get()
            ->all();
    }

    /**
     * @param  list<Tariff>  $options
     *
     * @throws ValidationException
     */
    private function resolveTariff(array $options, ?int $tariffId): ?Tariff
    {
        if ($options === []) {
            return $tariffId === null ? null : $this->fail('tariff_id', 'Обраний тариф не відповідає провайдеру або типу комунальної послуги лічильника.');
        }

        if ($tariffId === null) {
            return count($options) === 1 ? $options[0] : $this->fail('tariff_id', 'Для цього лічильника вкажіть тарифну зону (T1/T2).');
        }

        foreach ($options as $option) {
            if ($option->getKey() === $tariffId) {
                return $option;
            }
        }

        return $this->fail('tariff_id', 'Обраний тариф не відповідає провайдеру або типу комунальної послуги лічильника.');
    }

    /** @throws ValidationException */
    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
