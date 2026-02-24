<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Pagination format compliance', function () {
    it('returns standard pagination structure', function () {
        $addresses = Address::factory()->count(8)->create();
        foreach ($addresses as $address) {
            $this->user->addresses()->attach($address->id, ['is_primary' => false]);
        }

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.index', ['per_page' => 5]))
            ->assertSuccessful()
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
            ])
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 8);
    });

    it('respects per_page parameter', function () {
        $addresses = Address::factory()->count(10)->create();
        foreach ($addresses as $address) {
            $this->user->addresses()->attach($address->id, ['is_primary' => false]);
        }

        $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.index', ['per_page' => 3]))
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.per_page', 3);
    });

    it('supports page navigation', function () {
        $addresses = Address::factory()->count(6)->create();
        foreach ($addresses as $address) {
            $this->user->addresses()->attach($address->id, ['is_primary' => false]);
        }

        $page1 = $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.index', ['per_page' => 3, 'page' => 1]))
            ->assertSuccessful()
            ->assertJsonPath('meta.current_page', 1);

        $page2 = $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.index', ['per_page' => 3, 'page' => 2]))
            ->assertSuccessful()
            ->assertJsonPath('meta.current_page', 2);

        expect($page1->json('data.0.id'))->not->toBe($page2->json('data.0.id'));
    });

    it('returns empty data array when no results', function () {
        $this->actingAs($this->user, 'api')
            ->getJson(route('api.address.index'))
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });
});
