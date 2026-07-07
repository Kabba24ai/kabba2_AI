<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;

/**
 * Lightweight GD-based image processing (no external library in this
 * project). Callers must treat processing as best-effort: on any failure
 * (missing GD, unsupported format, corrupt file) the ORIGINAL upload is
 * returned unchanged — display layers should still use fixed containers
 * with object-fit: cover so nothing ever distorts.
 */
class ImageHelper
{
    /**
     * Scale proportionally, center-crop to square, and resize to at most
     * $size × $size (never upscales — smaller sources crop to their own
     * shorter side). Keeps the original format (jpg/png/webp) and client
     * file name so downstream storage helpers behave normally.
     */
    public static function squareThumbnail(UploadedFile $file, int $size = 500): UploadedFile
    {
        try {
            if (!function_exists('imagecreatetruecolor')) {
                return $file;
            }

            $mime = $file->getMimeType();

            $source = match ($mime) {
                'image/jpeg' => imagecreatefromjpeg($file->getRealPath()),
                'image/png'  => imagecreatefrompng($file->getRealPath()),
                'image/webp' => function_exists('imagecreatefromwebp')
                    ? imagecreatefromwebp($file->getRealPath()) : false,
                default      => false,
            };

            if ($source === false) {
                return $file;
            }

            $width  = imagesx($source);
            $height = imagesy($source);
            $side   = min($width, $height);
            $target = min($size, $side);

            // Center-crop window on the source
            $srcX = (int) floor(($width - $side) / 2);
            $srcY = (int) floor(($height - $side) / 2);

            $thumb = imagecreatetruecolor($target, $target);

            // Keep transparency for PNG/WebP instead of black fill
            if (in_array($mime, ['image/png', 'image/webp'], true)) {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                imagefill($thumb, 0, 0, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
            }

            imagecopyresampled($thumb, $source, 0, 0, $srcX, $srcY, $target, $target, $side, $side);
            imagedestroy($source);

            $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
            $tempPath  = tempnam(sys_get_temp_dir(), 'thumb_') . '.' . $extension;

            $written = match ($mime) {
                'image/jpeg' => imagejpeg($thumb, $tempPath, 85),
                'image/png'  => imagepng($thumb, $tempPath),
                'image/webp' => function_exists('imagewebp') ? imagewebp($thumb, $tempPath, 85) : false,
                default      => false,
            };
            imagedestroy($thumb);

            if (!$written) {
                return $file;
            }

            return new UploadedFile($tempPath, $file->getClientOriginalName(), $mime, null, true);
        } catch (\Throwable) {
            return $file;
        }
    }
}
