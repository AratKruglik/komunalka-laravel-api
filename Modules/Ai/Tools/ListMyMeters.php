<?php

declare(strict_types=1);

namespace Modules\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JsonException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\Ai\Actions\DescribeUserMeters;
use Modules\Auth\Models\User;
use Stringable;

class ListMyMeters implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly DescribeUserMeters $meters,
    ) {}

    public function name(): string
    {
        return 'list_my_meters';
    }

    public function description(): Stringable|string
    {
        return 'Показати активні лічильники користувача (вода, електроенергія, газ) з адресою, тарифними зонами та останніми показаннями. Використовуй, щоб обрати лічильник для запису показання.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    /** @throws JsonException */
    public function handle(Request $request): Stringable|string
    {
        return json_encode(['meters' => $this->meters->handle($this->user)], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
