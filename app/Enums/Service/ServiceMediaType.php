<?php

namespace App\Enums\Service;

enum ServiceMediaType: string
{
    case Image    = 'image';
    case Video    = 'video';
    case Pdf      = 'pdf';
    case Document = 'document';
    case Other    = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Image    => 'Image',
            self::Video    => 'Video',
            self::Pdf      => 'PDF',
            self::Document => 'Document',
            self::Other    => 'Other',
        };
    }

    /**
     * Classify an uploaded file into a media type. The extension is the
     * fallback when the sniffed MIME is generic (e.g. octet-stream).
     */
    public static function fromMime(?string $mime, ?string $extension = null): self
    {
        $byMime = match (true) {
            str_starts_with((string) $mime, 'image/') => self::Image,
            str_starts_with((string) $mime, 'video/') => self::Video,
            $mime === 'application/pdf'               => self::Pdf,
            in_array($mime, [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
            ], true) => self::Document,
            default  => self::Other,
        };

        if ($byMime !== self::Other || !$extension) {
            return $byMime;
        }

        return match (strtolower($extension)) {
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'heic' => self::Image,
            'mp4', 'mov', 'avi', 'webm'                 => self::Video,
            'pdf'                                       => self::Pdf,
            'doc', 'docx', 'xls', 'xlsx', 'txt'         => self::Document,
            default                                     => self::Other,
        };
    }
}
