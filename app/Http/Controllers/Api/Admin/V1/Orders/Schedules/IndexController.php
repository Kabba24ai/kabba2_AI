<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\Schedules;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\Schedules\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\OrderProducts\ListResource;

// Model
use App\Models\Orders\OrderProduct;

class IndexController extends BaseController
{
    /**
     * Orders Schedule List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request)
    {
        $validatedData = $request->validated();

        $perPage = $validatedData['per_page'] ?? 10;
        $search = $validatedData['search'] ?? null;
        $categoryId = $validatedData['category_id'] ?? null;
        $scheduleType = $validatedData['schedule_type'] ?? null;
        $scheduleStatus = $validatedData['schedule_status'] ?? null;
        $transportMode = $validatedData['transport_mode'] ?? null;

        $orders = OrderProduct::query()
            ->with('order', 'order.customer', 'order.shippingAddress', 'order.billingAddress', 'order.lastPayment', 'deliveryMedia', 'pickupMedia', 'equipment','deliveryStore','pickupStore')
            ->where('product_data->product_type', 'Rental')
            ->whereNotNull('delivery_date')
            ->when($search, function ($query) use ($search) {
                $query->whereHas('order.shippingAddress', function ($q) use ($search) {
                    $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$search}%"]);
                });
            })
            ->when(
                $categoryId,
                fn($query) => $query->whereHas('product.categories', function ($q) use ($categoryId) {
                    $q->where('product_categories.id', $categoryId);
                }),
            )
            ->when(
                $scheduleType && $scheduleStatus,
                function ($query) use ($scheduleType, $scheduleStatus, $transportMode) {
                    if ($scheduleType === "Delivery") {
                        $query->where('delivery_status', $scheduleStatus);
                        if ($transportMode && $transportMode !== 'All') {
                            $query->where('delivery_transport_mode', $transportMode);
                        }
                    } elseif ($scheduleType === "Return") {
                        $query->where('pickup_status', $scheduleStatus);
                        if ($transportMode && $transportMode !== 'All') {
                            $query->where('pickup_transport_mode', $transportMode);
                        }
                    }
                }
            )
            ->orderBy('delivery_date', 'asc')
            ->paginate($perPage);

        if ($orders->isEmpty()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.orders.schedules_not_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.schedules_found'),
            'orders' => ListResource::collection($orders),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }
}
