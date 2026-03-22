<?php

declare(strict_types=1);

namespace Modules\Export\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Export';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void {}
}
