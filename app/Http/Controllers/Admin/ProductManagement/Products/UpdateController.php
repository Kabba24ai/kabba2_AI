<?php

namespace App\Http\Controllers\Admin\ProductManagement\Products;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductManagement\Products\UpdateRequest;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductMediaChild;
use Illuminate\Support\Facades\DB;

class UpdateController extends Controller
{
    /**
     * Handle the incoming request to update a product.
     */
    public function __invoke(UpdateRequest $request, string $unique_id)
    {
        $validated = $request->validated();


        $product = Product::where('unique_id', $unique_id)->firstOrFail();

        DB::beginTransaction();

        try {
            // Base fields
            $productData = [
                'product_name' => $validated['product_name'],
                'product_type' => $validated['product_type'],
                'short_description' => $validated['short_description'] ?? null,
                'description' => $validated['description'] ?? null,
                'seo_title' => $validated['seo_title'] ?? null,
                'seo_description' => $validated['seo_description'] ?? null,
                'sku' => $validated['sku'] ?? null,
                'barcode' => $validated['barcode'] ?? null,
                'status' => $validated['status'] ?? 'Pending',
                'is_general_term_type' => $validated['is_general_term_type'] ?? false,
                'is_custom_term_type' => $validated['is_custom_term_type'] ?? false,
            ];

            // Include only relevant fields and clear opposite-type fields
            if ($validated['product_type'] === 'Retail') {
                $productData += [
                    'retail_price' => $validated['retail_price'] ?? null,
                    'retail_sale_price' => $validated['retail_sale_price'] ?? null,
                    'retail_product_cost' => $validated['retail_product_cost'] ?? null,

                    // Clear Rental fields
                    'rental_daily' => null,
                    'rental_weekend' => null,
                    'rental_weekly' => null,
                    'rental_monthly' => null,
                    'rental_damage_waiver_daily' => null,
                    'rental_damage_waiver_weekend' => null,
                    'rental_damage_waiver_weekly' => null,
                    'rental_damage_waiver_monthly' => null,
                    'rental_prepaid_cleaning' => null,
                    'rental_prepaid_fuel' => null,
                    'rental_fuel_gallons' => null,
                    'rental_fuel_type' => null,
                    'rental_def_gallons' => null,
                    'standard_delivery_fee' => null,
                    'extended_delivery_fee' => null,
                    'in_store_pickup' => null,
                    'delivery_and_pickup' => null,
                    'hour_tracking' => null,
                    'hour_rate' => null,
                    'sale_price_daily' => null,
                    'sale_price_weekend' => null,
                    'sale_price_weekly' => null,
                    'sale_price_monthly' => null,
                ];
            } elseif ($validated['product_type'] === 'Rental') {
                $productData += [
                    'rental_daily' => $validated['rental_daily'] ?? null,
                    'rental_weekend' => $validated['rental_weekend'] ?? null,
                    'rental_weekly' => $validated['rental_weekly'] ?? null,
                    'rental_monthly' => $validated['rental_monthly'] ?? null,

                    'rental_damage_waiver_daily' => $validated['rental_damage_waiver_daily'] ?? null,
                    'rental_damage_waiver_weekend' => $validated['rental_damage_waiver_weekend'] ?? null,
                    'rental_damage_waiver_weekly' => $validated['rental_damage_waiver_weekly'] ?? null,
                    'rental_damage_waiver_monthly' => $validated['rental_damage_waiver_monthly'] ?? null,

                    'rental_prepaid_cleaning' => $validated['rental_prepaid_cleaning'] ?? null,
                    'rental_prepaid_fuel' => $validated['rental_prepaid_fuel'] ?? null,
                    'rental_fuel_gallons' => $validated['rental_fuel_gallons'] ?? null,
                    'rental_fuel_type' => $validated['rental_fuel_type'] ?? null,
                    'rental_def_gallons' => $validated['rental_def_gallons'] ?? null,

                    'standard_delivery_fee' => $validated['standard_delivery_fee'] ?? null,
                    'extended_delivery_fee' => $validated['extended_delivery_fee'] ?? null,
                    'in_store_pickup' => $validated['in_store_pickup'] ?? null,
                    'delivery_and_pickup' => $validated['delivery_and_pickup'] ?? null,
                    'hour_tracking' => $validated['hour_tracking'] ?? null,
                    'hour_rate' => $validated['hour_rate'] ?? null,

                    'sale_price_daily' => $validated['sale_price_daily'] ?? null,
                    'sale_price_weekend' => $validated['sale_price_weekend'] ?? null,
                    'sale_price_weekly' => $validated['sale_price_weekly'] ?? null,
                    'sale_price_monthly' => $validated['sale_price_monthly'] ?? null,

                    // Clear Retail fields
                    'retail_price' => null,
                    'retail_sale_price' => null,
                    'retail_product_cost' => null,
                ];
            }

            // Update product
            $product->update($productData);

            // Sync categories
            $product->categories()->sync($validated['categories'] ?? []);

            // Sync options
            $product->options()->sync($validated['options'] ?? []);

            // Sync related products
            $product->relatedProducts()->sync($validated['related_products'] ?? []);

            // Replace custom terms if Custom selected
            if (!empty($validated['is_custom_term_type']) && !empty($validated['terms'])) {
                $terms = is_array($validated['terms']) ? $validated['terms'] : [$validated['terms']];
                $product->terms()->sync($terms); // assuming $terms are IDs
            } else {
                $product->terms()->detach();
            }

            // --- HOVER IMAGE HANDLING ---
            if ($request->hasFile('hover_image')) {

                $mediaData = MediaHelper::uploadStorageFile('Public Asset', $request->file('hover_image'), 'products', $product);
                if (!empty($mediaData['mediaObj'])) {
                    $product->update(['media_id' => $mediaData['mediaObj']->id]);
                }
            } elseif (empty($validated['existing_hover_image'])) {
                // If no new image and no old image to keep, clear the field
                if ($product->media) {
                    MediaHelper::removeFile($product->media);
                }
                $product->update(['media_id' => null]);
            }

            // --- MAIN IMAGES HANDLING ---
            $existingImageIds = $validated['existing_images'] ?? [];

            // Delete removed main images
            $product
                ->mediaChildren
                ->whereNotIn('id', $existingImageIds)
                ->each(function ($image) {
                    MediaHelper::removeFile($image->media);
                    $image->delete();
                });

            // Upload and attach additional product images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $mediaData = MediaHelper::uploadStorageFile('Public Asset', $image, 'products', $product);
                    if (!empty($mediaData['mediaObj'])) {
                        ProductMediaChild::create([
                            'product_id' => $product->id,
                            'media_id' => $mediaData['mediaObj']->id,
                        ]);
                    }
                }
            }


            DB::commit();

            // Determine redirect target
            $action = $request->input('action', 'save');
            $redirectUrl = match ($action) {
                'save'      => route('admin.product-management.products.edit', ['unique_id' => $product->unique_id]),
                'save_new'  => route('admin.product-management.products.create'),
                default     => route('admin.product-management.products.index'),
            };

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully.',
                'redirect_url' => $redirectUrl,
                'action' => $action
            ]);


            // flash('Product updated successfully.')->success();

            // return match ($request->input('action')) {
            //     'save' => redirect()->route('admin.product-management.products.edit', ['unique_id' => $product->unique_id]),
            //     'save_new' => redirect()->route('admin.product-management.products.create'),
            //     default => redirect()->route('admin.product-management.products.index'),
            // };
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            // Handle error for AJAX
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the product.'
            ], 500);

            // flash('Something went wrong while updating the product.')->error();

            // return redirect()
            //     ->back()
            //     ->withInput()
            //     ->withErrors(['error' => 'An error occurred while updating the product.']);
        }
    }
}
