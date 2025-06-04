<?php

namespace App\Http\Controllers\Admin\ProductManagement\Options;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductManagement\Options\StoreRequest;
use App\Models\ProductManagement\ProductOption;
use App\Models\ProductManagement\ProductOptionItem;
use Illuminate\Support\Facades\DB;

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
            // Create the product option (main record)
            $productOption = ProductOption::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
            ]);

            // Store each option item
            foreach ($validated['options'] as $option) {
                ProductOptionItem::create([
                    'product_option_id' => $productOption->id,
                    'label' => $option['label'],
                    'daily' => $option['daily'] ?? 0,
                    'weekend' => $option['weekend'] ?? 0,
                    'weekly' => $option['weekly'] ?? 0,
                    'monthly' => $option['monthly'] ?? 0,
                    'retail_price' => $option['retail_price'] ?? 0,
                    'charged' => $option['charged'],
                    'value' => $option['value'],
                    'comment' => $option['comment'] ?? null,
                    'accept_label' => $option['accept_label'] ?? null,
                    'decline_label' => $option['decline_label'] ?? null,
                ]);
            }

            DB::commit();

            flash('Product Option created successfully.')->success();

            return match ($request->input('action')) {
                'save' => redirect()->route('admin.product-management.options.edit', ['unique_id' => $productOption->unique_id]),
                'save_new' => redirect()->route('admin.product-management.options.create'),
                default => back(),
            };
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            flash('Something went wrong while saving.')->error();
            return back()->withInput();
        }
    }
}
