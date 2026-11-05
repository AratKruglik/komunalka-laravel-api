<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\TextResponse;
use Laravel\Ai\ToolChoice;
use Laravel\Ai\Tools\Request;
use Modules\Address\Models\Address;
use Modules\Ai\Actions\AnswerHelpQuestion;
use Modules\Ai\Agents\HelpAssistant;
use Modules\Ai\Enums\HelpTurn;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Ai\Tools\ListMyMeters;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\UtilityType;
use Symfony\Component\HttpKernel\Exception\HttpException;

mutates(AnswerHelpQuestion::class, HelpAssistant::class, ListMyMeters::class);

function photoTurnUpload(): UploadedFile
{
    $image = new Imagick;
    $image->newImage(80, 50, new ImagickPixel('gray'));
    $image->setImageFormat('jpeg');
    $path = tempnam(sys_get_temp_dir(), 'qa').'.jpg';
    file_put_contents($path, $image->getImageBlob());

    return new UploadedFile($path, 'meter.jpg', 'image/jpeg', null, true);
}

describe('AnswerHelpQuestion with a meter photo', function (): void {
    beforeEach(function (): void {
        $this->travelTo('2026-06-15 12:00:00');
        Storage::fake(AssistantMeterPhoto::DISK);
        config(['ai.providers.openrouter.key' => 'test-key']);
        $this->user = User::factory()->create();
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => true]);
        $this->meter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => UtilityType::factory()->create(['slug' => 'electricity'])->getKey(),
            'initial_reading' => 100,
        ]);
        $this->action = app(AnswerHelpQuestion::class);
    });

    it('tells the user the vision flow is unavailable when the provider key is missing', function (): void {
        config(['ai.providers.openrouter.key' => null]);
        HelpAssistant::fake();

        $result = $this->action->handle($this->user, '', photoTurnUpload());

        expect($result['text'])->toContain('Розпізнавання фото зараз недоступне')
            ->and($result['sources'])->toBe([['label' => 'Внести показання', 'path' => '/readings/create']])
            ->and($result['pending_approval'])->toBeNull()
            ->and(AssistantMeterPhoto::query()->count())->toBe(0);
        HelpAssistant::assertNeverPrompted();
    });

    it('stores the photo and sends a service block with photo id and meters to the model', function (): void {
        HelpAssistant::fake(['Яке значення на лічильнику?']);

        $result = $this->action->handle($this->user, '', photoTurnUpload());
        $photo = AssistantMeterPhoto::query()->firstOrFail();

        expect($result['text'])->toBe('Яке значення на лічильнику?')
            ->and($result['pending_approval'])->toBeNull()
            ->and($result['reading_result'])->toBeNull()
            ->and($photo->conversation_id)->toBe($result['conversation_id']);
        HelpAssistant::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, 'photo_id: '.$photo->getKey())
            && str_contains($prompt->prompt, 'лічильники:')
            && str_contains($prompt->prompt, 'exif_status: missing'));
    });

    it('falls back and forgets the photo when the model call fails', function (): void {
        HelpAssistant::fake(fn () => throw new RuntimeException('provider down'));

        $result = $this->action->handle($this->user, '', photoTurnUpload());

        expect($result['text'])->toContain('Не вдалося обробити фото')
            ->and($result['sources'])->toBe([['label' => 'Внести показання', 'path' => '/readings/create']])
            ->and(AssistantMeterPhoto::query()->count())->toBe(0)
            ->and(Storage::disk(AssistantMeterPhoto::DISK)->allFiles())->toBe([]);
    });

    it('logs an empty_reply warning and discards the photo when the model returns no text', function (): void {
        config(['ai-support.model' => 'test/vision-model']);
        Log::spy();
        HelpAssistant::fake([new TextResponse('', new TextUsage(outputTokens: 700), new Meta('openrouter', 'test/vision-model'))]);

        $result = $this->action->handle($this->user, 'Внеси показання холодної води', photoTurnUpload());

        expect($result['text'])->toContain('Не вдалося обробити фото')
            ->and($result['sources'])->toBe([['label' => 'Внести показання', 'path' => '/readings/create']])
            ->and($result['pending_approval'])->toBeNull()
            ->and(AssistantMeterPhoto::query()->count())->toBe(0)
            ->and(Storage::disk(AssistantMeterPhoto::DISK)->allFiles())->toBe([]);

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context): bool {
            $encoded = json_encode($context, JSON_THROW_ON_ERROR);

            return $message === 'Support agent failed'
                && $context['reason'] === 'empty_reply'
                && $context['model'] === 'test/vision-model'
                && $context['finish_reason'] === 'stop'
                && $context['output_tokens'] === 700
                && ! str_contains($encoded, 'base64')
                && ! str_contains($encoded, 'холодної води');
        })->once();
    });

    it('logs an empty_reply warning when a photo follow-up turn returns no text', function (): void {
        $conversation = app(ConversationStore::class)->storeConversation($this->user->getMorphClass(), $this->user->getKey(), 'x');
        AssistantMeterPhoto::factory()->create(['user_id' => $this->user->getKey(), 'conversation_id' => $conversation]);
        Log::spy();
        HelpAssistant::fake([new TextResponse('', new TextUsage(outputTokens: 5), new Meta('openrouter', 'test/vision-model'))]);

        $result = $this->action->handle($this->user, 'Так, внеси', null, $conversation);

        expect($result['text'])->toContain('Не вдалося обробити фото');

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context): bool => $message === 'Support agent failed' && $context['reason'] === 'empty_reply')
            ->once();
    });

    it('rejects a concurrent request for the same user', function (): void {
        $lock = Cache::lock('support-ai:'.$this->user->getKey(), 65);
        expect($lock->get())->toBeTrue();

        try {
            expect(fn () => $this->action->handle($this->user, '', photoTurnUpload()))
                ->toThrow(HttpException::class);
        } finally {
            $lock->release();
        }
    });

    it('refuses a foreign conversation', function (): void {
        $other = User::factory()->create();
        $conversation = app(ConversationStore::class)->storeConversation($other->getMorphClass(), $other->getKey(), 'x');

        expect(fn () => $this->action->handle($this->user, '', photoTurnUpload(), $conversation))
            ->toThrow(HttpException::class);
    });
});

describe('HelpAssistant tool sets', function (): void {
    it('exposes meter tools only for meter turns', function (): void {
        $user = User::factory()->create();
        $names = fn (HelpTurn $turn): array => array_map(
            fn ($tool): string => $tool->name(),
            iterator_to_array((new HelpAssistant($user, $turn))->tools(), false),
        );

        expect($names(HelpTurn::Question))->toBe(['search_support_knowledge'])
            ->and($names(HelpTurn::MeterPhoto))->toBe(['search_support_knowledge', 'list_my_meters', 'record_meter_reading'])
            ->and($names(HelpTurn::ApprovalResume))->toContain('record_meter_reading');
    });

    it('forces knowledge search only for plain questions', function (): void {
        $user = User::factory()->create();

        expect((new HelpAssistant($user, HelpTurn::Question))->toolChoice())->not->toBe(ToolChoice::auto)
            ->and((new HelpAssistant($user, HelpTurn::MeterPhoto))->toolChoice())->toBe(ToolChoice::auto);
    });

    it('does not promise data changes on plain question turns', function (): void {
        $user = User::factory()->create();

        expect((new HelpAssistant($user, HelpTurn::Question))->instructions())->toContain('немає інструментів для змін')
            ->and((new HelpAssistant($user, HelpTurn::MeterPhoto))->instructions())->toContain('record_meter_reading');
    });
});

describe('ListMyMeters tool', function (): void {
    it('returns only the current user meters as JSON', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);
        $gas = UtilityType::factory()->create(['slug' => 'gas'])->getKey();
        $mine = Meter::factory()->create(['address_id' => $address->getKey(), 'utility_type_id' => $gas]);
        Meter::factory()->create(['utility_type_id' => $gas]);

        $tool = app(ListMyMeters::class, ['user' => $user]);
        $payload = json_decode((string) $tool->handle(new Request([])), true, 512, JSON_THROW_ON_ERROR);

        expect($tool->name())->toBe('list_my_meters')
            ->and(array_column($payload['meters'], 'meter_id'))->toBe([$mine->getKey()]);
    });
});
