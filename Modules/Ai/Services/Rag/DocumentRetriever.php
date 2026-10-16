<?php

declare(strict_types=1);

namespace Modules\Ai\Services\Rag;

use Illuminate\Support\Collection;
use Laravel\Ai\Embeddings;
use Modules\Ai\Models\DocumentChunk;

class DocumentRetriever
{
    /** @return Collection<int, DocumentChunk> */
    public function retrieve(string $question): Collection
    {
        $sources = array_keys(config('ai-support.sources', []));
        $embeddingModel = (string) config('ai.providers.openrouter.models.embeddings.default');
        $query = DocumentChunk::query()->whereIn('source_path', $sources)
            ->where(function ($query) use ($embeddingModel): void {
                $query->where('metadata->embedding_model', $embeddingModel);
                if ($embeddingModel === 'openai/text-embedding-3-small') {
                    $query->orWhereNull('metadata->embedding_model');
                }
            });

        if (! $query->exists()) {
            return collect();
        }

        $embedding = Embeddings::for([$question])->cache()
            ->generate('openrouter', $embeddingModel)
            ->first();

        return $query
            ->whereVectorSimilarTo('embedding', $embedding, (float) config('ai-support.min_similarity', 0.45))
            ->limit(min(5, max(1, (int) config('ai-support.max_chunks', 5))))
            ->get(['id', 'source_path', 'heading', 'content']);
    }
}
