<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Classification;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Responses\Data\ChoiceAnswer;
use Laravel\Ai\Responses\Data\ToolCall;
use Modules\Address\Models\Address;
use Modules\Ai\Agents\MeterReadingAgent;
use Modules\Ai\Agents\ProductSupportAgent;
use Modules\Ai\Agents\PropertySetupAgent;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Ai\Models\HelpApproval;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\UtilityType;

describe('POST /help/ask', function (): void {
    beforeEach(function (): void {
        config(['ai.providers.openrouter.key' => null]);
    });
    it('redirects guests to login', function (): void {
        $this->post(route('help.ask'), ['question' => 'Як внести показання?'])
            ->assertRedirect(route('login'));
    });

    it('returns the assistant response with text and sources for an authenticated user', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('help.ask'), ['question' => 'Як внести показання?'])
            ->assertOk()
            ->assertJson([
                'text' => 'Щоб внести показання лічильника, перейдіть у розділ "Внести показання", оберіть лічильник і вкажіть нове значення.',
                'sources' => [
                    ['label' => 'Внести показання', 'path' => '/readings/create'],
                ],
            ])
            ->assertJsonStructure(['text', 'sources' => [['label', 'path']]]);
    });

    it('routes a classified support request through the read-only specialist and returns a public id', function (): void {
        config([
            'ai.providers.openrouter.key' => 'test-key',
            'ai-support.router_min_probability' => 0.7,
            'ai-support.router_min_margin' => 0.2,
        ]);
        Classification::fake([['intent' => new ChoiceAnswer('product_support', [
            'meter_reading' => 0.03,
            'property_setup' => 0.03,
            'product_support' => 0.90,
            'clarify' => 0.04,
        ], confidence: 0.9)]]);
        ProductSupportAgent::fake(['Немає перевірених джерел.']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('help.ask'), ['question' => 'Як працює застосунок?'])
            ->assertOk()->assertJsonPath('workflow', 'product_support');

        expect(HelpConversation::query()->whereKey($response->json('conversation_id'))->where('user_id', $user->id)->exists())->toBeTrue();
        ProductSupportAgent::assertPrompted('Як працює застосунок?');
    });

    it('shows a setup approval card for a complete active draft without writing a meter', function (): void {
        config(['ai.providers.openrouter.key' => 'test-key']);
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address);
        $utility = UtilityType::factory()->create();
        $conversation = HelpConversation::factory()->for($user)->create([
            'active_workflow' => HelpWorkflow::PropertySetup,
            'draft_version' => 1,
            'draft' => ['address_id' => $address->id, 'meter' => [
                'utility_type_id' => $utility->id, 'serial_number' => 'ABC', 'name' => 'Лічильник', 'initial_reading' => 0,
            ]],
            'expires_at' => now()->addDay(),
        ]);
        PropertySetupAgent::fake(['Підготував дані.']);

        $this->actingAs($user)->postJson(route('help.ask'), [
            'question' => 'Покажи підсумок', 'conversation_id' => $conversation->id,
        ])->assertOk()
            ->assertJsonPath('workflow', 'property_setup')
            ->assertJsonPath('pending_approval.tool', 'create_property_setup')
            ->assertJsonPath('pending_approval.version', 1);

        expect(Meter::query()->count())->toBe(0);
    });

    it('replaces a photo agents SDK approval with a durable public approval id', function (): void {
        $this->travelTo('2026-06-15 12:00:00');
        config(['ai.providers.openrouter.key' => 'test-key']);
        $user = User::factory()->create();
        $conversation = HelpConversation::factory()->for($user)->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address);
        $meter = Meter::factory()->create([
            'address_id' => $address->id,
            'utility_type_id' => UtilityType::factory()->create(['slug' => 'gas'])->id,
            'initial_reading' => 0,
        ]);
        $photo = AssistantMeterPhoto::factory()->for($user)->create(['help_conversation_id' => $conversation->id]);
        MeterReadingAgent::fake([
            new ToolCall('sdk-call', 'record_meter_reading', [
                'photo_id' => $photo->id, 'meter_id' => $meter->id,
                'reading_value' => 12, 'reading_date' => '2026-06-15',
            ]),
        ]);

        $response = $this->actingAs($user)->postJson(route('help.ask'), [
            'question' => 'Показання 12', 'conversation_id' => $conversation->id,
        ])->assertOk()->assertJsonPath('workflow', 'meter_reading');

        expect($response->json('pending_approval.id'))->toBeString()
            ->and(HelpApproval::query()->whereKey($response->json('pending_approval.id'))->exists())->toBeTrue();
    });

    it('returns a fallback response for an unrecognized question', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('help.ask'), ['question' => 'Розкажи анекдот'])
            ->assertOk()
            ->assertJson([
                'text' => 'Я поки не знаю точної відповіді на це запитання. Спробуйте переглянути дашборд або скористайтесь однією з підказок вище.',
            ]);
    });

    it('rejects an empty question with a validation error', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('help.ask'), ['question' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('question');
    });

    it('rejects a missing question field with a validation error', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('help.ask'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('question');
    });

    it('rejects a question over 1000 characters with a validation error', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('help.ask'), ['question' => str_repeat('а', 1001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('question');
    });

    it('accepts a question exactly at the 1000 character limit', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('help.ask'), ['question' => str_repeat('а', 1000)])
            ->assertOk();
    });

    it('rejects a malformed conversation identifier', function (): void {
        $this->actingAs(User::factory()->create())
            ->postJson(route('help.ask'), ['question' => 'Привіт', 'conversation_id' => 'bad-id'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('conversation_id');
    });

    it('does not allow a user to continue another users conversation', function (): void {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $conversationId = app(ConversationStore::class)->storeConversation(
            $owner->getMorphClass(),
            $owner->getKey(),
            'Особиста розмова',
        );

        $this->actingAs($otherUser)
            ->postJson(route('help.ask'), ['question' => 'Що ми обговорювали?', 'conversation_id' => $conversationId])
            ->assertForbidden();
    });

    it('maps an owner legacy conversation to a public conversation during provider fallback', function (): void {
        $user = User::factory()->create();
        $conversationId = app(ConversationStore::class)->storeConversation(
            $user->getMorphClass(),
            $user->getKey(),
            'Моя розмова',
        );

        $response = $this->actingAs($user)
            ->postJson(route('help.ask'), ['question' => 'Як внести показання?', 'conversation_id' => $conversationId])
            ->assertOk();

        $publicId = $response->json('conversation_id');
        expect($publicId)->not->toBe($conversationId)
            ->and(HelpConversation::query()->findOrFail($publicId)->agent_conversation_ids['product_support'])->toBe($conversationId);
    });

    it('rejects a concurrent AI request for the same user', function (): void {
        config(['ai.providers.openrouter.key' => 'test-key']);
        $user = User::factory()->create();
        $lock = Cache::lock('support-ai:'.$user->getKey(), 65);
        expect($lock->get())->toBeTrue();

        try {
            $this->actingAs($user)
                ->postJson(route('help.ask'), ['question' => 'Як внести показання?'])
                ->assertStatus(429);
        } finally {
            $lock->release();
        }
    });
});
