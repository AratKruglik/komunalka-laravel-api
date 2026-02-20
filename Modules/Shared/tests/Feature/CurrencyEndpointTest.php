<?php

declare(strict_types=1);

use Modules\Shared\Models\Currency;

beforeEach(function () {
    $this->baseUrl = '/api/v1/currencies';
});

describe('index', function () {
    it('returns a list of currencies', function () {
        Currency::factory()->count(3)->create();

        $this->getJson($this->baseUrl)
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'code', 'name', 'symbol', 'created_at', 'updated_at']]]);
    });
});

describe('show', function () {
    it('returns a single currency', function () {
        $currency = Currency::factory()->create();

        $this->getJson("{$this->baseUrl}/{$currency->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.id', $currency->id)
            ->assertJsonPath('data.code', $currency->code);
    });

    it('returns 404 for non-existent currency', function () {
        $this->getJson("{$this->baseUrl}/999")
            ->assertNotFound();
    });
});

describe('store', function () {
    it('creates a new currency', function () {
        $payload = ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'];

        $this->postJson($this->baseUrl, $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.code', 'USD')
            ->assertJsonPath('data.name', 'US Dollar')
            ->assertJsonPath('data.symbol', '$');

        $this->assertDatabaseHas('currencies', $payload);
    });

    it('validates required fields', function (string $field) {
        $payload = ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'];
        unset($payload[$field]);

        $this->postJson($this->baseUrl, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with(['code', 'name', 'symbol']);

    it('validates code must be exactly 3 characters', function () {
        $payload = ['code' => 'US', 'name' => 'US Dollar', 'symbol' => '$'];

        $this->postJson($this->baseUrl, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    });

    it('validates code uniqueness', function () {
        Currency::factory()->create(['code' => 'USD']);

        $payload = ['code' => 'USD', 'name' => 'Another Dollar', 'symbol' => '$'];

        $this->postJson($this->baseUrl, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    });
});

describe('update', function () {
    it('updates an existing currency', function () {
        $currency = Currency::factory()->create(['code' => 'USD']);
        $payload = ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'];

        $this->putJson("{$this->baseUrl}/{$currency->id}", $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.code', 'EUR')
            ->assertJsonPath('data.name', 'Euro');

        $this->assertDatabaseHas('currencies', ['id' => $currency->id, 'code' => 'EUR']);
    });

    it('allows keeping the same code on update', function () {
        $currency = Currency::factory()->create(['code' => 'USD']);
        $payload = ['code' => 'USD', 'name' => 'Updated Dollar', 'symbol' => '$'];

        $this->putJson("{$this->baseUrl}/{$currency->id}", $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.name', 'Updated Dollar');
    });
});

describe('destroy', function () {
    it('deletes a currency', function () {
        $currency = Currency::factory()->create();

        $this->deleteJson("{$this->baseUrl}/{$currency->id}")
            ->assertSuccessful();

        $this->assertDatabaseMissing('currencies', ['id' => $currency->id]);
    });
});
