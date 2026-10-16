<?php

namespace Modules\Ai\Services\Rag;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Embeddings;
use Modules\Ai\Models\DocumentChunk;

class DocumentIndexer
{
    public function __construct(private MarkdownChunker $chunker) {}

    public function indexMarkdown(string $sourcePath, string $markdownContent): void
    {
        $embeddingModel = (string) config('ai.providers.openrouter.models.embeddings.default');
        $records = $this->chunker->chunk($sourcePath, $markdownContent)
            ->map(fn ($chunk, $i) => [
                'source_path' => $sourcePath,
                'chunk_index' => $i,
                'heading' => Arr::get($chunk, 'heading'),
                'content' => Arr::get($chunk, 'content'),
                'embed_text' => trim((Arr::get($chunk, 'heading') ? Arr::get($chunk, 'heading')."\n\n" : '').Arr::get($chunk, 'content')),
            ])->map(fn ($record) => [
                ...$record,
                'content_hash' => hash('sha256', Arr::get($record, 'embed_text')),
            ]);

        $known = DocumentChunk::whereSourcePath($sourcePath)->get(['content_hash', 'embedding', 'metadata'])
            ->filter(fn (DocumentChunk $chunk) => ($chunk->metadata['embedding_model'] ?? 'openai/text-embedding-3-small') === $embeddingModel)
            ->mapWithKeys(fn (DocumentChunk $chunk) => [$chunk->content_hash => $chunk->embedding]);
        $recordsTodo = $records->reject(fn ($record) => $known->has($record['content_hash']))->values();

        $recordsTodo->chunk(100)->each(function ($chunk) use ($known, $embeddingModel): void {
            $vectors = Embeddings::for($chunk->pluck('embed_text')->all())
                ->cache()
                ->generate('openrouter', $embeddingModel)
                ->embeddings;
            $chunk->values()->each(fn (array $record, int $i) => $known->put($record['content_hash'], $vectors[$i]));
        });

        DB::transaction(function () use ($sourcePath, $records, $known, $embeddingModel): void {
            DocumentChunk::whereSourcePath($sourcePath)->delete();
            foreach ($records->chunk(100) as $batch) {
                DocumentChunk::insert($batch->map(fn (array $record) => [
                    'source_path' => $record['source_path'],
                    'source_type' => 'markdown',
                    'chunk_index' => $record['chunk_index'],
                    'heading' => $record['heading'],
                    'content' => $record['content'],
                    'content_hash' => $record['content_hash'],
                    'metadata' => json_encode(['embedding_model' => $embeddingModel], JSON_THROW_ON_ERROR),
                    'embedding' => json_encode($known[$record['content_hash']]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all());
            }
        });
    }
}
