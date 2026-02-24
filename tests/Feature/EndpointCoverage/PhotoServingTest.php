<?php

declare(strict_types=1);

describe('GET /api/v1/meter-readings/photos/{id}/optimized', function () {
    it('returns 404 for non-existent photo', function () {
        $this->getJson(route('api.meter-readings.photos.optimized', 99999))
            ->assertNotFound();
    });

    it('does not require authentication', function () {
        $response = $this->getJson(route('api.meter-readings.photos.optimized', 99999));

        expect($response->status())->not->toBe(401);
    });
});

describe('GET /api/v1/meter-readings/photos/{id}/thumbnail', function () {
    it('returns 404 for non-existent photo', function () {
        $this->getJson(route('api.meter-readings.photos.thumbnail', 99999))
            ->assertNotFound();
    });

    it('does not require authentication', function () {
        $response = $this->getJson(route('api.meter-readings.photos.thumbnail', 99999));

        expect($response->status())->not->toBe(401);
    });
});

describe('GET /api/v1/meterreading/images/{id}/optimized (legacy)', function () {
    it('returns 404 for non-existent image', function () {
        $this->getJson(route('api.legacy-meter-reading.images.optimized', 99999))
            ->assertNotFound();
    });

    it('does not require authentication', function () {
        $response = $this->getJson(route('api.legacy-meter-reading.images.optimized', 99999));

        expect($response->status())->not->toBe(401);
    });
});

describe('GET /api/v1/meterreading/images/{id}/thumbnail (legacy)', function () {
    it('returns 404 for non-existent image', function () {
        $this->getJson(route('api.legacy-meter-reading.images.thumbnail', 99999))
            ->assertNotFound();
    });

    it('does not require authentication', function () {
        $response = $this->getJson(route('api.legacy-meter-reading.images.thumbnail', 99999));

        expect($response->status())->not->toBe(401);
    });
});
