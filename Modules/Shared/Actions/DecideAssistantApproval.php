<?php

declare(strict_types=1);

namespace Modules\Shared\Actions;

use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ai\Actions\ResolveHelpApproval;
use Modules\Auth\Models\User;
use Modules\Shared\Http\Requests\DecideAssistantApprovalRequest;

class DecideAssistantApproval
{
    use AsAction;

    public function __construct(private readonly ResolveHelpApproval $resolve) {}

    /**
     * @param  array<string, mixed>  $edits
     * @return array<string, mixed>
     */
    public function handle(User $user, string $conversationId, string $approvalId, string $action, array $edits = []): array
    {
        return $this->resolve->handle($user, $conversationId, $approvalId, $action, $edits);
    }

    public function asController(DecideAssistantApprovalRequest $request): JsonResponse
    {
        return response()->json($this->handle(
            $request->user(),
            $request->validated('conversation_id'),
            $request->validated('approval_id'),
            $request->validated('action'),
            $request->validated('arguments', []),
        ));
    }
}
