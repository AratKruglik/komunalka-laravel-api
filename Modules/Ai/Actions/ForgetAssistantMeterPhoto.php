<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ai\Models\AssistantMeterPhoto;

class ForgetAssistantMeterPhoto
{
    use AsAction;

    public function handle(AssistantMeterPhoto $photo): void
    {
        $this->purgeStoredFile($photo);
        $photo->delete();
    }

    public function purgeStoredFile(AssistantMeterPhoto $photo): void
    {
        Storage::disk($photo->disk)->delete($photo->path);

        if ($photo->conversation_id !== null) {
            $this->removeFromConversation($photo);
        }
    }

    private function removeFromConversation(AssistantMeterPhoto $photo): void
    {
        $table = config('ai.conversations.tables.messages', 'agent_conversation_messages');
        $connection = DB::connection(config('ai.conversations.connection'));

        $messages = $connection->table($table)
            ->where('conversation_id', $photo->conversation_id)
            ->where('attachments', 'like', '%'.basename($photo->path).'%')
            ->get(['id', 'attachments']);

        foreach ($messages as $message) {
            $attachments = json_decode((string) $message->attachments, true);
            if (! is_array($attachments)) {
                continue;
            }

            $remaining = array_values(array_filter(
                $attachments,
                fn (mixed $attachment): bool => ! is_array($attachment) || ($attachment['path'] ?? null) !== $photo->path,
            ));

            $connection->table($table)->where('id', $message->id)->update([
                'attachments' => json_encode($remaining, JSON_THROW_ON_ERROR),
            ]);
        }
    }
}
