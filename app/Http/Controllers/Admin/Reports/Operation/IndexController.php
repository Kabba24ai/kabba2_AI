<?php

namespace App\Http\Controllers\Admin\Reports\Operation;

use App\Http\Controllers\Controller;
use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Models\MaintenanceManagement\Equipment;

class IndexController extends Controller
{
    public function __invoke()
    {
        $statuses = [
            EquipmentCurrentStatus::Available,
            EquipmentCurrentStatus::Rented,
            EquipmentCurrentStatus::Maintenance,
            EquipmentCurrentStatus::Damaged,
        ];

        $statusValues = array_map(static fn (EquipmentCurrentStatus $status) => $status->value, $statuses);

        // Count by unique equipment that is currently assigned to an active order product.
        $statusCounts = Equipment::query()
            ->whereNotNull('equipment.current_order_product_id')
            ->whereNull('equipment.deleted_at')
            ->join('order_products', 'order_products.id', '=', 'equipment.current_order_product_id')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->whereNull('order_products.deleted_at')
            ->whereNull('orders.deleted_at')
            ->whereIn('equipment.current_status', $statusValues)
            ->selectRaw('equipment.current_status as status, COUNT(DISTINCT equipment.id) as total')
            ->groupBy('equipment.current_status')
            ->pluck('total', 'status');

        $totalAssigned = (int) $statusCounts->sum();

        $chartData = collect($statuses)->map(function (EquipmentCurrentStatus $status) use ($statusCounts, $totalAssigned) {
            $count = (int) ($statusCounts[$status->value] ?? 0);
            return [
                'status' => $status->value,
                'label'  => $status->label(),
                'count'  => $count,
                'value'  => $totalAssigned > 0 ? round(($count / $totalAssigned) * 100, 1) : 0.0,
            ];
        })->values();

        return view('admin.reports.operation.index', [
            'chartData'     => $chartData,
            'totalAssigned' => $totalAssigned,
            'chartTitle'    => 'Rental Ready Fulfillment Rate',
        ]);
    }
}
