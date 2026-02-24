<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('address_id')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('utility_type_id')->constrained('utility_types')->restrictOnDelete();
            $table->foreignId('service_provider_id')->nullable()->constrained('service_providers')->nullOnDelete();
            $table->string('serial_number');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('model_name')->nullable();
            $table->string('location')->nullable();
            $table->date('installation_date')->nullable();
            $table->float('initial_reading')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['address_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meters');
    }
};
