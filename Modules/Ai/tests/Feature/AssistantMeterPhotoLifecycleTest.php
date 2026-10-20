<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Ai\Actions\ForgetAssistantMeterPhoto;
use Modules\Ai\Enums\HelpTurn;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Ai\Models\HelpConversation;

mutates(ForgetAssistantMeterPhoto::class);

describe('AssistantMeterPhoto', function (): void {
    beforeEach(function (): void {
        $this->travelTo('2026-06-15 12:00:00');
        Storage::fake(AssistantMeterPhoto::DISK);
    });

    it('treats only unconsumed unexpired photos as active', function (): void {
        $active = AssistantMeterPhoto::factory()->create();
        AssistantMeterPhoto::factory()->consumed()->create();
        AssistantMeterPhoto::factory()->expired()->create();

        expect(AssistantMeterPhoto::query()->active()->pluck('id')->all())->toBe([$active->getKey()]);
    });

    it('links a photo to a public conversation without changing its SDK conversation id', function (): void {
        $conversation = HelpConversation::factory()->create();
        $photo = AssistantMeterPhoto::factory()->create([
            'user_id' => $conversation->user_id,
            'help_conversation_id' => $conversation->id,
            'conversation_id' => '11111111-1111-4111-8111-111111111111',
        ]);

        expect($photo->helpConversation->is($conversation))->toBeTrue()
            ->and($photo->conversation_id)->not->toBe($conversation->id);
    });

    it('classifies exif status', function (): void {
        expect(AssistantMeterPhoto::factory()->make(['taken_at' => null])->exifStatus())->toBe('missing')
            ->and(AssistantMeterPhoto::factory()->make(['taken_at' => now()->addDay()])->exifStatus())->toBe('future')
            ->and(AssistantMeterPhoto::factory()->withExif()->make()->exifStatus())->toBe('exif');
    });

    it('exposes the exif date only for trusted timestamps', function (): void {
        expect(AssistantMeterPhoto::factory()->make(['taken_at' => '2026-06-14 09:00:00'])->exifDate())->toBe('2026-06-14')
            ->and(AssistantMeterPhoto::factory()->make(['taken_at' => null])->exifDate())->toBeNull()
            ->and(AssistantMeterPhoto::factory()->make(['taken_at' => now()->addDay()])->exifDate())->toBeNull();
    });

    it('prunes expired photos together with their files', function (): void {
        Storage::disk(AssistantMeterPhoto::DISK)->put('assistant-photos/old.jpg', 'x');
        Storage::disk(AssistantMeterPhoto::DISK)->put('assistant-photos/new.jpg', 'x');
        AssistantMeterPhoto::factory()->expired()->create(['path' => 'assistant-photos/old.jpg']);
        $fresh = AssistantMeterPhoto::factory()->create(['path' => 'assistant-photos/new.jpg']);

        $this->artisan('model:prune', ['--model' => [AssistantMeterPhoto::class]])->assertSuccessful();

        expect(AssistantMeterPhoto::query()->pluck('id')->all())->toBe([$fresh->getKey()])
            ->and(Storage::disk(AssistantMeterPhoto::DISK)->exists('assistant-photos/old.jpg'))->toBeFalse()
            ->and(Storage::disk(AssistantMeterPhoto::DISK)->exists('assistant-photos/new.jpg'))->toBeTrue();
    });

    it('cascades photo removal when the user is deleted', function (): void {
        $photo = AssistantMeterPhoto::factory()->create();

        $photo->user->delete();

        expect(AssistantMeterPhoto::query()->count())->toBe(0);
    });
});

describe('ForgetAssistantMeterPhoto', function (): void {
    beforeEach(function (): void {
        Storage::fake(AssistantMeterPhoto::DISK);
        Storage::disk(AssistantMeterPhoto::DISK)->put('assistant-photos/a.jpg', 'x');
    });

    it('deletes the file and the record', function (): void {
        $photo = AssistantMeterPhoto::factory()->create(['path' => 'assistant-photos/a.jpg']);

        app(ForgetAssistantMeterPhoto::class)->handle($photo);

        expect(Storage::disk(AssistantMeterPhoto::DISK)->exists('assistant-photos/a.jpg'))->toBeFalse()
            ->and(AssistantMeterPhoto::query()->count())->toBe(0);
    });

    it('removes the attachment reference from the conversation messages', function (): void {
        $table = config('ai.conversations.tables.messages', 'agent_conversation_messages');
        $connection = DB::connection(config('ai.conversations.connection'));
        $columns = $connection->getSchemaBuilder()->getColumnListing($table);
        if (! in_array('attachments', $columns, true)) {
            $this->markTestSkipped('attachments column is unavailable');
        }

        $photo = AssistantMeterPhoto::factory()->create(['path' => 'assistant-photos/a.jpg', 'conversation_id' => '11111111-1111-4111-8111-111111111111']);
        $row = ['id' => '22222222-2222-4222-8222-222222222222', 'conversation_id' => $photo->conversation_id, 'attachments' => json_encode([
            ['path' => 'assistant-photos/a.jpg'],
            ['path' => 'assistant-photos/other.jpg'],
        ])];
        $connection->table($table)->insert([
            ...$row,
            'agent' => 'x',
            'role' => 'user',
            'content' => 'x',
            'steps' => '[]',
            'usage' => '[]',
            'meta' => '[]',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(ForgetAssistantMeterPhoto::class)->handle($photo);

        $stored = json_decode((string) $connection->table($table)->where('id', $row['id'])->value('attachments'), true);
        expect($stored)->toBe([['path' => 'assistant-photos/other.jpg']]);
    });
});

describe('HelpTurn', function (): void {
    it('enables meter tools for every turn except plain questions', function (): void {
        expect(HelpTurn::Question->usesMeterTools())->toBeFalse()
            ->and(HelpTurn::MeterPhoto->usesMeterTools())->toBeTrue()
            ->and(HelpTurn::MeterFollowUp->usesMeterTools())->toBeTrue()
            ->and(HelpTurn::ApprovalResume->usesMeterTools())->toBeTrue();
    });
});
