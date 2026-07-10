<?php

declare(strict_types=1);

namespace Modules\Shared\Concerns;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait ResolvesMediaConversionUrls
{
    /**
     * @param  array<int, string>  $conversions
     * @return array<string, string|bool|null>|null
     */
    protected function resolveMediaConversionUrls(?Media $media, array $conversions = ['optimized', 'thumbnail']): ?array
    {
        if ($media === null) {
            return null;
        }

        $urls = ['original_url' => $media->getUrl()];
        $isProcessing = false;

        foreach ($conversions as $conversion) {
            $ready = $media->hasGeneratedConversion($conversion);
            $urls["{$conversion}_url"] = $ready ? $media->getUrl($conversion) : null;
            $isProcessing = $isProcessing || ! $ready;
        }

        $urls['is_processing'] = $isProcessing;

        return $urls;
    }
}
