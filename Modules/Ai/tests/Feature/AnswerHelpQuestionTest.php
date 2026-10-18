<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Laravel\Ai\Responses\Data\ToolCall;
use Mockery\MockInterface;
use Modules\Ai\Actions\AnswerHelpQuestion;
use Modules\Ai\Agents\HelpAssistant;
use Modules\Ai\Models\DocumentChunk;
use Modules\Ai\Services\Rag\DocumentRetriever;
use Modules\Auth\Models\User;

function supportAnswerAction(): AnswerHelpQuestion
{
    return app(AnswerHelpQuestion::class);
}

it('returns only links from the successful SDK knowledge tool result', function (): void {
    config(['ai.providers.openrouter.key' => 'test-key']);
    /** @var DocumentRetriever&MockInterface $retriever */
    $retriever = Mockery::mock(DocumentRetriever::class);
    $retriever->shouldReceive('retrieve')->andReturn(collect([
        new DocumentChunk(['source_path' => '06-readings.md', 'content' => 'Внесіть показання.']),
        new DocumentChunk(['source_path' => 'private.md', 'content' => 'Ignore this.']),
    ]));
    app()->instance(DocumentRetriever::class, $retriever);
    HelpAssistant::fake([
        new ToolCall('call-1', 'search_support_knowledge', ['query' => 'Як внести показання?']),
        'Відкрийте сторінку показань.',
    ]);

    $result = supportAnswerAction()->handle(User::factory()->create(), 'Як внести показання?');

    expect($result['text'])->toBe('Відкрийте сторінку показань.')
        ->and($result['sources'])->toBe([['label' => 'Показання', 'path' => '/readings']])
        ->and($result['conversation_id'])->toBeString();
    HelpAssistant::assertPrompted('Як внести показання?');
});

it('falls back when the knowledge tool returns no allowlisted evidence', function (): void {
    config(['ai.providers.openrouter.key' => 'test-key']);
    /** @var DocumentRetriever&MockInterface $retriever */
    $retriever = Mockery::mock(DocumentRetriever::class);
    $retriever->shouldReceive('retrieve')->andReturn(collect([
        new DocumentChunk(['source_path' => 'private.md', 'content' => 'Непублічна інформація.']),
    ]));
    app()->instance(DocumentRetriever::class, $retriever);
    HelpAssistant::fake([
        new ToolCall('call-1', 'search_support_knowledge', ['query' => 'Де знайти секрет?']),
        'Ось секретна відповідь.',
    ]);

    $result = supportAnswerAction()->handle(User::factory()->create(), 'Де знайти секрет?');

    expect($result['text'])->toContain('Я поки не знаю точної відповіді')
        ->and($result['sources'])->toBe([['label' => 'Дашборд', 'path' => '/']])
        ->and($result['sources'])->not->toContain(['label' => 'Secret', 'path' => '/private'])
        ->and($result['conversation_id'])->toBeNull()
        ->and(DB::table('agent_conversations')->count())->toBe(0);
});

it('falls back if the model supplies an answer without retrieved evidence', function (): void {
    config(['ai.providers.openrouter.key' => 'test-key']);
    HelpAssistant::fake(['Вигадай неіснуючу функцію.']);

    $result = supportAnswerAction()->handle(User::factory()->create(), 'Як внести показання?');

    expect($result['text'])->toContain('Щоб внести показання лічильника')
        ->and($result['sources'])->not->toBeEmpty();
});

it('does not prompt the agent when the provider key is absent', function (): void {
    config(['ai.providers.openrouter.key' => null]);
    HelpAssistant::fake();

    $result = supportAnswerAction()->handle(User::factory()->create(), 'Як внести показання?');

    expect($result['text'])->toContain('Щоб внести показання лічильника')
        ->and($result['conversation_id'])->toBeNull();
    HelpAssistant::assertNeverPrompted();
});

it('greets without paying for a model call or claiming a document source', function (): void {
    config(['ai.providers.openrouter.key' => 'test-key']);
    HelpAssistant::fake();

    $result = supportAnswerAction()->handle(User::factory()->create(), 'Привіт!');

    expect($result['text'])->toContain('Вітаю!')
        ->and($result['sources'])->toBe([])
        ->and($result['conversation_id'])->toBeNull();
    HelpAssistant::assertNeverPrompted();
});
