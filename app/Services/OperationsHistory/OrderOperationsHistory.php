<?php

namespace App\Services\OperationsHistory;

use App\Enums\Orders\OrderHistoryAction;
use App\Models\Dispatch\DispatchAuditLog;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Services\QueueLine\QueueLineHistory;
use Illuminate\Support\Collection;

/**
 * Order-level operational audit projection (2026-07-23, Enhancement 6/7).
 *
 * A READ-ONLY assembler for the Operations History modal on Order Details. It
 * duplicates nothing and writes nothing — every event is projected live from
 * the append-only records the operational workflows already keep. Four tabs:
 *
 *   • staging    — reuses QueueLineHistory (fuel verify/reverse ledger, stage /
 *                  rush / remove sidecar, and how the item left the yard)
 *   • dispatch   — driver departure workflow: Ready to Go (prep) → Load Map &
 *                  Go (departed) → Arrived, per leg, plus driver-checklist
 *                  order-history rows and dispatch date-change audit rows
 *   • checklist  — customer delivery/return/removal checklist order-history rows
 *   • assignment — reuses QueueLineHistory (equipment reserved + every switch)
 *
 * Every tab is a chronological Collection of normalized event arrays:
 *   { at: ?Carbon, title: string, detail: ?string, employee: ?string,
 *     actor: ?string, product: ?string, tone: string }
 *
 * Append-only by construction: nothing here can edit or remove a record, so the
 * audit philosophy (skips, reversals, repeats, overrides are all preserved) is
 * satisfied simply by never mutating the sources.
 */
final class OrderOperationsHistory
{
    /** @return array{staging: Collection, dispatch: Collection, checklist: Collection, assignment: Collection} */
    public static function forOrder(Order $order): array
    {
        $rentalRows = OrderProduct::where('order_id', $order->id)
            ->where('product_data->product_type', 'Rental')
            ->with(['softAssignment.equipment', 'product:id,product_name'])
            ->get()
            ->each(fn (OrderProduct $row) => $row->setRelation('order', $order));

        return [
            'staging'    => self::staging($rentalRows),
            'dispatch'   => self::dispatch($rentalRows, $order),
            'checklist'  => self::checklist($order),
            'assignment' => self::assignment($rentalRows),
        ];
    }

    /** Staging + fuel + how it left — QueueLineHistory minus the assignment rows. */
    private static function staging(Collection $rentalRows): Collection
    {
        return $rentalRows
            ->flatMap(fn (OrderProduct $row) => QueueLineHistory::timeline($row)
                ->reject(fn ($e) => $e['type'] === QueueLineHistory::TYPE_ASSIGNMENT)
                ->map(fn ($e) => self::normalize($e, $row)))
            ->sortBy([['at', 'asc']])
            ->values();
    }

    /** Equipment reserved + every canonical switch — QueueLineHistory assignment rows. */
    private static function assignment(Collection $rentalRows): Collection
    {
        return $rentalRows
            ->flatMap(fn (OrderProduct $row) => QueueLineHistory::timeline($row)
                ->filter(fn ($e) => $e['type'] === QueueLineHistory::TYPE_ASSIGNMENT)
                ->map(fn ($e) => self::normalize($e, $row)))
            ->sortBy([['at', 'asc']])
            ->values();
    }

    /** Driver departure workflow (net-new projection). */
    private static function dispatch(Collection $rentalRows, Order $order): Collection
    {
        $events = collect();

        foreach ($rentalRows as $row) {
            $product = $row->product?->product_name ?? $row->product_name;

            $stamps = [
                ['at' => $row->delivery_ready_to_go_at, 'title' => 'Ready to Go (prep)', 'detail' => 'Delivery — driver prep complete; truck not yet departed.'],
                ['at' => $row->delivery_on_my_way_at,   'title' => 'Load Map & Go — departed the yard', 'detail' => 'Delivery — driver went en route ("On My Way").'],
                ['at' => $row->delivery_arrived_at,      'title' => 'Arrived at customer', 'detail' => 'Delivery — driver marked Arrived.'],
                ['at' => $row->pickup_ready_to_go_at,   'title' => 'Return — Ready to Go (prep)', 'detail' => 'Return leg — driver prep complete.'],
                ['at' => $row->pickup_on_my_way_at,      'title' => 'Return — Load Map & Go', 'detail' => 'Return leg — driver went en route.'],
                ['at' => $row->pickup_arrived_at,        'title' => 'Return — Arrived', 'detail' => 'Return leg — driver marked Arrived.'],
            ];

            foreach ($stamps as $stamp) {
                if ($stamp['at']) {
                    $events->push([
                        'at' => $stamp['at'], 'title' => $stamp['title'], 'detail' => $stamp['detail'],
                        'employee' => null, 'actor' => null, 'product' => $product, 'tone' => 'indigo',
                    ]);
                }
            }

            foreach (DispatchAuditLog::where('order_product_id', $row->id)->with('user:id,first_name,last_name')->orderBy('created_at')->get() as $log) {
                $events->push([
                    'at' => $log->created_at,
                    'title' => ucwords(str_replace('_', ' ', (string) $log->action)),
                    'detail' => trim(sprintf('%s: %s → %s', $log->field, $log->old_value ?? '—', $log->new_value ?? '—'), ': '),
                    'employee' => null, 'actor' => $log->user?->full_name, 'product' => $product, 'tone' => 'sky',
                ]);
            }
        }

        // Driver-checklist order-history rows (fuel / key / status per save).
        foreach (self::historyRows($order, [OrderHistoryAction::DriverChecklistUpdated]) as $row) {
            $events->push([
                'at' => $row->action_date ?? $row->created_at,
                'title' => 'Driver checklist updated',
                'detail' => $row->description,
                'employee' => null, 'actor' => $row->user?->full_name, 'product' => null, 'tone' => 'sky',
            ]);
        }

        return $events->sortBy([['at', 'asc']])->values();
    }

    /** Customer checklist delivered / returned / removed (net-new projection). */
    private static function checklist(Order $order): Collection
    {
        $actions = [
            OrderHistoryAction::ChecklistDelivered,
            OrderHistoryAction::ChecklistReturned,
            OrderHistoryAction::ChecklistRemoved,
        ];

        return self::historyRows($order, $actions)
            ->map(fn ($row) => [
                'at' => $row->action_date ?? $row->created_at,
                'title' => $row->action?->label() ?? 'Customer checklist',
                'detail' => $row->description,
                'employee' => null,
                'actor' => $row->user?->full_name,
                'product' => null,
                'tone' => $row->action === OrderHistoryAction::ChecklistRemoved ? 'amber' : 'emerald',
            ])
            ->sortBy([['at', 'asc']])
            ->values();
    }

    /** @param array<int, OrderHistoryAction> $actions */
    private static function historyRows(Order $order, array $actions): Collection
    {
        return $order->history()
            ->whereIn('action', array_map(fn ($a) => $a->value, $actions))
            ->with('user:id,first_name,last_name')
            ->orderBy('action_date')
            ->get();
    }

    /** QueueLineHistory event → the modal's normalized shape, tagged with its product. */
    private static function normalize(array $event, OrderProduct $row): array
    {
        $tone = match ($event['type']) {
            QueueLineHistory::TYPE_FUEL => 'emerald',
            QueueLineHistory::TYPE_RELEASE => 'indigo',
            QueueLineHistory::TYPE_REVERSAL => 'amber',
            QueueLineHistory::TYPE_ASSIGNMENT => 'sky',
            default => 'gray',
        };

        return [
            'at' => $event['at'],
            'title' => $event['title'],
            'detail' => $event['detail'] ?? null,
            'employee' => $event['employee'] ?? null,
            'actor' => $event['actor'] ?? null,
            'product' => $row->product?->product_name ?? $row->product_name,
            'tone' => $tone,
        ];
    }
}
