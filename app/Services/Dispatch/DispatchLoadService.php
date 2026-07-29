<?php

namespace App\Services\Dispatch;

use App\Models\Dispatch\DispatchLoad;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use App\Services\Orders\OrderFinancialActivity;
use Illuminate\Support\Facades\DB;

/**
 * Combine / manage dispatch loads — grouping several order_product legs onto one
 * driver so they travel together. A load is single-leg and always belongs to one
 * driver. Members keep their own checklist/equipment/status and complete
 * individually; this service only touches the grouping FK, driver assignment (with
 * the canonical lock bookkeeping), and priority (renumbered so a load's members
 * stay a contiguous block in the driver's board sequence).
 *
 * Business-rule violations throw \RuntimeException; the controller maps them to 422.
 */
class DispatchLoadService
{
    public function __construct(private DispatchReorderService $reorder)
    {
    }

    /**
     * Combine the given order_product legs into a load (creating one, or adding to
     * the single existing load already among them) on $driverId.
     *
     * @param  string[] $memberUids order_product unique_ids
     */
    public function combine(array $memberUids, string $leg, int $driverId, ?int $userId): DispatchLoad
    {
        $leg = $leg === 'return' ? 'return' : 'delivery';
        $memberUids = array_values(array_unique(array_filter(array_map('strval', $memberUids))));
        if (count($memberUids) < 2) {
            throw new \RuntimeException('Select at least two items to combine into one dispatch.');
        }

        $driver = User::active()->where('is_driver', true)->find($driverId);
        if (!$driver) {
            throw new \RuntimeException('Choose a valid driver for the load.');
        }

        $ops = OrderProduct::with('order')->whereIn('unique_id', $memberUids)->get();
        if ($ops->count() !== count($memberUids)) {
            throw new \RuntimeException('One or more selected items could not be found.');
        }

        foreach ($ops as $op) {
            if (!$this->legIsActive($op, $leg)) {
                $num = $op->order?->order_number ?? $op->unique_id;
                throw new \RuntimeException("#{$num} has no active {$leg} leg to combine.");
            }
        }

        $loadCol = $this->loadColumn($leg);
        $existing = $ops->pluck($loadCol)->filter()->unique()->values();
        if ($existing->count() > 1) {
            throw new \RuntimeException('The selected items belong to different loads — ungroup them first.');
        }

        return DB::transaction(function () use ($ops, $leg, $driverId, $userId, $loadCol, $existing) {
            $load = $existing->count() === 1
                ? DispatchLoad::findOrFail($existing->first())
                : DispatchLoad::create(['leg' => $leg, 'driver_id' => $driverId, 'created_by' => $userId]);

            if ((int) $load->driver_id !== $driverId) {
                $load->update(['driver_id' => $driverId]);
            }

            $byField = $this->driverColumn($leg);
            $driverLock = $this->driverLockColumn($leg);

            foreach ($ops as $op) {
                $op->{$loadCol} = $load->id;
                $op->{$byField} = $driverId;
                $op->{$driverLock} = true;
                $op->save();
            }

            $this->regroup($driverId, $load);

            return $load->fresh();
        });
    }

    /** Reassign an entire load (and its members) to another driver, as a unit. */
    public function assignDriver(DispatchLoad $load, int $driverId): void
    {
        $driver = User::active()->where('is_driver', true)->find($driverId);
        if (!$driver) {
            throw new \RuntimeException('Choose a valid driver for the load.');
        }

        DB::transaction(function () use ($load, $driverId) {
            $byField = $this->driverColumn($load->leg);
            $driverLock = $this->driverLockColumn($load->leg);

            foreach ($load->members()->get() as $op) {
                $op->{$byField} = $driverId;
                $op->{$driverLock} = true;
                $op->save();
            }

            $load->update(['driver_id' => $driverId]);
            $this->regroup($driverId, $load);
        });
    }

    /** Dissolve the load; members remain on the driver at their current positions. */
    public function ungroup(DispatchLoad $load): void
    {
        DB::transaction(function () use ($load) {
            $col = $this->loadColumn($load->leg);
            OrderProduct::where($col, $load->id)->update([$col => null]);
            $load->delete();
        });
    }

    /** Remove one member; if fewer than two remain, the load dissolves. */
    public function removeMember(DispatchLoad $load, string $uid): void
    {
        DB::transaction(function () use ($load, $uid) {
            $col = $this->loadColumn($load->leg);

            $op = OrderProduct::where('unique_id', $uid)->where($col, $load->id)->first();
            if ($op) {
                $op->update([$col => null]);
            }

            if ($load->members()->count() < 2) {
                OrderProduct::where($col, $load->id)->update([$col => null]);
                $load->delete();
            }
        });
    }

    // ---- helpers ----

    private function loadColumn(string $leg): string
    {
        return $leg === 'delivery' ? 'delivery_load_id' : 'pickup_load_id';
    }

    private function driverColumn(string $leg): string
    {
        return $leg === 'delivery' ? 'delivery_by' : 'pickup_by';
    }

    private function driverLockColumn(string $leg): string
    {
        return $leg === 'delivery' ? 'delivery_driver_locked' : 'pickup_driver_locked';
    }

    private function legIsActive(OrderProduct $op, string $leg): bool
    {
        return $leg === 'delivery'
            ? ($op->delivery_status === 'Pending' && $op->delivery_transport_mode === 'Truck')
            : ($op->pickup_status === 'Pending' && $op->pickup_transport_mode === 'Truck');
    }

    /**
     * Renumber a driver's unified board sequence (both legs) with the load's members
     * pulled into one contiguous block, writing per-leg priorities (locked) so the
     * board and mobile list stay consistent.
     */
    private function regroup(int $driverId, DispatchLoad $load): void
    {
        $unified = $this->driverUnified($driverId);
        $memberUids = $load->members()->pluck('unique_id')->map(fn ($u) => (string) $u)->all();

        $order = $this->reorder->groupContiguous(
            array_map(fn ($r) => ['uid' => $r['uid'], 'leg' => $r['leg']], $unified),
            $memberUids,
            $load->leg
        );

        $opByUid = [];
        foreach ($unified as $r) {
            $opByUid[$r['uid']] = $r['op'];
        }

        foreach ($this->reorder->positions($order) as $row) {
            $op = $opByUid[$row['uid']] ?? null;
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
            $op->save();
        }
    }

    /**
     * The driver's current unified board order (both legs), matching the ordering in
     * IndexController::buildDriverCards (priority nulls-last, then effective date).
     *
     * @return array<int, array{uid:string, leg:string, op:OrderProduct, sort:int}>
     */
    private function driverUnified(int $driverId): array
    {
        $deliveryQ = OrderProduct::where('delivery_by', $driverId)
            ->where('delivery_status', 'Pending')
            ->where('delivery_transport_mode', 'Truck')
            ->orderByRaw('delivery_priority IS NULL, delivery_priority ASC')
            ->orderByRaw('COALESCE(dispatch_delivery_date, delivery_date) ASC');
        OrderFinancialActivity::excludeInactiveOrderProducts($deliveryQ);

        $returnQ = OrderProduct::where('pickup_by', $driverId)
            ->where('pickup_status', 'Pending')
            ->where('pickup_transport_mode', 'Truck')
            ->whereNotNull('pickup_date')
            ->orderByRaw('pickup_priority IS NULL, pickup_priority ASC')
            ->orderByRaw('COALESCE(dispatch_return_date, pickup_date) ASC');
        OrderFinancialActivity::excludeInactiveOrderProducts($returnQ);

        $tagged = [];
        foreach ($deliveryQ->get() as $op) {
            $tagged[] = ['uid' => (string) $op->unique_id, 'leg' => 'delivery', 'op' => $op, 'sort' => $op->delivery_priority ?? 9999];
        }
        foreach ($returnQ->get() as $op) {
            $tagged[] = ['uid' => (string) $op->unique_id, 'leg' => 'return', 'op' => $op, 'sort' => $op->pickup_priority ?? 9999];
        }

        usort($tagged, fn ($a, $b) => $a['sort'] <=> $b['sort']);

        return $tagged;
    }
}
