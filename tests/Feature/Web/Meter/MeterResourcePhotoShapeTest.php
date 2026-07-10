<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\UtilityType;

beforeEach(function (): void {
    $this->withoutVite();
    Storage::fake('public');
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->getKey(), ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create();
});

describe('MeterResource photo shape', function (): void {
    it('exposes photo as null when the meter has no photo', function (): void {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $this->actingAs($this->user)
            ->get(route('meters.edit', $meter->getKey()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('meter.data.attributes.photo', null),
            );
    });

    it('flags photo as processing when no conversion has been generated yet', function (): void {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);
        $meter->addMedia(UploadedFile::fake()->image('meter.jpg'))->toMediaCollection('photo');

        $this->actingAs($this->user)
            ->get(route('meters.edit', $meter->getKey()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('meter.data.attributes.photo.is_processing', true)
                ->whereType('meter.data.attributes.photo.original_url', 'string')
                ->where('meter.data.attributes.photo.optimized_url', null)
                ->where('meter.data.attributes.photo.thumbnail_url', null),
            );
    });

    it('exposes populated conversion urls once processing completes', function (): void {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);
        $meter->addMedia(UploadedFile::fake()->image('meter.jpg'))->toMediaCollection('photo');
        $media = $meter->getFirstMedia('photo');
        $media->markAsConversionGenerated('optimized')->save();
        $media->markAsConversionGenerated('thumbnail')->save();

        $this->actingAs($this->user)
            ->get(route('meters.edit', $meter->getKey()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('meter.data.attributes.photo.is_processing', false)
                ->whereType('meter.data.attributes.photo.optimized_url', 'string')
                ->whereType('meter.data.attributes.photo.thumbnail_url', 'string'),
            );
    });
});
