<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meter_id')->constrained('meters')->cascadeOnDelete();
            $table->float('reading_value');
            $table->date('reading_date');
            $table->float('previous_reading_value')->nullable();
            $table->float('consumption')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_estimated')->default(false);
            $table->foreignId('tariff_id')->nullable()->constrained('tariffs')->nullOnDelete();
            $table->timestamps();

            $table->index(['meter_id', 'reading_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
    }
};
