<?php

declare(strict_types=1);

it('uses dedicated models and preserves the legacy support model key', function (): void {
    expect(config('ai-support.router_model'))->toBe('typesafe/jev-1.14')
        ->and(config('ai-support.meter_model'))->toBe('openai/gpt-4o-mini')
        ->and(config('ai-support.setup_model'))->toBe('openai/gpt-4o-mini')
        ->and(config('ai-support.support_model'))->toBeString()
        ->and(config('ai-support.model'))->toBe(config('ai-support.support_model'));
});

it('keeps router thresholds disabled until calibration values are configured', function (): void {
    expect(config('ai-support.router_min_probability'))->toBeNull()
        ->and(config('ai-support.router_min_margin'))->toBeNull();
});
