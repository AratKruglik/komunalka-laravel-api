<?php

declare(strict_types=1);

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Tools\Request;
use Mockery\MockInterface;
use Modules\Ai\Agents\HelpAssistant;
use Modules\Ai\Enums\HelpTurn;
use Modules\Ai\Models\DocumentChunk;
use Modules\Ai\Services\Rag\DocumentRetriever;
use Modules\Ai\Tools\SearchSupportKnowledge;
use Modules\Auth\Models\User;

it('registers a first-class SDK agent with the knowledge search tool', function (): void {
    /** @var SearchSupportKnowledge&MockInterface $tool */
    $tool = Mockery::mock(SearchSupportKnowledge::class);
    app()->instance(SearchSupportKnowledge::class, $tool);
    $agent = new HelpAssistant(User::factory()->make(), HelpTurn::Question);

    expect($agent)->toBeInstanceOf(Agent::class)
        ->toBeInstanceOf(HasTools::class)
        ->and(iterator_to_array($agent->tools()))->toBe([$tool]);
});

it('returns only allowlisted passages from the read-only knowledge tool', function (): void {
    /** @var DocumentRetriever&MockInterface $retriever */
    $retriever = Mockery::mock(DocumentRetriever::class);
    $retriever->shouldReceive('retrieve')->andReturn(collect([
        new DocumentChunk(['source_path' => '06-readings.md', 'heading' => 'Показання', 'content' => 'Перейдіть до показань.']),
        new DocumentChunk(['source_path' => 'private.md', 'heading' => 'Secret', 'content' => 'Do not reveal.']),
    ]));

    $tool = new SearchSupportKnowledge($retriever);
    $result = json_decode((string) $tool->handle(new Request(['query' => 'Як внести показання?'])), true, 512, JSON_THROW_ON_ERROR);

    expect($tool->name())->toBe('search_support_knowledge')
        ->and($result['results'])->toHaveCount(1)
        ->and($result['results'][0]['source_path'])->toBe('06-readings.md')
        ->and(json_encode($result))->not->toContain('private.md', 'Do not reveal.');
});
