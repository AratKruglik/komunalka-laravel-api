<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Illuminate\Http\UploadedFile;
use Modules\Ai\Enums\HelpIntent;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Ai\Models\HelpConversation;

class RouteHelpTurn
{
    public function __construct(private readonly ClassifyHelpIntent $classifier) {}

    public function handle(HelpConversation $conversation, string $question, ?UploadedFile $photo = null): HelpIntent
    {
        if ($photo !== null || AssistantMeterPhoto::query()->active()
            ->where('user_id', $conversation->user_id)
            ->where('help_conversation_id', $conversation->id)
            ->exists()) {
            return HelpIntent::MeterReading;
        }

        if ($conversation->active_workflow === HelpWorkflow::PropertySetup && $conversation->draft !== null
            && ($conversation->expires_at === null || $conversation->expires_at->isFuture())) {
            return HelpIntent::PropertySetup;
        }

        if (blank(config('ai.providers.openrouter.key')) || preg_match('/^(привіт|вітаю|добрий день|доброго дня|добрий вечір)[\s!.?]*$/iu', trim($question)) === 1) {
            return HelpIntent::ProductSupport;
        }

        return $this->classifier->handle($question, $conversation->active_workflow, $conversation->next_field)->intent;
    }
}
