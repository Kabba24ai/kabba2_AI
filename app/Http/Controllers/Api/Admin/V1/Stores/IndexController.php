<?php

namespace App\Http\Controllers\Api\Admin\V1\Stores;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Request;

// Resources
use App\Http\Resources\Api\Admin\V1\Stores\ListResource;

// Model
use App\Models\Iam\Personnel\User;
use App\Models\Stores\Store;

class IndexController extends BaseController
{
    /**
     * Stores List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(Request $request)
    {

        $stores = Store::active()->orderByAdmin()->get();

        if ($stores->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.api.admin.v1.stores.no_stores_found'),
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.stores.stores_found'),
            'stores' => ListResource::collection($stores),
        ]);
    }
}
