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

    it('shows all quick chips at the start of a new session', function (): void {
        $this->actingAs($this->user);

        visit(route('dashboard'))
            ->click('[aria-label="Відкрити чат допомоги"]')
            ->assertSee('Як внести показання?')
            ->assertSee('Керування лічильниками')
            ->assertSee('Обрати провайдера')
            ->assertSee('Як рахуються тарифи?')
            ->assertNoJavaScriptErrors();
    });

    it('hides the quick chips after a chip is sent', function (): void {
        $this->actingAs($this->user);

        visit(route('dashboard'))
            ->click('[aria-label="Відкрити чат допомоги"]')
            ->click('Як внести показання?')
            ->assertSee('Щоб внести показання лічильника, перейдіть у розділ')
            ->assertDontSee('Керування лічильниками')
            ->assertDontSee('Обрати провайдера')
            ->assertDontSee('Як рахуються тарифи?')
            ->assertNoJavaScriptErrors();
    });

    it('hides the quick chips after a typed question', function (): void {
        $this->actingAs($this->user);

        visit(route('dashboard'))
            ->click('[aria-label="Відкрити чат допомоги"]')
            ->fill('[placeholder="Напишіть питання…"]', 'Тестове запитання без ключових слів')
            ->click('[aria-label="Надіслати повідомлення"]')
            ->assertSee('Я поки не знаю точної відповіді на це запитання.')
            ->assertDontSee('Як внести показання?')
            ->assertDontSee('Керування лічильниками')
            ->assertDontSee('Обрати провайдера')
            ->assertDontSee('Як рахуються тарифи?')
            ->assertNoJavaScriptErrors();
    });

    it('restores the quick chips after starting a new conversation', function (): void {
        $this->actingAs($this->user);

        visit(route('dashboard'))
            ->click('[aria-label="Відкрити чат допомоги"]')
            ->fill('[placeholder="Напишіть питання…"]', 'Тестове запитання без ключових слів')
            ->click('[aria-label="Надіслати повідомлення"]')
            ->assertSee('Я поки не знаю точної відповіді на це запитання.')
            ->click('[aria-label="Почати нову розмову"]')
            ->assertSee('Привіт! Я ХаткоБот — ваш помічник.')
            ->assertSee('Як внести показання?')
            ->assertSee('Керування лічильниками')
            ->assertSee('Обрати провайдера')
            ->assertSee('Як рахуються тарифи?')
            ->assertDontSee('Тестове запитання без ключових слів')
            ->assertNoJavaScriptErrors();
    });

    it('fits the widget inside a 640px viewport', function (): void {
        $this->actingAs($this->user);

        visit(route('dashboard'))
            ->resize(640, 800)
            ->click('[aria-label="Відкрити чат допомоги"]')
            ->assertVisible('[placeholder="Напишіть питання…"]')
            ->assertVisible('[aria-label="Надіслати повідомлення"]')
            ->assertNoJavaScriptErrors();
    });

    it('renders the widget wider than 384px on desktop and without horizontal scroll at 640px', function (): void {
        $this->actingAs($this->user);

        $page = visit(route('dashboard'))
            ->resize(1280, 800)
            ->click('[aria-label="Відкрити чат допомоги"]');

        $desktopWidth = $page->script(
            "document.querySelector('[placeholder=\"Напишіть питання…\"]').closest('.fixed').getBoundingClientRect().width",
        );

        expect($desktopWidth)->toBeGreaterThan(384);

        $page->resize(640, 800);

        $metrics = $page->script(
            "(() => { const r = document.querySelector('[placeholder=\"Напишіть питання…\"]').closest('.fixed').getBoundingClientRect(); return { left: r.left, overflow: document.documentElement.scrollWidth > window.innerWidth }; })()",
        );

        expect($metrics['left'])->toBeGreaterThanOrEqual(0)
            ->and($metrics['overflow'])->toBeFalse();

        $page->assertNoJavaScriptErrors();
    });

    it('keeps the quick chips hidden after closing and reopening the widget mid-session', function (): void {
        $this->actingAs($this->user);

        visit(route('dashboard'))
            ->click('[aria-label="Відкрити чат допомоги"]')
            ->click('Як внести показання?')
            ->assertSee('Щоб внести показання лічильника, перейдіть у розділ')
            ->click('[aria-expanded="true"][aria-label="Закрити чат допомоги"]')
            ->click('[aria-expanded="false"][aria-label="Відкрити чат допомоги"]')
            ->assertSee('Щоб внести показання лічильника, перейдіть у розділ')
            ->assertDontSee('Керування лічильниками')
            ->assertDontSee('Обрати провайдера')
            ->assertDontSee('Як рахуються тарифи?')
            ->assertNoJavaScriptErrors();
    });
});
