<?php

declare(strict_types=1);

namespace Modules\Shared\Actions;

use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Shared\Http\Requests\AskAssistantRequest;
use Modules\Shared\Support\AssistantResponder;

class AskAssistant
{
    use AsAction;

    public function __construct(
        private readonly AssistantResponder $responder,
    ) {}

    /** @return array{text: string, sources: list<array{label: string, path: string}>} */
    public function handle(string $question): array
    {
        return $this->responder->respond($question);
    }

    public function asController(AskAssistantRequest $request): JsonResponse
    {
        return response()->json(
            $this->handle($request->string('question')->toString()),
        );
    }
}
