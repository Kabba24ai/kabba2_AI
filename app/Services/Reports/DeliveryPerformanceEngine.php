<?php

namespace App\Services\Reports;

use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * DeliveryPerformanceEngine — Driver Performance overview for the
 * Delivery Performance sales report.
 *
 * Metrics are derived from OrderProduct delivery/return fields and the
 * per-order checklist question/answer trail (OrderProductChecklistQuestion
 * + OrderProductChecklistQuestionAnswers), scoped to the report's date
 * range and store filter via SalesReportingService::resolveDateRange().
 */
class DeliveryPerformanceEngine
{
    public function __construct(
        private SalesReportingService $reporting,
    ) {}

    // ─── Public API ───────────────────────────────────────────────────────────

    public function reportData(array $filters): array
    {
        [$start, $end] = $this->reporting->resolveDateRange($filters);
        $storeId = $filters['store'] ?? null;

        $drivers = $this->driverPerformance($start, $end, $storeId);

        return [
            'kpis'    => $this->kpis($drivers),
            'drivers' => $drivers,
            'funnel'  => $this->stepFunnel($start, $end, $storeId),
            'flagged' => $this->flaggedQuestions($start, $end, $storeId),
        ];
    }

    public function availableDrivers(): Collection
    {
        return User::active()
            ->where('is_driver', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);
    }

    // ─── Private: Driver Performance ───────────────────────────────────────────

    private function driverPerformance(?\Carbon\Carbon $start, ?\Carbon\Carbon $end, ?int $storeId): array
    {
        $drivers = $this->availableDrivers();
        if ($drivers->isEmpty()) {
            return [];
        }
        $driverIds = $drivers->pluck('id');

        $deliveries = $this->driverLegQuery('delivery', $driverIds, $start, $end, $storeId)
            ->selectRaw('order_products.delivery_by as driver_id')
            ->selectRaw('COUNT(*) as completed')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, order_products.delivery_ready_to_go_at, order_products.delivery_arrived_at)) as avg_minutes')
            ->groupBy('order_products.delivery_by')
            ->get()
            ->keyBy('driver_id');

        $returns = $this->driverLegQuery('pickup', $driverIds, $start, $end, $storeId)
            ->selectRaw('order_products.pickup_by as driver_id')
            ->selectRaw('COUNT(*) as completed')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, order_products.pickup_ready_to_go_at, order_products.pickup_arrived_at)) as avg_minutes')
            ->groupBy('order_products.pickup_by')
            ->get()
            ->keyBy('driver_id');

        $pendingDeliveries = OrderProduct::query()
            ->whereIn('delivery_by', $driverIds)
            ->where('delivery_status', 'Pending')
            ->where('delivery_transport_mode', 'Truck')
            ->selectRaw('delivery_by as driver_id, COUNT(*) as cnt')
            ->groupBy('delivery_by')
            ->pluck('cnt', 'driver_id');

        $pendingReturns = OrderProduct::query()
            ->whereIn('pickup_by', $driverIds)
            ->where('pickup_status', 'Pending')
            ->where('pickup_transport_mode', 'Truck')
            ->selectRaw('pickup_by as driver_id, COUNT(*) as cnt')
            ->groupBy('pickup_by')
            ->pluck('cnt', 'driver_id');

        $checklistStats = $this->checklistPassRateByDriver($driverIds, $start, $end, $storeId);

        return $drivers->map(function ($driver) use ($deliveries, $returns, $pendingDeliveries, $pendingReturns, $checklistStats) {
            $d = $deliveries->get($driver->id);
            $r = $returns->get($driver->id);
            $c = $checklistStats->get($driver->id);

            $deliveriesCompleted = (int) ($d->completed ?? 0);
            $returnsCompleted    = (int) ($r->completed ?? 0);
            $completedTotal      = $deliveriesCompleted + $returnsCompleted;

            $checklistTotal   = (int) ($c->total ?? 0);
            $checklistFlagged = (int) ($c->flagged ?? 0);

            return [
                'id'                   => $driver->id,
                'name'                 => trim("{$driver->first_name} {$driver->last_name}"),
                'deliveries_completed' => $deliveriesCompleted,
                'returns_completed'    => $returnsCompleted,
                'total_completed'      => $completedTotal,
                'pending_deliveries'   => (int) $pendingDeliveries->get($driver->id, 0),
                'pending_returns'      => (int) $pendingReturns->get($driver->id, 0),
                'avg_delivery_minutes' => $d && $d->avg_minutes !== null ? round($d->avg_minutes) : null,
                'avg_return_minutes'  => $r && $r->avg_minutes !== null ? round($r->avg_minutes) : null,
                'checklist_pass_rate' => $checklistTotal > 0 ? round((($checklistTotal - $checklistFlagged) / $checklistTotal) * 100, 1) : null,
                'checklist_total'     => $checklistTotal,
            ];
        })
        ->sortByDesc('total_completed')
        ->values()
        ->toArray();
    }

    private function driverLegQuery(string $leg, Collection $driverIds, ?\Carbon\Carbon $start, ?\Carbon\Carbon $end, ?int $storeId)
    {
        $byColumn      = $leg === 'delivery' ? 'delivery_by' : 'pickup_by';
        $doneColumn    = $leg === 'delivery' ? 'is_delivered' : 'is_returned';
        $storeColumn   = $leg === 'delivery' ? 'delivery_store_id' : 'pickup_store_id';

        $query = OrderProduct::query()
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->whereNull('orders.deleted_at')
            ->whereNull('order_products.deleted_at')
            ->whereIn("order_products.$byColumn", $driverIds)
            ->where("order_products.$doneColumn", 1);

        if ($start && $end) {
            $query->whereBetween('orders.order_date', [$start->toDateString(), $end->toDateString()]);
        }
        if ($storeId) {
            $query->where("order_products.$storeColumn", $storeId);
        }

        return $query;
    }

    /**
     * Checklist pass rate per driver: counts selected delivery/return answers
     * (is_delivery_answer / is_return_answer) flagged as damaged via
     * CustomerAdminQuestionAnswer.is_damaged, attributed to the driver who
     * performed that leg (delivery_by / pickup_by on the parent order product).
     */
    private function checklistPassRateByDriver(Collection $driverIds, ?\Carbon\Carbon $start, ?\Carbon\Carbon $end, ?int $storeId): Collection
    {
        $rows = DB::table('order_product_checklist_question_answers as a')
            ->join('order_product_checklist_questions as q', 'q.id', '=', 'a.order_product_checklist_question_id')
            ->join('order_products', 'order_products.id', '=', 'q.order_product_id')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->join('customer_admin_question_answers as caa', 'caa.id', '=', 'a.answer_id')
            ->whereNull('a.deleted_at')
            ->whereNull('q.deleted_at')
            ->whereNull('order_products.deleted_at')
            ->whereNull('orders.deleted_at')
            ->where(function ($w) use ($driverIds) {
                $w->whereIn('order_products.delivery_by', $driverIds)
                  ->orWhereIn('order_products.pickup_by', $driverIds);
            })
            ->where(function ($w) {
                $w->where('a.is_delivery_answer', true)
                  ->orWhere('a.is_return_answer', true);
            });

        if ($start && $end) {
            $rows->whereBetween('orders.order_date', [$start->toDateString(), $end->toDateString()]);
        }
        if ($storeId) {
            $rows->where(function ($w) use ($storeId) {
                $w->where('order_products.delivery_store_id', $storeId)
                  ->orWhere('order_products.pickup_store_id', $storeId);
            });
        }

        $rows = $rows->select(
            'order_products.delivery_by',
            'order_products.pickup_by',
            'a.is_delivery_answer',
            'a.is_return_answer',
            'caa.is_damaged'
        )->get();

        $stats = [];
        foreach ($rows as $row) {
            $driverId = $row->is_delivery_answer ? $row->delivery_by : $row->pickup_by;
            if (!$driverId || !$driverIds->contains($driverId)) {
                continue;
            }
            $stats[$driverId] ??= ['total' => 0, 'flagged' => 0];
            $stats[$driverId]['total']++;
            if ($row->is_damaged) {
                $stats[$driverId]['flagged']++;
            }
        }

        return collect($stats)->map(fn($s, $id) => (object) array_merge($s, ['driver_id' => $id]))->keyBy('driver_id');
    }

    // ─── Private: KPI cards ─────────────────────────────────────────────────────

    private function kpis(array $drivers): array
    {
        $deliveries = array_sum(array_column($drivers, 'deliveries_completed'));
        $returns    = array_sum(array_column($drivers, 'returns_completed'));

        $passRates = array_filter(array_column($drivers, 'checklist_pass_rate'), fn($v) => $v !== null);

        return [
            'total_deliveries'   => $deliveries,
            'total_returns'      => $returns,
            'avg_checklist_pass' => count($passRates) ? round(array_sum($passRates) / count($passRates), 1) : null,
        ];
    }

    // ─── Private: Step funnel ───────────────────────────────────────────────────

    /**
     * Counts orders at each delivery/return gate within the date range/store
     * filter — mirrors the 4-gate Delivery Steps / 2-gate Return Steps logic
     * shown on the order edit page.
     */
    private function stepFunnel(?\Carbon\Carbon $start, ?\Carbon\Carbon $end, ?int $storeId): array
    {
        $base = OrderProduct::query()
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->whereNull('orders.deleted_at')
            ->whereNull('order_products.deleted_at');

        if ($start && $end) {
            $base->whereBetween('orders.order_date', [$start->toDateString(), $end->toDateString()]);
        }
        if ($storeId) {
            $base->where(function ($w) use ($storeId) {
                $w->where('order_products.delivery_store_id', $storeId)
                  ->orWhere('order_products.pickup_store_id', $storeId);
            });
        }

        $hasMedia = function ($query, string $type, string $fk) {
            return (clone $query)->whereExists(function ($sub) use ($type, $fk) {
                $sub->selectRaw('1')
                    ->from('order_media')
                    ->whereColumn("order_media.$fk", $fk === 'order_id' ? 'orders.id' : 'order_products.id')
                    ->where('order_media.type', $type)
                    ->whereNull('order_media.deleted_at');
            })->count();
        };

        $total = (clone $base)->count();

        return [
            'total'              => $total,
            'delivery_terms'     => (clone $base)->whereIn('orders.terms_status', ['Accepted', 'Exempt'])->count(),
            'delivery_license'   => $hasMedia($base, 'license', 'order_id'),
            'delivery_checklist' => (clone $base)->where('order_products.is_delivered', 1)->count(),
            'delivery_video'     => $hasMedia($base, 'delivery', 'order_product_id'),
            'return_checklist'   => (clone $base)->where('order_products.is_returned', 1)->count(),
            'return_video'       => $hasMedia($base, 'pickup', 'order_product_id'),
        ];
    }

    // ─── Private: Flagged checklist questions ──────────────────────────────────

    /**
     * Top 10 checklist questions most often answered with a flagged
     * (is_damaged) response during delivery or return, grouped by category.
     */
    private function flaggedQuestions(?\Carbon\Carbon $start, ?\Carbon\Carbon $end, ?int $storeId): array
    {
        $query = DB::table('order_product_checklist_question_answers as a')
            ->join('order_product_checklist_questions as q', 'q.id', '=', 'a.order_product_checklist_question_id')
            ->join('order_products', 'order_products.id', '=', 'q.order_product_id')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->join('customer_admin_question_answers as caa', 'caa.id', '=', 'a.answer_id')
            ->leftJoin('customer_admin_questions as caq', 'caq.id', '=', 'q.question_id')
            ->leftJoin('customer_admin_categories as cac', 'cac.id', '=', 'q.question_category_id')
            ->whereNull('a.deleted_at')
            ->whereNull('q.deleted_at')
            ->whereNull('order_products.deleted_at')
            ->whereNull('orders.deleted_at')
            ->where(function ($w) {
                $w->where('a.is_delivery_answer', true)
                  ->orWhere('a.is_return_answer', true);
            })
            ->where('caa.is_damaged', true);

        if ($start && $end) {
            $query->whereBetween('orders.order_date', [$start->toDateString(), $end->toDateString()]);
        }
        if ($storeId) {
            $query->where(function ($w) use ($storeId) {
                $w->where('order_products.delivery_store_id', $storeId)
                  ->orWhere('order_products.pickup_store_id', $storeId);
            });
        }

        $rows = $query
            ->selectRaw("COALESCE(caq.question_name, q.question_name, 'Untitled Question') as question_name")
            ->selectRaw("COALESCE(cac.category_name, 'Uncategorized') as category_name")
            ->selectRaw('COUNT(*) as flagged_count')
            ->groupByRaw("COALESCE(caq.question_name, q.question_name, 'Untitled Question'), COALESCE(cac.category_name, 'Uncategorized')")
            ->orderByDesc('flagged_count')
            ->limit(10)
            ->get();

        return $rows->map(fn($r) => [
            'question'      => $r->question_name,
            'category'      => $r->category_name,
            'flagged_count' => (int) $r->flagged_count,
        ])->toArray();
    }
}
