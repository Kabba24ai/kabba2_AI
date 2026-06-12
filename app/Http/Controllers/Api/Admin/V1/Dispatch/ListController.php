<?php

namespace App\Http\Controllers\Api\Admin\V1\Dispatch;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\Admin\V1\Dispatch\ListRequest;
use App\Http\Resources\Api\Admin\V1\Dispatch\ListResource;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\JsonResponse;

class ListController extends BaseController
{
    /**
     * Dispatch list
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(ListRequest $request): JsonResponse
    {
        $v = $request->validated();

        $query = OrderProduct::query()
            ->with([
                'equipment',
                'equipment.productcategory',
                'softAssignment.equipment',
                'order',
                'order.customer',
                'order.shippingAddress',
                'order.lastPayment',
                'order.notes',
                'product.categories',
                'deliveryEmployee',
                'pickupEmployee',
                'deliveryStore',
                'pickupStore',
            ])
            ->where('product_data->product_type', 'Rental')
            ->whereHas('order')
            ->whereNotNull('delivery_date');

        // --- Text search filters ---
        if (!empty($v['order_number'])) {
            $query->whereHas('order', function ($q) use ($v) {
                $q->where('order_number', 'like', '%' . $v['order_number'] . '%')
                  ->orWhere('reference_order_number', 'like', '%' . $v['order_number'] . '%');
            });
        }

        if (!empty($v['customer_name'])) {
            $query->whereHas('order.shippingAddress', function ($q) use ($v) {
                $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$v['customer_name']}%"]);
            });
        }

        if (!empty($v['customer_company_name'])) {
            $query->whereHas('order', function ($q) use ($v) {
                $q->where('company_name', 'like', '%' . $v['customer_company_name'] . '%');
            });
        }

        if (!empty($v['customer_phone'])) {
            $query->whereHas('order.shippingAddress', function ($q) use ($v) {
                $q->where('phone', 'like', '%' . $v['customer_phone'] . '%');
            });
        }

        if (!empty($v['category'])) {
            $query->whereHas('product.categories', function ($q) use ($v) {
                $q->where('product_categories.id', $v['category']);
            });
        }

        if (!empty($v['payment_method']) && $v['payment_method'] !== 'All Methods') {
            $query->whereHas('order.lastPayment', function ($q) use ($v) {
                $q->where('payment_method', $v['payment_method']);
            });
        }

        if (!empty($v['payment_status']) && $v['payment_status'] !== 'All Status') {
            $query->whereHas('order.lastPayment', function ($q) use ($v) {
                $q->where('status', $v['payment_status']);
            });
        }

        // --- Schedule type + transport mode ---
        $scheduleTypes      = array_filter($v['schedule_type'] ?? [], fn($s) => $s !== '');
        $showAll            = (bool) ($v['show_all'] ?? false);
        $isDeliverySelected = in_array('Delivery', $scheduleTypes);
        $isReturnSelected   = in_array('Return',   $scheduleTypes);
        $isBothSelected     = $isDeliverySelected && $isReturnSelected;
        $isReturnOnly       = $isReturnSelected  && !$isDeliverySelected;
        $isDeliveryOnly     = $isDeliverySelected && !$isReturnSelected;

        if (!empty($scheduleTypes) && !$showAll) {
            $query->where(function ($q) use ($isDeliverySelected, $isReturnSelected, $isReturnOnly) {
                if ($isDeliverySelected) {
                    $q->where('delivery_status', 'Pending');
                }
                if ($isReturnSelected) {
                    if ($isReturnOnly) {
                        $q->where('pickup_status', 'Pending')
                          ->where('delivery_status', 'Completed');
                    } else {
                        $q->orWhere('pickup_status', 'Pending');
                    }
                }
            });
        }

        if (empty($scheduleTypes)) {
            $query->where(function ($q) {
                $q->where('delivery_transport_mode', 'Truck')
                  ->orWhere('pickup_transport_mode', 'Truck');
            });
            if (!$showAll) {
                $query->where(function ($q) {
                    $q->where('delivery_status', '!=', 'Completed')
                      ->orWhere('pickup_status', '!=', 'Completed');
                });
            }
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

        // --- Store location filter ---
        $storeLocations = array_filter($v['store_location'] ?? [], fn($s) => $s !== '');
        if (!empty($storeLocations)) {
            if (!empty($scheduleTypes)) {
                $query->where(function ($q) use ($isDeliverySelected, $isReturnSelected, $storeLocations) {
                    if ($isDeliverySelected) {
                        $q->orWhereIn('delivery_store_id', $storeLocations);
                    }
                    if ($isReturnSelected) {
                        $q->orWhereIn('pickup_store_id', $storeLocations);
                    }
                });
            } else {
                $query->where(function ($q) use ($storeLocations) {
                    $q->whereIn('delivery_store_id', $storeLocations)
                      ->orWhereIn('pickup_store_id', $storeLocations);
                });
            }
        }

        // --- Driver filter ---
        if (!empty($v['driver_id'])) {
            $driverId = (int) $v['driver_id'];
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

        // --- Date filter ---
        if (!empty($v['date_filter'])) {
            $dateFilter   = $v['date_filter'];
            $useBothDates = $isBothSelected || empty($scheduleTypes);
            $dateField    = $isReturnOnly ? 'pickup_date' : 'delivery_date';

            if ($useBothDates) {
                match ($dateFilter) {
                    'today' => $query->where(function ($q) {
                        $q->whereDate('delivery_date', '<=', today())
                          ->orWhereDate('pickup_date', '<=', today());
                    }),
                    'week'  => $query->where(function ($q) {
                        $q->whereBetween('delivery_date', [now()->startOfWeek(), now()->endOfWeek()])
                          ->orWhereBetween('pickup_date', [now()->startOfWeek(), now()->endOfWeek()]);
                    }),
                    'month' => $query->where(function ($q) {
                        $q->whereMonth('delivery_date', now()->month)
                          ->orWhereMonth('pickup_date', now()->month);
                    }),
                    default => null,
                };
            } else {
                match ($dateFilter) {
                    'today' => $query->whereDate($dateField, '<=', today()),
                    'week'  => $query->whereBetween($dateField, [now()->startOfWeek(), now()->endOfWeek()]),
                    'month' => $query->whereMonth($dateField, now()->month),
                    default => null,
                };
            }
        }

        $viewMode = $v['view_mode'] ?? 'combined';
        $perPage  = $v['per_page']  ?? 30;

        // --- Split view: two independent sorted lists ---
        if ($viewMode === 'split') {
            $deliveries = (clone $query)
                ->where('delivery_transport_mode', 'Truck')
                ->orderByRaw('delivery_priority IS NULL, delivery_priority ASC')
                ->orderBy('delivery_date', 'asc')
                ->get();

            $returns = (clone $query)
                ->where('pickup_transport_mode', 'Truck')
                ->where('delivery_status', 'Completed')
                ->orderByRaw('pickup_priority IS NULL, pickup_priority ASC')
                ->orderBy('pickup_date', 'asc')
                ->get();

            return response()->json([
                'success'    => true,
                'view_mode'  => 'split',
                'deliveries' => ListResource::collection($deliveries),
                'returns'    => ListResource::collection($returns),
                'total'      => $deliveries->count() + $returns->count(),
            ]);
        }

        // --- Combined view: single paginated sorted list ---
        $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;

        if ($isBothSelected || empty($scheduleTypes)) {
            $orderProducts = $query
                ->orderByRaw("LEAST(COALESCE(delivery_priority, 9999), COALESCE(pickup_priority, 9999)) ASC")
                ->orderByRaw("LEAST(COALESCE(delivery_date, '9999-12-31'), COALESCE(pickup_date, '9999-12-31')) ASC")
                ->paginate($perPageVal)->withQueryString();
        } elseif ($isReturnOnly) {
            $orderProducts = $query
                ->orderByRaw('pickup_priority IS NULL, pickup_priority ASC')
                ->orderBy('pickup_date', 'asc')
                ->paginate($perPageVal)->withQueryString();
        } else {
            $orderProducts = $query
                ->orderByRaw('delivery_priority IS NULL, delivery_priority ASC')
                ->orderBy('delivery_date', 'asc')
                ->paginate($perPageVal)->withQueryString();
        }

        return response()->json([
            'success'   => true,
            'view_mode' => 'combined',
            'data'      => ListResource::collection($orderProducts),
            'meta'      => [
                'total'        => $orderProducts->total(),
                'per_page'     => $orderProducts->perPage(),
                'current_page' => $orderProducts->currentPage(),
                'last_page'    => $orderProducts->lastPage(),
                'from'         => $orderProducts->firstItem(),
                'to'           => $orderProducts->lastItem(),
            ],
        ]);
    }
}
