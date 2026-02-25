<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_counters', function (Blueprint $table): void {
            $table->index('service_category_id');
        });

        Schema::table('address_service_category', function (Blueprint $table): void {
            $table->index('service_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_counters', function (Blueprint $table): void {
            $table->dropIndex(['service_category_id']);
        });

        Schema::table('address_service_category', function (Blueprint $table): void {
            $table->dropIndex(['service_category_id']);
        });
    }
};
