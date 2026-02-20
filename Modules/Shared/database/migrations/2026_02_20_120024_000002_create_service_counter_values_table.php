<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_counter_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_counter_id')->constrained()->cascadeOnDelete();
            $table->float('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_counter_values');
    }
};
