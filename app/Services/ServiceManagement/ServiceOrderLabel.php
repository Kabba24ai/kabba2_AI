<?php

namespace App\Services\ServiceManagement;

use App\Models\Orders\Order;

/**
 * THE single Customer Order search-result contract, shared by BOTH Service
 * intakes (Standard Service + Field Service). Every order result is formatted
 * through this one method so the two screens can never drift again:
 *
 *   {order #} — {customer} - {equipment · equipment · … +N more}
 *
 * e.g.  #3423 — Brayan Tobar - Stump Grinder - HD 37 Hp
 *
 * Equipment is summarised from the order's product names (the rental
 * descriptions) — first three, then "+N more" — so multiple orders for the same
 * customer are distinguishable at a glance. The rental date is deliberately NOT
 * part of the label: the machine identifies the order far better than a date.
 *
 * Requires the order's `products` relation to be loaded (both callers eager-load
 * it). Read-only presentation — it never changes eligibility, selection, or
 * persistence.
 */
class ServiceOrderLabel
{
    /**
     * @return array{order_id:int, customer_name:string, equipment_summary:string, label:string}
     */
    public static function for(Order $order): array
    {
        $customer = $order->customer_name ?? $order->customer?->full_name ?? 'Unknown';

        $names = $order->products->pluck('product_name')->filter()->unique()->values();
        $equipmentSummary = $names->isEmpty()
            ? ''
            : $names->take(3)->implode(' · ')
                . ($names->count() > 3 ? ' +' . ($names->count() - 3) . ' more' : '');

        $label = trim($order->order_number . ' — ' . $customer);
        if ($equipmentSummary !== '') {
            // " - " separates the customer from the product list; products stay
            // separated by " · " (matches the established Field Service format).
            $label .= ' - ' . $equipmentSummary;
        }

        return [
            'order_id'          => $order->id,
            'customer_name'     => $customer,
            'equipment_summary' => $equipmentSummary,
            'label'             => $label,
        ];
    }
}
