<?php

namespace App\Services\ServiceManagement;

use App\Models\Orders\Order;
use Illuminate\Support\Carbon;

/**
 * Shapes an Order into the compact structure the Service Ticket intake form
 * needs (id, label, rental date, and the serviceable equipment units). Used by
 * BOTH the live order-search endpoint and the validation round-trip rehydration
 * so the two never drift.
 *
 * Crucially, the serviceable unit is resolved from the SAME sources the Orders
 * page uses — the hard `order_products.equipment_id` FK OR the Queue Line soft
 * assignment. The old intake preload only considered the hard FK, so any order
 * whose unit was soft-assigned (staged but not yet dispatched) was invisible to
 * intake. Orders that resolve no unit at all (e.g. an extension-charge line) are
 * dropped — there is nothing to service.
 */
class ServiceIntakeOrderPresenter
{
    /** Eager-load set required by present(). */
    public static function relations(): array
    {
        return [
            'customer',
            'products' => fn ($q) => $q->with(['equipment', 'softAssignment.equipment']),
        ];
    }

    /** @return array|null  Null when the order has no serviceable equipment unit. */
    public static function present(?Order $order): ?array
    {
        if (!$order) {
            return null;
        }

        $units = $order->products
            ->map(function ($product) {
                // Hard assignment wins; fall back to the Queue Line soft assignment.
                $equipment = $product->equipment ?: $product->softAssignment?->equipment;
                if (!$equipment) {
                    return null;
                }

                return [
                    'id'    => $equipment->id,
                    'label' => $equipment->equipment_name
                        . ($equipment->equipment_id ? ' (' . $equipment->equipment_id . ')' : ''),
                    // Complaint-resolution keys — identical to the old preload.
                    'product_id'         => $product->product_id ?? $equipment->assigned_product_id,
                    'capabilities'       => $equipment->capabilities,
                    'symptom_profile_id' => $equipment->service_symptom_profile_id,
                ];
            })
            ->filter()
            ->unique('id')
            ->values();

        if ($units->isEmpty()) {
            return null;
        }

        $rentalDate = $order->products->whereNotNull('delivery_date')->min('delivery_date');

        return [
            'id'          => $order->id,
            'label'       => trim($order->order_number . ' — ' . ($order->customer_name ?? $order->customer?->full_name ?? 'Unknown')),
            'rental_date' => $rentalDate ? Carbon::parse($rentalDate)->format('M j, Y') : null,
            'equipment'   => $units,
        ];
    }
}
