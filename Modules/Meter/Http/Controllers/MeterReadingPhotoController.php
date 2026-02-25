<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class MeterReadingPhotoController extends Controller
{
    public function optimized(int $id): BinaryFileResponse
    {
        return $this->serveMediaConversion($id, 'optimized');
    }

    public function thumbnail(int $id): BinaryFileResponse
    {
        return $this->serveMediaConversion($id, 'thumbnail');
    }

    private function serveMediaConversion(int $id, string $conversion): BinaryFileResponse
    {
        $media = Media::query()->find($id);

        abort_if($media === null, Response::HTTP_NOT_FOUND);

        return response()->file($media->getPath($conversion));
    }
}
