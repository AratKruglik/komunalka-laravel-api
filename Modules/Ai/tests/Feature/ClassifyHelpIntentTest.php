<?php

declare(strict_types=1);

use Laravel\Ai\Classification;
use Laravel\Ai\Responses\ClassificationResponse;
use Laravel\Ai\Responses\Data\ChoiceAnswer;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Modules\Ai\Actions\ClassifyHelpIntent;
use Modules\Ai\Enums\HelpIntent;
use Modules\Ai\Enums\HelpWorkflow;

beforeEach(function (): void {
    config()->set('ai-support.router_min_probability', 0.70);
    config()->set('ai-support.router_min_margin', 0.20);
});

it('uses Jev to classify a clear setup request with minimal trusted state', function (): void {
    Classification::fake([[
        'intent' => new ChoiceAnswer('property_setup', [
            'meter_reading' => 0.05,
            'property_setup' => 0.82,
            'product_support' => 0.08,
            'clarify' => 0.05,
        ], confidence: 0.9),
    ]]);

    $decision = app(ClassifyHelpIntent::class)->handle('Додай лічильник', HelpWorkflow::PropertySetup, 'Потрібен лічильник');

    expect($decision->intent)->toBe(HelpIntent::PropertySetup);

    Classification::assertClassified(fn ($prompt): bool => $prompt->provider->name() === 'openrouter'
        && $prompt->model === 'typesafe/jev-1.14'
        && $prompt->asks('intent')
        && $prompt->attachments === []
        && $prompt->state === [
            'message' => 'Додай лічильник',
            'active_workflow' => 'property_setup',
            'context' => 'Потрібен лічильник',
        ]);
});

it('fails closed when calibration thresholds are absent', function (): void {
    config()->set('ai-support.router_min_probability', null);
    Classification::fake();

    expect(app(ClassifyHelpIntent::class)->handle('Додай адресу')->intent)->toBe(HelpIntent::Clarify);
    Classification::assertNothingClassified();
});

it('clarifies low probability and close competing routes', function (ChoiceAnswer $answer, float $minimum): void {
    config()->set('ai-support.router_min_probability', $minimum);
    Classification::fake([['intent' => $answer]]);

    expect(app(ClassifyHelpIntent::class)->handle('Допоможи з лічильником')->intent)->toBe(HelpIntent::Clarify);
})->with([
    'low probability' => [new ChoiceAnswer('meter_reading', [
        'meter_reading' => 0.60, 'property_setup' => 0.15, 'product_support' => 0.15, 'clarify' => 0.10,
    ], confidence: 0.8), 0.70],
    'small margin' => [new ChoiceAnswer('meter_reading', [
        'meter_reading' => 0.52, 'property_setup' => 0.40, 'product_support' => 0.04, 'clarify' => 0.04,
    ], confidence: 0.8), 0.50],
]);

it('clarifies malformed or incomplete classification results', function (ClassificationResponse|ChoiceAnswer $answer): void {
    Classification::fake([$answer instanceof ClassificationResponse ? $answer : ['intent' => $answer]]);

    expect(app(ClassifyHelpIntent::class)->handle('Додай адресу')->intent)->toBe(HelpIntent::Clarify);
})->with([
    'missing answer' => new ClassificationResponse([], new TextUsage, new Meta('openrouter', 'typesafe/jev-1.14')),
    'unknown choice' => new ChoiceAnswer('delete_everything', [
        'meter_reading' => 0.01, 'property_setup' => 0.95, 'product_support' => 0.02, 'clarify' => 0.02,
    ], confidence: 0.9),
    'missing probability' => new ChoiceAnswer('property_setup', [
        'meter_reading' => 0.05, 'property_setup' => 0.85, 'product_support' => 0.10,
    ], confidence: 0.9),
    'unknown confidence' => new ChoiceAnswer('property_setup', [
        'meter_reading' => 0.05, 'property_setup' => 0.82, 'product_support' => 0.08, 'clarify' => 0.05,
    ]),
]);

it('clarifies when the provider fails', function (): void {
    Classification::fake(fn () => throw new RuntimeException('provider unavailable'));

    expect(app(ClassifyHelpIntent::class)->handle('Додай адресу')->intent)->toBe(HelpIntent::Clarify);
});
