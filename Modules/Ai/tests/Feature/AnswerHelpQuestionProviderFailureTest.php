<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Modules\Address\Models\Address;
use Modules\Ai\Actions\AnswerHelpQuestion;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\UtilityType;

mutates(AnswerHelpQuestion::class);

function providerFailureUpload(): UploadedFile
{
    $image = new Imagick;
    $image->newImage(80, 50, new ImagickPixel('gray'));
    $image->setImageFormat('jpeg');
    $path = tempnam(sys_get_temp_dir(), 'qa').'.jpg';
    file_put_contents($path, $image->getImageBlob());

    return new UploadedFile($path, 'meter.jpg', 'image/jpeg', null, true);
}

describe('AnswerHelpQuestion when the AI provider rejects the request', function (): void {
    beforeEach(function (): void {
        Storage::fake(AssistantMeterPhoto::DISK);
        config([
            'ai.providers.openrouter.key' => 'test-key',
            'ai-support.model' => 'google/gemma-4-26b-a4b-it:free',
        ]);
        $this->user = User::factory()->create();
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => true]);
        Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => UtilityType::factory()->create(['slug' => 'electricity'])->getKey(),
            'initial_reading' => 100,
        ]);

        Http::preventStrayRequests();
        Http::fake(['*openrouter.ai/*' => Http::response([
            'error' => [
                'message' => 'Provider returned error',
                'code' => 429,
                'metadata' => [
                    'raw' => 'google/gemma-4-26b-a4b-it:free is temporarily rate-limited upstream. Please retry shortly.',
                    'provider_name' => 'Google AI Studio',
                ],
            ],
        ], 429)]);

        $this->action = app(AnswerHelpQuestion::class);
    });

    it('tells the user the assistant is busy instead of blaming the photo when the provider rate-limits', function (): void {
        $result = $this->action->handle($this->user, 'Внеси показання холодної води', providerFailureUpload());

        expect($result['text'])->toContain('перевантажений')
            ->not->toContain('Не вдалося обробити фото')
            ->and($result['sources'])->toBe([['label' => 'Внести показання', 'path' => '/readings/create']])
            ->and($result['pending_approval'])->toBeNull()
            ->and(AssistantMeterPhoto::query()->count())->toBe(0)
            ->and(Storage::disk(AssistantMeterPhoto::DISK)->allFiles())->toBe([]);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'openrouter.ai'));
    });

    it('logs the provider status and reason without any image data', function (): void {
        Log::spy();

        $this->action->handle($this->user, 'Внеси показання холодної води', providerFailureUpload());

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => $context['exception'] === RateLimitedException::class
                && $context['status'] === 429
                && $context['model'] === 'google/gemma-4-26b-a4b-it:free'
                && str_contains($context['reason'], 'rate-limited upstream')
                && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'base64')
                && ! str_contains(json_encode($context, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'холодної води'),
        )->once();
    });

    it('keeps the text fallback for a rate-limited plain question but logs the reason', function (): void {
        Log::spy();

        $result = $this->action->handle($this->user, 'Як внести показання?');

        expect($result['text'])->not->toContain('перевантажений')
            ->not->toContain('Не вдалося обробити фото');
        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => $context['exception'] === RateLimitedException::class
                && $context['status'] === 429,
        )->once();
    });
});

describe('AnswerHelpQuestion failure classification and log reason', function (): void {
    beforeEach(function (): void {
        Storage::fake(AssistantMeterPhoto::DISK);
        config([
            'ai.providers.openrouter.key' => 'test-key',
            'ai-support.model' => 'google/gemma-4-26b-a4b-it:free',
        ]);
        $this->user = User::factory()->create();
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => true]);
        Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => UtilityType::factory()->create(['slug' => 'electricity'])->getKey(),
            'initial_reading' => 100,
        ]);
        Http::preventStrayRequests();
        $this->action = app(AnswerHelpQuestion::class);
    });

    it('shows the busy text when the provider is overloaded and falls back to error.message for the log reason', function (): void {
        Http::fake(['*openrouter.ai/*' => Http::response(['error' => ['message' => 'Upstream unavailable']], 503)]);
        Log::spy();

        $result = $this->action->handle($this->user, 'Внеси показання', providerFailureUpload());

        expect($result['text'])->toContain('перевантажений')
            ->and(AssistantMeterPhoto::query()->count())->toBe(0);
        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => $context['exception'] === ProviderOverloadedException::class
                && $context['status'] === 503
                && $context['reason'] === 'Upstream unavailable',
        )->once();
    });

    it('keeps the generic photo fallback for a non-capacity provider error', function (): void {
        Http::fake(['*openrouter.ai/*' => Http::response(['error' => ['message' => 'Bad request']], 400)]);
        Log::spy();

        $result = $this->action->handle($this->user, 'Внеси показання', providerFailureUpload());

        expect($result['text'])->toContain('Не вдалося обробити фото')
            ->not->toContain('перевантажений')
            ->and($result['sources'])->toBe([['label' => 'Внести показання', 'path' => '/readings/create']]);
        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => $context['status'] === 400
                && $context['reason'] === 'Bad request',
        )->once();
    });

    it('logs a null status and the exception message when no HTTP response exists', function (): void {
        Http::fake(['*openrouter.ai/*' => fn () => throw new ConnectionException('Connection refused')]);
        Log::spy();

        $result = $this->action->handle($this->user, 'Внеси показання', providerFailureUpload());

        expect($result['text'])->toContain('Не вдалося обробити фото')
            ->not->toContain('перевантажений');
        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => $context['exception'] === ProviderConnectionException::class
                && $context['status'] === null
                && str_starts_with($context['reason'], 'Could not connect to AI provider'),
        )->once();
    });

    it('truncates a long provider raw reason to 300 characters', function (): void {
        Http::fake(['*openrouter.ai/*' => Http::response(['error' => ['metadata' => ['raw' => str_repeat('r', 500)]]], 429)]);
        Log::spy();

        $this->action->handle($this->user, 'Внеси показання', providerFailureUpload());

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => mb_strlen($context['reason']) === 303
                && str_starts_with($context['reason'], 'rrr'),
        )->once();
    });

    it('deletes the photo and shows the busy text when the provider raw payload is an object', function (): void {
        Http::fake(['*openrouter.ai/*' => Http::response(['error' => [
            'message' => 'Provider returned error',
            'code' => 429,
            'metadata' => ['raw' => ['error' => ['code' => 429, 'message' => 'upstream busy']]],
        ]], 429)]);
        Log::spy();

        $result = $this->action->handle($this->user, 'Внеси показання', providerFailureUpload());

        expect($result['text'])->toContain('перевантажений')
            ->and(AssistantMeterPhoto::query()->count())->toBe(0)
            ->and(Storage::disk(AssistantMeterPhoto::DISK)->allFiles())->toBe([]);
        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => $context['exception'] === RateLimitedException::class
                && $context['status'] === 429
                && $context['reason'] === 'Provider returned error',
        )->once();
    });

    it('does not throw on a text turn when the provider raw payload is an object', function (): void {
        Http::fake(['*openrouter.ai/*' => Http::response(['error' => [
            'message' => 'Provider returned error',
            'metadata' => ['raw' => ['error' => ['code' => 429, 'message' => 'upstream busy']]],
        ]], 429)]);
        Log::spy();

        $result = $this->action->handle($this->user, 'Як внести показання?');

        expect($result['text'])->not->toBe('')
            ->not->toContain('перевантажений');
        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => $context['exception'] === RateLimitedException::class,
        )->once();
    });

    it('does not put the message of a non-provider exception into the log reason', function (): void {
        Http::fake(['*openrouter.ai/*' => fn () => throw new RuntimeException('secret question text')]);
        Log::spy();

        $result = $this->action->handle($this->user, 'Внеси показання', providerFailureUpload());

        expect($result['text'])->toContain('Не вдалося обробити фото')
            ->and(AssistantMeterPhoto::query()->count())->toBe(0);
        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => $context['exception'] === RuntimeException::class
                && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'secret question text'),
        )->once();
    });
});
