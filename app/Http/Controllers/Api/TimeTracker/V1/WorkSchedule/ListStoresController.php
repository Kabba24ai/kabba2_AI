<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule;

use App\Http\Controllers\Api\BaseController;
use App\Models\Stores\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ListStoresController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $currentUser = Auth::user();

        $query = Store::active()->orderByAdmin();

        if (! $currentUser->isMasterAdmin()) {
            $query->where('id', $currentUser->store_id);
        }

        $stores = $query->get()
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