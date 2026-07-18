<?php

namespace App\Http\Controllers\Admin\ProductManagement\Categories;

use App\Http\Controllers\Controller;

// Request
use App\Http\Requests\Admin\ProductManagement\Categories\UpdateRequest;

// Helpers
use App\Helpers\MediaHelper;

// Models
use App\Models\ProductManagement\ProductCategory;
use App\Models\ProductManagement\ProductCategoryChild;

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

        // is_featured has no control on this form (managed via the toggle
        // switch on the category hierarchy list) — never overwrite it here.
        unset($validatedData['is_featured']);

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

        // Persist ordered product/subcategory rows in product_category_children
        if (isset($validatedData['products']) && is_array($validatedData['products'])) {
            $rowsToInsert = [];
            $now = now();

            foreach ($validatedData['products'] as $index => $row) {
                $productId = !empty($row['product_id']) ? (int) $row['product_id'] : null;
                $subcategoryId = !empty($row['sub_category_id']) ? (int) $row['sub_category_id'] : null;
                $sortOrder = (int) ($row['sort_order'] ?? ($index + 1));

                if (is_null($productId) && is_null($subcategoryId)) {
                    continue;
                }

                $rowsToInsert[] = [
                    'product_category_id' => $objProductCategory->id,
                    'product_id' => $productId,
                    'sub_category_id' => $subcategoryId,
                    'sort_order' => $sortOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            ProductCategoryChild::where('product_category_id', $objProductCategory->id)->delete();

            if (!empty($rowsToInsert)) {
                ProductCategoryChild::insert($rowsToInsert);
            }
        } else {
            ProductCategoryChild::where('product_category_id', $objProductCategory->id)->delete();
        }

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
