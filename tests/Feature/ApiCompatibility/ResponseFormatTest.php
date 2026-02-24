<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Shared\Models\UtilityType;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('API response format compliance', function () {
    it('returns single resource in data wrapper', function () {
        $this->actingAs($this->user, 'api')
            ->getJson(route('api.users.show', $this->user->getKey()))
            ->assertSuccessful()
            ->assertJsonStructure(['data' => ['id', 'email']]);
    });

    it('returns collection in data wrapper', function () {
        UtilityType::factory()->count(2)->create();

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.utility-types.index'))
            ->assertSuccessful()
            ->assertJsonStructure(['data' => [['id', 'slug', 'display_name']]]);
    });

    it('returns action success with message', function () {
        $refreshToken = \Modules\Auth\Models\RefreshToken::factory()
            ->create(['user_id' => $this->user->getKey()]);

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.auth.revoke-token'), [
                'refresh_token' => $refreshToken->token,
            ])->assertSuccessful()
            ->assertJsonStructure(['message']);
    });

    it('returns 404 with standard error structure', function () {
        $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.show', 99999))
            ->assertNotFound()
            ->assertJsonStructure(['statusCode', 'message', 'details', 'timestamp', 'path'])
            ->assertJsonPath('statusCode', 404)
            ->assertJsonPath('message', 'Not found');
    });

    it('returns 401 with standard error structure', function () {
        $this->getJson(route('api.address.index'))
            ->assertUnauthorized()
            ->assertJsonStructure(['statusCode', 'message', 'timestamp', 'path'])
            ->assertJsonPath('statusCode', 401);
    });

    it('returns 422 with validation error structure', function () {
        $this->actingAs($this->user, 'api')
            ->postJson(route('api.address.store'), [])
            ->assertUnprocessable()
            ->assertJsonStructure(['statusCode', 'message', 'errors'])
            ->assertJsonPath('statusCode', 422)
            ->assertJsonPath('message', 'Data validation error');
    });

    it('returns 403 with standard error structure', function () {
        $otherAddress = Address::factory()->create();

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [$otherAddress->getKey()],
                'from_date' => '2026-01-01',
                'to_date' => '2026-01-31',
                'format' => 'csv',
            ])->assertForbidden()
            ->assertJsonStructure(['statusCode', 'message', 'details', 'timestamp', 'path'])
            ->assertJsonPath('statusCode', 403);
    });

    it('uses ISO 8601 Zulu timestamp format in errors', function () {
        $response = $this->getJson(route('api.address.index'))
            ->assertUnauthorized();

        $timestamp = $response->json('timestamp');
        expect($timestamp)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?Z$/');
    });

    it('includes correct path in error responses', function () {
        $this->getJson(route('api.address.index'))
            ->assertUnauthorized()
            ->assertJsonPath('path', '/api/v1/address');
    });
});
