<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Stores;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\Api\EmploymentApplication\V1\Stores\StoreResource;
use App\Models\Stores\Store;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ListStoresController extends BaseController
{
   public function __invoke(Request $request): JsonResponse
    {

           $stores = Store::with([
                'state',
                'hoursOfOperation',
            ])
            ->active()->get();

            return response()->json([
                'success' => true,
                'message' => 'Stores fetched successfully.',
                'data'    => StoreResource::collection($stores),
            ]);
    }

}
