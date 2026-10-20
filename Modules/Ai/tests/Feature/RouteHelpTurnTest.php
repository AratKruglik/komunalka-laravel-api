<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Laravel\Ai\Classification;
use Modules\Ai\Actions\RouteHelpTurn;
use Modules\Ai\Enums\HelpIntent;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Ai\Models\HelpConversation;

beforeEach(function (): void {
    config(['ai.providers.openrouter.key' => 'test-key']);
    Classification::fake();
});

it('sends a new photo directly to the meter specialist without classification', function (): void {
    $conversation = HelpConversation::factory()->create();

    expect(app(RouteHelpTurn::class)->handle($conversation, '', UploadedFile::fake()->image('meter.jpg')))
        ->toBe(HelpIntent::MeterReading);
    Classification::assertNothingClassified();
});

it('keeps an active photo follow-up in the meter workflow', function (): void {
    $conversation = HelpConversation::factory()->create();
    AssistantMeterPhoto::factory()->create(['user_id' => $conversation->user_id, 'help_conversation_id' => $conversation->id]);

    expect(app(RouteHelpTurn::class)->handle($conversation, 'Там 1234'))->toBe(HelpIntent::MeterReading);
    Classification::assertNothingClassified();
});

it('continues an active setup draft without classification', function (): void {
    $conversation = HelpConversation::factory()->create([
        'active_workflow' => HelpWorkflow::PropertySetup,
        'draft' => ['address' => ['city' => 'Київ']],
        'expires_at' => now()->addHour(),
    ]);

    expect(app(RouteHelpTurn::class)->handle($conversation, 'Вулиця Хрещатик'))->toBe(HelpIntent::PropertySetup);
    Classification::assertNothingClassified();
});

it('fails closed for unclear text when router thresholds are not calibrated', function (): void {
    $conversation = HelpConversation::factory()->create();
    config(['ai-support.router_min_probability' => null]);

    expect(app(RouteHelpTurn::class)->handle($conversation, 'Додай адресу'))->toBe(HelpIntent::Clarify);
    Classification::assertNothingClassified();
});
