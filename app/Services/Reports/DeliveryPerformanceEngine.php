<?php

namespace App\Services\Reports;

use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use Illuminate\Support\Collection;

/**
 * DeliveryPerformanceEngine — Dispatch-focused Driver Performance overview
 * for the Delivery Performance sales report.
 *
 * Two distinct kinds of numbers are mixed here on purpose:
 *  - Backlog / assignment coverage is a LIVE queue snapshot (delivery_status
 *    = 'Pending' is a current state, not a historical fact), so it ignores
 *    the report's date range and only respects the store filter.
 *  - Completed deliveries/returns per driver ARE scoped to the date range,
 *    showing dispatch throughput for the selected period.
 *
 * There is no captured timestamp for "when a driver was assigned" (delivery_by/
 * pickup_by only reflect current state), so true assignment turnaround can't
 * be measured — assignment coverage (% of pending jobs with a driver already
 * assigned) is used instead as the closest available proxy.
 *
 * Every query here is restricted to {delivery|pickup}_transport_mode = 'Truck'.
 * 'Store' mode is a counter pickup/return — delivery_by/pickup_by can still be
 * set on those rows, but the job never went through the Dispatch module or the
 * driver checklist flow, so counting it as dispatch performance is wrong. This
 * was the source of inflated completion counts before this filter was added
 * (e.g. one driver's YTD delivery count included 280 Store-mode rows that were
 * never actually dispatched).
 */
class DeliveryPerformanceEngine
{
    public function __construct(
        private SalesReportingService $reporting,
    ) {}

    // ─── Public API ───────────────────────────────────────────────────────────

    public function reportData(array $filters): array
    {
        [$start, $end] = $this->reporting->resolveDateRange($filters);
        $storeId = $filters['store'] ?? null;

        $backlog = $this->dispatchBacklog($storeId);
        $drivers = $this->driverPerformance($start, $end, $storeId);

        return [
            'kpis'    => $this->kpis($backlog),
            'backlog' => $backlog,
            'drivers' => $drivers,
        ];
    }

    public function availableDrivers(): Collection
    {
        return User::active()
            ->where('is_driver', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);
    }

    // ─── Private: Dispatch backlog (live queue, store-scoped only) ─────────────

    /**
     * Mirrors App\Http\Controllers\Admin\OrderManagement\Dispatch\DriverSummaryController:
     * Truck-mode jobs still Pending, split into assigned vs unassigned.
     * Returns only count once the delivery leg is Completed (nothing to pick
     * up before it's been dropped off).
     */
    private function dispatchBacklog(?int $storeId): array
    {
        $pendingDeliveriesQuery = OrderProduct::query()
            ->where('delivery_status', 'Pending')
            ->where('delivery_transport_mode', 'Truck')
            ->when($storeId, fn($q) => $q->where('delivery_store_id', $storeId));

        $pendingReturnsQuery = OrderProduct::query()
            ->where('pickup_status', 'Pending')
            ->where('pickup_transport_mode', 'Truck')
            ->where('delivery_status', 'Completed')
            ->when($storeId, fn($q) => $q->where('pickup_store_id', $storeId));

        $pendingDeliveries     = (clone $pendingDeliveriesQuery)->count();
        $unassignedDeliveries  = (clone $pendingDeliveriesQuery)->whereNull('delivery_by')->count();
        $pendingReturns        = (clone $pendingReturnsQuery)->count();
        $unassignedReturns     = (clone $pendingReturnsQuery)->whereNull('pickup_by')->count();

        return [
            'pending_deliveries'    => $pendingDeliveries,
            'unassigned_deliveries' => $unassignedDeliveries,
            'pending_returns'       => $pendingReturns,
            'unassigned_returns'    => $unassignedReturns,
        ];
    }

    // ─── Private: Per-driver performance ───────────────────────────────────────

    private function driverPerformance(?\Carbon\Carbon $start, ?\Carbon\Carbon $end, ?int $storeId): array
    {
        $drivers = $this->availableDrivers();
        if ($drivers->isEmpty()) {
            return [];
        }
        $driverIds = $drivers->pluck('id');

        $deliveries = $this->driverLegQuery('delivery', $driverIds, $start, $end, $storeId)
            ->selectRaw('order_products.delivery_by as driver_id, COUNT(*) as completed')
            ->groupBy('order_products.delivery_by')
            ->get()
            ->keyBy('driver_id');

        $returns = $this->driverLegQuery('pickup', $driverIds, $start, $end, $storeId)
            ->selectRaw('order_products.pickup_by as driver_id, COUNT(*) as completed')
            ->groupBy('order_products.pickup_by')
            ->get()
            ->keyBy('driver_id');

        $pendingDeliveries = OrderProduct::query()
            ->whereIn('delivery_by', $driverIds)
            ->where('delivery_status', 'Pending')
            ->where('delivery_transport_mode', 'Truck')
            ->when($storeId, fn($q) => $q->where('delivery_store_id', $storeId))
            ->selectRaw('delivery_by as driver_id, COUNT(*) as cnt')
            ->groupBy('delivery_by')
            ->pluck('cnt', 'driver_id');

        $pendingReturns = OrderProduct::query()
            ->whereIn('pickup_by', $driverIds)
            ->where('pickup_status', 'Pending')
            ->where('pickup_transport_mode', 'Truck')
            ->when($storeId, fn($q) => $q->where('pickup_store_id', $storeId))
            ->selectRaw('pickup_by as driver_id, COUNT(*) as cnt')
            ->groupBy('pickup_by')
            ->pluck('cnt', 'driver_id');

        return $drivers->map(function ($driver) use ($deliveries, $returns, $pendingDeliveries, $pendingReturns) {
            $d = $deliveries->get($driver->id);
            $r = $returns->get($driver->id);

            $deliveriesCompleted = (int) ($d->completed ?? 0);
            $returnsCompleted    = (int) ($r->completed ?? 0);
            $pendingDel          = (int) $pendingDeliveries->get($driver->id, 0);
            $pendingRet          = (int) $pendingReturns->get($driver->id, 0);

            return [
                'id'                   => $driver->id,
                'name'                 => trim("{$driver->first_name} {$driver->last_name}"),
                'deliveries_completed' => $deliveriesCompleted,
                'returns_completed'    => $returnsCompleted,
                'total_completed'      => $deliveriesCompleted + $returnsCompleted,
                'pending_deliveries'   => $pendingDel,
                'pending_returns'      => $pendingRet,
                'total_pending'        => $pendingDel + $pendingRet,
            ];
        })
        ->sortByDesc('total_pending')
        ->values()
        ->toArray();
    }

    private function driverLegQuery(string $leg, Collection $driverIds, ?\Carbon\Carbon $start, ?\Carbon\Carbon $end, ?int $storeId)
    {
        $byColumn      = $leg === 'delivery' ? 'delivery_by' : 'pickup_by';
        $doneColumn    = $leg === 'delivery' ? 'is_delivered' : 'is_returned';
        $storeColumn   = $leg === 'delivery' ? 'delivery_store_id' : 'pickup_store_id';
        $transportMode = $leg === 'delivery' ? 'delivery_transport_mode' : 'pickup_transport_mode';

        $query = OrderProduct::query()
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->whereNull('orders.deleted_at')
            ->whereNull('order_products.deleted_at')
            ->whereIn("order_products.$byColumn", $driverIds)
            // Truck only — Store-mode pickups/dropoffs are a counter
            // transaction, not a dispatched driver run.
            ->where("order_products.$transportMode", 'Truck')
            ->where("order_products.$doneColumn", 1)
            // Administrative closures set is_delivered/is_returned without
            // any physical run (cancelled bookings, refunded-before-delivery
            // orders) — never count them as driver throughput. A row whose
            // DELIVERY leg was administratively closed had no physical
            // delivery AND no physical return; a delivered row whose return
            // leg alone was administratively closed still counts as a real
            // delivery but not as a real return.
            ->where('order_products.delivery_status', '!=', OrderProduct::STATUS_CLOSE_AS_COMPLETED);

        if ($leg !== 'delivery') {
            $query->where('order_products.pickup_status', '!=', OrderProduct::STATUS_CLOSE_AS_COMPLETED);
        }

        if ($start && $end) {
            $query->whereBetween('orders.order_date', [$start->toDateString(), $end->toDateString()]);
        }
        if ($storeId) {
            $query->where("order_products.$storeColumn", $storeId);
        }

        return $query;
    }

    // ─── Private: KPI cards ─────────────────────────────────────────────────────

    private function kpis(array $backlog): array
    {
        $deliveryRate = $backlog['pending_deliveries'] > 0
            ? round((($backlog['pending_deliveries'] - $backlog['unassigned_deliveries']) / $backlog['pending_deliveries']) * 100, 1)
            : null;

        $returnRate = $backlog['pending_returns'] > 0
            ? round((($backlog['pending_returns'] - $backlog['unassigned_returns']) / $backlog['pending_returns']) * 100, 1)
            : null;

        return [
            'pending_deliveries'      => $backlog['pending_deliveries'],
            'pending_returns'         => $backlog['pending_returns'],
            'unassigned_deliveries'   => $backlog['unassigned_deliveries'],
            'unassigned_returns'      => $backlog['unassigned_returns'],
            'delivery_assignment_rate' => $deliveryRate,
            'return_assignment_rate'   => $returnRate,
        ];
    }
}
