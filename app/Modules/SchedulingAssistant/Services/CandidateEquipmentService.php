<?php

namespace App\Modules\SchedulingAssistant\Services;

use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;
use Illuminate\Support\Collection;

class CandidateEquipmentService
{
    public function getCandidatesForOrderProduct(OrderProduct $orderProduct): Collection
    {
        $productId = (int) ($orderProduct->product_id ?? 0);

        if ($productId <= 0) {
            return collect();
        }

        $primaryCandidates = Equipment::query()
            ->where('assigned_product_id', $productId)
            ->orderBy('id')
            ->get()
            ->each(function (Equipment $equipment) {
                $equipment->setAttribute('assignment_relationship_type', 'primary');
            });

        $primaryIds = $primaryCandidates
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (empty($primaryIds)) {
            return $primaryCandidates;
        }

        $relatedCandidates = Equipment::query()
            ->where('assigned_product_id', '!=', $productId)
            ->where(function ($query) {
                $query->where('allow_upgrades', true)
                    ->orWhere('allow_downgrades', true);
            })
            ->whereNotNull('critical_matching_criteria')
            ->where(function ($query) use ($primaryIds) {
                foreach ($primaryIds as $primaryEquipmentId) {
                    $query->orWhereJsonContains('similar_equipment_ids', $primaryEquipmentId);
                }
            })
            ->orderBy('id')
            ->get()
            ->each(function (Equipment $equipment) {
                $relationshipType = $equipment->allow_upgrades ? 'upgrade' : 'downgrade';
                $equipment->setAttribute('assignment_relationship_type', $relationshipType);
            });

        return $primaryCandidates
            ->concat($relatedCandidates)
            ->unique('id')
            ->values();
    }
}
