<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use App\Models\Stores\Store;

trait BuildsTicketFormData
{
    /**
     * Data for the rental-order intake form (create page): recent orders that
     * carry equipment, each with its selectable equipment choices, plus
     * stores and active employees. Equipment outside the selected order is
     * never offered in this path.
     */
    private function intakeFormData(): array
    {
        $orders = Order::with(['customer', 'products' => fn ($q) => $q->whereNotNull('equipment_id')->with('equipment')])
            ->whereHas('products', fn ($q) => $q->whereNotNull('equipment_id'))
            ->latest('id')
            ->limit(300)
            ->get(['id', 'order_number', 'customer_id', 'customer_name']);

        $orderOptions = $orders->map(fn (Order $order) => [
            'id'          => $order->id,
            'label'       => trim($order->order_number . ' — ' . ($order->customer_name ?? $order->customer?->full_name ?? 'Unknown')),
            'rental_date' => ($date = $order->products->whereNotNull('delivery_date')->min('delivery_date'))
                ? \Illuminate\Support\Carbon::parse($date)->format('M j, Y') : null,
            'equipment'   => $order->products
                ->filter(fn ($product) => $product->equipment)
                ->map(fn ($product) => [
                    'id'    => $product->equipment->id,
                    'label' => $product->equipment->equipment_name
                        . ($product->equipment->equipment_id ? ' (' . $product->equipment->equipment_id . ')' : ''),
                ])->unique('id')->values(),
        ])->values();

        $employees = User::active()->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $stores = Store::where('status', 'Active')->orderBy('store_name')->get(['id', 'store_name']);

        return compact('orderOptions', 'employees', 'stores');
    }

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
