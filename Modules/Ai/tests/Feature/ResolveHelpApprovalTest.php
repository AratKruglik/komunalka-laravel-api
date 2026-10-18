<?php

declare(strict_types=1);

use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Contracts\ConversationStore;
use Modules\Address\Models\Address;
use Modules\Ai\Actions\ResolveHelpApproval;
use Modules\Ai\Agents\HelpAssistant;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;

mutates(ResolveHelpApproval::class);

describe('ResolveHelpApproval continuation failure', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $address = Address::factory()->create();
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => true]);
        $this->meter = Meter::factory()->create(['address_id' => $address->getKey()]);
        $this->photo = AssistantMeterPhoto::factory()->for($this->user)->create();
        $pending = new PendingApproval('appr-1', 'record_meter_reading', ['photo_id' => $this->photo->getKey(), 'meter_id' => $this->meter->getKey()]);

        $store = Mockery::mock(app(ConversationStore::class))->makePartial();
        $store->shouldReceive('pendingApprovalsFor')->andReturn([$pending]);
        $this->app->instance(ConversationStore::class, $store);
        $this->conversation = $store->storeConversation($this->user->getMorphClass(), $this->user->getKey(), 'Розмова');

        $this->commitReadingBeforeFailure = false;
        HelpAssistant::fake(function (): never {
            if ($this->commitReadingBeforeFailure) {
                MeterReading::factory()->create(['meter_id' => $this->meter->getKey()]);
                $this->photo->update(['consumed_at' => now()]);
            }

            throw new RuntimeException('provider down');
        });
    });

    it('returns a saved result instead of failing when the reading was already committed', function (): void {
        $this->commitReadingBeforeFailure = true;

        $reply = ResolveHelpApproval::run($this->user, $this->conversation, 'appr-1', 'approve');

        expect($reply['reading_result']['status'])->toBe('saved')
            ->and($reply['reading_result']['reading']['id'])->toBe(MeterReading::query()->firstOrFail()->getKey())
            ->and($reply['pending_approval'])->toBeNull()
            ->and(AssistantMeterPhoto::query()->find($this->photo->getKey()))->toBeNull();
    });

    it('still fails when nothing was saved', function (): void {
        ResolveHelpApproval::run($this->user, $this->conversation, 'appr-1', 'approve');
    })->throws(RuntimeException::class);
});
