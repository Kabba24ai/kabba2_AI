<?php

namespace App\Services\QueueLine;

use App\Enums\Orders\OrderHistoryAction;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\QueueLineFuelVerification;
use App\Models\Orders\QueueLineItem;
use Illuminate\Support\Collection;

/**
 * Combined Queue Line operational timeline for ONE order item (Phase 4 §5).
 *
 * A read-only presentation assembler — it duplicates nothing. Every event is
 * projected live from the records the lifecycle already writes:
 *   - order_histories (equipment_reassigned rows, filtered to this item)
 *   - queue_line_fuel_verifications (append-only verify/reverse ledger)
 *   - queue_line_items sidecar (stage / rush / remove / completion latch)
 *   - equipment_soft_assigns (current reservation episode)
 *   - order_products.delivery_ready_to_go_at (dispatch start)
 *
 * Answers the admin question: which machine was selected, who verified its
 * fuel, and how did it leave the Queue Line?
 */
final class QueueLineHistory
{
    public const TYPE_ASSIGNMENT = 'assignment';

    public const TYPE_FUEL = 'fuel';

    public const TYPE_RELEASE = 'release';

    public const TYPE_REVERSAL = 'reversal';

    public const TYPE_ADMIN = 'admin';

    /**
     * @return Collection<int, array{at: \Illuminate\Support\Carbon, type: string, title: string, detail: ?string, employee: ?string, actor: ?string, equipment: ?string}>
     *                          chronological (oldest first)
     */
    public static function timeline(OrderProduct $orderProduct): Collection
    {
        $events = collect()
            ->merge(self::assignmentEvents($orderProduct))
            ->merge(self::fuelEvents($orderProduct))
            ->merge(self::sidecarEvents($orderProduct))
            ->merge(self::releaseEvents($orderProduct));

        return $events
            ->sortBy([['at', 'asc']])
            ->values();
    }

    /** Current reservation + every recorded switch (order history is the canonical switch ledger). */
    private static function assignmentEvents(OrderProduct $orderProduct): Collection
    {
        $events = collect();

        // The live soft assignment is the only surviving episode row — its
        // created_at marks when the CURRENT unit was reserved/staged.
        if ($soft = $orderProduct->softAssignment) {
            $events->push([
                'at' => $soft->created_at,
                'type' => self::TYPE_ASSIGNMENT,
                'title' => 'Equipment reserved',
                'detail' => 'Current assignment: ' . ($soft->equipment?->equipment_name ?? 'unknown unit'),
                'employee' => $soft->assigned_by ? User::withTrashed()->find($soft->assigned_by)?->full_name : null,
                'actor' => null,
                'equipment' => $soft->equipment?->equipment_id,
            ]);
        }

        $switchRows = $orderProduct->order
            ? $orderProduct->order->history()
                ->where('action', OrderHistoryAction::EquipmentReassigned)
                ->orderBy('action_date')
                ->get()
            : collect();

        foreach ($switchRows as $row) {
            $extras = json_decode($row->extras ?? '', true) ?: [];

            if ((int) ($extras['order_product_id'] ?? 0) !== (int) $orderProduct->id) {
                continue; // switch belonged to a sibling item on this order
            }

            $classification = $extras['classification'] ?? null;
            $detailParts = array_filter([
                sprintf(
                    '%s → %s',
                    $extras['previous_equipment_name'] ?? 'unassigned',
                    $extras['replacement_equipment_name'] ?? 'unknown unit',
                ),
                $classification ? 'Classification: ' . ucfirst($classification) : null,
                filled($extras['reason'] ?? null) ? 'Reason: ' . $extras['reason'] : null,
            ]);

            $events->push([
                'at' => $row->action_date ?? $row->created_at,
                'type' => self::TYPE_ASSIGNMENT,
                'title' => 'Equipment switched',
                'detail' => implode(' · ', $detailParts),
                'employee' => $extras['performed_by_name'] ?? null,
                'actor' => $row->user?->full_name,
                'equipment' => null,
            ]);
        }

        return $events;
    }

    /** Full append-only fuel ledger — verifications AND reversals, all episodes. */
    private static function fuelEvents(OrderProduct $orderProduct): Collection
    {
        return QueueLineFuelVerification::query()
            ->where('order_product_id', $orderProduct->id)
            ->with(['equipment:id,equipment_id,equipment_name', 'performedBy:id,first_name,last_name', 'createdBy:id,first_name,last_name'])
            ->orderBy('created_at')
            ->get()
            ->map(function (QueueLineFuelVerification $row) use ($orderProduct) {
                $isReversal = $row->action === QueueLineFuelVerification::ACTION_REVERSED;
                $isCurrentEpisode = $orderProduct->softAssignment
                    && (int) $row->equipment_soft_assign_id === (int) $orderProduct->softAssignment->id;

                $detailParts = array_filter([
                    $isReversal && filled($row->reason) ? 'Reason: ' . $row->reason : null,
                    $isCurrentEpisode ? null : 'Earlier assignment of ' . ($row->equipment?->equipment_name ?? 'a previous unit'),
                    'Source: ' . str_replace('_', ' ', (string) $row->source),
                ]);

                return [
                    'at' => $row->created_at,
                    'type' => $isReversal ? self::TYPE_REVERSAL : self::TYPE_FUEL,
                    'title' => $isReversal ? 'Fuel verification reversed' : 'Fuel Full verified',
                    'detail' => implode(' · ', $detailParts) ?: null,
                    'employee' => $row->performedBy?->full_name,
                    'actor' => $row->createdBy?->full_name,
                    'equipment' => $row->equipment?->equipment_id,
                    // Structured flags for the mobile API (blade ignores them):
                    // prior-episode sign-offs must never read as current.
                    'source' => str_replace('_', ' ', (string) $row->source),
                    'current_episode' => $isCurrentEpisode,
                ];
            });
    }

    /** Stage / rush / remove events from the sidecar row (only current flags exist — no invented history). */
    private static function sidecarEvents(OrderProduct $orderProduct): Collection
    {
        $item = QueueLineItem::withTrashed()->where('order_product_id', $orderProduct->id)->first();

        if (! $item) {
            return collect();
        }

        $events = collect();

        if ($item->staged_at) {
            $events->push([
                'at' => $item->staged_at,
                'type' => self::TYPE_ADMIN,
                'title' => 'Marked staged on the Queue Line',
                'detail' => null,
                'employee' => null,
                'actor' => $item->stagedBy?->full_name,
                'equipment' => null,
            ]);
        }

        if ($item->rush_at) {
            $events->push([
                'at' => $item->rush_at,
                'type' => self::TYPE_ADMIN,
                'title' => 'Marked RUSH',
                'detail' => null,
                'employee' => null,
                'actor' => $item->rushBy?->full_name,
                'equipment' => null,
            ]);
        }

        if ($item->suppressed_forever && $item->suppressed_forever_at) {
            $events->push([
                'at' => $item->suppressed_forever_at,
                'type' => self::TYPE_ADMIN,
                'title' => 'Removed from the Queue Line',
                'detail' => 'Removed until restored — release enforcement no longer applies.',
                'employee' => null,
                'actor' => $item->suppressed_forever_by ? User::withTrashed()->find($item->suppressed_forever_by)?->full_name : null,
                'equipment' => null,
            ]);
        }

        return $events;
    }

    /** Dispatch start + the completion latch (how the item left the Queue Line). */
    private static function releaseEvents(OrderProduct $orderProduct): Collection
    {
        $events = collect();

        if ($orderProduct->delivery_ready_to_go_at) {
            $events->push([
                'at' => $orderProduct->delivery_ready_to_go_at,
                'type' => self::TYPE_RELEASE,
                'title' => 'Ready to Go (prep)',
                'detail' => 'Driver checklist prep complete — fuel, keys, attachments. Truck not yet departed.',
                'employee' => null,
                'actor' => null,
                'equipment' => null,
            ]);
        }

        if ($orderProduct->delivery_on_my_way_at) {
            $events->push([
                'at' => $orderProduct->delivery_on_my_way_at,
                'type' => self::TYPE_RELEASE,
                'title' => 'Load Map & Go — departed the yard',
                'detail' => 'Driver went en route ("On My Way") — the equipment left the yard.',
                'employee' => null,
                'actor' => null,
                'equipment' => null,
            ]);
        }

        $item = QueueLineItem::withTrashed()->where('order_product_id', $orderProduct->id)->first();

        if ($item?->completed_at) {
            $completedEquipment = $item->completed_equipment_id
                ? \App\Models\MaintenanceManagement\Equipment::withTrashed()->find($item->completed_equipment_id)
                : null;

            $events->push([
                'at' => $item->completed_at,
                'type' => self::TYPE_RELEASE,
                'title' => $item->staged_at === null ? 'Left the yard — FAST TRACK (not staged)' : 'Left the yard',
                'detail' => match ($item->completed_via) {
                    QueueLineService::VIA_DISPATCH_STARTED => 'Completed when the driver departed (Load Map & Go).',
                    QueueLineService::VIA_SCHEDULE_COMPLETED => 'Completed when the delivery was marked Completed (direct pickup / administrative completion).',
                    default => 'Completed when the customer checklist was saved (in-store handoff).',
                },
                'employee' => null,
                'actor' => null,
                'equipment' => $completedEquipment?->equipment_id,
            ]);
        }

        return $events;
    }
}
