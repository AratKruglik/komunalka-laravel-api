<?php

declare(strict_types=1);

use Laravel\Ai\Approvals\PendingApproval;
use Modules\Address\Models\Address;
use Modules\Ai\Actions\CreateMeterReadingApproval;
use Modules\Ai\Actions\EditMeterReadingApproval;
use Modules\Ai\Enums\HelpApprovalStatus;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Ai\Models\HelpApproval;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\UtilityType;

it('replaces an SDK meter approval id with a durable public approval', function (): void {
    $this->travelTo('2026-06-15 12:00:00');
    $user = User::factory()->create();
    $conversation = HelpConversation::factory()->for($user)->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address);
    $meter = Meter::factory()->create([
        'address_id' => $address->id,
        'utility_type_id' => UtilityType::factory()->create(['slug' => 'gas'])->id,
        'initial_reading' => 0,
    ]);
    $photo = AssistantMeterPhoto::factory()->for($user)->create(['help_conversation_id' => $conversation->id]);
    $pending = new PendingApproval('sdk-approval', 'record_meter_reading', [
        'photo_id' => $photo->id, 'meter_id' => $meter->id,
        'reading_value' => 12, 'reading_date' => '2026-06-15',
    ]);
    $action = app(CreateMeterReadingApproval::class);

    $card = $action->handle($user, $conversation, $pending, ['id' => 'sdk-approval', 'tool' => 'record_meter_reading']);
    $again = $action->handle($user, $conversation, $pending, ['id' => 'sdk-approval', 'tool' => 'record_meter_reading']);

    expect($card['id'])->toBe($again['id'])
        ->and($card['id'])->not->toBe('sdk-approval')
        ->and($card['version'])->toBe(0)
        ->and(HelpApproval::query()->findOrFail($card['id'])->status)->toBe(HelpApprovalStatus::Pending);
});

it('edits a meter proposal by cancelling its old version and issuing a new card', function (): void {
    $this->travelTo('2026-06-15 12:00:00');
    $user = User::factory()->create();
    $conversation = HelpConversation::factory()->for($user)->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address);
    $meter = Meter::factory()->create([
        'address_id' => $address->id,
        'utility_type_id' => UtilityType::factory()->create(['slug' => 'gas'])->id,
        'initial_reading' => 0,
    ]);
    $photo = AssistantMeterPhoto::factory()->for($user)->create(['help_conversation_id' => $conversation->id]);
    $pending = new PendingApproval('sdk-approval', 'record_meter_reading', [
        'photo_id' => $photo->id, 'meter_id' => $meter->id,
        'reading_value' => 12, 'reading_date' => '2026-06-15',
    ]);
    $original = app(CreateMeterReadingApproval::class)->handle($user, $conversation, $pending, ['id' => 'sdk-approval', 'tool' => 'record_meter_reading']);
    $old = HelpApproval::query()->findOrFail($original['id']);

    $new = app(EditMeterReadingApproval::class)->handle($user, $conversation, $old, 0, ['reading_value' => 13]);

    expect($new['id'])->not->toBe($old->id)
        ->and($new['version'])->toBe(1)
        ->and($new['reading_value'])->toBe(13.0)
        ->and($old->refresh()->status)->toBe(HelpApprovalStatus::Cancelled);
});
