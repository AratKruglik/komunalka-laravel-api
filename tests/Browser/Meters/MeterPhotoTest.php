<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
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

describe('Meter Photo Upload', function (): void {
    it('uploads photo successfully', function (): void {
        $this->actingAs($this->user)
            ->post(route('meters.photo', $this->meter->getKey()), [
                'photo' => UploadedFile::fake()->image('meter.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Фото завантажено');
    });

    it('rejects non-image file', function (): void {
        $this->actingAs($this->user)
            ->post(route('meters.photo', $this->meter->getKey()), [
                'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('photo');
    });

    it('rejects file over 10MB', function (): void {
        $this->actingAs($this->user)
            ->post(route('meters.photo', $this->meter->getKey()), [
                'photo' => UploadedFile::fake()->image('big.jpg')->size(11000),
            ])
            ->assertSessionHasErrors('photo');
    });

    it('rejects missing photo field', function (): void {
        $this->actingAs($this->user)
            ->post(route('meters.photo', $this->meter->getKey()), [])
            ->assertSessionHasErrors('photo');
    });

    it('redirects guest to login', function (): void {
        $this->post(route('meters.photo', $this->meter->getKey()), [
            'photo' => UploadedFile::fake()->image('meter.jpg'),
        ])
            ->assertRedirect(route('login'));
    });

    it('returns 404 for another user meter', function (): void {
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->post(route('meters.photo', $this->meter->getKey()), [
                'photo' => UploadedFile::fake()->image('meter.jpg'),
            ])
            ->assertNotFound();
    });
});
