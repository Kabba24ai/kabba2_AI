<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
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
        $orders = Order::with([
                'customer',
                'products' => fn ($q) => $q->whereNotNull('equipment_id')
                    ->with(['equipment', 'product.categories:product_categories.id']),
            ])
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
                    // Complaint-list keys: the rented product + what the
                    // machine is equipped with (null = unknown, hide nothing)
                    'product_id'   => $product->product_id ?? $product->equipment->assigned_product_id,
                    'capabilities' => $product->equipment->capabilities,
                    // Per-unit Reported-Problem template (highest resolution
                    // precedence — see the intake JS). Null → No Template Listed.
                    'symptom_profile_id' => $product->equipment->service_symptom_profile_id,
                ])->unique('id')->values(),
            // Search-aid keys for the intake filters — never stored on the ticket
            'product_ids'  => $order->products->pluck('product_id')->filter()->unique()->values(),
            'category_ids' => $order->products
                ->flatMap(fn ($line) => $line->product?->categories->pluck('id') ?? collect())
                ->unique()->values(),
        ])->values();

        // Intake filter sources: every category, every rentable product (with
        // its category keys so Filter Product can follow Filter Category).
        $filterCategories = ProductCategory::orderBy('title')->get(['id', 'title']);

        $filterProducts = Product::with('categories:product_categories.id')
            ->where('product_type', 'Rental')
            ->orderBy('product_name')
            ->get(['id', 'product_name'])
            ->map(fn (Product $product) => [
                'id'           => $product->id,
                'name'         => $product->product_name,
                'category_ids' => $product->categories->pluck('id')->values(),
            ])->values();

        // Equipment ID Override source: any unit in the fleet — the intake
        // must identify the machine actually being repaired even when the
        // order carries the wrong one. product_id lets the Category/Product
        // search aids narrow this list too (categories come from the
        // product→category map already shipped in filterProducts).
        $overrideEquipment = Equipment::orderBy('equipment_name')
            ->get(['id', 'equipment_name', 'equipment_id', 'assigned_product_id', 'service_symptom_profile_id'])
            ->map(fn (Equipment $unit) => [
                'id'                 => $unit->id,
                'label'              => $unit->equipment_name . ($unit->equipment_id ? ' (' . $unit->equipment_id . ')' : ''),
                'product_id'         => $unit->assigned_product_id,
                // The override unit's OWN attached template drives the Problem
                // Engine when it is chosen (override is the effective equipment).
                'symptom_profile_id' => $unit->service_symptom_profile_id,
            ])->values();

        // Symptom library + equipment profiles from the canonical Service
        // Problem Engine (shared with Field Service). The client resolves the
        // applicable profile against the selected equipment's product/category
        // (product-level wins over category-level) and assembles its checklist
        // from the profile's included categories + additions − exclusions;
        // equipment with no matching profile sees the full library.
        $symptomCategories = \App\Services\ServiceManagement\ServiceProblemLibrary::categories();
        $symptoms          = \App\Services\ServiceManagement\ServiceProblemLibrary::symptoms();
        $symptomProfiles   = \App\Services\ServiceManagement\ServiceProblemLibrary::profiles();

        $employees = User::active()->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $stores = Store::where('status', 'Active')->orderBy('store_name')->get(['id', 'store_name']);

        // Standard Equipment intake: the category picker. Only categories that
        // actually own fleet units are offered (an empty category can never
        // yield an equipment match), keeping the list short and honest. The
        // equipment itself is NOT loaded here — it is fetched per-category,
        // server-side, by EquipmentSearchController.
        $equipmentCategories = ProductCategory::whereHas('equipments')
            ->orderBy('title')
            ->get(['id', 'title']);

        // Validation round-trip for the Customer path: rehydrate the chosen
        // order (with its serviceable units) so the order chip + equipment
        // picker restore without the order needing to be in any preload.
        $oldCustomerOrder = null;
        if (old('ticket_source', 'customer') !== 'standard' && old('order_id')) {
            $oldCustomerOrder = \App\Services\ServiceManagement\ServiceIntakeOrderPresenter::present(
                \App\Models\Orders\Order::with(\App\Services\ServiceManagement\ServiceIntakeOrderPresenter::relations())
                    ->find((int) old('order_id'))
            );
        }

        // Validation round-trip for the Standard path: rehydrate the label of a
        // previously-chosen unit so the chip re-renders without another lookup.
        $oldStandardEquipment = null;
        if (old('ticket_source') === 'standard' && old('equipment_id')) {
            $unit = Equipment::find((int) old('equipment_id'));
            if ($unit) {
                $oldStandardEquipment = [
                    'id'                  => $unit->id,
                    'display_id'          => $unit->equipment_id,
                    'name'                => $unit->equipment_name,
                    'label'               => $unit->equipment_name . ($unit->equipment_id ? ' (' . $unit->equipment_id . ')' : ''),
                    'product_id'          => $unit->assigned_product_id,
                    'product_category_id' => $unit->product_category_id,
                    'symptom_profile_id'  => $unit->service_symptom_profile_id,
                ];
            }
        }

        return compact('orderOptions', 'filterCategories', 'filterProducts', 'overrideEquipment', 'symptomCategories', 'symptoms', 'symptomProfiles', 'employees', 'stores', 'equipmentCategories', 'oldStandardEquipment', 'oldCustomerOrder');
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
     * Resolve the Equipment ID Override (intake): equipment_id becomes the
     * unit actually being repaired — everything downstream already follows
     * it — while order_equipment_id preserves the order's original unit.
     * Selecting the order's own unit as the override is a no-op.
     */
    private function equipmentOverrideFields(array $validated): array
    {
        $overrideId       = (int) ($validated['equipment_override_id'] ?? 0);
        $orderEquipmentId = (int) ($validated['equipment_id'] ?? 0);

        if (!$overrideId || $overrideId === $orderEquipmentId) {
            return [];
        }

        return [
            'equipment_id'              => $overrideId,
            'order_equipment_id'        => $orderEquipmentId,
            'equipment_override'        => true,
            'equipment_override_reason' => $validated['equipment_override_reason'] ?? null,
            'equipment_override_by'     => auth()->id(),
            'equipment_override_at'     => now(),
        ];
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
