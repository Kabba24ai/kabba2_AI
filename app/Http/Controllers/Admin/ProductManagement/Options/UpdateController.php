<?php

namespace App\Http\Controllers\Admin\ProductManagement\Options;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductManagement\Options\UpdateRequest;

// Models
use App\Models\ProductManagement\ProductOption;
use Illuminate\Support\Facades\DB;

class UpdateController extends Controller
{
    /**
     * Handle the incoming request to update a product option.
     *
     * @param  \App\Http\Requests\Admin\ProductManagement\Options\UpdateRequest  $request
     * @param  string  $unique_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(UpdateRequest $request, string $unique_id)
    {
        $validated = $request->validated();

        $productOption = ProductOption::where('unique_id', $unique_id)->firstOrFail();

        // Update main model
        $productOption->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
        ]);

        // Sync or re-save options (you may want to delete/recreate or update existing related rows)
        DB::transaction(function () use ($productOption, $validated) {
            // Remove old options (or update if you track them separately)
            $productOption->items()->delete();

            foreach ($validated['options'] as $option) {
                $productOption->items()->create([
                    'label' => $option['label'],
                    'daily' => $option['daily'] ?? null,
                    'weekend' => $option['weekend'] ?? null,
                    'weekly' => $option['weekly'] ?? null,
                    'monthly' => $option['monthly'] ?? null,
                    'retail_price' => $option['retail_price'] ?? null,
                    'charged' => $option['charged'],
                    'value' => $option['value'],
                    'comment' => $option['comment'] ?? null,
                    'accept_label' => $option['accept_label'] ?? null,
                    'decline_label' => $option['decline_label'] ?? null,
                ]);
            }
        });

        flash('Product Option updated successfully.')->success();

        $action = $request->input('action');

        return match ($action) {
            'save' => redirect()->route('admin.product-management.options.edit', ['unique_id' => $productOption->unique_id]),
            'save_new' => redirect()->route('admin.product-management.options.create'),
            default => back(),
        };
    }
}
