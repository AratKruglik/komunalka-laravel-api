<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\UtilityType;

beforeEach(function () {
    $this->baseUrl = '/api/v1/meter';
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->id, ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create();
});

describe('GET /api/v1/meter (index)', function () {
    it('returns user meters', function () {
        Meter::factory()->count(2)->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson($this->baseUrl)
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    });

    it('excludes other users meters', function () {
        $otherAddress = Address::factory()->create();
        Meter::factory()->create([
            'address_id' => $otherAddress->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson($this->baseUrl)
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson($this->baseUrl)->assertUnauthorized();
    });
});

describe('GET /api/v1/meter/{id} (show)', function () {
    it('returns a meter', function () {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson("{$this->baseUrl}/{$meter->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.id', $meter->id)
            ->assertJsonStructure(['data' => ['id', 'serial_number', 'name', 'is_active', 'address_id', 'utility_type']]);
    });

    it('returns 404 for non-owned meter', function () {
        $otherAddress = Address::factory()->create();
        $meter = Meter::factory()->create([
            'address_id' => $otherAddress->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson("{$this->baseUrl}/{$meter->id}")
            ->assertNotFound();
    });
});

describe('GET /api/v1/meter/address/{addressId} (byAddress)', function () {
    it('returns meters by address', function () {
        Meter::factory()->count(2)->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson("{$this->baseUrl}/address/{$this->address->id}")
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    });

    it('returns 404 for non-owned address', function () {
        $otherAddress = Address::factory()->create();

        $this->actingAs($this->user, 'api')
            ->getJson("{$this->baseUrl}/address/{$otherAddress->id}")
            ->assertNotFound();
    });
});

describe('GET /api/v1/meter/active (active)', function () {
    it('returns only active meters', function () {
        Meter::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
            'is_active' => true,
        ]);

        Meter::factory()->inactive()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson("{$this->baseUrl}/active")
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    });
});

describe('POST /api/v1/meter (store)', function () {
    it('creates a meter', function () {
        $payload = [
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
            'serial_number' => 'M-123456',
            'name' => 'Лічильник газу',
            'initial_reading' => 0,
            'is_active' => true,
        ];

        $this->actingAs($this->user, 'api')
            ->postJson($this->baseUrl, $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Лічильник газу')
            ->assertJsonPath('data.address_id', $this->address->id);

        $this->assertDatabaseHas('meters', ['name' => 'Лічильник газу', 'serial_number' => 'M-123456']);
    });

    it('validates required fields', function (string $field) {
        $payload = [
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
            'serial_number' => 'M-123456',
            'name' => 'Test Meter',
            'initial_reading' => 0,
        ];
        unset($payload[$field]);

        $this->actingAs($this->user, 'api')
            ->postJson($this->baseUrl, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with(['address_id', 'utility_type_id', 'serial_number', 'name', 'initial_reading']);

    it('returns 404 for non-owned address', function () {
        $otherAddress = Address::factory()->create();

        $payload = [
            'address_id' => $otherAddress->id,
            'utility_type_id' => $this->utilityType->id,
            'serial_number' => 'M-999999',
            'name' => 'Test Meter',
            'initial_reading' => 0,
        ];

        $this->actingAs($this->user, 'api')
            ->postJson($this->baseUrl, $payload)
            ->assertNotFound();
    });
});

describe('POST /api/v1/meter/{id}/photo (uploadPhoto)', function () {
    it('uploads photo', function () {
        Storage::fake('public');

        $meter = Meter::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $photo = UploadedFile::fake()->image('meter.jpg');

        $this->actingAs($this->user, 'api')
            ->postJson("{$this->baseUrl}/{$meter->id}/photo", ['photo' => $photo])
            ->assertSuccessful();
    });

    it('validates image file', function () {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($this->user, 'api')
            ->postJson("{$this->baseUrl}/{$meter->id}/photo", ['photo' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
    });
});

describe('PUT /api/v1/meter/{id} (update)', function () {
    it('updates meter partially', function () {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
            'name' => 'Старий лічильник',
        ]);

        $this->actingAs($this->user, 'api')
            ->putJson("{$this->baseUrl}/{$meter->id}", ['name' => 'Новий лічильник'])
            ->assertSuccessful()
            ->assertJsonPath('data.name', 'Новий лічильник');

        $this->assertDatabaseHas('meters', ['id' => $meter->id, 'name' => 'Новий лічильник']);
    });

    it('returns 404 for non-owned meter', function () {
        $otherAddress = Address::factory()->create();
        $meter = Meter::factory()->create([
            'address_id' => $otherAddress->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->putJson("{$this->baseUrl}/{$meter->id}", ['name' => 'Updated'])
            ->assertNotFound();
    });
});

describe('DELETE /api/v1/meter/{id} (destroy)', function () {
    it('deletes meter', function () {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $this->utilityType->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->deleteJson("{$this->baseUrl}/{$meter->id}")
            ->assertSuccessful();

        $this->assertDatabaseMissing('meters', ['id' => $meter->id]);
    });

    it('returns 404 for non-owned meter', function () {
        $otherAddress = Address::factory()->create();
        $meter = Meter::factory()->create([
            'address_id' => $otherAddress->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->deleteJson("{$this->baseUrl}/{$meter->id}")
            ->assertNotFound();
    });
});
