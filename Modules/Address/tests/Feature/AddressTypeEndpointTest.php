<?php

declare(strict_types=1);

use Modules\Address\Models\AddressType;
use Modules\Auth\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('GET /addresstype (index)', function () {
    it('returns all address types', function () {
        AddressType::factory()->count(3)->create();

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.addresstype.index'))
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'description', 'icon']]]);
    });

    it('returns 401 without authentication', function () {
        $this->getJson(route('api.addresstype.index'))->assertUnauthorized();
    });
});

describe('GET /addresstype/{id} (show)', function () {
    it('returns a single address type', function () {
        $type = AddressType::factory()->create();

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.addresstype.show', $type->id))
            ->assertSuccessful()
            ->assertJsonPath('data.id', $type->id)
            ->assertJsonPath('data.name', $type->name);
    });

    it('returns 404 for non-existent address type', function () {
        $this->actingAs($this->user, 'api')
            ->getJson(route('api.addresstype.show', 999))
            ->assertNotFound();
    });
});
