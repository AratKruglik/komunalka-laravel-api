<?php

declare(strict_types=1);

describe('CORS', function () {
    it('includes CORS headers on API responses', function () {
        $this->getJson(route('api.utility-types.index'), [
            'Origin' => 'http://example.com',
        ])
            ->assertHeader('Access-Control-Allow-Origin');
    });

    it('responds to OPTIONS preflight requests', function () {
        $this->options(route('api.utility-types.index'), [], [
            'Origin' => 'http://example.com',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'Authorization',
        ])
            ->assertHeader('Access-Control-Allow-Origin')
            ->assertHeader('Access-Control-Allow-Methods');
    });
});
