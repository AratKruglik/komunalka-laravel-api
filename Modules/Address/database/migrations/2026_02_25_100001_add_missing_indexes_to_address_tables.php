<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table): void {
            $table->index('region_id');
            $table->index('address_type_id');
        });

        Schema::table('address_user', function (Blueprint $table): void {
            $table->index('address_id');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table): void {
            $table->dropIndex(['region_id']);
            $table->dropIndex(['address_type_id']);
        });

        Schema::table('address_user', function (Blueprint $table): void {
            $table->dropIndex(['address_id']);
        });
    }
};
