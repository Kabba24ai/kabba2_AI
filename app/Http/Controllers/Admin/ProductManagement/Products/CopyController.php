<?php

namespace App\Http\Controllers\Admin\ProductManagement\Products;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

// Helpers
use App\Helpers\MediaHelper;

// Models
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductMediaChild;

class CopyController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke($unique_id)
    {
        DB::beginTransaction();

        try {

            $product = Product::with(['categories', 'funnels', 'options', 'media',
                                      'mediaChildren.media', // ✅ important
                                      'terms', 'relatedProducts'
                                    ])->where('unique_id', $unique_id)->firstOrFail();

            $newProduct = $product->replicate();
            $newProduct->product_name = $product->product_name . ' (Copy)';
            $newProduct->save();

            // Copy relationships if any (e.g., categories, options)
            $newProduct->categories()->sync($product->categories->pluck('id')->toArray());
            $newProduct->options()->sync($product->options->pluck('id')->toArray());
            $newProduct->funnels()->sync($product->funnels->pluck('id')->toArray());
            $newProduct->terms()->sync($product->terms->pluck('id')->toArray());
            $newProduct->relatedProducts()->sync($product->relatedProducts->pluck('id')->toArray());

           // copy main image (media is single)
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

            // copy media children (multiple)
            $position = 0;

            foreach ($product->mediaChildren as $child) {

                // ensure child has media
                if (!$child->media) {
                    continue;
                }

                // copy the existing media file to a new file + create new Media row
                $mediaData = MediaHelper::copyExistingMediaOnDisk(
                    $child->media,
                    'Public Asset',
                    'products',
                    $newProduct
                );

                if (!empty($mediaData['mediaObj'])) {
                    ProductMediaChild::create([
                        'product_id' => $newProduct->id,
                        'sort_order' => $position++,
                        'media_id'   => $mediaData['mediaObj']->id,
                    ]);
                }
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to copy product: ' . $e->getMessage());
        }

        DB::commit();

        return redirect()->route('admin.product-management.products.edit', $newProduct->unique_id)
            ->with('success', 'Product copied successfully.');
    }
}
