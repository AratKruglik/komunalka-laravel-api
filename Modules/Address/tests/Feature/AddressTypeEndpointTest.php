<?php

declare(strict_types=1);

use Modules\Address\Models\AddressType;
use Modules\Auth\Models\User;

beforeEach(function () {
    $this->baseUrl = '/api/v1/addresstype';
    $this->user = User::factory()->create();
});

describe('GET /addresstype (index)', function () {
    it('returns all address types', function () {
        AddressType::factory()->count(3)->create();

        $this->actingAs($this->user, 'api')
            ->getJson($this->baseUrl)
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'description', 'icon']]]);
    });

    it('returns 401 without authentication', function () {
        $this->getJson($this->baseUrl)->assertUnauthorized();
    });
});

describe('GET /addresstype/{id} (show)', function () {
    it('returns a single address type', function () {
        $type = AddressType::factory()->create();

        $this->actingAs($this->user, 'api')
            ->getJson("{$this->baseUrl}/{$type->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.id', $type->id)
            ->assertJsonPath('data.name', $type->name);
    });

    it('returns 404 for non-existent address type', function () {
        $this->actingAs($this->user, 'api')
            ->getJson("{$this->baseUrl}/999")
            ->assertNotFound();
    });
});
