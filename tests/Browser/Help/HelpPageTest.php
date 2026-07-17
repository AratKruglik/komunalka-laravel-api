<?php

declare(strict_types=1);

use Modules\Auth\Models\User;

describe('Help Page Browser Rendering', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
    });

    it('renders the help page for an authenticated user without JavaScript errors', function (): void {
        $this->actingAs($this->user);

        visit(route('help.index'))
            ->assertSee('Допомога')
            ->assertSee('ХаткоБот — AI-помічник')
            ->assertSee('Онлайн · відповідає за секунди')
            ->assertNoJavaScriptErrors();
    });

    it('shows the assistant greeting message', function (): void {
        $this->actingAs($this->user);

        visit(route('help.index'))
            ->assertSee('Вітаю! Я ХаткоБот — ваш AI-помічник у Комуналці.')
            ->assertNoJavaScriptErrors();
    });

    it('renders the quick suggestion chips', function (): void {
        $this->actingAs($this->user);

        visit(route('help.index'))
            ->assertSee('Як внести показання?')
            ->assertSee('Керування лічильниками')
            ->assertSee('Обрати провайдера')
            ->assertSee('Як рахуються тарифи?')
            ->assertNoJavaScriptErrors();
    });

    it('renders the topic shortcuts and documentation panels', function (): void {
        $this->actingAs($this->user);

        visit(route('help.index'))
            ->assertSee('Про що можна запитати')
            ->assertSee('Документація')
            ->assertSee('Внесення показань')
            ->assertSee('Швидкий старт')
            ->assertNoJavaScriptErrors();
    });
});

describe('Help Page Chat Interaction', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
    });

    it('answers a readings question when the chip is clicked', function (): void {
        $this->actingAs($this->user);

        visit(route('help.index'))
            ->click('Як внести показання?')
            ->assertSee('Щоб внести показання лічильника, перейдіть у розділ')
            ->assertSee('/readings/create')
            ->assertNoJavaScriptErrors();
    });

    it('answers a meters question when the chip is clicked', function (): void {
        $this->actingAs($this->user);

        visit(route('help.index'))
            ->click('Керування лічильниками')
            ->assertSee('Керувати лічильниками можна у розділі')
            ->assertSee('/meters')
            ->assertNoJavaScriptErrors();
    });

    it('answers a typed provider question sent via the send button', function (): void {
        $this->actingAs($this->user);

        visit(route('help.index'))
            ->fill('[placeholder="Запитайте про будь-який розділ Комуналки…"]', 'Як обрати провайдера послуг?')
            ->click('[aria-label="Надіслати повідомлення"]')
            ->assertSee('Обрати чи змінити постачальника послуг можна у розділі')
            ->assertSee('/providers')
            ->assertNoJavaScriptErrors();
    });

    it('returns the fallback answer for an unrecognized question', function (): void {
        $this->actingAs($this->user);

        visit(route('help.index'))
            ->fill('[placeholder="Запитайте про будь-який розділ Комуналки…"]', 'Тестове запитання без ключових слів')
            ->click('[aria-label="Надіслати повідомлення"]')
            ->assertSee('Я поки не знаю точної відповіді на це запитання.')
            ->assertNoJavaScriptErrors();
    });
});

describe('Help Chat Widget', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
    });

    it('is globally mounted and opens from the dashboard', function (): void {
        $this->actingAs($this->user);

        visit(route('dashboard'))
            ->click('[aria-label="Відкрити чат допомоги"]')
            ->assertSee('ХаткоБот')
            ->assertSee('Привіт! Я ХаткоБот — ваш помічник.')
            ->assertNoJavaScriptErrors();
    });

    it('answers a question from the floating widget', function (): void {
        $this->actingAs($this->user);

        visit(route('dashboard'))
            ->click('[aria-label="Відкрити чат допомоги"]')
            ->click('Як внести показання?')
            ->assertSee('Щоб внести показання лічильника, перейдіть у розділ')
            ->assertNoJavaScriptErrors();
    });

    it('closes the floating widget with the Escape key', function (): void {
        $this->actingAs($this->user);

        visit(route('dashboard'))
            ->click('[aria-label="Відкрити чат допомоги"]')
            ->assertSee('ХаткоБот')
            ->keys('[placeholder="Напишіть питання…"]', 'Escape')
            ->assertDontSee('ХаткоБот')
            ->assertNoJavaScriptErrors();
    });
});
