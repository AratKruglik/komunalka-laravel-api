<?php

declare(strict_types=1);

namespace Modules\Ai\Providers;

use Illuminate\Support\ServiceProvider;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Parser\MarkdownParser;
use Modules\Ai\Actions\PurgeSupportConversations;
use Modules\Ai\Console\IngestDocuments;
use Modules\Auth\Models\User;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MarkdownParser::class, function (): MarkdownParser {
            $environment = new Environment;
            $environment->addExtension(new CommonMarkCoreExtension);
            $environment->addExtension(new TableExtension);

            return new MarkdownParser($environment);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(base_path('Modules/Ai/database/migrations'));

        User::deleted(fn (User $user) => app(PurgeSupportConversations::class)->handle($user));

        if ($this->app->runningInConsole()) {
            $this->commands([
                IngestDocuments::class,
            ]);
        }
    }
}
