<?php

namespace App\Http\Controllers\Admin\ProductManagement\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

// Helpers
use App\Helpers\MediaHelper;

// Request
use App\Http\Requests\Admin\ProductManagement\Categories\StoreRequest;

// Models
use App\Models\ProductManagement\ProductCategory;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(StoreRequest $request)
    {
        // Use validated data
        $validatedData = $request->validated();

        // Normalize is_featured to 'Yes' or 'No'
        $validatedData['is_featured'] = isset($validatedData['is_featured']) ? 'Yes' : 'No';

        $objProductCategory = ProductCategory::create($validatedData);

        // If media is uploaded, associate it with the product category
        if ($request->hasFile('media')) {
            $mediaData = MediaHelper::uploadStorageFile('Public Asset', $request->file('media'), 'product-categories', $objProductCategory);
            $objProductCategory->media_id = $mediaData['mediaObj']->id ?? null;
            $objProductCategory->save();
        }

        // Sync products if provided
        $objProductCategory->products()->sync($validatedData['products'] ?? []);


        flash('Product Category created successfully.')->success();

        // Determine the redirection based on the button clicked
        $action = $request->input('action');

        return match ($action) {
            'save' => redirect()->route('admin.product-management.categories.edit', ['unique_id' => $objProductCategory->unique_id]),
            'save_new' => redirect()->route('admin.product-management.categories.create'),
            default => back(), // fallback
        };
    }
}
