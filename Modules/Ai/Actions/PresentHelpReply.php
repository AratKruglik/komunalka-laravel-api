<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Illuminate\Validation\ValidationException;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\ToolResult;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ai\DTO\MeterReadingProposal;
use Modules\Auth\Models\User;
use Modules\Billing\Models\Tariff;

class PresentHelpReply
{
    use AsAction;

    public const string RECORD_TOOL = 'record_meter_reading';

    public const string DEFAULT_APPROVAL_TEXT = 'Перевірте дані та підтвердіть запис показання.';

    public function __construct(
        private readonly ValidateMeterReadingProposal $validate,
        private readonly DescribeUserMeters $meters,
    ) {}

    /**
     * @param  list<array{label: string, path: string}>  $sources
     * @return array<string, mixed>
     */
    public function plain(string $text, array $sources, ?string $conversationId): array
    {
        return [
            'text' => $text,
            'sources' => $sources,
            'conversation_id' => $conversationId,
            'pending_approval' => null,
            'reading_result' => null,
        ];
    }

    /** @return array<string, mixed> */
    public function handle(User $user, AgentResponse $response): array
    {
        $reply = $this->plain(trim($response->text), $this->sources($response), $response->conversationId);
        $reply['reading_result'] = $this->readingResult($response);

        $pending = $response->pendingApprovals->first(fn (PendingApproval $approval): bool => $approval->tool === self::RECORD_TOOL);
        if ($pending !== null) {
            $reply = $this->withApproval($reply, $user, $pending);
        }

        if ($reply['text'] === '' && $reply['reading_result'] !== null) {
            $reply['text'] = $reply['reading_result']['message'];
        }

        return $reply;
    }

    /** @return list<array{label: string, path: string}> */
    public function sources(AgentResponse $response): array
    {
        $allowedSources = config('ai-support.sources', []);
        $sources = [];

        /** @var ToolResult $result */
        foreach ($response->toolResults->all() as $result) {
            if ($result->name !== 'search_support_knowledge' || ! $result->successful()) {
                continue;
            }

            $payload = json_decode($result->text(), true);
            foreach (($payload['results'] ?? []) as $passage) {
                $sourcePath = $passage['source_path'] ?? null;
                if (is_string($sourcePath) && isset($allowedSources[$sourcePath])) {
                    $source = $allowedSources[$sourcePath];
                    $sources[$source['path']] = $source;
                }
            }
        }

        return array_values($sources);
    }

    /**
     * @param  array<string, mixed>  $reply
     * @return array<string, mixed>
     */
    private function withApproval(array $reply, User $user, PendingApproval $pending): array
    {
        try {
            $reply['pending_approval'] = $this->approvalCard($pending->id, $this->validate->handle($user, $pending->arguments));
            $reply['text'] = $reply['text'] === '' ? self::DEFAULT_APPROVAL_TEXT : $reply['text'];
        } catch (ValidationException $exception) {
            $message = (string) collect($exception->errors())->flatten()->first();
            $reply['text'] = $message;
            $reply['reading_result'] = ['status' => 'refused', 'message' => $message, 'reading' => null];
        }

        return $reply;
    }

    /** @return array<string, mixed> */
    private function approvalCard(string $approvalId, MeterReadingProposal $proposal): array
    {
        $meter = $proposal->meter;

        return [
            'id' => $approvalId,
            'tool' => self::RECORD_TOOL,
            'meter' => [
                'id' => $meter->getKey(),
                'name' => $meter->name,
                'location' => $meter->location,
                'serial_number' => $meter->serial_number,
                'utility_type' => [
                    'slug' => $meter->utilityType->slug,
                    'name' => $meter->utilityType->display_name,
                    'unit' => $meter->utilityType->unit,
                ],
                'address' => $this->meters->addressLabel($meter->address),
            ],
            'reading_value' => $proposal->readingValue,
            'reading_date' => $proposal->readingDate,
            'date_source' => $proposal->dateSource,
            'exif_date' => $proposal->exifDate,
            'tariff' => $proposal->tariff === null ? null : $this->tariffOption($proposal->tariff),
            'tariff_options' => array_map($this->tariffOption(...), $proposal->tariffOptions),
            'previous' => ['value' => $proposal->previousValue, 'date' => $proposal->previousDate],
            'consumption' => $proposal->consumption(),
            'expires_at' => $proposal->photo->expires_at->toIso8601String(),
        ];
    }

    /** @return array{id: int, name: string} */
    private function tariffOption(Tariff $tariff): array
    {
        return ['id' => $tariff->getKey(), 'name' => $tariff->name];
    }

    /** @return array{status: string, message: string, reading: ?array<string, mixed>}|null */
    private function readingResult(AgentResponse $response): ?array
    {
        $result = $response->toolResults->last(fn (ToolResult $result): bool => $result->name === self::RECORD_TOOL);
        if ($result === null) {
            return null;
        }

        if ($result->denied) {
            return ['status' => 'rejected', 'message' => 'Запис показання скасовано. Нічого не збережено.', 'reading' => null];
        }

        $payload = $result->failed ? null : json_decode($result->text(), true);
        if (! is_array($payload) || ! in_array($payload['status'] ?? null, ['saved', 'failed'], true)) {
            return ['status' => 'failed', 'message' => 'Не вдалося зберегти показання. Спробуйте ще раз або внесіть їх вручну.', 'reading' => null];
        }

        return [
            'status' => $payload['status'],
            'message' => (string) ($payload['message'] ?? ''),
            'reading' => $payload['reading'] ?? null,
        ];
    }
}
