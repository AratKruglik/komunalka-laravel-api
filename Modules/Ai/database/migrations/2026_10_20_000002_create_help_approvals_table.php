<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_approvals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('help_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('workflow');
            $table->string('status');
            $table->unsignedInteger('draft_version');
            $table->jsonb('payload');
            $table->char('payload_hash', 64);
            $table->uuid('idempotency_key')->unique();
            $table->jsonb('result')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'status', 'expires_at']);
            $table->index(['user_id', 'status', 'expires_at']);
            $table->unique(['conversation_id', 'workflow', 'draft_version', 'payload_hash'], 'help_approval_payload_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_approvals');
    }
};
