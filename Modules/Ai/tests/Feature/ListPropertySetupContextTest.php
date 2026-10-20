<?php

declare(strict_types=1);

use Laravel\Ai\Tools\Request;
use Modules\Address\Models\Address;
use Modules\Ai\Tools\ListPropertySetupContext;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;

it('shows only the authenticated users addresses and meters', function (): void {
    $user = User::factory()->create();
    $mine = Address::factory()->create();
    $foreign = Address::factory()->create();
    $user->addresses()->attach($mine);
    $mineMeter = Meter::factory()->create(['address_id' => $mine->id]);
    Meter::factory()->create(['address_id' => $foreign->id]);

    $payload = json_decode((string) app(ListPropertySetupContext::class, ['user' => $user])->handle(new Request([])), true, 512, JSON_THROW_ON_ERROR);

    expect(array_column($payload['addresses'], 'id'))->toBe([$mine->id])
        ->and(array_column($payload['meters'], 'id'))->toBe([$mineMeter->id]);
});
