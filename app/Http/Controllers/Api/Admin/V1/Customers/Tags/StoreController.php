<?php

namespace App\Http\Controllers\Api\Admin\V1\Customers\Tags;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Request
use App\Http\Requests\Api\Admin\V1\Customers\Tags\StoreRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\CustomerTags\ListResource;

// Model
use App\Models\Customers\Tag;

class StoreController extends BaseController
{
    /**
     * Create Customer Tags
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        $customerTag = Tag::create([
            'name' => $validated['name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.customers.tags.tag_created_successfully'),
            'customer_tag' => new ListResource($customerTag),
        ]);
    }
}
