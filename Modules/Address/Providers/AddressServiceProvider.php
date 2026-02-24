<?php

namespace Modules\Address\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AddressServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Address';

    protected string $nameLower = 'address';

    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->bind(
            \Modules\Address\Repositories\Contracts\RegionRepositoryInterface::class,
            \Modules\Address\Repositories\RegionRepository::class,
        );

        $this->app->bind(
            \Modules\Address\Repositories\Contracts\AddressTypeRepositoryInterface::class,
            \Modules\Address\Repositories\AddressTypeRepository::class,
        );

        $this->app->bind(
            \Modules\Address\Repositories\Contracts\AddressRepositoryInterface::class,
            \Modules\Address\Repositories\AddressRepository::class,
        );

        $this->app->bind(
            \Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface::class,
            \Modules\Address\Repositories\UserAddressRepository::class,
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
