<?php

namespace App\Http\Controllers\Admin\ProductManagement\Options\Concerns;

use App\Helpers\MediaHelper;
use App\Models\ProductManagement\ProductOption;
use App\Models\ProductManagement\ProductOptionGroup;

trait PersistsProductOptionGroups
{
    /**
     * Create/update the option groups submitted alongside a Product Option's
     * items, keeping only the groups resubmitted in this request.
     *
     * @param  ProductOption  $productOption
     * @param  array  $groups  Validated 'groups' payload.
     * @param  array<string,int>  $rowKeyToItemId  Maps each option row's client-side row_key to its saved item id.
     */
    private function saveProductOptionGroups(ProductOption $productOption, array $groups, array $rowKeyToItemId, $request)
    {
        $submittedGroupIds = [];

        foreach ($groups as $index => $group) {
            $itemIds = collect($group['item_row_keys'])
                ->map(fn ($rowKey) => $rowKeyToItemId[$rowKey] ?? null)
                ->filter()
                ->values()
                ->all();

            if (empty($itemIds)) {
                continue;
            }

            $id = $group['id'] ?? null;
            $objGroup = $id ? $productOption->groups()->find($id) : null;

            $attributes = [
                'name' => $group['name'],
                'message' => $group['message'] ?? null,
                'sort_order' => $index,
            ];

            if ($objGroup) {
                $objGroup->update($attributes);
            } else {
                $objGroup = $productOption->groups()->create($attributes);
            }

            $imageFile = $request->file("groups.$index.image");
            if ($imageFile) {
                $mediaData = MediaHelper::uploadStorageFile('Public Asset', $imageFile, 'option-groups', $objGroup);
                if (!empty($mediaData['mediaObj'])) {
                    $objGroup->update(['media_id' => $mediaData['mediaObj']->id]);
                }
            } elseif (!empty($group['use_default_image'])) {
                $objGroup->update(['media_id' => null]);
            }

            $objGroup->items()->sync($itemIds);

            $submittedGroupIds[] = $objGroup->id;
        }

        $productOption
            ->groups()
            ->whereNotIn('id', $submittedGroupIds)
            ->get()
            ->each(fn (ProductOptionGroup $group) => $group->delete());
    }
}
