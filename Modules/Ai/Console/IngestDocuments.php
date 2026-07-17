<?php

namespace Modules\Ai\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Ai\Services\Rag\DocumentIndexer;

#[Signature('ai:rag:ingest {path}')]
#[Description('Command description')]
class IngestDocuments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DocumentIndexer $indexer): int
    {
        collect(File::allFiles($this->argument('path')))
            ->each(function ($file) use ($indexer) {
                $ext = strtolower($file->getExtension());
                $relPath = $file->getRelativePathname();

                if ($ext === 'md') {
                    $indexer->indexMarkdown($relPath, $file->getContents());
                }

                $this->line($relPath);
            });

        $this->info('Ok');

        return self::SUCCESS;
    }
}
