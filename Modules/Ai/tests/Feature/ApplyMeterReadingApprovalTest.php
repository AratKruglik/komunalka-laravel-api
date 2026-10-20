<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Approvals\PendingApproval;
use Modules\Address\Models\Address;
use Modules\Ai\Actions\ApplyMeterReadingApproval;
use Modules\Ai\Actions\CreateMeterReadingApproval;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Ai\Models\HelpApproval;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

it('records a public meter proposal once and returns the stored result on replay', function (): void {
    $this->travelTo('2026-06-15 12:00:00');
    Storage::fake(AssistantMeterPhoto::DISK);
    $user = User::factory()->create();
    $conversation = HelpConversation::factory()->for($user)->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address);
    $meter = Meter::factory()->create([
        'address_id' => $address->id,
        'utility_type_id' => UtilityType::factory()->create(['slug' => 'cold-water'])->id,
        'initial_reading' => 10,
    ]);
    $image = new Imagick;
    $image->newImage(40, 40, new ImagickPixel('white'));
    $image->setImageFormat('jpeg');
    $photo = AssistantMeterPhoto::factory()->for($user)->create([
        'help_conversation_id' => $conversation->id, 'path' => 'assistant-photos/test.jpg',
    ]);
    Storage::disk(AssistantMeterPhoto::DISK)->put($photo->path, $image->getImageBlob());
    $pending = new PendingApproval('sdk-approval', 'record_meter_reading', [
        'photo_id' => $photo->id, 'meter_id' => $meter->id,
        'reading_value' => 25.5, 'reading_date' => '2026-06-15',
    ]);
    $card = app(CreateMeterReadingApproval::class)->handle($user, $conversation, $pending, ['id' => 'sdk-approval', 'tool' => 'record_meter_reading']);
    $approval = HelpApproval::query()->findOrFail($card['id']);
    $action = app(ApplyMeterReadingApproval::class);

    $first = $action->handle($user, $conversation, $approval, 0, 'approve');
    $second = $action->handle($user, $conversation, $approval, 0, 'approve');

    expect($first)->toEqual($second)
        ->and($first['status'])->toBe('saved')
        ->and(MeterReading::query()->count())->toBe(1)
        ->and(AssistantMeterPhoto::query()->whereKey($photo->id)->exists())->toBeFalse();
});
