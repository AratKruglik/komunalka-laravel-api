<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('address_service_category', function (Blueprint $table) {
            $table->foreign('address_id')->references('id')->on('addresses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('address_service_category', function (Blueprint $table) {
            $table->dropForeign(['address_id']);
        });
    }
};
