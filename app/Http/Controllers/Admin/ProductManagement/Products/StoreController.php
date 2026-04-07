<?php

namespace App\Http\Controllers\Admin\ProductManagement\Products;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

// Request
use App\Http\Requests\Admin\ProductManagement\Products\StoreRequest;

// Models
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductMediaChild;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // Create product record
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
                'truck_fee_size_setting' => $validated['truck_fee_size_setting'] ?? null,
                'track_insurance_size_setting' => $validated['track_insurance_size_setting'] ?? null,
                'prepaid_cleaning_rate_setting' => $validated['prepaid_cleaning_rate_setting'] ?? null,
                'prepaid_fuel_rate_setting' => $validated['prepaid_fuel_rate_setting'] ?? null,
                'is_tax_free_item' => $validated['is_tax_free_item'] ?? false,
                'apply_special_tax' => $validated['apply_special_tax'] ?? false,
                'apply_added_fees' => $validated['apply_added_fees'] ?? false,
            ];

            // Add fields based on product_type
            if ($validated['product_type'] === 'Retail') {
                $productData = array_merge($productData, [
                    'is_general_term_type' => false,
                    'is_custom_term_type' => false,

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
                    'rental_track_insurance_daily' => null,
                    'rental_track_insurance_weekend' => null,
                    'rental_track_insurance_weekly' => null,
                    'rental_track_insurance_monthly' => null,
                    'rental_prepaid_cleaning' => null,
                    'rental_prepaid_fuel' => null,
                    'standard_delivery_fee' => null,
                    'extended_delivery_fee' => null,
                    'in_store_pickup' => null,
                    'delivery_and_pickup' => null,
                    // 'hour_tracking' => null,
                    // 'hour_rate' => null,
                    'sale_price_daily' => null,
                    'sale_price_weekend' => null,
                    'sale_price_weekly' => null,
                    'sale_price_monthly' => null,
                    'related_product_price_daily' => null,
                    'related_product_price_weekend' => null,
                    'related_product_price_weekly' => null,
                    'related_product_price_monthly' => null,

                    // is_default_funnel field
                    'is_default_funnel' => false, // Rentals cannot be default funnel
                    'has_high_demand_alert' => false,
                ]);
            } elseif ($validated['product_type'] === 'Rental') {
                $productData = array_merge($productData, [
                    'is_general_term_type' => $validated['is_general_term_type'] ?? false,
                    'is_custom_term_type' => $validated['is_custom_term_type'] ?? false,

                    'rental_daily' => $validated['rental_daily'] ?? null,
                    'rental_weekend' => $validated['rental_weekend'] ?? null,
                    'rental_weekly' => $validated['rental_weekly'] ?? null,
                    'rental_monthly' => $validated['rental_monthly'] ?? null,

                    'rental_damage_waiver_daily' => $validated['rental_damage_waiver_daily'] ?? null,
                    'rental_damage_waiver_weekend' => $validated['rental_damage_waiver_weekend'] ?? null,
                    'rental_damage_waiver_weekly' => $validated['rental_damage_waiver_weekly'] ?? null,
                    'rental_damage_waiver_monthly' => $validated['rental_damage_waiver_monthly'] ?? null,

                    'rental_track_insurance_daily' => $validated['rental_track_insurance_daily'] ?? null,
                    'rental_track_insurance_weekend' => $validated['rental_track_insurance_weekend'] ?? null,
                    'rental_track_insurance_weekly' => $validated['rental_track_insurance_weekly'] ?? null,
                    'rental_track_insurance_monthly' => $validated['rental_track_insurance_monthly'] ?? null,

                    'rental_prepaid_cleaning' => $validated['rental_prepaid_cleaning'] ?? null,
                    'rental_prepaid_fuel' => $validated['rental_prepaid_fuel'] ?? null,

                    'standard_delivery_fee' => $validated['standard_delivery_fee'] ?? null,
                    'extended_delivery_fee' => $validated['extended_delivery_fee'] ?? null,

                    'in_store_pickup' => $validated['in_store_pickup'] ?? null,
                    'delivery_and_pickup' => $validated['delivery_and_pickup'] ?? 'No',
                    // 'hour_tracking' => $validated['hour_tracking'] ?? 'No',
                    // 'hour_rate' => $validated['hour_rate'] ?? null,

                    'sale_price_daily' => $validated['sale_price_daily'] ?? null,
                    'sale_price_weekend' => $validated['sale_price_weekend'] ?? null,
                    'sale_price_weekly' => $validated['sale_price_weekly'] ?? null,
                    'sale_price_monthly' => $validated['sale_price_monthly'] ?? null,

                    'related_product_price_daily' => $validated['related_product_price_daily'] ?? null,
                    'related_product_price_weekend' => $validated['related_product_price_weekend'] ?? null,
                    'related_product_price_weekly' => $validated['related_product_price_weekly'] ?? null,
                    'related_product_price_monthly' => $validated['related_product_price_monthly'] ?? null,

                    // Clear Retail fields
                    'retail_price' => null,
                    'retail_sale_price' => null,
                    'retail_product_cost' => null,

                    // is_default_funnel field
                    'is_default_funnel' => $validated['is_default_funnel'] ?? false,
                    'has_high_demand_alert' => $validated['has_high_demand_alert'] ?? false,
                ]);
            }

            // Finally, create the product
            $product = Product::create($productData);

            if (!empty($validated['funnels']) && is_array($validated['funnels'])) {
                $product->funnels()->sync($validated['funnels']);
            }else {
                $product->funnels()->detach();
            }

            // Save specific terms if custom is selected
            if (!empty($validated['is_custom_term_type']) && !empty($validated['terms'])) {
                $terms = is_array($validated['terms']) ? $validated['terms'] : [$validated['terms']];
                $product->terms()->sync($terms); // assuming $terms are IDs
            } else {
                $product->terms()->detach();
            }

            // ✅ Upload & associate hover image (single)
            if ($request->hasFile('hover_image')) {
                $mediaData = MediaHelper::uploadStorageFile('Public Asset', $request->file('hover_image'), 'products', $product);

                if (!empty($mediaData['mediaObj'])) {
                    $product->media_id = $mediaData['mediaObj']->id;
                    $product->save();
                }
            }

            // ✅ Upload & associate multiple main images to ProductMediaChild
            if ($request->hasFile('images')) {
                $position = 1;
                foreach ($request->file('images') as $image) {
                    $mediaData = MediaHelper::uploadStorageFile('Public Asset', $image, 'products', $product);
                    if (!empty($mediaData['mediaObj'])) {
                        ProductMediaChild::create([
                            'product_id' => $product->id,
                            'sort_order' => $position++,
                            'media_id' => $mediaData['mediaObj']->id,
                        ]);
                    }
                }
            }

            // Sync categories
            $product->categories()->sync($validated['categories'] ?? []);

            // Sync options
            $product->options()->sync($validated['options'] ?? []);

            // Sync related products
            if (isset($validated['related_products']) && is_array($validated['related_products'])) {
                // Prepare sync data with sort order
                $syncData = [];
                foreach ($validated['related_products'] as $index => $productId) {
                    $syncData[$productId] = ['sort_order' => $index + 1];
                }
                $product->relatedProducts()->sync($syncData);
            } else {
                $product->relatedProducts()->sync([]);
            }

            // Determine action before transaction commit
            $action = $request->input('action', 'save');
            $newProduct = null;

            // Save as new: duplicate the just-created product and all related data
            if ($action === 'save_new') {
                $product->load([
                    'categories',
                    'options',
                    'funnels',
                    'terms',
                    'relatedProducts',
                    'media',
                    'mediaChildren.media',
                ]);

                $newProduct = $product->replicate();
                $newProduct->product_name = $this->generateUniqueCopyName($product->product_name);
                $newProduct->save();

                $newProduct->categories()->sync($product->categories->pluck('id')->toArray());
                $newProduct->options()->sync($product->options->pluck('id')->toArray());
                $newProduct->funnels()->sync($product->funnels->pluck('id')->toArray());
                $newProduct->terms()->sync($product->terms->pluck('id')->toArray());

                $relatedSyncData = [];
                foreach ($product->relatedProducts as $relatedProduct) {
                    $relatedSyncData[$relatedProduct->id] = [
                        'sort_order' => $relatedProduct->pivot->sort_order ?? 0,
                    ];
                }
                $newProduct->relatedProducts()->sync($relatedSyncData);

                if ($product->media) {
                    $mediaData = MediaHelper::copyExistingMediaOnDisk(
                        $product->media,
                        'Public Asset',
                        'products',
                        $newProduct
                    );

                    if (!empty($mediaData['mediaObj'])) {
                        $newProduct->media_id = $mediaData['mediaObj']->id;
                        $newProduct->save();
                    }
                }

                foreach ($product->mediaChildren as $child) {
                    if (!$child->media) {
                        continue;
                    }

                    $mediaData = MediaHelper::copyExistingMediaOnDisk(
                        $child->media,
                        'Public Asset',
                        'products',
                        $newProduct
                    );

                    if (!empty($mediaData['mediaObj'])) {
                        ProductMediaChild::create([
                            'product_id' => $newProduct->id,
                            'sort_order' => $child->sort_order,
                            'media_id' => $mediaData['mediaObj']->id,
                        ]);
                    }
                }
            }

            DB::commit();

            // Determine redirect target
            $redirectUrl = match ($action) {
                'save'      => route('admin.product-management.products.edit', ['unique_id' => $product->unique_id]),
                'save_new'  => route('admin.product-management.products.edit', ['unique_id' => $newProduct?->unique_id ?? $product->unique_id]),
                default     => route('admin.product-management.products.index'),
            };

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.',
                'redirect_url' => $redirectUrl,
                'action' => $action
            ]);

            // flash('Product created successfully.')->success();

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


            // flash('something went wrong.')->error();

            // return redirect()
            //     ->back()
            //     ->withInput()
            //     ->withErrors(['error' => 'An error occurred while saving the product.']);
        }
    }

    private function generateUniqueCopyName(string $baseName): string
    {
        $firstCandidate = $baseName . ' (Copy)';
        if (!Product::where('product_name', $firstCandidate)->exists()) {
            return $firstCandidate;
        }

        $counter = 2;
        do {
            $candidate = $baseName . ' (Copy ' . $counter . ')';
            $counter++;
        } while (Product::where('product_name', $candidate)->exists());

        return $candidate;
    }
}
