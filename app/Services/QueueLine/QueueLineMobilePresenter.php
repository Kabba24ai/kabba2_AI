<?php

namespace App\Services\QueueLine;

use App\Models\Orders\OrderProduct;
use App\Models\Orders\QueueLineFuelVerification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * THE serializer for the mobile Queue Line API (standalone-module mission §4).
 *
 * Every business decision the app must never re-implement is resolved HERE,
 * server-side, from the canonical services: eligibility/urgency/ordering
 * (QueueLineEligibility), fuel currency (QueueFuelVerificationService,
 * episode-bound), assignment classification, readiness, and the allowed
 * action set. The app renders; it never decides.
 *
 * Identity contract: only business identifiers cross the wire — unique_ids
 * and the equipment display id (the physical barcode string). No internal
 * database ids, ever.
 */
final class QueueLineMobilePresenter
{
    // §14 readiness — Queue Line PREPARES; Dispatch/Customer Checklist release.
    // Staging is ALL-OR-NOTHING (2026-07-20): ready_* now requires the
    // COMPLETE staging set (fuel + key + staged latch) — the same rule as
    // the web board's Staged section. Changed in place before any app build
    // consumed the contract.
    public const READINESS_EQUIPMENT_REQUIRED = 'equipment_assignment_required';

    public const READINESS_STAGING_REQUIRED = 'staging_required';

    public const READINESS_DISPATCH = 'ready_for_dispatch';

    public const READINESS_HANDOFF = 'ready_for_customer_handoff';

    /**
     * The full board feed: canonical eligibility + ordering + one batched
     * fuel-state query (never per-item). Items are grouped into the three
     * Queue Line sections (2026-07-23) — Pending, Staged, Completed — over
     * the SAME date window (Overdue/Today/Tomorrow) the active board already
     * uses; Completed is sourced from completedQuery() since boardQuery()
     * excludes completed rows by design (that exclusion still governs the
     * web board, which never shows a Completed section).
     *
     * @return array{items: array{pending: array, staged: array, completed: array}, meta: array}
     */
    public static function board(?int $storeId = null): array
    {
        $activeRows = QueueLineEligibility::filterFinanciallyActive(
            QueueLineEligibility::boardQuery($storeId)->get()
        );
        $completedRows = QueueLineEligibility::filterFinanciallyActive(
            QueueLineEligibility::completedQuery($storeId)->get()
        );

        $allRows = $activeRows->concat($completedRows);
        $fuelByAssignment = self::fuelMap($allRows);
        $keyByAssignment = QueueLineStagingService::keyMap($allRows);

        $serialize = fn (OrderProduct $row) => self::serialize(
            $row,
            self::currentFuelFor($row, $fuelByAssignment),
            self::currentKeyFor($row, $keyByAssignment),
        );

        $pending = QueueLineEligibility::sortItems(
            $activeRows->filter(fn (OrderProduct $row) => QueueLineEligibility::boardStatus($row) === QueueLineEligibility::STATUS_PENDING)
        );
        $staged = QueueLineEligibility::sortItems(
            $activeRows->filter(fn (OrderProduct $row) => QueueLineEligibility::boardStatus($row) === QueueLineEligibility::STATUS_STAGED)
        );
        $completed = $completedRows->sortByDesc(fn (OrderProduct $row) => $row->queueLineItem?->completed_at)->values();

        $items = [
            'pending' => $pending->map($serialize)->values()->all(),
            'staged' => $staged->map($serialize)->values()->all(),
            'completed' => $completed->map($serialize)->values()->all(),
        ];

        return ['items' => $items, 'meta' => self::meta($activeRows, $fuelByAssignment, $keyByAssignment, $completed->count())];
    }

    /** Lightweight counts for the home-page badge (§15) — no item payloads. */
    public static function summary(?int $storeId = null): array
    {
        $activeRows = QueueLineEligibility::filterFinanciallyActive(
            QueueLineEligibility::boardQuery($storeId)->get()
        );
        $completedCount = QueueLineEligibility::filterFinanciallyActive(
            QueueLineEligibility::completedQuery($storeId)->get()
        )->count();
        $fuelByAssignment = self::fuelMap($activeRows);
        $keyByAssignment = QueueLineStagingService::keyMap($activeRows);

        return self::meta($activeRows, $fuelByAssignment, $keyByAssignment, $completedCount)['counts'];
    }

    /** One item, detail depth (adds suppression + completion state). */
    public static function item(OrderProduct $row): array
    {
        $fuel = QueueFuelVerificationService::currentVerification($row);
        $key = QueueLineStagingService::currentKey($row);
        $data = self::serialize($row, $fuel, $key);

        $queueItem = $row->queueLineItem;
        $data['suppression'] = [
            'removed_today' => (bool) $queueItem?->isSuppressedToday(),
            'removed_forever' => (bool) $queueItem?->suppressed_forever,
        ];
        $data['completed'] = $queueItem?->completed_at !== null;

        return $data;
    }

    /**
     * @param  QueueLineFuelVerification|null  $fuel  the CURRENT-episode verification (pre-resolved)
     * @param  \App\Models\Orders\QueueLineKeyConfirmation|null  $key  the CURRENT-episode key confirmation (pre-resolved)
     */
    public static function serialize(OrderProduct $row, ?QueueLineFuelVerification $fuel, $key = null): array
    {
        $classification = QueueLineEligibility::classifyAssignment($row);
        $equipment = $row->softAssignment?->equipment;
        $order = $row->order;

        $assignmentState = match ($classification) {
            QueueLineEligibility::ASSIGNMENT_UNASSIGNED => 'needs_equipment',
            QueueLineEligibility::ASSIGNMENT_UNKNOWN => 'assignment_product_unknown',
            default => $classification, // direct | alternate
        };

        return [
            'order_product_unique_id' => $row->unique_id,
            'order_unique_id' => $order->unique_id,
            'order_number' => (string) $order->order_number,
            'customer_name' => $order->customer_name,
            'store' => $row->deliveryStore ? [
                'unique_id' => $row->deliveryStore->unique_id,
                'name' => $row->deliveryStore->store_name,
            ] : null,
            'product' => [
                'unique_id' => $row->product?->unique_id,
                'name' => $row->product_name,
                'image_url' => $row->product?->image_url,
            ],
            'delivery' => [
                'type_label' => QueueLineEligibility::deliveryTypeLabel($row),
                'transport_mode' => $row->delivery_transport_mode,
                'date' => $row->delivery_date ? Carbon::parse($row->delivery_date)->format('Y-m-d') : null,
                'dispatch_date' => $row->dispatch_delivery_date
                    ? Carbon::parse($row->dispatch_delivery_date)->format('Y-m-d')
                    : null,
                'time' => $row->delivery_time,
            ],
            'urgency' => $row->queueLineItem?->isRushed() ? 'rush' : QueueLineEligibility::bucketFor($row),
            'assignment_state' => $assignmentState,
            'equipment' => $equipment ? [
                'unique_id' => $equipment->unique_id,
                'display_id' => $equipment->equipment_id,
                'name' => $equipment->equipment_name,
                'assigned_product_name' => $equipment->assignedProduct?->product_name,
                'status' => $equipment->current_status?->value,
                'status_label' => $equipment->status_label,
                'rental_ready' => QueueLineEligibility::rentalReadyLabel($row),
            ] : null,
            'options_count' => QueueLineEligibility::optionsCount($row),
            // Applicability (2026-07-21): 'not_applicable' = the unit has no
            // such trait (not Diesel/Gas → no fuel; no 1 Key / 2 Keys
            // starting mechanism → no key). The app must HIDE that check in
            // the staging dialog and omit its field from mark-staged.
            'fuel' => [
                'state' => $equipment && ! $equipment->requiresFuelCheck()
                    ? 'not_applicable'
                    : ($fuel ? 'verified' : 'not_verified'),
                'verified_by' => $fuel?->performedBy?->full_name,
                'verified_at' => $fuel?->created_at?->toIso8601String(),
            ],
            'key' => [
                'state' => $equipment && ! $equipment->requiresKeyCheck()
                    ? 'not_applicable'
                    : ($key ? 'confirmed' : 'not_confirmed'),
                'confirmed_by' => $key?->performedBy?->full_name,
                'confirmed_at' => $key?->created_at?->toIso8601String(),
            ],
            'payment_label' => $order->last_payment_status === null
                ? 'No Payment Recorded' // approved Phase 2 wording — never guessed
                : (string) $order->last_payment_status,
            'status' => QueueLineEligibility::boardStatus($row), // pending | staged | completed — the board section this item belongs to
            'staged' => (bool) $row->queueLineItem?->isStaged(),
            'fully_staged' => QueueLineStagingService::isFullyStaged($row, $fuel, $key),
            'completed' => $isCompleted = $row->queueLineItem?->completed_at !== null,
            'readiness' => self::readiness($assignmentState, QueueLineStagingService::isFullyStaged($row, $fuel, $key), $row),
            'available_actions' => self::availableActions($assignmentState, QueueLineStagingService::isFullyStaged($row, $fuel, $key), $isCompleted),
        ];
    }

    private static function readiness(string $assignmentState, bool $fullyStaged, OrderProduct $row): string
    {
        if ($assignmentState === 'needs_equipment') {
            return self::READINESS_EQUIPMENT_REQUIRED;
        }

        if (! $fullyStaged) {
            return self::READINESS_STAGING_REQUIRED;
        }

        return $row->delivery_transport_mode === 'Truck'
            ? self::READINESS_DISPATCH
            : self::READINESS_HANDOFF;
    }

    /**
     * The server decides what the app may offer — the app never infers.
     * Explicit boolean map (§8): every action is present with its current
     * availability. Staging is ALL-OR-NOTHING: mark_staged records fuel +
     * key + staged atomically, return_to_pending reverses the whole set —
     * there is no fuel-only step. Release actions never appear
     * (Driver/Customer Checklist own them).
     */
    private static function availableActions(string $assignmentState, bool $fullyStaged, bool $isCompleted = false): array
    {
        // Completed items already left the yard — read-only, history only.
        if ($isCompleted) {
            return [
                'assign_equipment' => false,
                'switch_equipment' => false,
                'mark_staged' => false,
                'return_to_pending' => false,
                'view_history' => true,
            ];
        }

        $needsEquipment = $assignmentState === 'needs_equipment';

        return [
            'assign_equipment' => $needsEquipment,
            'switch_equipment' => ! $needsEquipment,
            'mark_staged' => ! $needsEquipment && ! $fullyStaged,
            'return_to_pending' => $fullyStaged,
            'view_history' => true,
        ];
    }

    /** Current verifications for a whole board in ONE query, keyed by episode. */
    private static function fuelMap(Collection $rows): Collection
    {
        return QueueLineFuelVerification::query()
            ->whereIn('order_product_id', $rows->pluck('id')->all() ?: [0])
            ->where('action', QueueLineFuelVerification::ACTION_VERIFIED)
            ->whereDoesntHave('reversal')
            ->with('performedBy:id,first_name,last_name')
            ->get()
            ->keyBy('equipment_soft_assign_id');
    }

    private static function currentFuelFor(OrderProduct $row, Collection $fuelByAssignment): ?QueueLineFuelVerification
    {
        return $row->softAssignment
            ? ($fuelByAssignment[$row->softAssignment->id] ?? null)
            : null;
    }

    private static function currentKeyFor(OrderProduct $row, Collection $keyByAssignment)
    {
        return $row->softAssignment
            ? ($keyByAssignment[$row->softAssignment->id] ?? null)
            : null;
    }

    private static function meta(Collection $rows, Collection $fuelByAssignment, Collection $keyByAssignment, int $completedCount = 0): array
    {
        $counts = [
            'total' => $rows->count(),
            'rush' => 0, 'overdue' => 0, 'today' => 0, 'tomorrow' => 0,
            'needs_equipment' => 0,
            'staging_required' => 0, // assigned but not yet fully staged
            'completed' => $completedCount,
        ];

        foreach ($rows as $row) {
            $counts[$row->queueLineItem?->isRushed() ? 'rush' : QueueLineEligibility::bucketFor($row)]++;

            if (! $row->softAssignment?->equipment) {
                $counts['needs_equipment']++;
            } elseif (! QueueLineStagingService::isFullyStaged(
                $row,
                self::currentFuelFor($row, $fuelByAssignment),
                self::currentKeyFor($row, $keyByAssignment),
            )) {
                $counts['staging_required']++;
            }
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'counts' => $counts,
        ];
    }
}
