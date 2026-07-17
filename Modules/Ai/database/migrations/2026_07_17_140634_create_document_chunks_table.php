<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::ensureVectorExtensionExists();

        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();

            $table->string('source_path')->index();
            $table->string('source_type')->default('markdown')->index();
            $table->unsignedBigInteger('chunk_index');
            $table->string('heading')->nullable();
            $table->text('content');
            $table->string('content_hash', 64);
            $table->jsonb('metadata')->nullable();

            $table->vector('embedding', 1536)->vectorIndex();

            $table->timestamps();
            $table->unique(['source_path', 'chunk_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
