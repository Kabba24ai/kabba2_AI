<?php

namespace App\Traits;

use App\Helpers\MediaHelper;
use App\Models\Global\Media;
use Illuminate\Http\Request;

trait HandlesMediaUpload
{
    /**
     * Handle a single image field upload.
     * Removes the old media file if one exists, uploads the new one,
     * and writes the new media ID into $validated[$field].
     * If no file was submitted, removes $field from $validated so the column
     * is not overwritten.
     */
    protected function uploadImage(
        Request $request,
        string  $field,
        mixed   $model,
        string  $folder,
        array   &$validated
    ): void {
        if ($request->hasFile($field)) {
            // Remove old media if a model record already has one
            if ($model && !is_null($model->{$field})) {
                $old = Media::find($model->{$field});
                if ($old) {
                    MediaHelper::removeFile($old);
                }
            }

            $mediaData = MediaHelper::uploadStorageFile(
                'Public Asset',
                $request->file($field),
                $folder,
                null
            );

            if (!empty($mediaData['mediaObj'])) {
                $validated[$field] = $mediaData['mediaObj']->id;
            }
        } else {
            unset($validated[$field]);
        }
    }

    /**
     * Remove an image from a model field, delete the media record,
     * and clear the field on the model.
     */
    protected function removeImage(mixed $model, string $field): void
    {
        if ($model->{$field}) {
            $media = Media::find($model->{$field});
            if ($media) {
                MediaHelper::removeFile($media);
            }
            $model->update([$field => null]);
        }
    }
}
