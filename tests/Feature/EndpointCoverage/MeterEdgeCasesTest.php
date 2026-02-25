<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\UtilityType;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->id, ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create();
});

describe('GET /api/v1/meter/active (edge cases)', function () {
    it('excludes other users active meters', function () {
        Meter::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
            'is_active' => true,
        ]);

        $otherAddress = Address::factory()->create();
        Meter::factory()->create([
            'address_id' => $otherAddress->id,
            'utility_type_id' => $this->utilityType->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.meter.active'))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    });
});

describe('POST /api/v1/meter (edge cases)', function () {
    it('handles duplicate serial_number gracefully', function () {
        Meter::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
            'serial_number' => 'DUPLICATE-001',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter.store'), [
                'address_id' => $this->address->id,
                'utility_type_id' => $this->utilityType->id,
                'serial_number' => 'DUPLICATE-001',
                'name' => 'Duplicate Meter',
                'initial_reading' => 0,
            ]);

        expect($response->status())->not->toBe(500);
    });

    it('creates meter with optional service_provider_id', function () {
        $serviceProvider = ServiceProvider::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter.store'), [
                'address_id' => $this->address->id,
                'utility_type_id' => $this->utilityType->id,
                'service_provider_id' => $serviceProvider->id,
                'serial_number' => 'WITH-PROVIDER-001',
                'name' => 'Meter With Provider',
                'initial_reading' => 0,
            ])->assertCreated();

        $this->assertDatabaseHas('meters', [
            'serial_number' => 'WITH-PROVIDER-001',
            'service_provider_id' => $serviceProvider->id,
        ]);
    });
});

describe('POST /api/v1/meter/{id}/photo (edge cases)', function () {
    it('returns 404 for non-owned meter photo upload', function () {
        $otherAddress = Address::factory()->create();
        $meter = Meter::factory()->create([
            'address_id' => $otherAddress->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        Storage::fake('public');
        $photo = UploadedFile::fake()->image('meter.jpg');

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter.upload-photo', $meter->getKey()), ['photo' => $photo])
            ->assertNotFound();
    });

    it('rejects oversized file', function () {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $photo = UploadedFile::fake()->image('large.jpg')->size(11000);

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter.upload-photo', $meter->getKey()), ['photo' => $photo])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
    });
});

describe('PUT /api/v1/users/{id} (edge cases)', function () {
    it('allows updating another users profile when authenticated', function () {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->putJson(route('api.users.update', $otherUser->getKey()), [
                'first_name' => 'Updated',
            ]);

        expect($response->status())->not->toBe(500);
    });
});

describe('DELETE /api/v1/users/{id} (edge cases)', function () {
    it('admin can delete user', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'api')
            ->deleteJson(route('api.users.destroy', $this->user->getKey()))
            ->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
    });

    it('regular user cannot delete self', function () {
        $this->actingAs($this->user, 'api')
            ->deleteJson(route('api.users.destroy', $this->user->getKey()))
            ->assertForbidden();
    });
});
