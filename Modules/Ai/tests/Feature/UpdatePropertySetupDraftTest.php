<?php

declare(strict_types=1);

use Modules\Ai\Actions\UpdatePropertySetupDraft;
use Modules\Ai\Enums\HelpApprovalStatus;
use Modules\Ai\Models\HelpApproval;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('merges allowlisted fields, versions the draft, and cancels an old proposal', function (): void {
    $conversation = HelpConversation::factory()->create(['draft' => ['address' => ['city' => 'Київ']], 'draft_version' => 1]);
    $approval = HelpApproval::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $conversation->user_id, 'draft_version' => 1]);

    $updated = app(UpdatePropertySetupDraft::class)->handle($conversation->user, $conversation, [
        'address' => ['street' => 'Хрещатик', 'user_id' => 999],
        'user_id' => 999,
    ]);

    expect($updated->draft)->toBe(['address' => ['city' => 'Київ', 'street' => 'Хрещатик']])
        ->and($updated->draft_version)->toBe(2)
        ->and($updated->expires_at->isFuture())->toBeTrue()
        ->and($approval->refresh()->status)->toBe(HelpApprovalStatus::Cancelled);
});

it('does not allow a foreign user to mutate a draft', function (): void {
    $conversation = HelpConversation::factory()->create();

    expect(fn () => app(UpdatePropertySetupDraft::class)->handle(User::factory()->create(), $conversation, ['address' => ['city' => 'Київ']]))
        ->toThrow(HttpException::class);
});

it('rejects an edit when its approval became stale before the draft lock', function (): void {
    $conversation = HelpConversation::factory()->create(['draft' => ['address' => ['city' => 'Київ']], 'draft_version' => 1]);
    $approval = HelpApproval::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $conversation->user_id, 'draft_version' => 1]);
    $action = app(UpdatePropertySetupDraft::class);

    $action->handle($conversation->user, $conversation, ['address' => ['street' => 'Перша']]);

    expect(fn () => $action->handle($conversation->user, $conversation, ['address' => ['street' => 'Застаріла']], $approval, 1))
        ->toThrow(HttpException::class);
    expect($conversation->refresh()->draft['address']['street'])->toBe('Перша');
});
