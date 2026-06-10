<?php

namespace App\Http\Controllers\Api\Admin\V1\Dispatch;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\Admin\V1\Dispatch\IndexRequest;
use App\Helpers\CustomHelper;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\JsonResponse;

class IndexController extends BaseController
{
    /**
     * Dispatch job list for a driver — Deliveries + Returns combined, sorted by priority.
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request)
    {
        $validated  = $request->validated();
        $user       = auth('api_user')->user();
        $dateFilter = $validated['date_filter'] ?? 'Today';
        $driverId   = $validated['driver_id']   ?? $user->id;

        $baseWith = ['order.customer', 'order.shippingAddress', 'equipment', 'softAssignment.equipment'];

        // ---- Pending deliveries assigned to this driver ----
        $deliveries = OrderProduct::with(array_merge($baseWith, ['deliveryStore']))
            ->whereHas('order')
            ->where('product_data->product_type', 'Rental')
            ->whereNotNull('delivery_date')
            ->where('delivery_transport_mode', 'Truck')
            ->where('delivery_status', 'Pending')
            ->where('delivery_by', $driverId)
            ->when($dateFilter === 'Today',    fn($q) => $q->whereDate('delivery_date', '<=', today()))
            ->when($dateFilter === 'Tomorrow', fn($q) => $q->whereDate('delivery_date', now()->addDay()->toDateString()))
            ->orderByRaw('delivery_priority IS NULL, delivery_priority ASC')
            ->orderBy('delivery_date')
            ->get();

        // ---- Pending returns assigned to this driver (delivery already complete) ----
        $returns = OrderProduct::with(array_merge($baseWith, ['pickupStore']))
            ->whereHas('order')
            ->where('product_data->product_type', 'Rental')
            ->whereNotNull('delivery_date')
            ->where('pickup_transport_mode', 'Truck')
            ->where('pickup_status', 'Pending')
            ->where('delivery_status', 'Completed')
            ->where('pickup_by', $driverId)
            ->when($dateFilter === 'Today',    fn($q) => $q->whereDate('pickup_date', '<=', today()))
            ->when($dateFilter === 'Tomorrow', fn($q) => $q->whereDate('pickup_date', now()->addDay()->toDateString()))
            ->orderByRaw('pickup_priority IS NULL, pickup_priority ASC')
            ->orderBy('pickup_date')
            ->get();

        // Tag slot type so we can sort combined list
        $deliveries->each(fn($j) => $j->setAttribute('_slot', 'Delivery'));
        $returns->each(fn($j)    => $j->setAttribute('_slot', 'Return'));

        // Combine and sort by priority (nulls last), preserving delivery/return sub-order
        $combined = $deliveries->concat($returns)
            ->sortBy(fn($job) => $job->getAttribute('_slot') === 'Delivery'
                ? ($job->delivery_priority ?? 9999)
                : ($job->pickup_priority   ?? 9999))
            ->values();

        if ($combined->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No dispatch jobs found.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dispatch jobs found.',
            'jobs'    => $combined->map(fn($job) => $this->formatJob($job)),
            'total'   => $combined->count(),
        ]);
    }

    private function formatJob(OrderProduct $job): array
    {
        $slot       = $job->getAttribute('_slot');
        $isDelivery = $slot === 'Delivery';
        $addr       = $job->order?->shippingAddress;

        $addressParts = array_filter([
            $addr?->address  ?? '',
            $addr?->city     ?? '',
            $addr?->state    ?? '',
            $addr?->zip_code ?? '',
        ]);

        return [
            'id'           => $job->id,
            'unique_id'    => $job->unique_id,
            'product_name' => $job->product_name,
            'schedule_type' => $slot,
            'priority'     => $isDelivery ? $job->delivery_priority : $job->pickup_priority,

            'date'           => $isDelivery
                ? ($job->delivery_date ? CustomHelper::formatDate($job->delivery_date) : '')
                : ($job->pickup_date   ? CustomHelper::formatDate($job->pickup_date)   : ''),
            'time'           => $isDelivery
                ? ($job->delivery_time ? CustomHelper::formatTime($job->delivery_time) : '')
                : ($job->pickup_time   ? CustomHelper::formatTime($job->pickup_time)   : ''),
            'status'         => $isDelivery ? $job->delivery_status : $job->pickup_status,
            'transport_mode' => $isDelivery ? $job->delivery_transport_mode : $job->pickup_transport_mode,

            // Start point = where driver departs from; End point = where driver returns to
            'start_point' => $isDelivery ? ($job->deliveryStore?->store_name ?? 'Custom') : null,
            'end_point'   => !$isDelivery ? ($job->pickupStore?->store_name  ?? 'Custom') : null,

            'customer_name'  => $job->order?->customer_name ?? '',
            'customer_phone' => $addr?->phone ?? '',

            'address'      => implode(', ', $addressParts),
            'address_full' => $addr?->full_address ?? '',

            'equipment_name' => $job->equipment?->equipment_name
                ?? $job->softAssignment?->equipment?->equipment_name
                ?? '',

            'order_unique_id' => $job->order?->unique_id    ?? '',
            'order_number'    => $job->order?->order_number ?? '',
        ];
    }
}
