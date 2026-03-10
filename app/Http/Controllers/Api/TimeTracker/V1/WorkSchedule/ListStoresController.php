<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule;

use App\Http\Controllers\Api\BaseController;
use App\Models\Stores\Store;
use Illuminate\Http\JsonResponse;

class ListStoresController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $stores = Store::active()
            ->orderByAdmin()
            ->get()
            ->map(function ($store) {
                return [
                    'id' => $store->id,
                    'name' => $store->store_name,
                    'address' => $store->full_address,
                    'is_primary' => $store->is_primary,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Stores fetched successfully.',
            'data' => $stores,
        ]);
    }
}