<?php

namespace App\Services\Dispatch;

/**
 * Pure (DB-free) ordering logic for the Dispatch drag-and-drop board.
 *
 * A driver's schedule is ONE unified activity sequence spanning both legs
 * (deliveries and returns interleaved) — this is what the driver's mobile app
 * shows as a single-column route. It is physically stored across two columns on
 * `order_products` (`delivery_priority` for delivery-leg cards, `pickup_priority`
 * for return-leg cards), but read as a single ordered list.
 *
 * The Combined board view edits that unified sequence directly. The Separate
 * board view edits one leg's column at a time, so a leg reorder has to be
 * reconciled back into the unified sequence while the OTHER leg's cards keep
 * their positions — that reconciliation lives in {@see reconcileSeparate()}.
 *
 * This class holds NO database access on purpose so the sequencing rules can be
 * unit-tested without a MySQL server; persistence lives in ReorderController.
 *
 * An "item" throughout is an associative array: ['uid' => string, 'leg' => 'delivery'|'return'].
 */
class DispatchReorderService
{
    /**
     * Reconcile a Separate-view leg reorder into the driver's full unified order.
     *
     * @param  array       $currentUnified  The driver's current unified order (pre-move),
     *                                       as [['uid','leg'], ...]. For an arrival this does
     *                                       NOT yet include the moved card; for a departure it
     *                                       still includes it.
     * @param  string[]    $newLegOrder     The post-move order of the dragged leg's column
     *                                       (uids only). For an arrival this includes the moved
     *                                       card; for a departure it excludes it.
     * @param  string      $leg             The dragged leg ('delivery' or 'return').
     * @param  string|null $arrivingUid     UID that arrived from another driver (cross-driver
     *                                       drop), or null.
     * @param  string|null $departingUid    UID that left for another driver, or null.
     * @return array                        New unified order as [['uid','leg'], ...].
     */
    public function reconcileSeparate(
        array $currentUnified,
        array $newLegOrder,
        string $leg,
        ?string $arrivingUid = null,
        ?string $departingUid = null
    ): array {
        $unified = array_values($currentUnified);

        // A card that left this driver is dropped from the sequence entirely.
        if ($departingUid !== null) {
            $unified = array_values(array_filter(
                $unified,
                fn ($it) => ($it['uid'] ?? null) !== $departingUid
            ));
        }

        // A card that arrived is inserted adjacent to its neighbour in the new leg
        // order: right after the leg-card that now precedes it, or at the front if
        // it is first in the leg order. Its exact position relative to the OTHER
        // leg is inherently ambiguous in Separate view (the dispatcher only
        // expressed its order among same-leg cards) — Combined view is the tool for
        // precise cross-leg placement.
        if ($arrivingUid !== null) {
            $insertAt = 0;
            $k = array_search($arrivingUid, $newLegOrder, true);
            if ($k !== false && $k > 0) {
                $predUid = $newLegOrder[$k - 1];
                foreach ($unified as $i => $it) {
                    if (($it['uid'] ?? null) === $predUid) {
                        $insertAt = $i + 1;
                        break;
                    }
                }
            }
            array_splice($unified, $insertAt, 0, [['uid' => $arrivingUid, 'leg' => $leg]]);
        }

        // Replace the dragged leg's slots, in unified order, with the new leg order.
        // Other-leg cards are pinned exactly where they are. Slot count now matches
        // the new leg order length by construction (departing removed / arriving
        // inserted above), but we fall back to the existing uid if it ever doesn't.
        $queue = array_values($newLegOrder);
        $qi = 0;
        $result = [];
        foreach ($unified as $it) {
            if (($it['leg'] ?? null) === $leg) {
                $uid = $queue[$qi] ?? ($it['uid'] ?? null);
                $qi++;
                if ($uid !== null) {
                    $result[] = ['uid' => $uid, 'leg' => $leg];
                }
            } else {
                $result[] = $it;
            }
        }

        return $result;
    }

    /**
     * Insert an arriving card into a driver's list by effective DATE (used when a
     * job is moved to another driver or an idle driver): it lands ahead of the first
     * existing card dated strictly later than it, otherwise at the end. Stable — the
     * existing order is preserved; only the arriving card is positioned. This lets a
     * job dated earlier take a higher route position even if the current list was
     * arranged out of date order.
     *
     * @param  array  $current    Current list as [['uid','leg'], ...] (WITHOUT the arriving card).
     * @param  array  $arriving   ['uid'=>, 'leg'=>] card being inserted.
     * @param  array  $dateByKey  Map of "uid|leg" => 'Y-m-d' effective date (or null = undated → sorts last).
     * @return array              New list as [['uid','leg'], ...] including the arriving card.
     */
    public function insertByDate(array $current, array $arriving, array $dateByKey): array
    {
        $keyOf = fn ($it) => ($it['uid'] ?? '') . '|' . ($it['leg'] ?? '');
        $arrDate = $dateByKey[$keyOf($arriving)] ?? null;
        $arrItem = ['uid' => $arriving['uid'], 'leg' => $arriving['leg']];

        $result = [];
        $inserted = false;
        foreach ($current as $it) {
            if (!$inserted && $this->dateGreater($dateByKey[$keyOf($it)] ?? null, $arrDate)) {
                $result[] = $arrItem;
                $inserted = true;
            }
            $result[] = $it;
        }
        if (!$inserted) {
            $result[] = $arrItem;
        }

        return $result;
    }

    /**
     * True if date $a is strictly later than $b. Null = undated, treated as far future
     * (sorts last). 'Y-m-d' strings compare lexically == chronologically.
     */
    private function dateGreater(?string $a, ?string $b): bool
    {
        if ($a === $b) {
            return false;
        }
        if ($a === null) {
            return true;
        }
        if ($b === null) {
            return false;
        }

        return strcmp($a, $b) > 0;
    }

    /**
     * Pull a set of member cards into one contiguous block within a unified order,
     * positioned where the first member currently sits, preserving the members'
     * relative order and the non-members' relative order. Used to keep a combined
     * load's items adjacent in a driver's sequence.
     *
     * @param  array       $unified     [['uid','leg'], ...]
     * @param  string[]    $memberUids  uids that belong to the load
     * @param  string|null $leg         if given, only items of this leg are members
     *                                  (an order_product can have both legs pending)
     * @return array                    reordered [['uid','leg'], ...]
     */
    public function groupContiguous(array $unified, array $memberUids, ?string $leg = null): array
    {
        $isMember = array_flip($memberUids);
        $members = [];
        $rest = [];
        $insertAt = null;

        foreach ($unified as $it) {
            $match = isset($isMember[$it['uid'] ?? '']) && ($leg === null || ($it['leg'] ?? null) === $leg);
            if ($match) {
                if ($insertAt === null) {
                    $insertAt = count($rest); // block lands where the first member was
                }
                $members[] = $it;
            } else {
                $rest[] = $it;
            }
        }

        if ($insertAt === null) {
            return array_values($unified); // no members present
        }

        array_splice($rest, $insertAt, 0, $members);

        return array_values($rest);
    }

    /**
     * Map a unified order to contiguous 1..N positions per item.
     *
     * @param  array $unified  [['uid','leg'], ...]
     * @return array           [['uid','leg','priority'], ...] with priority = 1-based position.
     */
    public function positions(array $unified): array
    {
        $out = [];
        $pos = 0;
        foreach ($unified as $it) {
            if (!isset($it['uid'], $it['leg'])) {
                continue;
            }
            $pos++;
            $out[] = ['uid' => $it['uid'], 'leg' => $it['leg'], 'priority' => $pos];
        }

        return $out;
    }
}
