<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_meter_photos', function (Blueprint $table): void {
            $table->foreignUuid('help_conversation_id')
                ->nullable()
                ->constrained('help_conversations')
                ->nullOnDelete();
            $table->index(['user_id', 'help_conversation_id', 'consumed_at', 'expires_at'], 'assistant_photos_public_active_index');
        });
    }

    public function down(): void
    {
        Schema::table('assistant_meter_photos', function (Blueprint $table): void {
            $table->dropIndex('assistant_photos_public_active_index');
            $table->dropConstrainedForeignId('help_conversation_id');
        });
    }
};
