<?php

namespace App\Http\Controllers\Admin\ProductManagement\Options;

use App\Http\Controllers\Admin\ProductManagement\Options\Concerns\PersistsProductOptionGroups;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductManagement\Options\UpdateRequest;

// Models
use App\Models\ProductManagement\ProductOption;
use Illuminate\Support\Facades\DB;

class UpdateController extends Controller
{
    use PersistsProductOptionGroups;

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
        DB::transaction(function () use ($productOption, $validated, $request) {
            $submittedOptions = collect($validated['options']);

            // Get all existing item IDs from DB
            $existingItems = $productOption->items()->get()->keyBy('id');

            // Keep track of processed IDs
            $submittedIds = [];
            $rowKeyToItemId = [];

            foreach ($submittedOptions as $option) {
                $id = $option['id'] ?? null;

                // Prepare attributes
                $attributes = [
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
                    'sort_order' => $option['sort_order'] ?? 0,
                ];

                if ($id && $existingItems->has($id)) {
                    // Update existing
                    $existingItems[$id]->update($attributes);
                    $submittedIds[] = $id;
                    $item = $existingItems[$id];
                } else {
                    // Create new
                    $item = $productOption->items()->create($attributes);
                    $submittedIds[] = $item->id;
                }

                if (!empty($option['row_key'])) {
                    $rowKeyToItemId[$option['row_key']] = $item->id;
                }
            }

            // Only delete old items that were not submitted
            $productOption
                ->items()
                ->whereIn('id', $existingItems->keys()) // only from originally loaded items
                ->whereNotIn('id', $submittedIds)
                ->delete();

            if (!empty($validated['groups'])) {
                $this->saveProductOptionGroups($productOption, $validated['groups'], $rowKeyToItemId, $request);
            } else {
                $productOption->groups()->delete();
            }
        });

        flash('Product Option updated successfully.')->success();

        $action = $request->input('action');

        return match ($action) {
            'save' => redirect()->route('admin.product-management.options.edit', ['unique_id' => $productOption->unique_id]),
            default => redirect()->route('admin.product-management.options.index'),
        };
    }
}
