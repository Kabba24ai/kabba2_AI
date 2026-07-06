<?php

namespace App\Services\WaitList;

use App\Enums\WaitList\WaitListMatchType;
use App\Enums\WaitList\WaitListRequestType;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\WaitList\EquipmentWaitList;
use App\Models\WaitList\EquipmentWaitListAlert;
use Illuminate\Support\Collection;

/**
 * Evaluates waiting records when equipment is returned/checked in and fires
 * internal alerts. Matching is deliberately simple: (1) exact equipment ID,
 * (2) category. No substitution, upgrade/downgrade, date logic, or
 * prioritization — managers decide everything manually. Fires regardless of
 * the equipment's resulting status (maintenance, damaged, available, …).
 */
class WaitListMatcher
{
    /** @return Collection<EquipmentWaitListAlert> newly created alerts */
    public static function evaluateReturn(Equipment $equipment): Collection
    {
        $alerts = collect();

        // 1. Exact equipment ID match against any of the record's (max 3) IDs
        $exactMatches = EquipmentWaitList::waiting()
            ->where('request_type', WaitListRequestType::SpecificEquipment->value)
            ->whereHas('items', fn ($q) => $q->where('equipment_id', $equipment->id))
            ->get();

        foreach ($exactMatches as $waitList) {
            $alerts->push(self::fireAlert($waitList, $equipment, WaitListMatchType::ExactEquipment));
        }

        // 2. Category match against the returned equipment's category
        if ($equipment->product_category_id) {
            $categoryMatches = EquipmentWaitList::waiting()
                ->where('request_type', WaitListRequestType::Category->value)
                ->where('product_category_id', $equipment->product_category_id)
                ->get();

            foreach ($categoryMatches as $waitList) {
                $alerts->push(self::fireAlert($waitList, $equipment, WaitListMatchType::Category));
            }
        }

        $created = $alerts->filter()->values();

        if ($created->isNotEmpty()) {
            app(WaitListPushService::class)->sendFirstPush($created);
        }

        return $created;
    }

    /** One open alert per wait list + equipment pair — no duplicate noise. */
    private static function fireAlert(
        EquipmentWaitList $waitList,
        Equipment $equipment,
        WaitListMatchType $matchType,
    ): ?EquipmentWaitListAlert {
        $alreadyOpen = EquipmentWaitListAlert::open()
            ->where('equipment_wait_list_id', $waitList->id)
            ->where('equipment_id', $equipment->id)
            ->exists();

        if ($alreadyOpen) {
            return null;
        }

        return EquipmentWaitListAlert::create([
            'equipment_wait_list_id'    => $waitList->id,
            'equipment_id'              => $equipment->id,
            'match_type'                => $matchType,
            'matched_category_id'       => $equipment->product_category_id,
            'equipment_status_at_match' => $equipment->current_status?->value,
        ]);
    }
}
