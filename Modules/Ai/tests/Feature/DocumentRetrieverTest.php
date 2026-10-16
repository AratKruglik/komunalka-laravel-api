<?php

declare(strict_types=1);

use Laravel\Ai\Embeddings;
use Modules\Ai\Models\DocumentChunk;
use Modules\Ai\Services\Rag\DocumentRetriever;

it('searches only vectors created by the current embedding model', function (): void {
    config(['ai.providers.openrouter.models.embeddings.default' => 'openai/text-embedding-3-large']);
    $vector = Embeddings::fakeEmbedding(1536);
    Embeddings::fake([[$vector]]);

    foreach (['openai/text-embedding-3-small', 'openai/text-embedding-3-large'] as $index => $model) {
        DocumentChunk::query()->create([
            'source_path' => '06-readings.md',
            'source_type' => 'markdown',
            'chunk_index' => $index,
            'heading' => $model,
            'content' => 'Внесення показань',
            'content_hash' => hash('sha256', $model),
            'metadata' => ['embedding_model' => $model],
            'embedding' => $vector,
        ]);
    }

    $chunks = app(DocumentRetriever::class)->retrieve('Як внести показання?');

    expect($chunks)->toHaveCount(1)
        ->and($chunks->first()->heading)->toBe('openai/text-embedding-3-large');
});
