<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Contracts\ConversationStore;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Auth\Models\User;
use Modules\Shared\Actions\DecideAssistantApproval;
use Modules\Shared\Http\Requests\DecideAssistantApprovalRequest;

mutates(DecideAssistantApproval::class, DecideAssistantApprovalRequest::class);

describe('POST /help/approve', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->conversation = app(ConversationStore::class)->storeConversation($this->user->getMorphClass(), $this->user->getKey(), 'Моя розмова');
        $this->payload = fn (array $overrides = []): array => [
            'conversation_id' => $this->conversation,
            'approval_id' => 'appr-1',
            'action' => 'approve',
            ...$overrides,
        ];
    });

    it('redirects guests to login', function (): void {
        $this->post(route('help.approve'), ($this->payload)())->assertRedirect(route('login'));
    });

    it('validates the payload', function (array $overrides, string $field): void {
        $this->actingAs($this->user)
            ->postJson(route('help.approve'), ($this->payload)($overrides))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with([
        'bad conversation id' => [['conversation_id' => 'nope'], 'conversation_id'],
        'missing approval id' => [['approval_id' => ''], 'approval_id'],
        'unknown action' => [['action' => 'delete'], 'action'],
        'edit without arguments' => [['action' => 'edit'], 'arguments'],
        'edit with empty arguments' => [['action' => 'edit', 'arguments' => []], 'arguments'],
        'edit with disallowed key' => [['action' => 'edit', 'arguments' => ['meter_id' => 5]], 'arguments'],
        'edit overflow value' => [['action' => 'edit', 'arguments' => ['reading_value' => '1e400']], 'arguments.reading_value'],
        'edit negative value' => [['action' => 'edit', 'arguments' => ['reading_value' => -1]], 'arguments.reading_value'],
        'edit wrong date format' => [['action' => 'edit', 'arguments' => ['reading_date' => '15.06.2026']], 'arguments.reading_date'],
        'edit non integer tariff' => [['action' => 'edit', 'arguments' => ['tariff_id' => 'x']], 'arguments.tariff_id'],
    ]);

    it('forbids deciding on another user\'s conversation', function (): void {
        $this->actingAs(User::factory()->create())
            ->postJson(route('help.approve'), ($this->payload)())
            ->assertForbidden();
    });

    it('answers 409 with the stale message when the approval is unknown', function (): void {
        $this->actingAs($this->user)
            ->postJson(route('help.approve'), ($this->payload)())
            ->assertStatus(409);
    });
});

describe('POST /help/ask with a photo', function (): void {
    beforeEach(function (): void {
        Storage::fake(AssistantMeterPhoto::DISK);
        config(['ai.providers.openrouter.key' => null]);
        $this->user = User::factory()->create();
    });

    it('accepts a photo without a question', function (): void {
        $this->actingAs($this->user)
            ->postJson(route('help.ask'), ['photo' => UploadedFile::fake()->image('meter.jpg')])
            ->assertOk()
            ->assertJsonStructure(['text', 'sources', 'conversation_id', 'pending_approval', 'reading_result']);
    });

    it('rejects an unsupported photo type', function (): void {
        $this->actingAs($this->user)
            ->postJson(route('help.ask'), ['photo' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
    });

    it('rejects a photo above 10 MB', function (): void {
        $this->actingAs($this->user)
            ->postJson(route('help.ask'), ['photo' => UploadedFile::fake()->create('x.jpg', 10241, 'image/jpeg')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
    });

    it('includes the contract keys in a text answer', function (): void {
        $this->actingAs($this->user)
            ->postJson(route('help.ask'), ['question' => 'Як внести показання?'])
            ->assertOk()
            ->assertJsonPath('pending_approval', null)
            ->assertJsonPath('reading_result', null);
    });
});
