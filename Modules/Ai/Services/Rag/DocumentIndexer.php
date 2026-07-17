<?php

namespace Modules\Ai\Services\Rag;

use Illuminate\Support\Arr;
use Laravel\Ai\Embeddings;
use Modules\Ai\Models\DocumentChunk;

class DocumentIndexer
{
    public function __construct(private MarkdownChunker $chunker) {}

    public function indexMarkdown(string $sourcePath, string $markdownContent): void
    {
        $records = $this->chunker->chunk($sourcePath, $markdownContent)
            ->map(fn ($chunk, $i) => [
                'source_path' => $sourcePath,
                'chunk_index' => $i,
                'heading' => Arr::get($chunk, 'heading'),
                'content' => Arr::get($chunk, 'content'),
                'embed_text' => trim(
                    Arr::get($chunk, 'heading') ? Arr::get($chunk, 'heading')."\n\n" : ''
                ).Arr::get($chunk, 'content'),
            ])->map(fn ($record) => [
                ...$record,
                'content_hash' => hash('sha256', Arr::get($record, 'embed_text')),
            ]);

        DocumentChunk::whereSourcePath($sourcePath)
            ->whereNotIn('content_hash', $records->pluck('content_hash'))
            ->delete();

        $indexedDocumentChunks = DocumentChunk::whereSourcePath($sourcePath)->pluck('content_hash')->flip();
        $recordsTodo = $records->reject(
            fn ($record) => $indexedDocumentChunks->has(Arr::get($record, 'content_hash'))
        )->values();

        $recordsTodo->chunk(100)->each(function ($chunk) {
            $vectors = Embeddings::for($chunk->pluck('embed_text')->all())
                ->cache()
                ->generate()
                ->embeddings;

            DocumentChunk::upsert(
                $chunk->values()->map(fn (array $record, int $i) => [
                    'source_path' => Arr::get($record, 'source_path'),
                    'chunk_index' => Arr::get($record, 'chunk_index'),
                    'heading' => Arr::get($record, 'heading'),
                    'content' => Arr::get($record, 'content'),
                    'content_hash' => Arr::get($record, 'content_hash'),
                    'embedding' => json_encode(Arr::get($vectors, $i)),
                ])->all(),
                uniqueBy: ['source_path', 'chunk_index'],
                update: ['source_type', 'heading', 'content', 'content_hash', 'embedding'],
            );
        });
    }
}
