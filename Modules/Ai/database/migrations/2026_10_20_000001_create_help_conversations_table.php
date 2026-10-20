<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_conversations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('active_workflow')->nullable();
            $table->string('active_agent')->nullable();
            $table->jsonb('draft')->nullable();
            $table->unsignedInteger('draft_version')->default(0);
            $table->string('next_field')->nullable();
            $table->jsonb('handoff_summary')->nullable();
            $table->jsonb('agent_conversation_ids')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
            $table->index(['user_id', 'active_workflow']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_conversations');
    }
};
