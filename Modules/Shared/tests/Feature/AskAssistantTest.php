<?php

declare(strict_types=1);

use Modules\Auth\Models\User;

describe('POST /help/ask', function (): void {
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
});
