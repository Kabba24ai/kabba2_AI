<?php

namespace App\Services\WaitList;

use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Enums\WaitList\WaitListMatchType;
use App\Enums\WaitList\WaitListRequestType;
use App\Enums\WaitList\WaitListStorePreference;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\WaitList\EquipmentWaitList;
use App\Models\WaitList\EquipmentWaitListAlert;
use Illuminate\Support\Collection;

/**
 * Evaluates waiting records when the customer return checklist confirms an
 * equipment unit has returned. A returned unit matches a record when:
 *
 *   1. the record is still waiting (active/acknowledged),
 *   2. the unit's product is one of the record's selected acceptable products,
 *   3. the unit belongs to the record's category,
 *   4. the unit is no longer rented, and
 *   5. the record's store preference is satisfied.
 *
 * The unit's current operational status (available, maintenance hold,
 * damaged, …) never blocks the match — the alert means "a potentially
 * suitable unit has returned; evaluate it", and the status is surfaced on
 * the Wait List page. Nothing is reserved, promised, or auto-selected;
 * one unit can match many records and one record many units.
 *
 * Legacy fallbacks (un-migrated historical records with no selected
 * products): a category record matches on category, a specific-equipment
 * record matches on its chosen unit IDs — the original semantics, never
 * reinterpreted.
 *
 * Idempotent per return event: a (record, unit) pair alerts at most once
 * per status change — reprocessing the same return creates nothing, while
 * a genuine later return of the same unit can alert again even if the
 * previous match was resolved with Keep Waiting.
 */
class WaitListMatcher
{
    /** @return Collection<EquipmentWaitListAlert> newly created alerts */
    public static function evaluateReturn(Equipment $equipment): Collection
    {
        // Rule 4: a unit still rented is never a contact opportunity.
        if ($equipment->current_status === EquipmentCurrentStatus::Rented) {
            return collect();
        }

        $candidates = EquipmentWaitList::waiting()
            ->with(['selectedProducts:products.id', 'items'])
            ->where(function ($q) use ($equipment) {
                if ($equipment->assigned_product_id) {
                    $q->orWhereHas('selectedProducts',
                        fn ($p) => $p->where('products.id', $equipment->assigned_product_id));
                }

                if ($equipment->product_category_id) {
                    $q->orWhere(fn ($w) => $w
                        ->where('request_type', WaitListRequestType::Category->value)
                        ->whereDoesntHave('selectedProducts')
                        ->where('product_category_id', $equipment->product_category_id));
                }

                $q->orWhere(fn ($w) => $w
                    ->where('request_type', WaitListRequestType::SpecificEquipment->value)
                    ->whereDoesntHave('selectedProducts')
                    ->whereHas('items', fn ($i) => $i->where('equipment_id', $equipment->id)));
            })
            ->get();

        $alerts = collect();

        foreach ($candidates as $waitList) {
            $matchType = self::resolveMatchType($waitList, $equipment);

            if ($matchType === null || ! self::storePreferenceSatisfied($waitList, $equipment)) {
                continue;
            }

            $alerts->push(self::fireAlert($waitList, $equipment, $matchType));
        }

        $created = $alerts->filter()->values();

        // The database alert is canonical; pushes are only a delivery
        // channel. A push-layer failure (even failing to construct the
        // Firebase client) must never surface to callers or undo a match.
        if ($created->isNotEmpty()) {
            try {
                app(WaitListPushService::class)->sendFirstPush($created);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Wait list push dispatch failed; alerts remain canonical', [
                    'alert_ids' => $created->pluck('id')->all(),
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return $created;
    }

    private static function resolveMatchType(EquipmentWaitList $waitList, Equipment $equipment): ?WaitListMatchType
    {
        // Unified rule: unit's product is selected AND unit is in the category
        if ($equipment->assigned_product_id
            && $waitList->selectedProducts->contains('id', $equipment->assigned_product_id)) {
            if ($waitList->product_category_id !== null
                && $waitList->product_category_id !== $equipment->product_category_id) {
                return null;
            }

            return WaitListMatchType::Product;
        }

        if ($waitList->selectedProducts->isNotEmpty()) {
            return null;
        }

        // Legacy fallbacks — original semantics for un-migrated records
        if ($waitList->request_type === WaitListRequestType::Category
            && $waitList->product_category_id !== null
            && $waitList->product_category_id === $equipment->product_category_id) {
            return WaitListMatchType::Category;
        }

        if ($waitList->request_type === WaitListRequestType::SpecificEquipment
            && $waitList->items->contains('equipment_id', $equipment->id)) {
            return WaitListMatchType::ExactEquipment;
        }

        return null;
    }

    /**
     * Any Store and Preferred-with-Transfer accept a return at any store;
     * Specific Store requires the unit to be at that store.
     */
    private static function storePreferenceSatisfied(EquipmentWaitList $waitList, Equipment $equipment): bool
    {
        if ($waitList->store_preference !== WaitListStorePreference::SpecificStore) {
            return true;
        }

        return $waitList->store_id === null || $waitList->store_id === $equipment->store_id;
    }

    /**
     * One alert per (record, unit, return event). "Same event" is anchored
     * to the unit's current_status_changed_at, which every return
     * transition stamps — so double-processing a return is a no-op, while a
     * genuinely new return can alert again even after a Keep Waiting.
     */
    private static function fireAlert(
        EquipmentWaitList $waitList,
        Equipment $equipment,
        WaitListMatchType $matchType,
    ): ?EquipmentWaitListAlert {
        $duplicate = EquipmentWaitListAlert::query()
            ->where('equipment_wait_list_id', $waitList->id)
            ->where('equipment_id', $equipment->id)
            ->where(function ($q) use ($equipment) {
                $q->open();

                if ($equipment->current_status_changed_at) {
                    $q->orWhere('created_at', '>=', $equipment->current_status_changed_at);
                }
            })
            ->exists();

        if ($duplicate) {
            return null;
        }

        return EquipmentWaitListAlert::create([
            'equipment_wait_list_id'    => $waitList->id,
            'equipment_id'              => $equipment->id,
            'match_type'                => $matchType,
            'matched_category_id'       => $equipment->product_category_id,
            'matched_product_id'        => $equipment->assigned_product_id,
            'equipment_status_at_match' => $equipment->current_status?->value,
        ]);
    }
}
