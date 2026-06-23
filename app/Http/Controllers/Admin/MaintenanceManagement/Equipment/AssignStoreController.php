<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\MaintenanceManagement\Equipment\AssignStoreRequest;

// Models
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentSoftAssign;
use App\Models\Orders\OrderProduct;
use App\Models\Stores\Store;

// Enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;

class AssignStoreController extends Controller
{
    public function __invoke(AssignStoreRequest $request)
    {
        $validated = $request->validated();

        $store     = Store::where('unique_id', $validated['store_unique_id'])->first();
        $equipment = Equipment::with('store')->where('unique_id', $validated['equipment_unique_id'])->first();

        if (!$store || !$equipment) {
            return response()->json([
                'success' => false,
                'message' => 'Store or Equipment not found.',
            ], 404);
        }

        if ($equipment->store_id === $store->id) {
            return response()->json([
                'success' => false,
                'message' => 'This Store is already assigned to the Equipment.',
            ], 400);
        }

        $oldStoreName = $equipment->store?->store_name ?? 'Unknown';

        // Assign new store
        $equipment->store_id = $store->id;
        $equipment->save();

        // --- Sync truck delivery/return store on active order products ---
        // Rule: for truck delivery orders only, the equipment's physical location
        //       is the effective load/pickup point. In-store orders are excluded.
        $activeScheduleFilter = fn ($q) => $q->where(function ($sub) {
            $sub->where(function ($d) {
                $d->where('delivery_transport_mode', 'Truck')
                  ->where('delivery_status', 'Pending');
            })->orWhere(function ($r) {
                $r->where('pickup_transport_mode', 'Truck')
                  ->where('pickup_status', 'Pending');
            });
        });

        // Hard-assigned order products
        $hardAssigned = OrderProduct::where('equipment_id', $equipment->id)
            ->tap($activeScheduleFilter)
            ->with('order')
            ->get();

        // Soft-assigned order products
        $softProductIds = EquipmentSoftAssign::where('equipment_id', $equipment->id)
            ->pluck('order_product_id');

        $softAssigned = $softProductIds->isNotEmpty()
            ? OrderProduct::whereIn('id', $softProductIds)
                ->tap($activeScheduleFilter)
                ->with('order')
                ->get()
            : collect();

        $orderProducts = $hardAssigned->merge($softAssigned)->unique('id');

        $userId     = auth()->id();
        $syncedCount = 0;

        foreach ($orderProducts as $op) {
            $changed = false;

            if ($op->delivery_transport_mode === 'Truck' && $op->delivery_status === 'Pending') {
                $op->delivery_store_id = $store->id;
                $changed = true;
            }

            if ($op->pickup_transport_mode === 'Truck' && $op->pickup_status === 'Pending') {
                $op->pickup_store_id = $store->id;
                $changed = true;
            }

            if ($changed) {
                $op->save();
                $syncedCount++;

                if ($op->order) {
                    $op->order->history()->create([
                        'customer_id' => $op->order->customer_id,
                        'user_id'     => $userId,
                        'action_by'   => OrderHistoryActionBy::User,
                        'action_date' => now(),
                        'action'      => OrderHistoryAction::ProductScheduleUpdated,
                        'description' => "Delivery/Return store auto-synced from {$oldStoreName} to {$store->store_name} — equipment {$equipment->equipment_id} relocated (truck delivery order)",
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Store assigned successfully!' . ($syncedCount > 0 ? " ({$syncedCount} truck order schedule(s) updated to {$store->store_name}.)" : ''),
        ]);
    }
}
