<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_providers', function (Blueprint $table): void {
            $table->index('address_id');
            $table->index('utility_type_id');
            $table->index('is_active');
        });

        Schema::table('tariffs', function (Blueprint $table): void {
            $table->index('service_provider_id');
            $table->index('utility_type_id');
            $table->index(['service_provider_id', 'utility_type_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::table('service_providers', function (Blueprint $table): void {
            $table->dropIndex(['address_id']);
            $table->dropIndex(['utility_type_id']);
            $table->dropIndex(['is_active']);
        });

        Schema::table('tariffs', function (Blueprint $table): void {
            $table->dropIndex(['service_provider_id']);
            $table->dropIndex(['utility_type_id']);
            $table->dropIndex(['service_provider_id', 'utility_type_id', 'effective_from']);
        });
    }
};
