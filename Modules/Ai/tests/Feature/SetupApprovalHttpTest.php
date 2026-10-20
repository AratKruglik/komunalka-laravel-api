<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Ai\Actions\CreatePropertySetupApproval;
use Modules\Ai\Models\HelpApproval;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;
use Modules\Shared\Models\UtilityType;

it('requires the proposal version and applies a user-owned setup package once', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address);
    $conversation = HelpConversation::factory()->for($user)->create([
        'draft_version' => 1,
        'draft' => ['address_id' => $address->id, 'provider' => [
            'utility_type_id' => UtilityType::factory()->create()->id,
            'name' => 'Новий провайдер',
        ]],
    ]);
    $approval = app(CreatePropertySetupApproval::class)->handle($user, $conversation);
    $payload = ['conversation_id' => $conversation->id, 'approval_id' => $approval->id, 'action' => 'approve'];

    $this->actingAs($user)->postJson(route('help.approve'), $payload)->assertUnprocessable();
    $first = $this->actingAs($user)->postJson(route('help.approve'), [...$payload, 'version' => 1])
        ->assertOk()->assertJsonPath('setup_result.status', 'applied');
    $this->actingAs($user)->postJson(route('help.approve'), [...$payload, 'version' => 1])
        ->assertOk()->assertJsonPath('setup_result.provider.id', $first->json('setup_result.provider.id'));

    expect(ServiceProvider::query()->count())->toBe(1);
});

it('forbids another user from approving the setup card', function (): void {
    $owner = User::factory()->create();
    $conversation = HelpConversation::factory()->for($owner)->create();
    $approval = HelpApproval::factory()->create([
        'conversation_id' => $conversation->id, 'user_id' => $owner->id,
    ]);

    $this->actingAs(User::factory()->create())->postJson(route('help.approve'), [
        'conversation_id' => $conversation->id,
        'approval_id' => $approval->id,
        'version' => 0,
        'action' => 'approve',
    ])->assertForbidden();
});
