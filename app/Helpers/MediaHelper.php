<?php

namespace App\Helpers;

use App\Models\Global\Media;
use Illuminate\Support\Facades\Storage;
use Str;

class MediaHelper
{
    const SEPARATOR = '/';
    const MAX_FILE_SIZE = 5120; // 5MB in KB

    /**
     * Upload a file to the storage/public directory.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $directory
     * @param  string|null  $disk
     * @return string|null  The file path or null if the upload fails
     */
    public static function uploadFile($file, $directory, $disk = 'public')
    {
        try {
            // Validate file size
            if ($file->getSize() > self::MAX_FILE_SIZE * 1024) {
                throw new \Exception('File size exceeds the maximum limit of 5MB.');
            }

            // Validate file type (jpg, jpeg, png)
            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            if (!in_array($file->getClientOriginalExtension(), $allowedExtensions)) {
                throw new \Exception('Invalid file type. Only jpg, jpeg, and png are allowed.');
            }

            // Generate a unique file name
            $fileName = Str::random(20) . '.' . $file->getClientOriginalExtension();

            // Store the file in the storage/public directory
            $filePath = $file->storeAs('uploads/' . $directory, $fileName, $disk);
            return $filePath;
        } catch (\Exception $e) {
            // Log the error and return null
            \Log::error('File upload failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Remove a file from storage.
     *
     * @param  string  $filePath
     * @param  string|null  $disk
     * @return bool  True if the file was successfully deleted, otherwise false
     */
    public static function removeFile($media)
    {
        try {
            // Determine the disk to use
            $disk = $media->asset_type === "Secure Asset" ? 'secure_asset' : 'public_asset';

            $filePath = $media->getFilePath();
            // Check if the file exists
            if (Storage::disk($disk)->exists($filePath)) {
                // Delete the file
                Storage::disk($disk)->delete($filePath);

                $media->delete(); // Delete the media record from the database
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            // Log the error and return false
            \Log::error('File removal failed: ' . $e->getMessage());
            return false;
        }
    }

    /* Upload Storage File to Storage  */
    public static function uploadOriginalFileToStorage($asset_type, $file, $full_path, $filename)
    {
        $diskName = $asset_type === "Secure Asset" ? 'secure_asset' : 'public_asset';
        // Upload the file to Storage
        $path = Storage::disk($diskName)->put($full_path . '/' . $filename, file_get_contents($file));
    }

    /* Upload Storage File */
    public static function uploadStorageFile($asset_type, $file, $folder, $model = null, $is_used = 'Yes',)
    {
        if (!is_null($file)) {
            $file_mime_type = \File::mimeType($file);
            $file_extension = $file->getClientOriginalExtension();
            $original_file_name = $file->getClientOriginalName();
            $file_type = explode('/', $file_mime_type)[0]; // image/video/audio/etc.
            $file_size = \File::size($file);

            $original_file_name_without_extension = Str::lower(pathinfo($original_file_name, PATHINFO_FILENAME));
            $replace_words = ["png", "jpg", "jpeg", 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
            $original_file_name_without_extension = str_replace($replace_words, '', $original_file_name_without_extension);
            $filename = Str::random(6) . '-media-' . preg_replace("/[^a-z0-9\_\-\.]/i", '', $original_file_name_without_extension . '.' . $file_extension);

            // File Store s3 Bucket
            $folder_name = now()->format('Y/m') . ($folder ? '/' . $folder : '');
            $full_path =  $folder_name;

            // File move to destination folder
            self::uploadOriginalFileToStorage($asset_type, $file, $full_path, $filename);

            $mediaObj = Media::create([
                'asset_type' => $asset_type,
                'folder_name' => $folder_name,
                'file_name' => $filename,
                'original_file_name' => $original_file_name,
                'file_type' => $file_type,
                'mime_type' => $file_mime_type,
                'file_extension' => $file_extension,
                'file_size' => $file_size,
                'is_used' => $is_used,
                'model_type' => optional($model)->getMorphClass(),
                'model_id' => optional($model)->getKey(),
            ]);

            $returnArr = [
                'original_file_name' => $original_file_name,
                'filename' => $filename,
                'mediaObj' => $mediaObj,
                'full_path' => $full_path . self::SEPARATOR . $filename,
            ];
            return $returnArr;
        }
    }
}
