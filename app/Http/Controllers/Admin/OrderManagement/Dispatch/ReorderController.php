<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchAuditLog;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use App\Services\Dispatch\DispatchReorderService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Drag-and-drop reorder / cross-driver reassign for the Dispatch board.
 *
 * The board is server-authoritative: the browser reports the new ordering intent,
 * this controller validates it, renumbers the affected driver(s)' unified activity
 * sequence 1..N atomically, and persists. On any failure the transaction rolls
 * back and the board is re-rendered from the database (which restores the original
 * order). Priority and driver assignment are written using the existing columns and
 * the same lock bookkeeping the canonical schedule writer
 * ({@see \App\Http\Controllers\Admin\OrderManagement\Orders\UpdateProductScheduleController})
 * uses — no parallel assignment path is created.
 *
 * Placement modes:
 *  - 'manual' (within-driver reorder): the card keeps the exact position the
 *    dispatcher dragged it to.
 *  - 'date' (moved to another driver or an idle driver): the arriving card is
 *    auto-placed by effective date, then the dispatcher explicitly approves that
 *    placement. `preview=true` returns the computed placement WITHOUT writing so the
 *    UI can show the confirmation; the approved commit re-sends without `preview`.
 *
 * See {@see DispatchReorderService} for the (unit-tested, DB-free) sequencing rules.
 */
class ReorderController extends Controller
{
    public function __construct(private DispatchReorderService $service)
    {
    }

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'view'             => 'required|in:combined,separate',
            'leg'              => 'required|in:delivery,return',
            'moved_uid'        => 'required|string',
            'source_driver_id' => 'required|integer',
            'dest_driver_id'   => 'required|integer',
            'dest_idle'        => 'sometimes|boolean',
            'placement'        => 'sometimes|in:manual,date',
            'preview'          => 'sometimes|boolean',
            'confirmed'        => 'sometimes|boolean',

            // Combined-view payload: full unified orders ([{uid, leg}]).
            'dest_order'         => 'sometimes|array',
            'dest_order.*.uid'   => 'required_with:dest_order|string',
            'dest_order.*.leg'   => 'required_with:dest_order|in:delivery,return',
            'source_order'       => 'sometimes|array',
            'source_order.*.uid' => 'required_with:source_order|string',
            'source_order.*.leg' => 'required_with:source_order|in:delivery,return',

            // Separate-view payload: dragged leg column order + current unified snapshot.
            'dest_leg_order'                 => 'sometimes|array',
            'dest_leg_order.*'               => 'string',
            'dest_current_unified'           => 'sometimes|array',
            'dest_current_unified.*.uid'     => 'required_with:dest_current_unified|string',
            'dest_current_unified.*.leg'     => 'required_with:dest_current_unified|in:delivery,return',
            'source_leg_order'               => 'sometimes|array',
            'source_leg_order.*'             => 'string',
            'source_current_unified'         => 'sometimes|array',
            'source_current_unified.*.uid'   => 'required_with:source_current_unified|string',
            'source_current_unified.*.leg'   => 'required_with:source_current_unified|in:delivery,return',
        ]);

        $view        = $validated['view'];
        $leg         = $validated['leg'];
        $movedUid    = $validated['moved_uid'];
        $srcDriver   = (int) $validated['source_driver_id'];
        $dstDriver   = (int) $validated['dest_driver_id'];
        $crossDriver = $srcDriver !== $dstDriver;
        $destIdle    = (bool) ($validated['dest_idle'] ?? false);
        $placement   = $validated['placement'] ?? 'manual';
        $isPreview   = (bool) ($validated['preview'] ?? false);

        // Both endpoints of the move must be real, active drivers.
        $drivers = User::active()->where('is_driver', true)
            ->whereIn('id', array_unique([$srcDriver, $dstDriver]))
            ->get()->keyBy('id');
        if (!$drivers->has($srcDriver) || !$drivers->has($dstDriver)) {
            return response()->json(['success' => false, 'message' => 'Invalid driver for this move.'], 422);
        }

        // Load every referenced order product once (no N+1), with the order eager for
        // the confirmation preview.
        $rawUids = [$movedUid];
        foreach (['dest_order', 'source_order', 'dest_current_unified', 'source_current_unified'] as $k) {
            foreach (($validated[$k] ?? []) as $it) {
                if (isset($it['uid'])) {
                    $rawUids[] = (string) $it['uid'];
                }
            }
        }
        foreach (['dest_leg_order', 'source_leg_order'] as $k) {
            foreach (($validated[$k] ?? []) as $uid) {
                $rawUids[] = (string) $uid;
            }
        }
        $rawUids = array_values(array_unique($rawUids));

        /** @var Collection<string, OrderProduct> $byUid */
        $byUid = OrderProduct::with('order')
            ->whereIn('unique_id', $rawUids)->get()->keyBy('unique_id');

        $moved = $byUid->get($movedUid);
        if (!$moved) {
            return response()->json(['success' => false, 'message' => 'Unknown job in the board payload.'], 422);
        }

        // Type-safety backstop: the dragged leg must be a genuine pending Truck leg.
        if (!$this->legIsActive($moved, $leg)) {
            return response()->json(['success' => false, 'message' => 'That job has no active ' . $leg . ' leg to move.'], 422);
        }

        // Current-assignment-state guard: the card must still be assigned to the
        // source driver on this leg. If not, the board is stale.
        $byField = $leg === 'delivery' ? 'delivery_by' : 'pickup_by';
        if ((int) $moved->$byField !== $srcDriver) {
            return response()->json([
                'success' => false,
                'message' => 'This job is no longer assigned as shown — the board has been refreshed.',
            ], 409);
        }

        $arriving = ['uid' => $movedUid, 'leg' => $leg];

        // ---- Source driver's new order (gap-close). Unchanged by placement mode. ----
        if ($view === 'combined') {
            $srcUnified = $crossDriver ? $this->normalizeItems($validated['source_order'] ?? []) : null;
        } else {
            $srcUnified = $crossDriver
                ? $this->service->reconcileSeparate(
                    $this->normalizeItems($validated['source_current_unified'] ?? []),
                    array_values($validated['source_leg_order'] ?? []),
                    $leg,
                    null,
                    $movedUid
                )
                : null;
        }

        // ---- Destination driver's new order. ----
        if ($placement === 'date') {
            // The dispatcher's exact drop slot is ignored; the arriving card is placed
            // by effective date into the destination list (which excludes it).
            $destCurrent = $view === 'combined'
                ? $this->normalizeItems($validated['dest_order'] ?? [])
                : ($destIdle ? [] : $this->normalizeItems($validated['dest_current_unified'] ?? []));
            $destCurrent = array_values(array_filter($destCurrent, fn ($it) => $it['uid'] !== $movedUid));

            $dateByKey = [];
            foreach ($destCurrent as $it) {
                $dateByKey[$it['uid'] . '|' . $it['leg']] = $this->effectiveDate($byUid->get($it['uid']), $it['leg']);
            }
            $dateByKey[$movedUid . '|' . $leg] = $this->effectiveDate($moved, $leg);

            $destUnified = $this->service->insertByDate($destCurrent, $arriving, $dateByKey);
        } else {
            // Manual (within-driver reorder): honour the dragged position.
            if ($view === 'combined') {
                $destUnified = $this->normalizeItems($validated['dest_order'] ?? []);
            } else {
                $destUnified = $destIdle
                    ? [$arriving]
                    : $this->service->reconcileSeparate(
                        $this->normalizeItems($validated['dest_current_unified'] ?? []),
                        array_values($validated['dest_leg_order'] ?? []),
                        $leg,
                        $crossDriver ? $movedUid : null,
                        null
                    );
            }
        }

        $destUids = array_column($destUnified, 'uid');
        if (empty($destUids) || !in_array($movedUid, $destUids, true)) {
            return response()->json(['success' => false, 'message' => 'The moved job is missing from the destination.'], 422);
        }

        // ---- Preview: report the computed placement without writing anything. ----
        if ($isPreview) {
            $legUids = array_values(array_map(
                fn ($it) => $it['uid'],
                array_filter($destUnified, fn ($it) => $it['leg'] === $leg)
            ));
            $eff = $this->effectiveDate($moved, $leg);
            $legIdx = array_search($movedUid, $legUids, true);
            $uniIdx = array_search($movedUid, $destUids, true);

            return response()->json(['success' => true, 'preview' => [
                'order_number'     => $moved->order?->order_number ?? '',
                'customer'         => $moved->order?->customer_name ?? '',
                'leg'              => $leg,
                'date_label'       => $eff ? Carbon::parse($eff)->format('M j') : '—',
                'driver_name'      => $drivers->get($dstDriver)?->full_name ?? 'this driver',
                'leg_position'     => $legIdx === false ? 0 : $legIdx + 1,
                'leg_total'        => count($legUids),
                'unified_position' => $uniIdx === false ? 0 : $uniIdx + 1,
                'unified_total'    => count($destUids),
            ]]);
        }

        // ---- Commit. ----
        $oldPriority = $leg === 'delivery' ? $moved->delivery_priority : $moved->pickup_priority;

        DB::transaction(function () use ($destUnified, $srcUnified, $byUid, $movedUid, $dstDriver, $leg, $crossDriver) {
            $this->renumber(
                $destUnified,
                $byUid,
                $crossDriver ? $movedUid : null,
                $crossDriver ? $dstDriver : null,
                $leg
            );

            if ($srcUnified !== null) {
                $this->renumber($srcUnified, $byUid, null, null, $leg);
            }
        });

        $newPosition = array_search($movedUid, $destUids, true);
        DispatchAuditLog::create([
            'order_product_id' => $moved->id,
            'action'           => $crossDriver ? 'dispatch_reassigned' : 'dispatch_reordered',
            'field'            => $leg === 'delivery' ? 'delivery_priority' : 'pickup_priority',
            'old_value'        => $crossDriver ? (string) $srcDriver : (string) ($oldPriority ?? ''),
            'new_value'        => $crossDriver ? (string) $dstDriver : (string) ($newPosition === false ? '' : $newPosition + 1),
            'user_id'          => auth()->id(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Keep only well-formed {uid, leg} items with a valid leg.
     */
    private function normalizeItems(array $items): array
    {
        $out = [];
        foreach ($items as $it) {
            if (!isset($it['uid'], $it['leg']) || !in_array($it['leg'], ['delivery', 'return'], true)) {
                continue;
            }
            $out[] = ['uid' => (string) $it['uid'], 'leg' => $it['leg']];
        }

        return $out;
    }

    private function legIsActive(OrderProduct $op, string $leg): bool
    {
        return $leg === 'delivery'
            ? ($op->delivery_status === 'Pending' && $op->delivery_transport_mode === 'Truck')
            : ($op->pickup_status === 'Pending' && $op->pickup_transport_mode === 'Truck');
    }

    /**
     * Effective dispatch date for a leg as 'Y-m-d' (dispatch override, else the base
     * schedule date), or null if undated.
     */
    private function effectiveDate(?OrderProduct $op, string $leg): ?string
    {
        if (!$op) {
            return null;
        }
        $val = $leg === 'delivery'
            ? ($op->dispatch_delivery_date ?? $op->delivery_date)
            : ($op->dispatch_return_date ?? $op->pickup_date);
        if (!$val) {
            return null;
        }
        try {
            return Carbon::parse($val)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Write a unified order as contiguous 1..N priorities on the correct leg column,
     * locking each priority. When $assignUid is given (cross-driver move) that card is
     * (re)assigned to $assignDriverId on the dragged $leg with driver-lock set — the
     * same bookkeeping UpdateProductScheduleController applies on assignment.
     *
     * @param  Collection<string, OrderProduct> $byUid
     */
    private function renumber(array $unified, Collection $byUid, ?string $assignUid, ?int $assignDriverId, string $leg): void
    {
        foreach ($this->service->positions($unified) as $row) {
            $op = $byUid->get($row['uid']);
            if (!$op) {
                continue;
            }

            if ($row['leg'] === 'delivery') {
                $op->delivery_priority = $row['priority'];
                $op->delivery_priority_locked = true;
            } else {
                $op->pickup_priority = $row['priority'];
                $op->pickup_priority_locked = true;
            }

            if ($assignUid !== null && $row['uid'] === $assignUid && $assignDriverId !== null) {
                if ($leg === 'delivery') {
                    $op->delivery_by = $assignDriverId;
                    $op->delivery_driver_locked = true;
                } else {
                    $op->pickup_by = $assignDriverId;
                    $op->pickup_driver_locked = true;
                }
            }

            $op->save();
        }
    }
}
