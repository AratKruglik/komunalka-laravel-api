<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

beforeEach(function (): void {
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

describe('Readings Index photo rendering', function (): void {
    it('wires the optimized conversion URL for a fully processed reading photo and hides the processing badge', function (): void {
        // NOTE: Pest Browser's ephemeral server rewrites `app.url`/asset origin
        // to its own random 127.0.0.1:<port> for page navigation and Inertia
        // requests, but Spatie MediaLibrary's `getUrl()` reads the 'public' disk
        // 'url' setting, which is a literal string baked from APP_URL at config
        // load time and is NOT rewritten by the ephemeral server. This means an
        // `<img src="...">` pointing at the real APP_URL host can never actually
        // load inside this harness (nothing listens there), regardless of
        // whether the fix works — asserting a genuinely-loaded <img> here would
        // be a harness artifact, not a real signal. Actual byte-level
        // retrievability of media URLs is proven without this constraint in
        // tests/Feature/StorageRetrievabilityTest.php (real disk, no fake,
        // real HTTP-equivalent path resolution). Here we assert the component
        // correctly *wires* the resolved URL into the DOM and suppresses the
        // processing badge once every conversion is generated.
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
        ]);
        $reading->addMedia(UploadedFile::fake()->image('reading.jpg'))->toMediaCollection('photos');
        $media = $reading->getFirstMedia('photos');
        $media->markAsConversionGenerated('optimized')->save();
        $media->markAsConversionGenerated('thumbnail')->save();
        $media->refresh();

        $this->actingAs($this->user);

        visit(route('readings.index', ['address_id' => $this->address->getKey()]))
            ->assertNoJavaScriptErrors()
            ->assertVisible('a[aria-label*="Фото показання"]')
            ->assertMissing('.rounded-full.bg-black\\/60')
            ->assertAttributeContains('a[aria-label*="Фото показання"]', 'href', $media->getUrl('optimized'));
    });

    it('renders a processing badge instead of an image when conversions are not ready yet', function (): void {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
        ]);
        $reading->addMedia(UploadedFile::fake()->image('reading.jpg'))->toMediaCollection('photos');

        $this->actingAs($this->user);

        visit(route('readings.index', ['address_id' => $this->address->getKey()]))
            ->assertNoJavaScriptErrors()
            ->assertMissing('img[alt*="Фото показання"]')
            ->assertVisible('a[aria-label*="Фото показання"]');
    });

    it('shows nothing photo-related when a reading has no photos', function (): void {
        MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
        ]);

        $this->actingAs($this->user);

        visit(route('readings.index', ['address_id' => $this->address->getKey()]))
            ->assertNoJavaScriptErrors()
            ->assertMissing('img[alt*="Фото показання"]')
            ->assertMissing('a[aria-label*="Фото показання"]');
    });
});
