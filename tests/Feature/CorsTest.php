<?php

declare(strict_types=1);

describe('CORS', function () {
    it('includes CORS headers on API responses', function () {
        $this->getJson('/api/v1/utility-types', [
            'Origin' => 'http://example.com',
        ])
            ->assertHeader('Access-Control-Allow-Origin');
    });

    it('responds to OPTIONS preflight requests', function () {
        $this->options('/api/v1/utility-types', [], [
            'Origin' => 'http://example.com',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'Authorization',
        ])
            ->assertHeader('Access-Control-Allow-Origin')
            ->assertHeader('Access-Control-Allow-Methods');
    });
});
