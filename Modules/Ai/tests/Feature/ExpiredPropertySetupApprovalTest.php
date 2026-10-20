<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Ai\Actions\CreatePropertySetupApproval;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;
use Modules\Shared\Models\UtilityType;

it('does not restore or apply an expired property setup approval', function (): void {
    $this->travelTo('2026-10-20 10:00:00');

    $user = User::factory()->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address);
    $conversation = HelpConversation::factory()->for($user)->create([
        'draft_version' => 1,
        'draft' => [
            'address_id' => $address->id,
            'provider' => [
                'utility_type_id' => UtilityType::factory()->create()->id,
                'name' => 'Провайдер, що прострочився',
            ],
        ],
    ]);
    $approval = app(CreatePropertySetupApproval::class)->handle($user, $conversation);

    $this->travel(16)->minutes();

    $this->actingAs($user)
        ->getJson(route('help.state', ['conversation_id' => $conversation->id]))
        ->assertOk()
        ->assertJsonPath('pending_approval', null);

    $this->actingAs($user)
        ->postJson(route('help.approve'), [
            'conversation_id' => $conversation->id,
            'approval_id' => $approval->id,
            'version' => 1,
            'action' => 'approve',
        ])
        ->assertConflict();

    $this->actingAs($user)
        ->postJson(route('help.approve'), [
            'conversation_id' => $conversation->id,
            'approval_id' => $approval->id,
            'version' => 1,
            'action' => 'edit',
            'arguments' => ['provider' => ['name' => 'Змінена назва']],
        ])
        ->assertConflict();

    expect(ServiceProvider::query()->count())->toBe(0);
});
