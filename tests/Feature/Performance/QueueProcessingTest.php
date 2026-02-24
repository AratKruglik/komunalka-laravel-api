<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\UtilityType;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->id, ['is_primary' => true]);
});

describe('Queue processing (sync mode)', function () {
    it('processes meter photo upload synchronously', function () {
        $utilityType = UtilityType::factory()->create();
        $meter = Meter::factory()->create([
            'address_id' => $this->address->id,
            'utility_type_id' => $utilityType->id,
        ]);

        $photo = UploadedFile::fake()->image('meter.jpg', 800, 600);

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.meter.upload-photo', $meter->getKey()), ['photo' => $photo])
            ->assertSuccessful();

        $this->assertDatabaseHas('media', [
            'model_type' => Meter::class,
            'model_id' => $meter->getKey(),
            'collection_name' => 'photo',
        ]);
    });

    it('processes avatar upload synchronously', function () {
        $avatar = UploadedFile::fake()->image('avatar.jpg', 400, 400);

        $this->actingAs($this->user, 'api')
            ->putJson(route('api.users.update', $this->user->getKey()), ['avatar' => $avatar])
            ->assertSuccessful();

        $this->assertDatabaseHas('media', [
            'model_type' => User::class,
            'model_id' => $this->user->getKey(),
            'collection_name' => 'avatar',
        ]);
    });
});
