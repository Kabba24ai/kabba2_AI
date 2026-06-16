<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\Schedules;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\Schedules\DispatchRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\OrderProducts\ListResource;

// Model
use App\Models\Orders\OrderProduct;

class DispatchController extends BaseController
{
    /**
     * Orders Dispatch List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(DispatchRequest $request)
    {
        $validatedData = $request->validated();

        $perPage = $validatedData['per_page'] ?? 10;
        $search = $validatedData['search'] ?? null;
        $categoryId = $validatedData['category_id'] ?? null;
        $scheduleType = $validatedData['schedule_type'] ?? null;
        $dateFilter = $validatedData['date_filter'] ?? null;

        $query = OrderProduct::query()->with('equipment', 'equipment.productcategory', 'order', 'order.customer', 'product.categories', 'order.shippingAddress', 'order.billingAddress', 'order.lastPayment', 'order.notes', 'deliveryEmployee', 'pickupEmployee')->where('product_data->product_type', 'Rental')->whereHas('order')->whereNotNull('delivery_date');

        if ($search) {
            $query->whereHas('order.shippingAddress', function ($q) use ($search) {
                $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        if ($categoryId) {
            $query->whereHas('product.categories', function ($q) use ($categoryId) {
                $q->where('product_categories.id', $categoryId);
            });
        }

        if ($scheduleType) {
            $scheduleTypes = $scheduleType === 'All' ? ['Delivery', 'Return'] : [$scheduleType];
        }

        $isDeliverySelected = in_array('Delivery', $scheduleTypes);
        $isReturnSelected = in_array('Return', $scheduleTypes);
        $isBothSelected = $isDeliverySelected && $isReturnSelected;
        $isReturnOnly = $isReturnSelected && !$isDeliverySelected;
        $isDeliveryOnly = $isDeliverySelected && !$isReturnSelected;

        // Default (show_all=false): hide completed rows — only show actionable items
        if (!empty($scheduleTypes)) {
            $query->where(function ($q) use ($isDeliverySelected, $isReturnSelected, $isReturnOnly) {
                if ($isDeliverySelected) {
                    $q->where('delivery_status', 'Pending');
                }
                if ($isReturnSelected) {
                    if ($isReturnOnly) {
                        $q->where('pickup_status', 'Pending')->where('delivery_status', 'Completed');
                    } else {
                        $q->orWhere('pickup_status', 'Pending');
                    }
                }
            });
        }

        // Always restrict to Truck transport mode
        if (empty($scheduleTypes)) {
            $query->where(function ($q) {
                $q->where('delivery_transport_mode', 'Truck')->orWhere('pickup_transport_mode', 'Truck');
            });

            $query->where(function ($q) {
                $q->where('delivery_status', '!=', 'Completed')->orWhere('pickup_status', '!=', 'Completed');
            });
        } else {
            $query->where(function ($q) use ($isDeliverySelected, $isReturnSelected) {
                if ($isDeliverySelected) {
                    $q->where('delivery_transport_mode', 'Truck');
                }
                if ($isReturnSelected) {
                    $q->orWhere('pickup_transport_mode', 'Truck');
                }
            });
        }

        // Driver filter — scope by slot(s) matching the schedule type selection
        if (isset($validatedData['driver_id']) && !empty($validatedData['driver_id'])) {
            $driverId = (int) $validatedData['driver_id'];
            $query->where(function ($q) use ($driverId, $isDeliveryOnly, $isReturnOnly) {
                if ($isDeliveryOnly) {
                    $q->where('delivery_by', $driverId);
                } elseif ($isReturnOnly) {
                    $q->where('pickup_by', $driverId);
                } else {
                    $q->where('delivery_by', $driverId)->orWhere('pickup_by', $driverId);
                }
            });
        }

         // Date filter — reference the correct date column(s) per schedule selection
        if ($dateFilter && $dateFilter !== 'All') {
            $useBothDates = $isBothSelected || empty($scheduleTypes);
            $dateField    = $isReturnOnly ? 'pickup_date' : 'delivery_date';

            if ($useBothDates) {
                if ($dateFilter === 'Today') {
                    $query->where(function ($q) {
                        $q->whereDate('delivery_date', '<=', today())
                            ->orWhereDate('pickup_date', '<=', today());
                    });
                }  elseif ($dateFilter === "Tomorrow") {
                    $query->where(function ($q) {
                        $q->whereDate('delivery_date', now()->addDay()->toDateString())
                            ->orWhereDate('pickup_date', now()->addDay()->toDateString());
                    });
                } elseif ($dateFilter === 'This Week') {
                    $query->where(function ($q) {
                        $q->whereBetween('delivery_date', [now()->startOfWeek(), now()->endOfWeek()])
                            ->orWhereBetween('pickup_date', [now()->startOfWeek(), now()->endOfWeek()]);
                    });
                } elseif ($dateFilter === 'This Month') {
                    $query->where(function ($q) {
                        $q->whereMonth('delivery_date', now()->month)
                            ->orWhereMonth('pickup_date', now()->month);
                    });
                }
            } else {
                if ($dateFilter === 'Today') {
                    $query->whereDate($dateField, '<=', today());
                } elseif ($dateFilter === "Tomorrow") {
                    $query->whereDate($dateField, now()->addDay()->toDateString());
                } elseif ($dateFilter === 'This Week') {
                    $query->whereBetween($dateField, [now()->startOfWeek(), now()->endOfWeek()]);
                } elseif ($dateFilter === 'This Month') {
                    $query->whereMonth($dateField, now()->month);
                }
            }
        }

        $orders = $query->paginate($perPage);

        if ($orders->isEmpty()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.orders.dispatch_schedules_not_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.dispatch_schedules_found'),
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
