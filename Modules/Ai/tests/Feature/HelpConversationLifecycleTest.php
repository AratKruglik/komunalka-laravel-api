<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Modules\Ai\Actions\GetOrCreateHelpConversation;
use Modules\Ai\Enums\HelpApprovalStatus;
use Modules\Ai\Models\HelpApproval;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Actions\DeleteUser;
use Modules\Auth\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('creates a public conversation owned by the current user', function (): void {
    $user = User::factory()->create();

    $conversation = app(GetOrCreateHelpConversation::class)->handle($user, null);

    expect($conversation->user_id)->toBe($user->getKey())
        ->and($conversation->id)->toBeUuid()
        ->and($conversation->agent_conversation_ids)->toBe([])
        ->and(app(GetOrCreateHelpConversation::class)->handle($user, $conversation->id)->is($conversation))->toBeTrue();
});

it('does not resolve another users public conversation', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $conversation = HelpConversation::factory()->for($owner)->create();

    app(GetOrCreateHelpConversation::class)->handle($other, $conversation->id);
})->throws(HttpException::class);

it('cascades public conversations and their approvals when the user is deleted', function (): void {
    $user = User::factory()->create();
    $conversation = HelpConversation::factory()->for($user)->create();
    $approval = HelpApproval::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'status' => HelpApprovalStatus::Pending,
    ]);

    DeleteUser::run($user);

    expect(DB::table('help_conversations')->where('id', $conversation->id)->exists())->toBeFalse()
        ->and(DB::table('help_approvals')->where('id', $approval->id)->exists())->toBeFalse();
});
