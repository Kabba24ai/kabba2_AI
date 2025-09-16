<?php

namespace App\Http\Controllers\Admin\ProductManagement\Categories;

use App\Http\Controllers\Controller;

// Request
use App\Http\Requests\Admin\ProductManagement\Categories\UpdateRequest;

// Helpers
use App\Helpers\MediaHelper;

// Models
use App\Models\ProductManagement\ProductCategory;

class UpdateController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function __invoke($unique_id, UpdateRequest $request)
    {

        $validatedData = $request->validated();

        // Normalize is_featured to 'Yes' or 'No'
        $validatedData['is_featured'] = isset($validatedData['is_featured']) ? 'Yes' : 'No';

        $objProductCategory = ProductCategory::where('unique_id', $unique_id)->firstOrFail();
        $objProductCategory->fill($validatedData);

        if ($request->has('media') && !is_null($request->file('media'))) {
            if (!is_null($objProductCategory->media)) {
                MediaHelper::removeFile($objProductCategory->media);
            }
            $mediaData = MediaHelper::uploadStorageFile("Public Asset", $request->file('media'), 'product-categories', $objProductCategory);
            $objProductCategory->media_id = $mediaData['mediaObj']->id ?? null;
        }

        // Handle hover media
        if ($request->has('hover_media') && !is_null($request->file('hover_media'))) {
            if (!is_null($objProductCategory->hover_media_id)) {
                MediaHelper::removeFile($objProductCategory->hoverMedia);
            }
            $hoverMediaData = MediaHelper::uploadStorageFile("Public Asset", $request->file('hover_media'), 'product-categories', $objProductCategory);
            $objProductCategory->hover_media_id = $hoverMediaData['mediaObj']->id ?? null;
        }

        $objProductCategory->save();

        // Sync products
        $objProductCategory->products()->sync($validatedData['products'] ?? []);


        flash('Product category updated successfully.')->success();

        // Determine the redirection based on the button clicked
        $action = $request->input('action');

        return match ($action) {
            'save' => redirect()->route('admin.product-management.categories.edit', ['unique_id' => $objProductCategory->unique_id]),
            'save_new' => redirect()->route('admin.product-management.categories.create'),
            default => back(), // fallback
        };

    }
}
