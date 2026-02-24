<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Shared\Models\ServiceCounter;
use Modules\Shared\Models\ServiceCounterValue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->id, ['is_primary' => true]);
    $this->serviceCounter = ServiceCounter::factory()->create([
        'address_id' => $this->address->id,
    ]);
});

describe('POST /api/v1/meterreading (store)', function () {
    it('creates service counter value', function () {
        $payload = [
            'service_counter_id' => $this->serviceCounter->id,
            'value' => 123.45,
        ];

        $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/meterreading', $payload)
            ->assertCreated();

        $this->assertDatabaseHas('service_counter_values', [
            'service_counter_id' => $this->serviceCounter->id,
            'value' => 123.45,
        ]);
    });

    it('validates required fields', function (string $field) {
        $payload = [
            'service_counter_id' => $this->serviceCounter->id,
            'value' => 100.0,
        ];
        unset($payload[$field]);

        $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/meterreading', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with(['service_counter_id', 'value']);
});

describe('GET /api/v1/meterreading/{id} (show)', function () {
    it('returns value', function () {
        $counterValue = ServiceCounterValue::factory()->create([
            'service_counter_id' => $this->serviceCounter->id,
            'value' => 55.0,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/meterreading/{$counterValue->id}")
            ->assertSuccessful();
    });

    it('returns 404 for non-owned value', function () {
        $otherAddress = Address::factory()->create();
        $otherCounter = ServiceCounter::factory()->create(['address_id' => $otherAddress->id]);
        $counterValue = ServiceCounterValue::factory()->create(['service_counter_id' => $otherCounter->id]);

        $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/meterreading/{$counterValue->id}")
            ->assertNotFound();
    });
});

describe('DELETE /api/v1/meterreading/{id} (destroy)', function () {
    it('deletes value', function () {
        $counterValue = ServiceCounterValue::factory()->create([
            'service_counter_id' => $this->serviceCounter->id,
            'value' => 88.0,
        ]);

        $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/meterreading/{$counterValue->id}")
            ->assertSuccessful();

        $this->assertDatabaseMissing('service_counter_values', ['id' => $counterValue->id]);
    });
});
