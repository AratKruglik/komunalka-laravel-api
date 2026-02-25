<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meters', function (Blueprint $table): void {
            $table->index('utility_type_id');
            $table->index('service_provider_id');
        });

        Schema::table('meter_readings', function (Blueprint $table): void {
            $table->index('tariff_id');
        });
    }

    public function down(): void
    {
        Schema::table('meters', function (Blueprint $table): void {
            $table->dropIndex(['utility_type_id']);
            $table->dropIndex(['service_provider_id']);
        });

        Schema::table('meter_readings', function (Blueprint $table): void {
            $table->dropIndex(['tariff_id']);
        });
    }
};
