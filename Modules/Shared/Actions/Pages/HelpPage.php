<?php

declare(strict_types=1);

namespace Modules\Shared\Actions\Pages;

use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsAction;

class HelpPage
{
    use AsAction;

    public function handle(): Response
    {
        return Inertia::render('Help/Index', [
            'greeting' => 'Вітаю! Я ХаткоБот — ваш AI-помічник у Комуналці. Запитайте мене про внесення показань, лічильники, провайдерів чи адреси — я підкажу крок за кроком і відкрию відповідний розділ документації.',
            'suggestedChips' => [
                'Як внести показання?',
                'Керування лічильниками',
                'Обрати провайдера',
                'Як рахуються тарифи?',
            ],
            'topicShortcuts' => [
                ['label' => 'Внесення показань', 'path' => '/readings/create'],
                ['label' => 'Лічильники', 'path' => '/meters'],
                ['label' => 'Провайдери', 'path' => '/providers'],
                ['label' => 'Адреси', 'path' => '/addresses'],
            ],
            'docLinks' => [
                ['label' => 'Швидкий старт', 'path' => '/'],
                ['label' => 'Внесення показань', 'path' => '/readings/create'],
                ['label' => 'Тарифи та розрахунки', 'path' => '/providers'],
            ],
            'askEndpoint' => route('help.ask'),
        ]);
    }

    public function asController(): Response
    {
        return $this->handle();
    }
}
