<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        throw_if(
            app()->isProduction()
                && config('broadcasting.default') === 'reverb'
                && blank(config('broadcasting.connections.reverb.secret')),
            RuntimeException::class,
            'REVERB_APP_SECRET must be set when broadcasting via Reverb in production.',
        );

        Gate::define('viewApiDocs', function ($user = null) {
            return in_array(app()->environment(), ['local', 'testing']);
        });

        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('support-ai', function (Request $request): array {
            $identity = $request->user()?->getAuthIdentifier() ?: $request->ip();

            $limits = [
                Limit::perMinute(6)->by('support-minute:'.$identity),
                Limit::perDay(100)->by('support-day:'.$identity),
            ];

            if ($request->hasFile('photo')) {
                $limits[] = Limit::perMinute(3)->by('support-vision-minute:'.$identity);
                $limits[] = Limit::perDay(20)->by('support-vision-day:'.$identity);
            }

            return $limits;
        });
    }
}
