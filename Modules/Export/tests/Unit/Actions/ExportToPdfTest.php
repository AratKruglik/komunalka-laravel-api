<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Modules\Export\Actions\ExportToPdf;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

beforeEach(function () {
    $this->mockPdfBuilder = Mockery::mock(PdfBuilder::class);
    $this->mockPdfBuilder->shouldReceive('view')->andReturnSelf();
    $this->mockPdfBuilder->shouldReceive('landscape')->andReturnSelf();
    $this->mockPdfBuilder->shouldReceive('format')->andReturnSelf();

    Pdf::swap($this->mockPdfBuilder);
});

it('generates non-empty PDF output', function () {
    $this->mockPdfBuilder->shouldReceive('base64')
        ->once()
        ->andReturn(base64_encode('fake-pdf-binary'));

    $readings = new Collection;

    $pdf = app(ExportToPdf::class)->handle(
        $readings,
        CarbonImmutable::parse('2026-01-01'),
        CarbonImmutable::parse('2026-01-31'),
    );

    expect($pdf)->toBeString()
        ->not->toBeEmpty()
        ->toBe('fake-pdf-binary');
});

it('handles empty collection', function () {
    $this->mockPdfBuilder->shouldReceive('base64')
        ->once()
        ->andReturn(base64_encode('empty-pdf'));

    $readings = new Collection;

    $pdf = app(ExportToPdf::class)->handle(
        $readings,
        CarbonImmutable::parse('2026-01-01'),
        CarbonImmutable::parse('2026-01-31'),
    );

    expect($pdf)->toBeString()
        ->not->toBeEmpty();
});
