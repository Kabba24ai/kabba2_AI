<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;

class DriverSummaryController extends Controller
{
    public function __invoke()
    {
        $drivers = User::active()
            ->where('is_driver', true)
            ->orderBy('first_name')
            ->get();

        $driverIds = $drivers->pluck('id');

        // Pending delivery counts per driver (Truck only)
        $deliveryCounts = OrderProduct::query()
            ->whereIn('delivery_by', $driverIds)
            ->where('delivery_status', 'Pending')
            ->where('delivery_transport_mode', 'Truck')
            ->selectRaw('delivery_by as driver_id, COUNT(*) as cnt')
            ->groupBy('delivery_by')
            ->pluck('cnt', 'driver_id');

        // Pending return counts per driver (Truck only)
        $returnCounts = OrderProduct::query()
            ->whereIn('pickup_by', $driverIds)
            ->where('pickup_status', 'Pending')
            ->where('pickup_transport_mode', 'Truck')
            ->selectRaw('pickup_by as driver_id, COUNT(*) as cnt')
            ->groupBy('pickup_by')
            ->pluck('cnt', 'driver_id');

        // Unassigned counts (Truck, Pending, no driver)
        $unassignedDeliveries = OrderProduct::query()
            ->whereNull('delivery_by')
            ->where('delivery_status', 'Pending')
            ->where('delivery_transport_mode', 'Truck')
            ->count();

        $unassignedReturns = OrderProduct::query()
            ->whereNull('pickup_by')
            ->where('pickup_status', 'Pending')
            ->where('pickup_transport_mode', 'Truck')
            ->where('delivery_status', 'Completed')
            ->count();

        // Attach counts to each driver
        $drivers = $drivers->map(function ($driver) use ($deliveryCounts, $returnCounts) {
            $driver->pending_deliveries = $deliveryCounts->get($driver->id, 0);
            $driver->pending_returns    = $returnCounts->get($driver->id, 0);
            $driver->total_pending      = $driver->pending_deliveries + $driver->pending_returns;
            return $driver;
        })->sortByDesc('total_pending')->values();

        return view('admin.order_management.dispatch.driver', compact(
            'drivers',
            'unassignedDeliveries',
            'unassignedReturns',
        ));
    }
}
