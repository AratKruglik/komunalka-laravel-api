<?php

declare(strict_types=1);

describe('Health checks', function () {
    it('returns ok on /health', function () {
        $this->getJson('/health')
            ->assertSuccessful()
            ->assertJsonStructure(['status', 'timestamp'])
            ->assertJson(['status' => 'ok']);
    });

    it('returns ok with database status on /health/ready', function () {
        $this->getJson('/health/ready')
            ->assertSuccessful()
            ->assertJsonStructure(['status', 'database', 'timestamp'])
            ->assertJson([
                'status' => 'ok',
                'database' => 'connected',
            ]);
    });
});
