<?php

namespace App\Http\Controllers\Admin\OrderManagement\ScheduleAssignment;

use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;
use App\Modules\SchedulingAssistant\Services\ConflictDetectionService;
use Illuminate\Http\Request;

class AutoAssignDirectController extends Controller
{
    public function __invoke(Request $request, ConflictDetectionService $conflictDetectionService)
    {
        // Single-item mode: if an order_product_id is provided, only process that one
        if ($request->filled('order_product_id')) {
            $single = OrderProduct::with(['product.categories', 'softAssignment', 'order'])
                ->find((int) $request->order_product_id);

            $orderProducts = $single ? collect([$single]) : collect();
        } else {
            // Build the same unassigned-order-products query used on the schedule assignment page
            $query = OrderProduct::query()
                ->with(['product.categories', 'softAssignment', 'order'])
                ->where('product_data->product_type', 'Rental')
                ->whereHas('order')
                ->whereNotNull('delivery_date')
                ->whereDoesntHave('softAssignment')
                ->whereDoesntHave('equipment')
                ->where(function ($q) {
                    $q->where('delivery_status', 'Pending')->orWhere('pickup_status', 'Pending');
                })
                ->where('delivery_status', '!=', 'Reschedule')
                ->where('pickup_status', '!=', 'Reschedule');

            // Apply the category filter from the current Schedule Assignment filter
            if ($request->filled('category')) {
                $query->whereHas('product.categories', function ($q) use ($request) {
                    $q->where('product_categories.id', $request->category);
                });
            }

            $orderProducts = $query->get();
        }

        $assigned = 0;
        $skipped  = 0;
        $details  = [];

        foreach ($orderProducts as $orderProduct) {
            $productId = (int) ($orderProduct->product_id ?? 0);

            if ($productId <= 0) {
                $skipped++;
                $details[] = [
                    'product_name' => $orderProduct->product_name,
                    'order'        => $orderProduct->order?->order_number ?? '—',
                    'status'       => 'skipped',
                    'reason'       => 'No product linked to this order line',
                ];
                continue;
            }

            // Find equipment with a Direct Assignment to this product,
            // that is Available or Maintenance Hold (not Rented / Damaged)
            $candidates = Equipment::query()
                ->where('assigned_product_id', $productId)
                ->whereIn('current_status', [
                    EquipmentCurrentStatus::Available->value,
                    EquipmentCurrentStatus::Maintenance->value,
                ])
                // Prefer Available over Maintenance Hold
                ->orderByRaw("FIELD(current_status, 'available', 'maintenance')")
                ->orderBy('id')
                ->get();

            if ($candidates->isEmpty()) {
                $skipped++;
                $details[] = [
                    'product_name' => $orderProduct->product_name,
                    'order'        => $orderProduct->order?->order_number ?? '—',
                    'status'       => 'skipped',
                    'reason'       => 'No direct-assignment equipment is Available or Maint. Hold',
                ];
                continue;
            }

            // Pick the first candidate without a date conflict
            $eligible = null;
            foreach ($candidates as $equipment) {
                if (!$conflictDetectionService->hasConflict($equipment->id, $orderProduct)) {
                    $eligible = $equipment;
                    break;
                }
            }

            if (!$eligible) {
                $skipped++;
                $details[] = [
                    'product_name' => $orderProduct->product_name,
                    'order'        => $orderProduct->order?->order_number ?? '—',
                    'status'       => 'skipped',
                    'reason'       => 'All direct-assignment equipment has a scheduling conflict',
                ];
                continue;
            }

            // Create the soft assignment (mirrors AssignEquipmentController)
            $orderProduct->softAssignment()->delete();
            $orderProduct->softAssignment()->create([
                'equipment_id' => $eligible->id,
                'order_id'     => $orderProduct->order_id,
                'assigned_by'  => auth()->id(),
            ]);

            $assigned++;
            $details[] = [
                'product_name'   => $orderProduct->product_name,
                'order'          => $orderProduct->order?->order_number ?? '—',
                'status'         => 'assigned',
                'equipment_name' => $eligible->equipment_name,
                'equipment_id'   => $eligible->equipment_id,
            ];
        }

        return response()->json([
            'success'  => true,
            'assigned' => $assigned,
            'skipped'  => $skipped,
            'total'    => $orderProducts->count(),
            'details'  => $details,
        ]);
    }
}
