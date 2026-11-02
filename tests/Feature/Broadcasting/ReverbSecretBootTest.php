<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;

describe('Reverb secret boot guard', function (): void {
    it('fails fast in production when reverb has a blank secret', function (): void {
        config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.secret' => '']);
        app()->detectEnvironment(fn (): string => 'production');

        expect(fn () => (new AppServiceProvider(app()))->boot())
            ->toThrow(RuntimeException::class, 'REVERB_APP_SECRET');
    });

    it('boots in production when the secret is set', function (): void {
        config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.secret' => 'secret']);
        app()->detectEnvironment(fn (): string => 'production');

        expect(fn () => (new AppServiceProvider(app()))->boot())->not->toThrow(RuntimeException::class);
    });
});
