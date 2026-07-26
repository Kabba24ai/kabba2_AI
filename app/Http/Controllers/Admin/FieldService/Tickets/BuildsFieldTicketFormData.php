<?php

namespace App\Http\Controllers\Admin\FieldService\Tickets;

use App\Models\Dispatch\DispatchAiTruck;
use App\Models\Iam\Personnel\User;
use App\Models\Locations\State;
use App\Models\Orders\Order;
use App\Services\ServiceManagement\ServiceOrderLabel;
use App\Services\ServiceManagement\ServiceProblemLibrary;

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
                // All line items so the option label can list the order's
                // products (helps identify the right order); equipment +
                // product categories are eager-loaded for serviceable-unit
                // resolution and equipment-profile problem filtering below.
                'products' => fn ($q) => $q->with(['equipment', 'product.categories:product_categories.id']),
            ])
            ->latest('id')
            ->limit(300)
            ->get(['id', 'order_number', 'reference_order_number', 'customer_id', 'customer_name', 'customer_phone']);

        $orderOptions = $orders->map(function (Order $order) {
            $ship = $order->shippingAddress;

            // One shared Customer Order label contract (identical on the Standard
            // Service intake) so the two search results can never drift again:
            // order # — customer - equipment (first three, then "+N more").
            $label = ServiceOrderLabel::for($order)['label'];

            return [
                'id'       => $order->id,
                'label'    => $label,
                // Reference number — searchable in the client-side Choices box
                // (via customProperties) without appearing in the visible label.
                'reference' => $order->reference_order_number,
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
                        // Problem-engine resolution keys — same anchors the shop
                        // intake uses (attached profile → product → category).
                        'product_id'         => $product->product_id ?? $product->equipment->assigned_product_id,
                        'symptom_profile_id' => $product->equipment->service_symptom_profile_id,
                        'category_ids'       => $product->product?->categories->pluck('id')->values() ?? collect(),
                    ])->unique('id')->values(),
            ];
        })->values();

        // Canonical Service Problem Engine (shared with the shop intake): one
        // repository, one category grouping, one equipment-profile applicability
        // model. Field Service filters its problem search to the selected
        // equipment's applicable symptoms just as the shop checklist does.
        $problemCategories = ServiceProblemLibrary::categories();
        $problems          = ServiceProblemLibrary::symptoms();
        $problemProfiles   = ServiceProblemLibrary::profiles();

        $technicians = User::active()->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $trucks = DispatchAiTruck::where('is_active', true)
            ->orderBy('truck_name')
            ->get(['id', 'truck_name', 'truck_number']);

        // Shared U.S. state list (canonical inclusion policy incl. DC/territories
        // lives in the states table); submitted as the two-letter abbreviation.
        $states = State::orderBy('name')->get(['id', 'name', 'abbreviation']);

        return compact('orderOptions', 'problemCategories', 'problems', 'problemProfiles', 'technicians', 'trucks', 'states');
    }
}
