<?php

declare(strict_types=1);

use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;
use Mockery\MockInterface;
use Modules\Ai\Models\DocumentChunk;
use Modules\Ai\Services\Rag\DocumentIndexer;
use Modules\Ai\Services\Rag\MarkdownChunker;

it('keeps all sections when a new section is inserted between indexed sections', function (): void {
    Embeddings::fake();
    /** @var MarkdownChunker&MockInterface $chunker */
    $chunker = Mockery::mock(MarkdownChunker::class);
    $chunker->shouldReceive('chunk')->andReturn(
        collect([
            ['heading' => 'First', 'content' => 'First section.'],
            ['heading' => 'Last', 'content' => 'Last section.'],
        ]),
        collect([
            ['heading' => 'First', 'content' => 'First section.'],
            ['heading' => 'Middle', 'content' => 'Middle section.'],
            ['heading' => 'Last', 'content' => 'Last section.'],
        ]),
    );
    $indexer = new DocumentIndexer($chunker);

    $indexer->indexMarkdown('test.md', 'first version');
    $indexer->indexMarkdown('test.md', 'second version');

    $content = DocumentChunk::query()->where('source_path', 'test.md')->orderBy('chunk_index')->pluck('content')->all();

    expect($content)->toBe(['First section.', 'Middle section.', 'Last section.']);
});

it('regenerates vectors when the configured embedding model changes', function (): void {
    $models = [];
    Embeddings::fake(function (EmbeddingsPrompt $prompt) use (&$models): array {
        $models[] = $prompt->model;

        return [Embeddings::fakeEmbedding($prompt->dimensions)];
    });
    /** @var MarkdownChunker&MockInterface $chunker */
    $chunker = Mockery::mock(MarkdownChunker::class);
    $chunker->shouldReceive('chunk')->andReturn(collect([
        ['heading' => 'Показання', 'content' => 'Введіть нове значення.'],
    ]));
    $indexer = new DocumentIndexer($chunker);

    $indexer->indexMarkdown('06-readings.md', 'same content');
    config(['ai.providers.openrouter.models.embeddings.default' => 'openai/text-embedding-3-large']);
    $indexer->indexMarkdown('06-readings.md', 'same content');

    expect($models)->toBe(['openai/text-embedding-3-small', 'openai/text-embedding-3-large'])
        ->and(DocumentChunk::query()->where('source_path', '06-readings.md')->firstOrFail()->metadata['embedding_model'])
        ->toBe('openai/text-embedding-3-large');
});
