<?php

declare(strict_types=1);

use Modules\Ai\Agents\MeterReadingAgent;
use Modules\Ai\Agents\ProductSupportAgent;
use Modules\Ai\Agents\PropertySetupAgent;
use Modules\Ai\Models\HelpConversation;
use Modules\Ai\Tools\ListMyMeters;
use Modules\Ai\Tools\RecordMeterReading;
use Modules\Ai\Tools\SearchSupportKnowledge;
use Modules\Auth\Models\User;

it('gives the support agent only the knowledge search tool', function (): void {
    $tools = iterator_to_array((function () {
        yield from (new ProductSupportAgent)->tools();
    })());

    expect($tools)->toHaveCount(1)
        ->and($tools[0])->toBeInstanceOf(SearchSupportKnowledge::class);
});

it('gives the setup agent read and draft proposal tools without domain write tools', function (): void {
    $conversation = HelpConversation::factory()->create();
    $names = array_map(fn ($tool): string => $tool->name(), iterator_to_array((new PropertySetupAgent($conversation->user, $conversation))->tools()));

    expect($names)->toBe(['list_property_setup_context', 'propose_property_setup']);
});

it('gives the meter agent only user-scoped meter tools', function (): void {
    $user = User::factory()->make();
    $tools = iterator_to_array((function () use ($user) {
        yield from (new MeterReadingAgent($user))->tools();
    })());

    expect($tools)->toHaveCount(2)
        ->and($tools[0])->toBeInstanceOf(ListMyMeters::class)
        ->and($tools[1])->toBeInstanceOf(RecordMeterReading::class);
});
