<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Http\Request;

/**
 * Phase 2B — equipment-level Rental Ready inspection HISTORY (read-only).
 *
 * Lists EVERY canonical inspection for a unit — Draft, Completed, Voided,
 * Superseded, Abandoned — with the inspection header as the row of record.
 * Lifecycle and result are shown separately. No writes.
 */
class RentalReadyHistoryController extends Controller
{
    public function __invoke(Request $request, string $equipment)
    {
        $equipmentModel = Equipment::where('unique_id', $equipment)->firstOrFail();

        $inspections = EquipmentRentalReadyTemplate::query()
            ->where('equipment_id', $equipmentModel->id)
            ->with(['employee:id,first_name,last_name', 'orderProduct.order:id,unique_id,order_number', 'order:id,unique_id,order_number'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.checklist_management.equipment_management.rental_ready_history', [
            'equipment' => $equipmentModel,
            'inspections' => $inspections,
        ]);
    }
}
