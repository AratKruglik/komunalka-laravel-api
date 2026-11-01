<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;

describe('POST /broadcasting/auth for address channels', function (): void {
    beforeEach(function (): void {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);
        Broadcast::purge();
        require base_path('routes/channels.php');
        $this->address = Address::factory()->create();
    });

    it('signs the subscription for a user linked to the address', function (): void {
        $user = User::factory()->create();
        $user->addresses()->attach($this->address->getKey(), ['is_primary' => true]);

        $this->actingAs($user)
            ->post('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-address.'.$this->address->getKey(),
            ])
            ->assertOk()
            ->assertJsonStructure(['auth']);
    });

    it('rejects a user not linked to the address', function (): void {
        $this->actingAs(User::factory()->create())
            ->post('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-address.'.$this->address->getKey(),
            ])
            ->assertForbidden();
    });

    it('rejects guests', function (): void {
        $this->post('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-address.'.$this->address->getKey(),
        ])->assertForbidden();
    });
});
