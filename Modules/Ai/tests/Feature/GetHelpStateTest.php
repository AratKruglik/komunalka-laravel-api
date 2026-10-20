<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Ai\Actions\CreatePropertySetupApproval;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;
use Modules\Shared\Models\UtilityType;

it('restores the pending setup card for its owner', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address);
    $conversation = HelpConversation::factory()->for($user)->create([
        'draft_version' => 1,
        'draft' => ['address_id' => $address->id, 'provider' => [
            'utility_type_id' => UtilityType::factory()->create()->id,
            'name' => 'Провайдер',
        ]],
    ]);
    $approval = app(CreatePropertySetupApproval::class)->handle($user, $conversation);

    $this->actingAs($user)->getJson(route('help.state', ['conversation_id' => $conversation->id]))
        ->assertOk()->assertJsonPath('pending_approval.id', $approval->id)
        ->assertJsonPath('pending_approval.version', 1);
});

it('forbids restoring another users conversation', function (): void {
    $conversation = HelpConversation::factory()->create();

    $this->actingAs(User::factory()->create())
        ->getJson(route('help.state', ['conversation_id' => $conversation->id]))->assertForbidden();
});
