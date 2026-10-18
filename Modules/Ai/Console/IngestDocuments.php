<?php

namespace Modules\Ai\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Ai\Services\Rag\DocumentIndexer;

#[Signature('ai:rag:ingest {path=docs/user_docs} {--prune : Remove indexed documents no longer present in the directory}')]
#[Description('Index Markdown documentation for the support agent')]
class IngestDocuments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DocumentIndexer $indexer): int
    {
        $inputPath = (string) $this->argument('path');
        $path = str_starts_with($inputPath, '/') ? $inputPath : base_path($inputPath);
        if (! File::isDirectory($path)) {
            $this->error("Directory not found: {$path}");

            return self::FAILURE;
        }

        $files = collect(File::allFiles($path))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'md'
                && array_key_exists($file->getFilename(), config('ai-support.sources', [])));
        $paths = $files->map(fn ($file) => $file->getRelativePathname())->all();

        foreach ($files as $file) {
            $indexer->indexMarkdown($file->getRelativePathname(), $file->getContents());
            $this->line($file->getRelativePathname());
        }

        if ($this->option('prune')) {
            DB::table('document_chunks')
                ->whereIn('source_path', array_keys(config('ai-support.sources', [])))
                ->whereNotIn('source_path', $paths)
                ->delete();
        }

        $this->info('Indexed '.$files->count().' Markdown files.');

        return self::SUCCESS;
    }
}
