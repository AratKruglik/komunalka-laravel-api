<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\User;

describe('HandleInertiaRequests shared auth.user avatar', function (): void {
    beforeEach(function (): void {
        $this->withoutVite();
        Storage::fake('public');
    });

    it('exposes the nested avatar payload for a user with an avatar', function (): void {
        $user = User::factory()->create();
        $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))->toMediaCollection('avatar');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('auth.user.data.attributes.avatar')
                ->where('auth.user.data.attributes.avatar.is_processing', true)
                ->whereType('auth.user.data.attributes.avatar.original_url', 'string')
                ->where('auth.user.data.attributes.avatar.optimized_url', null)
                ->where('auth.user.data.attributes.avatar.thumbnail_url', null),
            );
    });

    it('exposes null avatar for a user without an avatar', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.data.attributes.avatar', null),
            );
    });

    it('reflects generated conversions once processing completes', function (): void {
        $user = User::factory()->create();
        $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))->toMediaCollection('avatar');
        $media = $user->getFirstMedia('avatar');
        $media->markAsConversionGenerated('optimized')->save();
        $media->markAsConversionGenerated('thumbnail')->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.data.attributes.avatar.is_processing', false)
                ->whereType('auth.user.data.attributes.avatar.optimized_url', 'string')
                ->whereType('auth.user.data.attributes.avatar.thumbnail_url', 'string'),
            );
    });
});
