<?php

namespace App\Services\MediaLibrary;

use App\Helpers\MediaHelper;
use App\Models\Global\Media;
use App\Models\Global\MediaFolder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaLibraryService
{
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    const MAX_SIZE_MB = 10;

    public function upload(UploadedFile $file, array $options = []): Media
    {
        $folderId   = $options['media_folder_id'] ?? null;
        $folder     = $folderId ? MediaFolder::find($folderId) : null;
        $folderSlug = $folder?->slug ?? 'media_library';

        $mediaData = MediaHelper::uploadStorageFile('Public Asset', $file, $folderSlug, null);
        $media = $mediaData['mediaObj'];

        $dimensions = $this->extractDimensions($media);

        $media->update(array_filter([
            'alt_text'        => $options['alt_text'] ?? null,
            'title'           => $options['title'] ?? null,
            'media_folder_id' => $folderId,
            'uploaded_by'     => auth()->id(),
            'width'           => $dimensions['width'] ?? null,
            'height'          => $dimensions['height'] ?? null,
        ], fn($v) => $v !== null && $v !== ''));

        return $media->fresh();
    }

    public function list(array $filters = [], int $perPage = 40)
    {
        $query = Media::query()->orderBy('id', 'DESC');

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('original_file_name', 'LIKE', "%{$s}%")
                  ->orWhere('alt_text', 'LIKE', "%{$s}%")
                  ->orWhere('title', 'LIKE', "%{$s}%");
            });
        }

        if (isset($filters['folder_id']) && $filters['folder_id'] !== '' && $filters['folder_id'] !== null) {
            $query->where('media_folder_id', $filters['folder_id'] ?: null);
        }

        if (!empty($filters['type'])) {
            match ($filters['type']) {
                'image'    => $query->where('file_type', 'image'),
                'document' => $query->whereNotIn('file_type', ['image', 'video', 'audio']),
                default    => null,
            };
        }

        return $query->paginate($perPage);
    }

    public function updateMetadata(Media $media, array $data): void
    {
        $allowed = ['alt_text', 'title', 'caption', 'description', 'media_folder_id'];
        $media->update(array_intersect_key($data, array_flip($allowed)));
    }

    public function replace(Media $media, UploadedFile $newFile): Media
    {
        $disk = $media->asset_type === 'Secure Asset' ? 'secure_asset' : 'public_asset';

        Storage::disk($disk)->delete($media->getFilePath());

        $ext      = $newFile->getClientOriginalExtension();
        $nameOnly = Str::lower(pathinfo($newFile->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = Str::random(6) . '-media-' . preg_replace('/[^a-z0-9\_\-\.]/i', '', $nameOnly . '.' . $ext);

        Storage::disk($disk)->put($media->folder_name . '/' . $filename, file_get_contents($newFile));

        $mimeType = $newFile->getMimeType();
        $dims     = $this->extractDimensionsFromUploadedFile($newFile, $mimeType);

        $media->update(array_merge([
            'file_name'          => $filename,
            'original_file_name' => $newFile->getClientOriginalName(),
            'file_extension'     => $ext,
            'mime_type'          => $mimeType,
            'file_size'          => $newFile->getSize(),
            'file_type'          => explode('/', $mimeType ?? 'image/jpeg')[0],
        ], $dims));

        return $media->fresh();
    }

    public function getFolders(): \Illuminate\Database\Eloquent\Collection
    {
        return MediaFolder::withCount('media')->orderBy('name')->get();
    }

    public function createFolder(string $name, ?int $parentId = null): MediaFolder
    {
        return MediaFolder::create([
            'name'      => $name,
            'slug'      => Str::slug($name),
            'parent_id' => $parentId,
            'created_by'=> auth()->id(),
        ]);
    }

    protected function extractDimensions(Media $media): array
    {
        try {
            if (!$media->isImage() || str_contains($media->mime_type ?? '', 'svg')) {
                return [];
            }

            $disk     = $media->asset_type === 'Secure Asset' ? 'secure_asset' : 'public_asset';
            $contents = Storage::disk($disk)->get($media->getFilePath());
            if (!$contents) return [];

            $size = @getimagesizefromstring($contents);
            return $size ? ['width' => $size[0], 'height' => $size[1]] : [];
        } catch (\Throwable $e) {
            Log::warning('MediaLibrary: dimension extraction failed: ' . $e->getMessage());
            return [];
        }
    }

    protected function extractDimensionsFromUploadedFile(UploadedFile $file, ?string $mime): array
    {
        try {
            if (!str_starts_with($mime ?? '', 'image/') || str_contains($mime ?? '', 'svg')) {
                return [];
            }
            $size = @getimagesizefromstring(file_get_contents($file->getRealPath()));
            return $size ? ['width' => $size[0], 'height' => $size[1]] : [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
