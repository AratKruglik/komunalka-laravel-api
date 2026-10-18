<?php

declare(strict_types=1);

namespace Modules\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;
use Laravel\Ai\Concerns\InteractsWithApprovals;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\Ai\Actions\ValidateMeterReadingProposal;
use Modules\Ai\DTO\MeterReadingProposal;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Auth\Models\User;
use Modules\Meter\Actions\CreateBatchReadings;
use Modules\Meter\DTO\BatchReadingData;
use Modules\Meter\DTO\CreateMeterReadingData;
use Modules\Meter\Models\MeterReading;
use Stringable;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class RecordMeterReading implements Approvable, Tool
{
    use InteractsWithApprovals;

    public function __construct(
        private readonly User $user,
        private readonly ValidateMeterReadingProposal $validate,
        private readonly CreateBatchReadings $createReadings,
    ) {}

    public function name(): string
    {
        return 'record_meter_reading';
    }

    public function description(): Stringable|string
    {
        return 'Записати показання лічильника з фото. Викликай лише коли відомі photo_id, лічильник, значення на фото і дата показання. Користувач підтверджує запис окремо.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'photo_id' => $schema->string()->required(),
            'meter_id' => $schema->integer()->required(),
            'reading_value' => $schema->number()->required(),
            'reading_date' => $schema->string()->description('Дата у форматі YYYY-MM-DD')->required(),
            'tariff_id' => $schema->integer()->nullable()->description('Тарифна зона, лише для багатозонних лічильників'),
        ];
    }

    /** @throws JsonException */
    public function handle(Request $request): Stringable|string
    {
        try {
            $reading = DB::transaction(fn (): MeterReading => $this->record($request->all()));
        } catch (ValidationException $exception) {
            return $this->encode(['status' => 'failed', 'message' => (string) collect($exception->errors())->flatten()->first()]);
        } catch (HttpException) {
            return $this->encode(['status' => 'failed', 'message' => 'Лічильник не знайдено серед ваших активних.']);
        } catch (Throwable $exception) {
            Log::error('Assistant meter reading tool failed', [
                'exception' => $exception::class,
                'user_id' => $this->user->getKey(),
            ]);

            return $this->encode(['status' => 'failed', 'message' => 'Не вдалося зберегти показання. Спробуйте ще раз або внесіть їх вручну.']);
        }

        return $this->encode([
            'status' => 'saved',
            'message' => 'Показання збережено.',
            'reading' => [
                'id' => $reading->getKey(),
                'meter_id' => $reading->meter_id,
                'reading_value' => $reading->reading_value,
                'reading_date' => $reading->reading_date->toDateString(),
                'consumption' => $reading->consumption,
            ],
        ]);
    }

    /** @param array<string, mixed> $arguments */
    private function record(array $arguments): MeterReading
    {
        $photoId = $arguments['photo_id'] ?? null;
        if (is_string($photoId) && Str::isUuid($photoId)) {
            AssistantMeterPhoto::query()
                ->whereKey($photoId)
                ->where('user_id', $this->user->getKey())
                ->lockForUpdate()
                ->first();
        }

        $proposal = $this->validate->handle($this->user, $arguments);
        $temporaryPath = $this->copyPhotoToTemporaryFile($proposal);

        try {
            $result = $this->createReadings->handle(
                (int) $this->user->getKey(),
                new BatchReadingData(
                    readings: [new CreateMeterReadingData(
                        meterId: (int) $proposal->meter->getKey(),
                        readingValue: $proposal->readingValue,
                        readingDate: $proposal->readingDate,
                        notes: null,
                        isEstimated: false,
                        tariffId: $proposal->tariff?->getKey(),
                    )],
                    photos: [$proposal->meter->getKey() => [$temporaryPath]],
                ),
            );
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

        $proposal->photo->update(['consumed_at' => now()]);

        return $result->readings[0];
    }

    private function copyPhotoToTemporaryFile(MeterReadingProposal $proposal): string
    {
        $contents = Storage::disk($proposal->photo->disk)->get($proposal->photo->path);
        if ($contents === null) {
            throw ValidationException::withMessages(['photo_id' => ['Фото вже використано або строк його дії минув. Надішліть фото ще раз.']]);
        }

        $path = sys_get_temp_dir().'/'.Str::uuid().'.jpg';
        file_put_contents($path, $contents);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws JsonException
     */
    private function encode(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
