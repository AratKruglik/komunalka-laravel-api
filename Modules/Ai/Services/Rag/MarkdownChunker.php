<?php

namespace Modules\Ai\Services\Rag;

use BenBjurstrom\MarkdownObject\Build\MarkdownObjectBuilder;
use BenBjurstrom\MarkdownObject\Tokenizer\TikTokenizer;
use Illuminate\Support\Collection;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Parser\MarkdownParser;

class MarkdownChunker
{
    private TikTokenizer $tokenizer;

    public function __construct(
        private CommonMarkConverter $converter,
        private MarkdownParser $markdownParser,
        private MarkdownObjectBuilder $builder
    ) {
        $this->tokenizer = TikTokenizer::forModel('text-embedding-3-small');
    }

    /**
     * @throws CommonMarkException
     */
    public function chunk(string $fileName, string $markdownContent): Collection
    {
        $document = $this->markdownParser->parse($markdownContent);

        $chunks = $this->builder->build(
            document: $document,
            filename: $fileName,
            source: $markdownContent,
            tokenizer: $this->tokenizer
        )->toMarkdownChunks(
            tok: $this->tokenizer
        );

        return collect($chunks)->map(fn ($chunk) => [
            'heading' => implode(' > ', $chunk->breadcrumb),
            'content' => trim($chunk->markdown),
        ])->filter(
            fn ($content) => ! empty($content['content'])
        )->values();
    }
}
