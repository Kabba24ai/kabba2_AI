<?php

namespace App\Services\MediaLibrary;

use App\Models\Global\Media;

/**
 * Stub — future WebP conversion and multi-size generation.
 * Implement with Intervention Image or Spatie Image Optimizer.
 */
class ImageOptimizationService
{
    public function generateThumbnail(Media $media, int $width = 300, int $height = 300): ?string
    {
        // TODO: Generate thumbnail using Intervention/Image
        return null;
    }

    public function generateVariants(Media $media): array
    {
        // TODO: Generate thumbnail / medium / large / original
        return [
            'thumbnail' => null,
            'medium'    => null,
            'large'     => null,
            'original'  => $media->url,
        ];
    }

    public function convertToWebP(Media $media): ?Media
    {
        // TODO: Convert to WebP and create new Media record
        return null;
    }
}
