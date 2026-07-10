<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

beforeEach(function (): void {
    $this->withoutVite();
    Storage::fake('public');
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->getKey(), ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create();
    $this->meter = Meter::factory()->create([
        'address_id' => $this->address->getKey(),
        'utility_type_id' => $this->utilityType->getKey(),
    ]);
});

describe('MeterReadingResource photos shape', function (): void {
    it('returns an empty photos array when the reading has no photos', function (): void {
        MeterReading::factory()->create(['meter_id' => $this->meter->getKey()]);

        $this->actingAs($this->user)
            ->get(route('readings.index', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('readings.data.0.attributes.photos', 0),
            );
    });

    it('flags a photo as processing when no conversion has been generated yet', function (): void {
        $reading = MeterReading::factory()->create(['meter_id' => $this->meter->getKey()]);
        $reading->addMedia(UploadedFile::fake()->image('reading.jpg'))->toMediaCollection('photos');

        $this->actingAs($this->user)
            ->get(route('readings.index', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('readings.data.0.attributes.photos.0.id')
                ->where('readings.data.0.attributes.photos.0.is_processing', true)
                ->whereType('readings.data.0.attributes.photos.0.original_url', 'string')
                ->where('readings.data.0.attributes.photos.0.optimized_url', null)
                ->where('readings.data.0.attributes.photos.0.thumbnail_url', null),
            );
    });

    it('exposes populated conversion urls for a photo once processing completes', function (): void {
        $reading = MeterReading::factory()->create(['meter_id' => $this->meter->getKey()]);
        $reading->addMedia(UploadedFile::fake()->image('reading.jpg'))->toMediaCollection('photos');
        $media = $reading->getFirstMedia('photos');
        $media->markAsConversionGenerated('optimized')->save();
        $media->markAsConversionGenerated('thumbnail')->save();

        $this->actingAs($this->user)
            ->get(route('readings.index', ['address_id' => $this->address->getKey()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('readings.data.0.attributes.photos.0.is_processing', false)
                ->whereType('readings.data.0.attributes.photos.0.optimized_url', 'string')
                ->whereType('readings.data.0.attributes.photos.0.thumbnail_url', 'string'),
            );
    });
});
