<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refresh_tokens', function (Blueprint $table): void {
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('refresh_tokens', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
        });
    }
};
