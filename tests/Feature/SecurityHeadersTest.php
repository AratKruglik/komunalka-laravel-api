<?php

declare(strict_types=1);

use App\Http\Middleware\SecurityHeaders;

mutates(SecurityHeaders::class);

describe('SecurityHeaders middleware', function (): void {
    it('applies all security headers', function (): void {
        $this->get('/up')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer-when-downgrade')
            ->assertHeaderMissing('X-Powered-By');
    });
});
