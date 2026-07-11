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
            ->get(['id', 'equipment_name', 'equipment_id', 'assigned_product_id'])
            ->map(fn (Equipment $unit) => [
                'id'         => $unit->id,
                'label'      => $unit->equipment_name . ($unit->equipment_id ? ' (' . $unit->equipment_id . ')' : ''),
                'product_id' => $unit->assigned_product_id,
            ])->values();

        // Structured complaint library — grouped, capability-aware. The
        // client filters it against the selected equipment's product,
        // categories, and recorded capabilities.
        $complaintTypes = \App\Models\Service\ServiceComplaintType::active()
            ->orderBy('display_order')
            ->get()
            ->map(fn (\App\Models\Service\ServiceComplaintType $type) => [
                'id'                    => $type->id,
                'name'                  => $type->name,
                'group'                 => $type->system_group->value,
                'group_label'           => $type->system_group->label(),
                'group_order'           => $type->system_group->sortOrder(),
                'required_capabilities' => $type->required_capabilities ?? [],
                'product_ids'           => $type->applicable_product_ids,
                'category_ids'          => $type->applicable_category_ids,
            ])->values();

        $employees = User::active()->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $stores = Store::where('status', 'Active')->orderBy('store_name')->get(['id', 'store_name']);

        return compact('orderOptions', 'filterCategories', 'filterProducts', 'overrideEquipment', 'complaintTypes', 'employees', 'stores');
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
