<?php

$routerMinProbability = env('AI_ROUTER_MIN_PROBABILITY');
$routerMinMargin = env('AI_ROUTER_MIN_MARGIN');

return [
    'router_model' => env('AI_ROUTER_MODEL', 'typesafe/jev-1.14'),
    'meter_model' => env('AI_METER_MODEL', 'openai/gpt-4o-mini'),
    'setup_model' => env('AI_SETUP_MODEL', 'openai/gpt-4o-mini'),
    'support_model' => env('AI_SUPPORT_MODEL', 'openai/gpt-4o-mini'),
    'model' => env('AI_SUPPORT_MODEL', 'openai/gpt-4o-mini'),
    'router_min_probability' => $routerMinProbability === null || $routerMinProbability === '' ? null : (float) $routerMinProbability,
    'router_min_margin' => $routerMinMargin === null || $routerMinMargin === '' ? null : (float) $routerMinMargin,
    'min_similarity' => (float) env('AI_SUPPORT_MIN_SIMILARITY', 0.45),
    'max_chunks' => (int) env('AI_SUPPORT_MAX_CHUNKS', 5),
    'sources' => [
        '01-getting-started.md' => ['label' => 'Початок роботи', 'path' => '/settings'],
        '02-navigation-dashboard.md' => ['label' => 'Дашборд', 'path' => '/'],
        '03-addresses.md' => ['label' => 'Мої адреси', 'path' => '/addresses'],
        '04-providers-tariffs.md' => ['label' => 'Провайдери та тарифи', 'path' => '/providers'],
        '05-meters.md' => ['label' => 'Лічильники', 'path' => '/meters'],
        '06-readings.md' => ['label' => 'Показання', 'path' => '/readings'],
        '07-settings.md' => ['label' => 'Налаштування', 'path' => '/settings'],
        '08-help-assistant.md' => ['label' => 'Допомога', 'path' => '/help'],
        '09-user-scenarios.md' => ['label' => 'Сценарії користування', 'path' => '/help'],
        '10-known-limitations.md' => ['label' => 'Обмеження', 'path' => '/help'],
    ],
];
