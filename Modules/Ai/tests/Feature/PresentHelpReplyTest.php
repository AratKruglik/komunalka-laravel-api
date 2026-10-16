<?php

declare(strict_types=1);

use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\ToolResult;
use Modules\Address\Models\Address;
use Modules\Ai\Actions\PresentHelpReply;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\UtilityType;

mutates(PresentHelpReply::class);

describe('PresentHelpReply', function (): void {
    beforeEach(function (): void {
        $this->travelTo('2026-06-15 12:00:00');
        $this->user = User::factory()->create();
        $address = Address::factory()->create(['city' => 'Київ', 'street' => 'Хрещатик', 'building_number' => '1', 'apartment_number' => '5']);
        $this->user->addresses()->attach($address->getKey(), ['is_primary' => true]);
        $this->meter = Meter::factory()->create([
            'address_id' => $address->getKey(),
            'utility_type_id' => UtilityType::factory()->create(['slug' => 'gas', 'unit' => 'm³'])->getKey(),
            'initial_reading' => 100,
        ]);
        $this->photo = AssistantMeterPhoto::factory()->for($this->user)->create(['taken_at' => '2026-06-14 10:00:00']);
        $this->present = app(PresentHelpReply::class);
        $this->pending = fn (array $overrides = []): PendingApproval => new PendingApproval('appr-1', 'record_meter_reading', [
            'photo_id' => $this->photo->getKey(),
            'meter_id' => $this->meter->getKey(),
            'reading_value' => 130.5,
            'reading_date' => '2026-06-14',
            ...$overrides,
        ]);
    });

    it('builds a plain reply with contract keys', function (): void {
        expect($this->present->plain('Привіт', [], 'c-1'))->toBe([
            'text' => 'Привіт',
            'sources' => [],
            'conversation_id' => 'c-1',
            'pending_approval' => null,
            'reading_result' => null,
        ]);
    });

    it('returns an approval card for a valid pending record_meter_reading call', function (): void {
        $response = AgentResponse::fakeWithPendingApprovals([($this->pending)()]);

        $reply = $this->present->handle($this->user, $response);

        expect($reply['text'])->toBe(PresentHelpReply::DEFAULT_APPROVAL_TEXT)
            ->and($reply['reading_result'])->toBeNull()
            ->and($reply['pending_approval'])->toMatchArray([
                'id' => 'appr-1',
                'tool' => 'record_meter_reading',
                'reading_value' => 130.5,
                'reading_date' => '2026-06-14',
                'date_source' => 'exif',
                'exif_date' => '2026-06-14',
                'consumption' => 30.5,
                'previous' => ['value' => 100.0, 'date' => null],
                'tariff' => null,
                'tariff_options' => [],
            ])
            ->and($reply['pending_approval']['meter'])->toMatchArray([
                'id' => $this->meter->getKey(),
                'address' => 'Київ, Хрещатик 1, кв. 5',
                'utility_type' => ['slug' => 'gas', 'name' => $this->meter->utilityType->display_name, 'unit' => 'm³'],
            ])
            ->and($reply['pending_approval'])->toHaveKey('expires_at');
    });

    it('turns an invalid proposal into a refused result instead of a card', function (): void {
        $response = AgentResponse::fakeWithPendingApprovals([($this->pending)(['reading_value' => 5])]);

        $reply = $this->present->handle($this->user, $response);

        expect($reply['pending_approval'])->toBeNull()
            ->and($reply['reading_result']['status'])->toBe('refused')
            ->and($reply['text'])->toBe('Значення показника не може бути менше попереднього.');
    });

    it('does not build a card for a proposal using another user\'s photo', function (): void {
        $foreign = AssistantMeterPhoto::factory()->create();
        $response = AgentResponse::fakeWithPendingApprovals([($this->pending)(['photo_id' => $foreign->getKey()])]);

        $reply = $this->present->handle($this->user, $response);

        expect($reply['pending_approval'])->toBeNull()
            ->and($reply['reading_result']['status'])->toBe('refused');
    });

    it('ignores pending approvals of other tools', function (): void {
        $response = AgentResponse::fakeWithPendingApprovals([new PendingApproval('x', 'other_tool', [])]);

        expect($this->present->handle($this->user, $response)['pending_approval'])->toBeNull();
    });

    it('maps tool results to a reading result', function (array $result, array $flags, string $status, string $message): void {
        $response = AgentResponse::fakeWithPendingApprovals([]);
        $response->toolResults = collect([new ToolResult('t1', 'record_meter_reading', [], $result, ...$flags)]);

        $reading = $this->present->handle($this->user, $response)['reading_result'];

        expect($reading['status'])->toBe($status)
            ->and($reading['message'])->toBe($message);
    })->with([
        'saved' => [['status' => 'saved', 'message' => 'Показання збережено.', 'reading' => ['id' => 1]], [], 'saved', 'Показання збережено.'],
        'failed payload' => [['status' => 'failed', 'message' => 'Фото вже використано'], [], 'failed', 'Фото вже використано'],
        'denied' => [['status' => 'saved'], ['denied' => true], 'rejected', 'Запис показання скасовано. Нічого не збережено.'],
        'tool error' => [['status' => 'saved'], ['failed' => true], 'failed', 'Не вдалося зберегти показання. Спробуйте ще раз або внесіть їх вручну.'],
        'unknown payload' => [['status' => 'weird'], [], 'failed', 'Не вдалося зберегти показання. Спробуйте ще раз або внесіть їх вручну.'],
    ]);

    it('uses the reading result message when the model text is empty', function (): void {
        $response = AgentResponse::fakeWithPendingApprovals([]);
        $response->toolResults = collect([new ToolResult('t1', 'record_meter_reading', [], ['status' => 'saved', 'message' => 'Показання збережено.'])]);

        expect($this->present->handle($this->user, $response)['text'])->toBe('Показання збережено.');
    });

    it('collects only allowlisted knowledge sources', function (): void {
        $response = AgentResponse::fakeWithPendingApprovals([]);
        $response->toolResults = collect([
            new ToolResult('t1', 'search_support_knowledge', [], ['results' => [
                ['source_path' => '06-readings.md'],
                ['source_path' => 'private.md'],
            ]]),
            new ToolResult('t2', 'search_support_knowledge', [], ['results' => [['source_path' => '06-readings.md']]], failed: true),
        ]);

        expect($this->present->sources($response))->toBe([['label' => 'Показання', 'path' => '/readings']]);
    });
});
