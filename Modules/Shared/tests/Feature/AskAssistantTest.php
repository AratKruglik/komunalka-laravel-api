<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Contracts\ConversationStore;
use Modules\Auth\Models\User;

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

    it('allows the owner to continue a conversation during provider fallback', function (): void {
        $user = User::factory()->create();
        $conversationId = app(ConversationStore::class)->storeConversation(
            $user->getMorphClass(),
            $user->getKey(),
            'Моя розмова',
        );

        $this->actingAs($user)
            ->postJson(route('help.ask'), ['question' => 'Як внести показання?', 'conversation_id' => $conversationId])
            ->assertOk()
            ->assertJsonPath('conversation_id', $conversationId);
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
