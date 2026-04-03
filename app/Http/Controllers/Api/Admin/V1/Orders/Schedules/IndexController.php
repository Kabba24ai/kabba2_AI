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
        $dateFilter = $validatedData['date_filter'] ?? null;

        $orders = OrderProduct::query()
            ->with('order', 'order.customer', 'order.shippingAddress', 'order.billingAddress', 'order.lastPayment', 'deliveryMedia', 'pickupMedia', 'equipment','deliveryStore','pickupStore')
            ->where('product_data->product_type', 'Rental')
            ->whereNotNull('delivery_date')
            ->where(function ($q) {
                $q->where(function ($subQ) {
                   $subQ->where('delivery_status', '!=', 'Completed')->orWhere('pickup_status', '!=', 'Completed');
                })->whereNot(function ($subQ) {
                   $subQ->where('delivery_status', 'Completed')->where('pickup_status', 'Completed');
                });
            })
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
                        $query->where('pickup_status', $scheduleStatus)
                              ->where('delivery_status', 'Completed');
                        if ($transportMode && $transportMode !== 'All') {
                            $query->where('pickup_transport_mode', $transportMode);
                        }
                    }
                }
            )
            ->when(
                $dateFilter && $dateFilter !== 'All',
                function ($query) use ($dateFilter, $scheduleType) {
                    if ($scheduleType === "Delivery") {
                        if ($dateFilter === "Today") {
                            $query->whereDate('delivery_date', "<=", today());
                        } elseif ($dateFilter === "Tomorrow") {
                            $query->whereDate('delivery_date', now()->addDay()->toDateString());
                        } elseif ($dateFilter === "This Week") {
                            $query->whereBetween('delivery_date', [now()->startOfWeek(), now()->endOfWeek()]);
                        } elseif ($dateFilter === "This Month") {
                            $query->whereBetween('delivery_date', [now()->startOfMonth(), now()->endOfMonth()]);
                        }
                    } elseif ($scheduleType === "Return") {
                        if ($dateFilter === "Today") {
                            $query->whereDate('pickup_date', "<=", today());
                        } elseif ($dateFilter === "Tomorrow") {
                            $query->whereDate('pickup_date', now()->addDay()->toDateString());
                        } elseif ($dateFilter === "This Week") {
                            $query->whereBetween('pickup_date', [now()->startOfWeek(), now()->endOfWeek()]);
                        } elseif ($dateFilter === "This Month") {
                            $query->whereBetween('pickup_date', [now()->startOfMonth(), now()->endOfMonth()]);
                        }
                    }
                }
            )
            ->when(
                $scheduleType === "Return",
                fn($query) => $query->orderBy('pickup_date', 'asc'),
                fn($query) => $query->orderBy('delivery_date', $dateFilter === 'Today' ? 'desc' : 'asc')
            )
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
