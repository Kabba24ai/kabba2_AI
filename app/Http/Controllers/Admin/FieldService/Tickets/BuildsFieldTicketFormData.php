<?php

namespace App\Http\Controllers\Admin\FieldService\Tickets;

use App\Models\Dispatch\DispatchAiTruck;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Service\ServiceSymptom;
use App\Models\Service\ServiceSymptomCategory;

trait BuildsFieldTicketFormData
{
    /**
     * Data for the Field Service Request form. The Order is the canonical
     * source: selecting it derives Customer, Equipment, Delivery Address, and
     * Contact — so each order option carries all of them. Problems come from
     * the shared shop symptom library (one problem vocabulary module-wide);
     * plus technicians and active service trucks for dispatch.
     */
    private function fieldTicketFormData(): array
    {
        $orders = Order::with([
                'customer',
                'shippingAddress',
                'products' => fn ($q) => $q->whereNotNull('equipment_id')->with('equipment'),
            ])
            ->latest('id')
            ->limit(300)
            ->get(['id', 'order_number', 'customer_id', 'customer_name', 'customer_phone']);

        $orderOptions = $orders->map(function (Order $order) {
            $ship = $order->shippingAddress;

            return [
                'id'       => $order->id,
                'label'    => trim($order->order_number . ' — ' . ($order->customer_name ?? $order->customer?->full_name ?? 'Unknown')),
                'customer' => $order->customer_name ?? $order->customer?->full_name,
                // Derived contact — the shipping-address recipient, else the order customer.
                'contact'  => [
                    'name'  => $ship?->full_name ?: ($order->customer_name ?? $order->customer?->full_name),
                    'phone' => $ship?->phone ?: $order->customer_phone,
                ],
                // Derived delivery address — the order's shipping address.
                'address'  => [
                    'full'   => $ship?->full_address,
                    'street' => $ship?->address,
                    'city'   => $ship?->city,
                    'state'  => $ship?->state,
                    'zip'    => $ship?->zip_code,
                ],
                'equipment' => $order->products
                    ->filter(fn ($product) => $product->equipment)
                    ->map(fn ($product) => [
                        'id'     => $product->equipment->id,
                        'label'  => $product->equipment->equipment_name
                            . ($product->equipment->equipment_id ? ' (' . $product->equipment->equipment_id . ')' : ''),
                        'model'  => $product->equipment->equipment_name,
                        'serial' => $product->equipment->serial_number,
                    ])->unique('id')->values(),
            ];
        })->values();

        // Shared shop symptom library — the Field Service Request reports
        // problems from the SAME vocabulary the shop intake uses.
        $problemCategories = ServiceSymptomCategory::active()
            ->orderBy('display_order')
            ->get(['id', 'name']);

        $problems = ServiceSymptom::active()
            ->orderBy('display_order')
            ->get(['id', 'name', 'service_symptom_category_id'])
            ->map(fn (ServiceSymptom $s) => [
                'id'          => $s->id,
                'name'        => $s->name,
                'category_id' => $s->service_symptom_category_id,
            ])->values();

        $technicians = User::active()->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $trucks = DispatchAiTruck::where('is_active', true)
            ->orderBy('truck_name')
            ->get(['id', 'truck_name', 'truck_number']);

        return compact('orderOptions', 'problemCategories', 'problems', 'technicians', 'trucks');
    }
}
