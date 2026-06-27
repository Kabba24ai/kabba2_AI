<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\Schedules;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\Admin\V1\Orders\Schedules\DispatchRequest;
use App\Http\Resources\Api\Admin\V1\OrderProducts\ListResource;
use App\Http\Resources\Api\Admin\V1\Users\ListResource as UsersListResource;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\JsonResponse;

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

        $perPage      = $validatedData['per_page']      ?? 10;
        $search       = $validatedData['search']        ?? null;
        $categoryId   = $validatedData['category_id']   ?? null;
        $scheduleType = $validatedData['schedule_type'] ?? null;
        $dateFilter   = $validatedData['date_filter']   ?? null;
        $driverId     = $validatedData['driver_id']     ?? null;
        $scheduleTypes = [];

        // Fetch active drivers and use their IDs to scope the order query
        $drivers   = User::active()->where('is_driver', true)->orderBy('first_name')->get();
        $driverIds = $drivers->pluck('id');

        $query = OrderProduct::query()->with('equipment', 'equipment.store', 'equipment.productcategory', 'order', 'order.customer', 'product.categories', 'order.shippingAddress', 'order.billingAddress', 'order.lastPayment', 'order.notes', 'deliveryEmployee', 'pickupEmployee',  'deliveryStore', 'pickupStore')->where('product_data->product_type', 'Rental')->whereHas('order')->whereNotNull('delivery_date');

        $query->where('delivery_status', '!=', 'Reschedule')->where('pickup_status', '!=', 'Reschedule');

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

        // Scope to active drivers only
        $query->where(function ($q) use ($driverIds, $isDeliveryOnly, $isReturnOnly) {
            if ($isDeliveryOnly) {
                $q->whereIn('delivery_by', $driverIds);
            } elseif ($isReturnOnly) {
                $q->whereIn('pickup_by', $driverIds);
            } else {
                $q->whereIn('delivery_by', $driverIds)->orWhereIn('pickup_by', $driverIds);
            }
        });

        // Driver filter — scope by slot(s) matching the schedule type selection
        if ($driverId) {
            $driverId = (int) $driverId;
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
                    // Couple status + date: only show actionable pending items due today or overdue
                    $query->where(function ($q) {
                        $q->where(function ($sub) {
                            $sub->where('delivery_status', 'Pending')
                                ->whereDate('delivery_date', '<=', today());
                        })->orWhere(function ($sub) {
                            $sub->where('pickup_status', 'Pending')
                                ->whereNotNull('pickup_date')
                                ->whereDate('pickup_date', '<=', today());
                        });
                    });
                } elseif ($dateFilter === "Tomorrow") {
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

        if ($isBothSelected || empty($scheduleTypes)) {
            $orderProducts = $query
                ->orderByRaw("LEAST(COALESCE(delivery_priority, 9999), COALESCE(pickup_priority, 9999)) ASC")
                ->orderByRaw("LEAST(COALESCE(delivery_date, '9999-12-31'), COALESCE(pickup_date, '9999-12-31')) ASC")
                ->paginate($perPage)->withQueryString();
        } elseif ($isReturnOnly) {
            $orderProducts = $query
                ->orderByRaw('pickup_priority IS NULL, pickup_priority ASC')
                ->orderBy('pickup_date', 'asc')
                ->paginate($perPage)->withQueryString();
        } else {
            $orderProducts = $query
                ->orderByRaw('delivery_priority IS NULL, delivery_priority ASC')
                ->orderBy('delivery_date', 'asc')
                ->paginate($perPage)->withQueryString();
        }

        if ($orderProducts->isEmpty()) {
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
            'orders'  => ListResource::collection($orderProducts),
            'pagination' => [
                'current_page' => $orderProducts->currentPage(),
                'last_page' => $orderProducts->lastPage(),
                'per_page' => $orderProducts->perPage(),
                'total' => $orderProducts->total(),
            ],
        ]);
    }
}
