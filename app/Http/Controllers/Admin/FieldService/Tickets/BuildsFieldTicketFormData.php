<?php

namespace App\Http\Controllers\Admin\FieldService\Tickets;

use App\Models\Dispatch\DispatchAiTruck;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;

trait BuildsFieldTicketFormData
{
    /**
     * Data for the field ticket creation form: recent orders (each with its
     * order-scoped equipment choices), the full equipment list for tickets
     * without an order, plus technicians and active service trucks.
     */
    private function fieldTicketFormData(): array
    {
        $orders = Order::with(['customer', 'products' => fn ($q) => $q->whereNotNull('equipment_id')->with('equipment')])
            ->latest('id')
            ->limit(300)
            ->get(['id', 'order_number', 'customer_id', 'customer_name']);

        $orderOptions = $orders->map(fn (Order $order) => [
            'id'        => $order->id,
            'label'     => trim($order->order_number . ' — ' . ($order->customer_name ?? $order->customer?->full_name ?? 'Unknown')),
            'customer'  => $order->customer_name ?? $order->customer?->full_name,
            'equipment' => $order->products
                ->filter(fn ($product) => $product->equipment)
                ->map(fn ($product) => [
                    'id'     => $product->equipment->id,
                    'label'  => $product->equipment->equipment_name
                        . ($product->equipment->equipment_id ? ' (' . $product->equipment->equipment_id . ')' : ''),
                    'serial' => $product->equipment->serial_number,
                ])->unique('id')->values(),
        ])->values();

        $equipmentOptions = Equipment::orderBy('equipment_name')
            ->get(['id', 'equipment_name', 'equipment_id', 'serial_number'])
            ->map(fn (Equipment $equipment) => [
                'id'     => $equipment->id,
                'label'  => $equipment->equipment_name
                    . ($equipment->equipment_id ? ' (' . $equipment->equipment_id . ')' : ''),
                'serial' => $equipment->serial_number,
            ])->values();

        $technicians = User::active()->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $trucks = DispatchAiTruck::where('is_active', true)
            ->orderBy('truck_name')
            ->get(['id', 'truck_name', 'truck_number']);

        return compact('orderOptions', 'equipmentOptions', 'technicians', 'trucks');
    }
}
