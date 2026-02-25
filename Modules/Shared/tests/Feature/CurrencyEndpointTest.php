<?php

declare(strict_types=1);

use Modules\Auth\Models\User;
use Modules\Shared\Models\Currency;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

describe('index', function () {
    it('returns a list of currencies without auth', function () {
        Currency::factory()->count(3)->create();

        $this->getJson(route('api.currencies.index'))
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'code', 'name', 'symbol', 'created_at', 'updated_at']]]);
    });
});

describe('show', function () {
    it('returns a single currency without auth', function () {
        $currency = Currency::factory()->create();

        $this->getJson(route('api.currencies.show', ['currency' => $currency->id]))
            ->assertSuccessful()
            ->assertJsonPath('data.id', $currency->id)
            ->assertJsonPath('data.code', $currency->code);
    });

    it('returns 404 for non-existent currency', function () {
        $this->getJson(route('api.currencies.show', ['currency' => 999]))
            ->assertNotFound();
    });
});

describe('store', function () {
    it('admin creates a new currency', function () {
        $payload = ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'];

        $this->actingAs($this->admin, 'api')
            ->postJson(route('api.currencies.store'), $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.code', 'USD')
            ->assertJsonPath('data.name', 'US Dollar')
            ->assertJsonPath('data.symbol', '$');

        $this->assertDatabaseHas('currencies', $payload);
    });

    it('returns 401 without auth', function () {
        $payload = ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'];

        $this->postJson(route('api.currencies.store'), $payload)
            ->assertUnauthorized();
    });

    it('returns 403 for regular user', function () {
        $user = User::factory()->create();
        $payload = ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'];

        $this->actingAs($user, 'api')
            ->postJson(route('api.currencies.store'), $payload)
            ->assertForbidden();
    });

    it('validates required fields', function (string $field) {
        $payload = ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'];
        unset($payload[$field]);

        $this->actingAs($this->admin, 'api')
            ->postJson(route('api.currencies.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with(['code', 'name', 'symbol']);

    it('validates code must be exactly 3 characters', function () {
        $payload = ['code' => 'US', 'name' => 'US Dollar', 'symbol' => '$'];

        $this->actingAs($this->admin, 'api')
            ->postJson(route('api.currencies.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    });

    it('validates code uniqueness', function () {
        Currency::factory()->create(['code' => 'USD']);

        $payload = ['code' => 'USD', 'name' => 'Another Dollar', 'symbol' => '$'];

        $this->actingAs($this->admin, 'api')
            ->postJson(route('api.currencies.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    });
});

describe('update', function () {
    it('admin updates an existing currency', function () {
        $currency = Currency::factory()->create(['code' => 'USD']);
        $payload = ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'];

        $this->actingAs($this->admin, 'api')
            ->putJson(route('api.currencies.update', ['currency' => $currency->id]), $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.code', 'EUR')
            ->assertJsonPath('data.name', 'Euro');

        $this->assertDatabaseHas('currencies', ['id' => $currency->id, 'code' => 'EUR']);
    });

    it('allows keeping the same code on update', function () {
        $currency = Currency::factory()->create(['code' => 'USD']);
        $payload = ['code' => 'USD', 'name' => 'Updated Dollar', 'symbol' => '$'];

        $this->actingAs($this->admin, 'api')
            ->putJson(route('api.currencies.update', ['currency' => $currency->id]), $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.name', 'Updated Dollar');
    });

    it('returns 401 without auth', function () {
        $currency = Currency::factory()->create();

        $this->putJson(route('api.currencies.update', ['currency' => $currency->id]), ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'])
            ->assertUnauthorized();
    });

    it('returns 403 for regular user', function () {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();

        $this->actingAs($user, 'api')
            ->putJson(route('api.currencies.update', ['currency' => $currency->id]), ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'])
            ->assertForbidden();
    });
});

describe('destroy', function () {
    it('admin deletes a currency', function () {
        $currency = Currency::factory()->create();

        $this->actingAs($this->admin, 'api')
            ->deleteJson(route('api.currencies.destroy', ['currency' => $currency->id]))
            ->assertSuccessful();

        $this->assertDatabaseMissing('currencies', ['id' => $currency->id]);
    });

    it('returns 401 without auth', function () {
        $currency = Currency::factory()->create();

        $this->deleteJson(route('api.currencies.destroy', ['currency' => $currency->id]))
            ->assertUnauthorized();
    });

    it('returns 403 for regular user', function () {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();

        $this->actingAs($user, 'api')
            ->deleteJson(route('api.currencies.destroy', ['currency' => $currency->id]))
            ->assertForbidden();
    });
});
