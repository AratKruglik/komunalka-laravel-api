<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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

        DB::statement('
                CREATE INDEX document_chunks_content_tsv_idx ON document_chunks USING gin (content_tsv)
            ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS document_chunks_content_tsv_idx');
        DB::statement('ALTER TABLE document_chunks DROP COLUMN IF EXISTS content_tsv');
    }
};
