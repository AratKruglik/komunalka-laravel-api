<?php

declare(strict_types=1);

namespace Modules\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\Ai\Actions\UpdatePropertySetupDraft;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;
use Stringable;

class ProposePropertySetup implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly HelpConversation $conversation,
        private readonly UpdatePropertySetupDraft $update,
    ) {}

    public function name(): string
    {
        return 'propose_property_setup';
    }

    public function description(): Stringable|string
    {
        return 'Додати підтверджені користувачем поля адреси, провайдера або лічильника до чернетки. Не створює доменні записи. Передай JSON object із address_id або секціями address, provider, meter.';
    }

    public function schema(JsonSchema $schema): array
    {
        return ['payload' => $schema->string()->required()];
    }

    public function handle(Request $request): Stringable|string
    {
        $arguments = $request->validate(['payload' => ['required', 'string', 'max:10000']]);
        $payload = json_decode($arguments['payload'], true);
        if (! is_array($payload) || array_is_list($payload)) {
            throw ValidationException::withMessages(['payload' => 'Потрібен JSON object з полями чернетки.']);
        }

        $conversation = $this->update->handle($this->user, $this->conversation, $payload);

        return json_encode(['status' => 'draft_updated', 'version' => $conversation->draft_version], JSON_THROW_ON_ERROR);
    }
}
