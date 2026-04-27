<?php

declare(strict_types=1);

namespace Modules\Meter\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Meter\Repositories\Contracts\MeterReadingRepositoryInterface;
use Modules\Meter\Repositories\Contracts\MeterRepositoryInterface;
use Modules\Meter\Repositories\MeterReadingRepository;
use Modules\Meter\Repositories\MeterRepository;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MeterServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Meter';

    protected string $nameLower = 'meter';

    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->bind(
            MeterRepositoryInterface::class,
            MeterRepository::class,
        );

        $this->app->bind(
            MeterReadingRepositoryInterface::class,
            MeterReadingRepository::class,
        );
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
        }
    }

    /** @return array<int, string> */
    public function provides(): array
    {
        return [];
    }
}
