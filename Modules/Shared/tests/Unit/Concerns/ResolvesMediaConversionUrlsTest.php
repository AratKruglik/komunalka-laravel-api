<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Address\Models\Address;
use Modules\Meter\Models\Meter;
use Modules\Shared\Concerns\ResolvesMediaConversionUrls;
use Modules\Shared\Models\UtilityType;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function (): void {
    Storage::fake('public');

    $this->resolver = new class
    {
        use ResolvesMediaConversionUrls;

        public function resolve(?Media $media, array $conversions = ['optimized', 'thumbnail']): ?array
        {
            return $this->resolveMediaConversionUrls($media, $conversions);
        }
    };

    $this->meter = Meter::factory()->create([
        'address_id' => Address::factory()->create()->getKey(),
        'utility_type_id' => UtilityType::factory()->create()->getKey(),
    ]);
});

describe('ResolvesMediaConversionUrls', function (): void {
    it('returns null when no media is attached', function (): void {
        expect($this->resolver->resolve(null))->toBeNull();
    });

    it('marks conversions as processing when none have been generated yet', function (): void {
        $this->meter->addMedia(UploadedFile::fake()->image('meter.jpg'))->toMediaCollection('photo');
        $media = $this->meter->getFirstMedia('photo');

        $result = $this->resolver->resolve($media);

        expect($result)->toBe([
            'original_url' => $media->getUrl(),
            'optimized_url' => null,
            'thumbnail_url' => null,
            'is_processing' => true,
        ]);
    });

    it('exposes conversion urls and stops flagging processing once every conversion is generated', function (): void {
        $this->meter->addMedia(UploadedFile::fake()->image('meter.jpg'))->toMediaCollection('photo');
        $media = $this->meter->getFirstMedia('photo');

        $media->markAsConversionGenerated('optimized')->save();
        $media->markAsConversionGenerated('thumbnail')->save();
        $media->refresh();

        $result = $this->resolver->resolve($media);

        expect($result)->toBe([
            'original_url' => $media->getUrl(),
            'optimized_url' => $media->getUrl('optimized'),
            'thumbnail_url' => $media->getUrl('thumbnail'),
            'is_processing' => false,
        ]);
    });

    it('flags processing as true when only some of the requested conversions are generated', function (): void {
        $this->meter->addMedia(UploadedFile::fake()->image('meter.jpg'))->toMediaCollection('photo');
        $media = $this->meter->getFirstMedia('photo');

        $media->markAsConversionGenerated('optimized')->save();
        $media->refresh();

        $result = $this->resolver->resolve($media);

        expect($result['optimized_url'])->not->toBeNull()
            ->and($result['thumbnail_url'])->toBeNull()
            ->and($result['is_processing'])->toBeTrue();
    });

    it('respects a custom conversions list', function (): void {
        $this->meter->addMedia(UploadedFile::fake()->image('meter.jpg'))->toMediaCollection('photo');
        $media = $this->meter->getFirstMedia('photo');

        $result = $this->resolver->resolve($media, ['optimized']);

        expect($result)->toBe([
            'original_url' => $media->getUrl(),
            'optimized_url' => null,
            'is_processing' => true,
        ]);
    });
});
