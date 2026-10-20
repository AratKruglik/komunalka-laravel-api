<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Ai\Actions\CreatePropertySetupApproval;
use Modules\Ai\Enums\HelpApprovalStatus;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;
use Modules\Shared\Models\UtilityType;

it('returns one pending approval and a bounded card for the same draft version', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address);
    $utility = UtilityType::factory()->create();
    $conversation = HelpConversation::factory()->for($user)->create([
        'draft_version' => 2,
        'draft' => ['address_id' => $address->id, 'meter' => [
            'utility_type_id' => $utility->id, 'serial_number' => 'ABC', 'name' => 'Лічильник', 'initial_reading' => 0,
        ]],
    ]);
    $action = app(CreatePropertySetupApproval::class);

    $first = $action->handle($user, $conversation);
    $second = $action->handle($user, $conversation);

    expect($first->id)->toBe($second->id)
        ->and($first->status)->toBe(HelpApprovalStatus::Pending)
        ->and($action->card($first))->not->toHaveKey('payload_hash')
        ->and($action->card($first)['version'])->toBe(2);
});

it('issues a new version when the previous approval has expired', function (): void {
    $this->travelTo('2026-10-20 10:00:00');
    $user = User::factory()->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address);
    $conversation = HelpConversation::factory()->for($user)->create([
        'draft_version' => 1,
        'draft' => ['address_id' => $address->id, 'meter' => [
            'utility_type_id' => UtilityType::factory()->create()->id,
            'serial_number' => 'EXPIRED-1', 'name' => 'Лічильник', 'initial_reading' => 0,
        ]],
    ]);
    $action = app(CreatePropertySetupApproval::class);
    $expired = $action->handle($user, $conversation);

    $this->travel(16)->minutes();
    $renewed = $action->handle($user, $conversation->refresh());

    expect($renewed->id)->not->toBe($expired->id)
        ->and($renewed->draft_version)->toBe(2)
        ->and($renewed->expires_at->isFuture())->toBeTrue()
        ->and($conversation->refresh()->draft_version)->toBe(2);
});
