<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;

trait BuildsTicketFormData
{
    /** Shared dropdown data for the create/edit ticket forms. */
    private function ticketFormData(): array
    {
        $equipmentList = Equipment::orderBy('equipment_name')
            ->get(['id', 'equipment_name', 'equipment_id']);

        $employees = User::active()->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        // Recent orders for the Related Order selector (Choices.js searchable).
        // Only a reference is stored — the Order stays the source of truth.
        $orders = Order::with('customer')
            ->latest('id')
            ->limit(300)
            ->get(['id', 'order_number', 'customer_id', 'customer_name']);

        return compact('equipmentList', 'employees', 'orders');
    }

    /**
     * Resolve the order-reference fields (Order Association Rule):
     * store only order_id / customer_id / rental_date derived from the
     * selected order — never duplicate order documentation.
     */
    private function orderReferenceFields(array $validated): array
    {
        if (empty($validated['order_id'])) {
            return ['order_id' => null, 'customer_id' => null, 'rental_date' => null];
        }

        $order = Order::with('products')->find($validated['order_id']);

        return [
            'order_id'    => $order?->id,
            'customer_id' => $order?->customer_id,
            'rental_date' => $validated['rental_date']
                ?? $order?->products?->whereNotNull('delivery_date')->min('delivery_date'),
        ];
    }
}
