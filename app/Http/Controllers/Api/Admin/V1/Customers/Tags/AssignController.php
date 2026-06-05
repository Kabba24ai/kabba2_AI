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

        // Store as JSON array of tag IDs
        $customer->tags = json_encode(array_values(array_unique($validated['tag_ids'])));
        $customer->save();

        return response()->json([
            'success'  => true,
            'message'  => trans('messages.api.admin.v1.customers.tags.tags_assigned_successfully'),
        ]);
    }
}
