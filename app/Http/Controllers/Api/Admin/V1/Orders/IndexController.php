<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\Orders\ListResource;

// Model
use App\Models\Orders\Order;

class IndexController extends BaseController
{
    /**
     * Orders List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request)
    {
        $validatedData = $request->validated();

        $perPage = $validatedData['per_page'] ?? 10;
        $status  = $validatedData['status'] ?? null;

        $orders = Order::query()->with('shippingAddress', 'products.product', 'lastPayment')
        ->when($status && $status !== 'All', fn($query) => $query->whereHas('lastPayment', function ($q) use ($status) {
            $q->where('status', $status);
        }))
        ->orderByDesc('id')
        ->paginate($perPage);

        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.api.admin.v1.orders.no_orders_found'),
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.orders_found'),
            'tournaments' => ListResource::collection($orders),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }
}
