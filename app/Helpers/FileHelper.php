<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Str;

class FileHelper
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
    public static function removeFile($filePath, $disk = 'public')
    {
        try {
            // Check if the file exists
            if (Storage::disk($disk)->exists($filePath)) {
                // Delete the file
                Storage::disk($disk)->delete($filePath);
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
}
