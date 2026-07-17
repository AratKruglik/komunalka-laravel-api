<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\User;

describe('Help Page', function (): void {
    it('redirects guests to login', function (): void {
        $this->get(route('help.index'))
            ->assertRedirect(route('login'));
    });

    it('renders the Help/Index page for authenticated users with the documented prop shape', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withoutVite()
            ->get(route('help.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Help/Index')
                ->has('greeting')
                ->where('greeting', 'Вітаю! Я ХаткоБот — ваш AI-помічник у Комуналці. Запитайте мене про внесення показань, лічильники, провайдерів чи адреси — я підкажу крок за кроком і відкрию відповідний розділ документації.')
                ->has('suggestedChips', 4)
                ->has('topicShortcuts', 4)
                ->has('topicShortcuts.0', fn (Assert $shortcut) => $shortcut
                    ->hasAll(['label', 'path'])
                )
                ->has('docLinks', 3)
                ->has('docLinks.0', fn (Assert $link) => $link
                    ->hasAll(['label', 'path'])
                )
                ->where('askEndpoint', route('help.ask'))
            );
    });
});
