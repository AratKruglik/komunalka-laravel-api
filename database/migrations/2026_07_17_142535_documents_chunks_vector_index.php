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
        DB::statement("
                ALTER TABLE document_chunks
                ADD COLUMN content_tsv tsvector
                GENERATED ALWAYS AS (to_tsvector('simple', content)) STORED
            ");

        DB::statement("
                CREATE INDEX document_chunks_content_tsv_idx ON document_chunks USING gin (content_tsv)
            ");
    }
};
