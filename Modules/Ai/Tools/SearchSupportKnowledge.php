<?php

declare(strict_types=1);

namespace Modules\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JsonException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\Ai\Models\DocumentChunk;
use Modules\Ai\Services\Rag\DocumentRetriever;
use Stringable;

class SearchSupportKnowledge implements Tool
{
    public function __construct(private readonly DocumentRetriever $retriever) {}

    public function name(): string
    {
        return 'search_support_knowledge';
    }

    public function description(): Stringable|string
    {
        return 'Знайти перевірені фрагменти довідки Комуналки для відповіді на запитання користувача. Передай пошукове запитання українською.';
    }

    public function schema(JsonSchema $schema): array
    {
        return ['query' => $schema->string()->required()];
    }

    /**
     * @throws JsonException
     */
    public function handle(Request $request): Stringable|string
    {
        $arguments = $request->validate(['query' => ['required', 'string', 'max:1000']]);
        $allowedSources = config('ai-support.sources', []);
        $chunks = $this->retriever->retrieve($arguments['query'])
            ->filter(fn (DocumentChunk $chunk) => array_key_exists($chunk->source_path, $allowedSources))
            ->values();

        $results = $chunks->map(fn (DocumentChunk $chunk) => [
            'chunk_id' => $chunk->getKey(),
            'source_path' => $chunk->source_path,
            'heading' => $chunk->heading,
            'content' => $chunk->content,
        ])->all();

        return json_encode(['results' => $results], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
