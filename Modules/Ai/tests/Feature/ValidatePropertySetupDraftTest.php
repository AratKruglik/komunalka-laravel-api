<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Modules\Address\Models\Address;
use Modules\Ai\Actions\ValidatePropertySetupDraft;
use Modules\Auth\Models\User;
use Modules\Shared\Models\UtilityType;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('accepts an address and meter package for the authenticated user', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address);
    $utility = UtilityType::factory()->create();

    $data = app(ValidatePropertySetupDraft::class)->handle($user, [
        'address_id' => $address->id,
        'meter' => ['utility_type_id' => $utility->id, 'serial_number' => 'ABC', 'name' => 'Лічильник', 'initial_reading' => 0],
    ]);

    expect($data['meter']['serial_number'])->toBe('ABC');
});

it('rejects a foreign address in a proposed package', function (): void {
    $user = User::factory()->create();
    $address = Address::factory()->create();
    $utility = UtilityType::factory()->create();

    expect(fn () => app(ValidatePropertySetupDraft::class)->handle($user, [
        'address_id' => $address->id,
        'meter' => ['utility_type_id' => $utility->id, 'serial_number' => 'ABC', 'name' => 'Лічильник', 'initial_reading' => 0],
    ]))->toThrow(HttpException::class);
});

it('rejects missing required fields before creating an approval', function (): void {
    $user = User::factory()->create();

    expect(fn () => app(ValidatePropertySetupDraft::class)->handle($user, ['address' => ['city' => 'Київ']]))
        ->toThrow(ValidationException::class);
});
