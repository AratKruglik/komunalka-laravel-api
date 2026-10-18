<?php

declare(strict_types=1);

namespace Modules\Shared\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ai\Actions\AnswerHelpQuestion;
use Modules\Auth\Models\User;
use Modules\Shared\Http\Requests\AskAssistantRequest;

class AskAssistant
{
    use AsAction;

    public function __construct(
        private readonly AnswerHelpQuestion $answer,
    ) {}

    /** @return array<string, mixed> */
    public function handle(User $user, string $question, ?UploadedFile $photo = null, ?string $conversationId = null): array
    {
        return $this->answer->handle($user, $question, $photo, $conversationId);
    }

    public function asController(AskAssistantRequest $request): JsonResponse
    {
        return response()->json(
            $this->handle($request->user(), $request->string('question')->toString(), $request->file('photo'), $request->validated('conversation_id')),
        );
    }
}
