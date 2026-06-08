<?php

namespace App\Http\Controllers\Api\Admin\V1\Customers\Tags;

use App\Http\Controllers\Api\BaseController;

// Request
use App\Http\Requests\Api\Admin\V1\Customers\Tags\AssignTagsRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\CustomerTags\ListResource;

// Models
use App\Models\Customers\Customer;
use App\Models\Customers\Tag;

class AssignController extends BaseController
{
    /**
     * Assign Multiple Tags to a Customer
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(AssignTagsRequest $request)
    {

        $validated = $request->validated();

        $customer = Customer::where('unique_id', $validated['customer_unique_id'])->first();

        $existingTagIds = [];

        if (! empty($customer->tags)) {
            $decodedTags = json_decode($customer->tags, true);

            $existingTagIds = is_array($decodedTags)
                ? $decodedTags
                : explode(',', $customer->tags);
        }

        $mergedTagIds = array_values(array_unique(array_filter(array_merge(
            $existingTagIds,
            $validated['tag_ids']
        ))));

        // Keep old tags and append new unique tags only
        $customer->tags = json_encode($mergedTagIds);
        $customer->save();

        return response()->json([
            'success'  => true,
            'message'  => trans('messages.api.admin.v1.customers.tags.tags_assigned_successfully'),
        ]);
    }
}
