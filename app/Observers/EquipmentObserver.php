<?php

namespace App\Observers;

use App\Models\MaintenanceManagement\Equipment;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;

class EquipmentObserver
{
    public function updating(Equipment $equipment)
    {
        // Only log when status actually changes
        if ($equipment->isDirty('current_status')) {

            EquipmentStatusLog::create([
                'equipment_id' => $equipment->id,
                'from_status'  => optional($equipment->getOriginal('current_status'))->value
                                    ?? $equipment->getOriginal('current_status'),
                'to_status'    => $equipment->current_status->value,
                'changed_by'   => auth()->id(),
                'changed_at'   => now(),
            ]);
        }
    }
}
