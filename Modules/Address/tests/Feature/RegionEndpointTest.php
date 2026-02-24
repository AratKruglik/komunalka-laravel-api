<?php

declare(strict_types=1);

use Modules\Address\Models\Region;
use Modules\Auth\Models\User;

beforeEach(function () {
    $this->baseUrl = '/api/v1/region';
    $this->user = User::factory()->create();
});

describe('GET /region (index)', function () {
    it('returns all regions', function () {
        Region::factory()->count(3)->create();

        $this->actingAs($this->user, 'api')
            ->getJson($this->baseUrl)
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'name']]]);
    });

    it('returns 401 without authentication', function () {
        $this->getJson($this->baseUrl)->assertUnauthorized();
    });
});

describe('GET /region/{id} (show)', function () {
    it('returns a single region', function () {
        $region = Region::factory()->create();

        $this->actingAs($this->user, 'api')
            ->getJson("{$this->baseUrl}/{$region->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.id', $region->id)
            ->assertJsonPath('data.name', $region->name);
    });

    it('returns 404 for non-existent region', function () {
        $this->actingAs($this->user, 'api')
            ->getJson("{$this->baseUrl}/999")
            ->assertNotFound();
    });
});
