<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchAuditLog;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use App\Services\Dispatch\DispatchReorderService;
use Illuminate\Http\Request;
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

        // Both endpoints of the move must be real, active drivers.
        $driverIds = User::active()->where('is_driver', true)
            ->whereIn('id', array_unique([$srcDriver, $dstDriver]))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (!in_array($srcDriver, $driverIds, true) || !in_array($dstDriver, $driverIds, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid driver for this move.'], 422);
        }

        // Resolve the destination (and, for cross-driver moves, the source) driver's
        // new unified order. Combined view sends it directly; Separate view sends a
        // single leg's order that the service reconciles into the unified sequence.
        if ($view === 'combined') {
            $destUnified = $this->normalizeItems($validated['dest_order'] ?? []);
            $srcUnified  = $crossDriver ? $this->normalizeItems($validated['source_order'] ?? []) : null;
        } else {
            $destUnified = $destIdle
                ? [['uid' => $movedUid, 'leg' => $leg]]
                : $this->service->reconcileSeparate(
                    $this->normalizeItems($validated['dest_current_unified'] ?? []),
                    array_values($validated['dest_leg_order'] ?? []),
                    $leg,
                    $crossDriver ? $movedUid : null,
                    null
                );

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

        $destUids = array_column($destUnified, 'uid');
        if (empty($destUids) || !in_array($movedUid, $destUids, true)) {
            return response()->json(['success' => false, 'message' => 'The moved job is missing from the destination.'], 422);
        }

        // Load every referenced order product once (no N+1).
        $allUids = array_values(array_unique(array_merge(
            $destUids,
            $srcUnified !== null ? array_column($srcUnified, 'uid') : []
        )));
        /** @var Collection<string, OrderProduct> $byUid */
        $byUid = OrderProduct::whereIn('unique_id', $allUids)->get()->keyBy('unique_id');

        foreach ($allUids as $uid) {
            if (!$byUid->has($uid)) {
                return response()->json(['success' => false, 'message' => 'Unknown job in the board payload.'], 422);
            }
        }

        $moved = $byUid->get($movedUid);
        $byField = $leg === 'delivery' ? 'delivery_by' : 'pickup_by';

        // Type safety backstop: the dragged leg must be a genuine pending Truck leg
        // on this job (a delivery can never masquerade as a return, or vice versa).
        if (!$this->legIsActive($moved, $leg)) {
            return response()->json(['success' => false, 'message' => 'That job has no active ' . $leg . ' leg to move.'], 422);
        }

        // Current-assignment-state guard: the card must still be assigned to the
        // source driver on this leg. If not, the board is stale — refuse and let the
        // client refresh rather than writing against an outdated view.
        if ((int) $moved->$byField !== $srcDriver) {
            return response()->json([
                'success' => false,
                'message' => 'This job is no longer assigned as shown — the board has been refreshed.',
            ], 409);
        }

        $oldPriority = $leg === 'delivery' ? $moved->delivery_priority : $moved->pickup_priority;

        DB::transaction(function () use ($destUnified, $srcUnified, $byUid, $movedUid, $dstDriver, $leg, $crossDriver) {
            // Destination: renumber 1..N; (re)assign the moved card's driver only on a
            // cross-driver move, mirroring the canonical writer's lock bookkeeping.
            $this->renumber(
                $destUnified,
                $byUid,
                $crossDriver ? $movedUid : null,
                $crossDriver ? $dstDriver : null,
                $leg
            );

            // Source: close the gap left behind.
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
