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
use App\Models\ProductManagement\ProductCategoryChild;

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

        // New categories are featured on the homepage by default — the create
        // form has no is_featured control, so this always applies on create.
        // Toggle it off afterward from the category hierarchy list if needed.
        $validatedData['is_featured'] = 'Yes';

        $objProductCategory = ProductCategory::create($validatedData);

        // If media is uploaded, associate it with the product category
        if ($request->hasFile('media')) {
            $mediaData = MediaHelper::uploadStorageFile('Public Asset', $request->file('media'), 'product-categories', $objProductCategory);
            $objProductCategory->media_id = $mediaData['mediaObj']->id ?? null;
            $objProductCategory->save();
        }

        // If hover media is uploaded, associate it with the product category
        if ($request->hasFile('hover_media')) {
            $hoverMediaData = MediaHelper::uploadStorageFile('Public Asset', $request->file('hover_media'), 'product-categories', $objProductCategory);
            $objProductCategory->hover_media_id = $hoverMediaData['mediaObj']->id ?? null;
            $objProductCategory->save();
        }

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
