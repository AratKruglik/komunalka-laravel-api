<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Address\Models\AddressType;
use Modules\Address\Models\Region;
use Modules\Ai\Actions\ApplyPropertySetupApproval;
use Modules\Ai\Actions\CreatePropertySetupApproval;
use Modules\Ai\Enums\HelpApprovalStatus;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\Currency;
use Modules\Shared\Models\UtilityType;

it('creates an address, provider and meter atomically and replays the same result', function (): void {
    $user = User::factory()->create();
    $utility = UtilityType::factory()->create();
    $conversation = HelpConversation::factory()->for($user)->create([
        'draft_version' => 1,
        'draft' => [
            'address' => [
                'region_id' => Region::factory()->create()->id,
                'address_type_id' => AddressType::factory()->create()->id,
                'city' => 'Київ', 'street' => 'Хрещатик', 'building_number' => '1',
            ],
            'provider' => ['utility_type_id' => $utility->id, 'name' => 'Провайдер'],
            'meter' => ['utility_type_id' => $utility->id, 'serial_number' => 'ABC-123', 'name' => 'Лічильник', 'initial_reading' => 0],
        ],
    ]);
    $approval = app(CreatePropertySetupApproval::class)->handle($user, $conversation);
    $action = app(ApplyPropertySetupApproval::class);

    $first = $action->handle($user, $conversation, $approval, 1, 'approve');
    $again = $action->handle($user, $conversation, $approval, 1, 'approve');

    expect($first)->toEqual($again)
        ->and($first['status'])->toBe('applied')
        ->and(Address::query()->count())->toBe(1)
        ->and(ServiceProvider::query()->count())->toBe(1)
        ->and(Meter::query()->count())->toBe(1)
        ->and($approval->refresh()->status)->toBe(HelpApprovalStatus::Applied);
});

it('rejects an approval without writing domain records', function (): void {
    $user = User::factory()->create();
    $conversation = HelpConversation::factory()->for($user)->create([
        'draft_version' => 1,
        'draft' => ['address' => [
            'region_id' => Region::factory()->create()->id,
            'address_type_id' => AddressType::factory()->create()->id,
            'city' => 'Київ', 'street' => 'Хрещатик', 'building_number' => '1',
        ]],
    ]);
    $approval = app(CreatePropertySetupApproval::class)->handle($user, $conversation);

    $result = app(ApplyPropertySetupApproval::class)->handle($user, $conversation, $approval, 1, 'reject');

    expect($result['status'])->toBe('rejected')
        ->and(Address::query()->count())->toBe(0)
        ->and($approval->refresh()->status)->toBe(HelpApprovalStatus::Cancelled);
});

it('creates a requested provider tariff with the approved package', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address);
    $utility = UtilityType::factory()->create();
    $currency = Currency::factory()->create();
    $conversation = HelpConversation::factory()->for($user)->create([
        'draft_version' => 1,
        'draft' => ['address_id' => $address->id, 'provider' => [
            'utility_type_id' => $utility->id, 'name' => 'Провайдер',
            'tariffs' => [[
                'utility_type_id' => $utility->id, 'currency_id' => $currency->id,
                'name' => 'Базовий', 'base_rate' => '2.50', 'effective_from' => '2026-06-15',
            ]],
        ]],
    ]);
    $approval = app(CreatePropertySetupApproval::class)->handle($user, $conversation);

    app(ApplyPropertySetupApproval::class)->handle($user, $conversation, $approval, 1, 'approve');

    expect(ServiceProvider::query()->firstOrFail()->tariffs()->count())->toBe(1);
});
