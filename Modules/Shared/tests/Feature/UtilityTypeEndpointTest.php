<?php

declare(strict_types=1);

use Modules\Shared\Models\UtilityType;

describe('index', function () {
    it('returns only active utility types', function () {
        UtilityType::factory()->count(3)->create();
        UtilityType::factory()->inactive()->count(2)->create();

        $this->getJson(route('api.utility-types.index'))
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'slug', 'display_name', 'unit', 'description', 'is_active', 'created_at', 'updated_at']]]);
    });

    it('does not return inactive utility types', function () {
        UtilityType::factory()->inactive()->create(['slug' => 'inactive-type']);

        $response = $this->getJson(route('api.utility-types.index'))->assertSuccessful();

        $slugs = collect($response->json('data'))->pluck('slug');
        expect($slugs)->not->toContain('inactive-type');
    });
});

describe('show', function () {
    it('returns a single utility type', function () {
        $utilityType = UtilityType::factory()->create();

        $this->getJson(route('api.utility-types.show', ['utility_type' => $utilityType->id]))
            ->assertSuccessful()
            ->assertJsonPath('data.id', $utilityType->id)
            ->assertJsonPath('data.slug', $utilityType->slug);
    });

    it('returns 404 for non-existent utility type', function () {
        $this->getJson(route('api.utility-types.show', ['utility_type' => 999]))
            ->assertNotFound();
    });
});
