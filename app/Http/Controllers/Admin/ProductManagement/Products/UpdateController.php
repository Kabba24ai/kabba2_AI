<?php

namespace App\Http\Controllers\Admin\ProductManagement\Products;

use App\Helpers\DeliveryTierHelper;
use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductManagement\Products\UpdateRequest;
use App\Models\MaintenanceManagement\Equipment;
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
            $slug = $validated['slug'] ?? null;
            if ($validated['product_type'] === 'Retail' && $slug !== null) {
                // Ensure slug does not end with '-rental'
                if (str_ends_with($slug, '-rental')) {
                    $slug = str_replace('-rental', '', $slug);
                }
            }

            // Base fields
            $productData = [
                'product_name' => $validated['product_name'],
                'product_type' => $validated['product_type'],
                'slug' => $slug,
                'short_description' => $validated['short_description'] ?? null,
                'description' => $validated['description'] ?? null,
                'seo_title' => $validated['seo_title'] ?? null,
                'seo_description' => $validated['seo_description'] ?? null,
                'sku' => $validated['sku'] ?? null,
                'barcode' => $validated['barcode'] ?? null,
                'status' => $validated['status'] ?? 'Pending',
                'truck_fee_size_setting' => $validated['truck_fee_size_setting'] ?? null,
                'track_insurance_size_setting' => $validated['track_insurance_size_setting'] ?? null,
                'tire_insurance_size_setting' => $validated['tire_insurance_size_setting'] ?? null,
                'prepaid_cleaning_rate_setting' => $validated['prepaid_cleaning_rate_setting'] ?? null,
                'prepaid_fuel_rate_setting' => $validated['prepaid_fuel_rate_setting'] ?? null,
                'is_tax_free_item' => $validated['is_tax_free_item'] ?? false,
                'apply_special_tax' => $validated['apply_special_tax'] ?? false,
                'apply_added_fees' => $validated['apply_added_fees'] ?? false,
                'hide_cc_payment_option' => $validated['hide_cc_payment_option'] ?? false,
            ];

            // Include only relevant fields and clear opposite-type fields
            if ($validated['product_type'] === 'Retail') {
                $productData += [
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
                    'rental_tire_insurance_daily' => null,
                    'rental_tire_insurance_weekend' => null,
                    'rental_tire_insurance_weekly' => null,
                    'rental_tire_insurance_monthly' => null,
                    'rental_prepaid_cleaning' => null,
                    'rental_prepaid_fuel' => null,
                    'standard_delivery_fee' => null,
                    'extended_delivery_fee' => null,
                    'custom_1_delivery_fee' => null,
                    'custom_2_delivery_fee' => null,
                    'custom_3_delivery_fee' => null,
                    'custom_4_delivery_fee' => null,
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
                    'is_default_funnel' => false,
                    'has_high_demand_alert' => false,
                ];
            } elseif ($validated['product_type'] === 'Rental') {
                $productData += [
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

                    'rental_tire_insurance_daily' => $validated['rental_tire_insurance_daily'] ?? null,
                    'rental_tire_insurance_weekend' => $validated['rental_tire_insurance_weekend'] ?? null,
                    'rental_tire_insurance_weekly' => $validated['rental_tire_insurance_weekly'] ?? null,
                    'rental_tire_insurance_monthly' => $validated['rental_tire_insurance_monthly'] ?? null,

                    'rental_prepaid_cleaning' => $validated['rental_prepaid_cleaning'] ?? null,
                    'rental_prepaid_fuel' => $validated['rental_prepaid_fuel'] ?? null,

                    'standard_delivery_fee' => $validated['standard_delivery_fee'] ?? null,
                    'extended_delivery_fee' => $validated['extended_delivery_fee'] ?? null,
                    // Submitted Custom rates win (blank = NULL); fields absent from the
                    // request fall back to the global rates for the selected size
                    ...DeliveryTierHelper::resolveCustomFees($validated),
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

                    'is_default_funnel' => $validated['is_default_funnel'] ?? false,
                    'has_high_demand_alert' => $validated['has_high_demand_alert'] ?? false,
                ];
            }

            // Update product
            $product->update($productData);

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

            // Replace custom terms if Custom selected
            if (!empty($validated['is_custom_term_type']) && !empty($validated['terms'])) {
                $terms = is_array($validated['terms']) ? $validated['terms'] : [$validated['terms']];
                $product->terms()->sync($terms); // assuming $terms are IDs
            } else {
                $product->terms()->detach();
            }

            // Sync funnels
            if (!empty($validated['funnels']) && is_array($validated['funnels'])) {
                $product->funnels()->sync($validated['funnels']);
            } else {
                $product->funnels()->detach();
            }

            // Sync direct equipment assignments
            $selectedEquipmentIds = $validated['assigned_equipment_ids'] ?? [];
            Equipment::where('assigned_product_id', $product->id)
                ->whereNotIn('id', $selectedEquipmentIds)
                ->update(['assigned_product_id' => null]);
            if (!empty($selectedEquipmentIds)) {
                Equipment::whereIn('id', $selectedEquipmentIds)
                    ->update(['assigned_product_id' => $product->id]);
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

            // Decode sort order JSON
            $imageOrder = json_decode($validated['image_sort_order'] ?? '[]', true);

            // Map into ['id-or-name' => sort_order]
            $ordered = [];
            $pos = 1;

            foreach ($imageOrder as $key) {
                // strip "new-" prefix if you want only the filename
                $cleanKey = str_starts_with($key, 'new-')
                    ? substr($key, 4)
                    : $key;

                $ordered[$cleanKey] = $pos++;
            }

            // Update existing
            foreach ($product->mediaChildren as $child) {
                $id = (string) $child->id;
                if (isset($ordered[$id])) {
                    $child->update(['sort_order' => $ordered[$id]]);
                }
            }

            // Upload and attach additional product images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $mediaData = MediaHelper::uploadStorageFile('Public Asset', $image, 'products', $product);
                    if (!empty($mediaData['mediaObj'])) {
                        ProductMediaChild::create([
                            'product_id' => $product->id,
                            'media_id' => $mediaData['mediaObj']->id,
                            'sort_order' => $ordered[$mediaData['mediaObj']->original_file_name] ?? 0,
                        ]);
                    }
                }
            }

            // Determine action before transaction commit
            $action = $request->input('action', 'save');
            $newProduct = null;

            // Save as new: duplicate the just-updated product and all related data
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
                            'media_id' => $mediaData['mediaObj']->id,
                            'sort_order' => $child->sort_order,
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
