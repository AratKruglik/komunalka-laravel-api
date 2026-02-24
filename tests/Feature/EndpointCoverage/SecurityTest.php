<?php

declare(strict_types=1);

use Modules\Address\Models\AddressType;
use Modules\Address\Models\Region;
use Modules\Auth\Models\User;

describe('Security edge cases', function () {
    it('handles SQL injection in login email', function () {
        $this->postJson(route('api.auth.login'), [
            'email' => "' OR '1'='1",
            'password' => 'password123',
        ])->assertUnprocessable();
    });

    it('handles XSS in register username without 500 error', function () {
        $payload = [
            'username' => '<img src=x onerror=alert(1)>',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone_number' => '+380501234567',
            'email' => 'xss-test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson(route('api.auth.register'), $payload);

        expect($response->status())->not->toBe(500);
    });

    it('handles XSS in address fields', function () {
        $user = User::factory()->create();
        $region = Region::factory()->create();
        $addressType = AddressType::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.address.store'), [
                'region_id' => $region->id,
                'address_type_id' => $addressType->id,
                'city' => '<script>alert("xss")</script>',
                'street' => '<img src=x onerror=alert(1)>',
                'building_number' => '1',
            ]);

        expect($response->status())->not->toBe(500);
    });

    it('handles very long string inputs gracefully', function () {
        $this->postJson(route('api.auth.register'), [
            'username' => str_repeat('a', 1000),
            'first_name' => str_repeat('b', 1000),
            'last_name' => str_repeat('c', 1000),
            'email' => str_repeat('d', 500).'@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertUnprocessable();
    });

    it('handles unicode and emoji in text fields', function () {
        $user = User::factory()->create();
        $region = Region::factory()->create();
        $addressType = AddressType::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.address.store'), [
                'region_id' => $region->id,
                'address_type_id' => $addressType->id,
                'city' => 'Київ 🏙️',
                'street' => 'Вулиця Тараса Шевченка',
                'building_number' => '1',
            ]);

        expect($response->status())->not->toBe(500);
    });

    it('returns 401 not 500 for expired JWT', function () {
        $this->withHeader('Authorization', 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJ0ZXN0Iiwic3ViIjoiMSIsImV4cCI6MX0.fake')
            ->getJson(route('api.address.index'))
            ->assertUnauthorized();
    });

    it('returns 401 not 500 for malformed JWT', function () {
        $this->withHeader('Authorization', 'Bearer completely-invalid-token')
            ->getJson(route('api.address.index'))
            ->assertUnauthorized();
    });
});
