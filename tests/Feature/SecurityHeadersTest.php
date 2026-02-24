<?php

declare(strict_types=1);

describe('SecurityHeaders middleware', function () {
    it('sets X-Content-Type-Options header to nosniff', function () {
        $this->get('/up')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    });

    it('sets X-Frame-Options header to DENY', function () {
        $this->get('/up')
            ->assertHeader('X-Frame-Options', 'DENY');
    });

    it('sets Referrer-Policy header', function () {
        $this->get('/up')
            ->assertHeader('Referrer-Policy', 'no-referrer-when-downgrade');
    });

    it('removes X-Powered-By header', function () {
        $this->get('/up')
            ->assertHeaderMissing('X-Powered-By');
    });

    it('applies security headers to API routes', function () {
        $response = $this->getJson(route('api.utility-types.index'));

        $response
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer-when-downgrade')
            ->assertHeaderMissing('X-Powered-By');
    });

    it('applies security headers to web routes', function () {
        $response = $this->get('/health');

        $response
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer-when-downgrade')
            ->assertHeaderMissing('X-Powered-By');
    });
});
