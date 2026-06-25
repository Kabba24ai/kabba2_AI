<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\Schedules;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\Admin\V1\Orders\Schedules\DispatchRequest;
use App\Http\Resources\Api\Admin\V1\OrderProducts\ListResource;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\JsonResponse;

class DispatchDriverController extends BaseController
{
    /**
     * Orders Dispatch Driver List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(DispatchRequest $request)
    {
        $validatedData = $request->validated();

        $search       = $validatedData['search']      ?? null;
        $categoryId   = $validatedData['category_id'] ?? null;
        $scheduleType = $validatedData['schedule_type'] ?? null;
        $dateFilter   = $validatedData['date_filter']  ?? null;
        $driverId     = isset($validatedData['driver_id']) ? (int) $validatedData['driver_id'] : null;

        $scheduleTypes      = $scheduleType ? ($scheduleType === 'All' ? ['Delivery', 'Return'] : [$scheduleType]) : [];
        $isDeliverySelected = in_array('Delivery', $scheduleTypes);
        $isReturnSelected   = in_array('Return',   $scheduleTypes);
        $isReturnOnly       = $isReturnSelected && !$isDeliverySelected;
        $isDeliveryOnly     = $isDeliverySelected && !$isReturnSelected;

        // Fetch active drivers, optionally scoped to a single driver_id
        $driversQuery = User::active()->where('is_driver', true)->orderBy('first_name');
        if ($driverId) {
            $driversQuery->where('id', $driverId);
        }
        $drivers   = $driversQuery->get();
        $driverIds = $drivers->pluck('id');

        $with = [
            'order', 'order.customer', 'order.shippingAddress', 'order.lastPayment', 'order.notes',
            'equipment', 'equipment.store', 'equipment.productcategory',
            'deliveryStore', 'pickupStore', 'deliveryEmployee', 'pickupEmployee',
        ];

        // ── Shared filter helper ────────────────────────────────────────────
        $applyCommonFilters = function ($q) use ($search, $categoryId) {
            $q->where('product_data->product_type', 'Rental')
              ->whereHas('order')
              ->whereNotNull('delivery_date')
              ->where('delivery_status', '!=', 'Reschedule')
              ->where('pickup_status',   '!=', 'Reschedule');

            if ($search) {
                $q->whereHas('order.shippingAddress', fn($s) =>
                    $s->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$search}%"])
                );
            }

            if ($categoryId) {
                $q->whereHas('product.categories', fn($s) =>
                    $s->where('product_categories.id', $categoryId)
                );
            }
        };

        $applyDateFilter = function ($q, string $field, string $filter) {
            match ($filter) {
                'Today'      => $q->whereDate($field, '<=', today()),
                'Tomorrow'   => $q->whereDate($field, now()->addDay()->toDateString()),
                'This Week'  => $q->whereBetween($field, [now()->startOfWeek(), now()->endOfWeek()]),
                'This Month' => $q->whereMonth($field, now()->month),
                default      => null,
            };
        };

        // ── Delivery jobs ───────────────────────────────────────────────────
        $deliveryJobs = collect();

        if (!$isReturnOnly) {
            $dq = OrderProduct::with($with)
                ->whereIn('delivery_by', $driverIds)
                ->where('delivery_status', 'Pending')
                ->where('delivery_transport_mode', 'Truck')
                ->whereNotNull('delivery_date');

            $applyCommonFilters($dq);

            if ($dateFilter && $dateFilter !== 'All') {
                $applyDateFilter($dq, 'delivery_date', $dateFilter);
            }

            $deliveryJobs = $dq
                ->orderByRaw('delivery_priority IS NULL, delivery_priority ASC')
                ->orderBy('delivery_date')
                ->get()
                ->each(fn($j) => $j->setAttribute('_slot', 'delivery'))
                ->groupBy('delivery_by');
        }

        // ── Return (pickup) jobs ────────────────────────────────────────────
        $returnJobs = collect();

        if (!$isDeliveryOnly) {
            $rq = OrderProduct::with($with)
                ->whereIn('pickup_by', $driverIds)
                ->where('pickup_status', 'Pending')
                ->where('pickup_transport_mode', 'Truck')
                ->whereNotNull('pickup_date');

            $applyCommonFilters($rq);

            if ($dateFilter && $dateFilter !== 'All') {
                $applyDateFilter($rq, 'pickup_date', $dateFilter);
            }

            $returnJobs = $rq
                ->orderByRaw('pickup_priority IS NULL, pickup_priority ASC')
                ->orderBy('pickup_date')
                ->get()
                ->each(fn($j) => $j->setAttribute('_slot', 'return'))
                ->groupBy('pickup_by');
        }

        // ── Build driver-grouped response ───────────────────────────────────
        $result = $drivers->map(function ($driver) use ($deliveryJobs, $returnJobs) {
            $deliveries = $deliveryJobs->get($driver->id, collect());
            $returns    = $returnJobs->get($driver->id, collect());

            $combined = $deliveries->concat($returns)
                ->sortBy(fn($job) => $job->getAttribute('_slot') === 'delivery'
                    ? ($job->delivery_priority ?? 9999)
                    : ($job->pickup_priority   ?? 9999))
                ->values();

            return [
                'id'         => $driver->id,
                'full_name'  => $driver->full_name,
                'email'      => $driver->email,
                'total_jobs' => $combined->count(),
                'combined_jobs' => $combined->map(fn($job) => [
                    'slot'          => $job->getAttribute('_slot'),
                    'priority'      => $job->getAttribute('_slot') === 'delivery'
                                        ? $job->delivery_priority
                                        : $job->pickup_priority,
                    'order_product' => new ListResource($job),
                ])->values(),
            ];
        })->values();

        if ($result->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.api.admin.v1.orders.dispatch_schedules_not_found'),
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.dispatch_schedules_found'),
            'drivers' => $result,
        ]);
    }
}
