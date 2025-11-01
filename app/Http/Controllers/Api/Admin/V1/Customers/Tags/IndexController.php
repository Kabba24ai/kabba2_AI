<?php

namespace App\Http\Controllers\Api\Admin\V1\Customers\Tags;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Resources
use App\Http\Resources\Api\Admin\V1\CustomerTags\ListResource;

// Model
use App\Models\Customers\Tag;

class IndexController extends BaseController
{
    /**
     * Customer Tags List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke()
    {
        $customerTags = Tag::query()->get();

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.customers.tags.tags_found'),
            'customer_tags' => ListResource::collection($customerTags),
        ]);
    }
}
