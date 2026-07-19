<?php

namespace App\Console\Commands\Diagnostics;

use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Services\Dashboard\DashboardFinancialPresenter;
use App\Services\Reports\BillingRevenueAttributionService;
use App\Services\Reports\EmployeePerformanceEngine;
use App\Services\Reports\PaymentReconciliationLedger;
use App\Services\Reports\ProductSalesPerformanceEngine;
use App\Services\Reports\ProductSalesRankingReport;
use App\Services\Reports\SalesByStoresReport;
use App\Services\Reports\SalesTaxReportEngine;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;

/**
 * Payment Architecture Finalization — Tier 1 verification aid.
 *
 * STRICTLY READ-ONLY. Every operation in this command is a SELECT — there
 * is no ::create(/->save(/->update(/->delete(/DB::insert(/DB::update(/
 * DB::delete(/DB::statement( anywhere in this class, no migration or
 * artisan call is invoked, no queue job is dispatched, no cache is written,
 * and no gateway/HTTP call is made. It is safe to run against a real
 * production or staging database at any time; running it twice produces
 * identical output (excluding the wall-clock timing line).
 *
 * For each Tier 1 area it computes the metric TWICE against the SAME real
 * data: once using the exact OLD (pre-fix) formula — reproduced verbatim
 * from the code this project replaced — and once using the CURRENT
 * (fixed) service, or, where feasible, by invoking the real private method
 * via reflection instead of a second hand-written copy (see
 * employeeCategoryBreakdownSection()) so "NEW" is never itself a
 * re-implementation that could silently drift from production code.
 *
 * A nonzero delta is not itself a problem — see the "Interpretation" line
 * printed after each section, and the guide printed at the end.
 */
class PaymentArchitectureTier1Comparison extends Command
{
    protected $signature = 'diagnostics:tier1-comparison
        {--start= : Start date (Y-m-d), default 90 days ago}
        {--end= : End date (Y-m-d), default today}
        {--sample=5 : Number of individual affected orders to print per section (1-50)}';

    protected $description = 'READ-ONLY: compare Tier 1 old-formula vs new-formula totals against real data. No writes of any kind.';

    private const MAX_SAMPLE = 50;

    private const REQUIRED_TABLES = [
        'orders', 'order_payments', 'order_products',
        'order_payment_refund_allocations', 'billing_charges', 'customers',
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->line('<bg=blue;fg=white;options=bold> READ-ONLY DIAGNOSTIC </>  This command performs zero writes: no inserts, updates, deletes, migrations, backfills, queue dispatches, cache mutations, or gateway calls. Every query below is a SELECT.');
        $this->newLine();

        if (! $this->tablesAvailable()) {
            return self::FAILURE;
        }

        $dates = $this->resolveAndValidateDates();
        if ($dates === null) {
            return self::FAILURE;
        }
        [$start, $end] = $dates;

        $sampleSize = $this->resolveSampleSize();

        $filters = [
            'date_range' => 'custom',
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(),
        ];

        $this->info("Comparison window: {$start->toDateString()} to {$end->toDateString()} (sample size: {$sampleSize})");
        $this->newLine();

        $this->customerPendingSalesSection($sampleSize);
        $this->salesReportPaymentStatusFilterSection($start, $end, $sampleSize);
        $this->salesTaxSection($filters, $start, $end, $sampleSize);
        $this->productRankingSection($filters);
        $this->salesByStoreSection($filters);
        $this->productSalesPerformanceSection($filters);
        $this->billingAttributionSection($filters);
        $this->employeePodStatsSection($start, $end);
        $this->employeeCategoryBreakdownSection($filters);
        $this->dashboardSalesPreviewSection();
        $this->reconciliationLedgerSelfCheck($filters);

        $this->newLine();
        $this->printInterpretationGuide();

        $this->newLine();
        $this->info('Comparison complete. Nothing was written to the database at any point during this run.');

        return self::SUCCESS;
    }

    // ─── Input validation ────────────────────────────────────────────────────

    private function tablesAvailable(): bool
    {
        $missing = array_values(array_filter(
            self::REQUIRED_TABLES,
            fn (string $table) => ! Schema::hasTable($table)
        ));

        if (empty($missing)) {
            return true;
        }

        $this->error('Cannot run: the following required tables do not exist in this database: ' . implode(', ', $missing));

        if (in_array('order_payment_refund_allocations', $missing, true)) {
            $this->error('order_payment_refund_allocations missing means the Phase 3B refund allocation architecture has not been migrated on this database. Every Tier 1 allocation-aware comparison in this command would silently compare against no allocation data, which is a misleading result, not a real verification — refusing to run rather than produce that.');
        }

        $this->line('Run `php artisan migrate:status` to see what is pending, and `php artisan payments:audit` once migrated.');

        return false;
    }

    private function resolveAndValidateDates(): ?array
    {
        $startInput = $this->option('start');
        $endInput   = $this->option('end');

        $start = $startInput ? $this->parseStrictDate($startInput, '--start') : now()->subDays(90)->startOfDay();
        if ($start === false) {
            return null;
        }

        $end = $endInput ? $this->parseStrictDate($endInput, '--end') : now()->endOfDay();
        if ($end === false) {
            return null;
        }

        if ($end->lt($start)) {
            $this->error("Invalid range: --end ({$end->toDateString()}) is before --start ({$start->toDateString()}).");
            return null;
        }

        return [$start->startOfDay(), $end->endOfDay()];
    }

    /** @return Carbon|false Carbon on success, false (and prints an error) on an invalid Y-m-d string. */
    private function parseStrictDate(string $value, string $optionName)
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $this->error("Invalid {$optionName}: \"{$value}\" is not in Y-m-d format (e.g. 2026-07-16).");
            return false;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            $this->error("Invalid {$optionName}: \"{$value}\" is not a real calendar date.");
            return false;
        }

        // createFromFormat is lenient about overflow (e.g. 2026-02-30 silently
        // rolls to March) — round-tripping catches that instead of accepting it.
        if ($date->format('Y-m-d') !== $value) {
            $this->error("Invalid {$optionName}: \"{$value}\" is not a real calendar date.");
            return false;
        }

        return $date;
    }

    private function resolveSampleSize(): int
    {
        $raw = $this->option('sample');

        if (! is_numeric($raw) || (int) $raw < 1) {
            $this->warn("--sample must be a positive integer; using default of 5.");
            return 5;
        }

        $value = (int) $raw;

        if ($value > self::MAX_SAMPLE) {
            $this->warn("--sample of {$value} exceeds the maximum of " . self::MAX_SAMPLE . '; clamping.');
            return self::MAX_SAMPLE;
        }

        return $value;
    }

    // ─── Output helpers ──────────────────────────────────────────────────────

    private function sectionHeader(string $title): void
    {
        $this->line("<fg=cyan;options=bold>{$title}</>");
    }

    private function comparisonTable(string $metricLabel, float|int $old, float|int $new, bool $isCurrency = true): void
    {
        $fmt = fn ($v) => $isCurrency ? number_format((float) $v, 2) : (string) $v;
        $delta = $new - $old;
        $this->table(
            ['Metric', 'OLD (legacy formula)', 'NEW (canonical)', 'Delta'],
            [[$metricLabel, $fmt($old), $fmt($new), $fmt($delta)]]
        );
    }

    private function affectedOrders(int $count, array $sampleRows, array $sampleHeaders): void
    {
        $this->line("Affected orders: <options=bold>{$count}</>");
        if ($count > 0 && ! empty($sampleRows)) {
            $this->table($sampleHeaders, $sampleRows);
        }
    }

    private function interpretation(string $note): void
    {
        $this->line("<fg=yellow>Interpretation:</> {$note}");
        $this->newLine();
    }

    // ─── Section: Customer Pending Sales ─────────────────────────────────────

    private function customerPendingSalesSection(int $sampleSize): void
    {
        $this->sectionHeader('[1] Customer Pending Sales — Customer::getPendingSalesAttribute() (company-wide, all-time)');

        $oldTotal = (float) DB::table('orders as o')
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('order_payments')
                    ->whereColumn('order_payments.order_id', 'o.id')
                    ->where('order_payments.status', 'Pending')
                    ->whereRaw('order_payments.id = (SELECT MAX(id) FROM order_payments WHERE order_id = o.id)');
            })
            ->sum('o.grand_total');

        $affectedOrderIds = DB::table('orders as o')
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('order_payments')
                    ->whereColumn('order_payments.order_id', 'o.id')
                    ->where('order_payments.status', 'Pending');
            })
            ->pluck('o.id');

        $customerIds = DB::table('orders')
            ->whereIn('id', $affectedOrderIds)
            ->whereNotNull('customer_id')
            ->distinct()
            ->pluck('customer_id');

        $newTotal = 0.0;
        $sampleRows = [];
        foreach (Customer::whereIn('id', $customerIds)->get() as $customer) {
            $pending = (float) $customer->pending_sales;
            $newTotal += $pending;
            if (count($sampleRows) < $sampleSize && $pending > 0) {
                $sampleRows[] = [$customer->id, $customer->full_name, number_format($pending, 2)];
            }
        }

        $this->comparisonTable('Total pending sales', $oldTotal, $newTotal);
        $this->affectedOrders($affectedOrderIds->count(), $sampleRows, ['Customer ID', 'Name', 'Pending Sales (NEW)']);
        $this->interpretation('NEW is typically <= OLD wherever a customer had already settled part of an order before a later pending attempt — that partial payment no longer inflates their pending total. NEW > OLD is not automatically a regression, though: OLD and NEW scope slightly different order sets (OLD only counts an order whose single latest row is Pending; NEW counts any order with a Pending row anywhere in its history), and the two totals are computed by separate queries, so a write landing between them on a live database can also produce a difference. Inspect the flagged customers rather than treating any single instance as conclusive.');
    }

    // ─── Section: Sales Report payment-status filtering ─────────────────────

    private function salesReportPaymentStatusFilterSection(Carbon $start, Carbon $end, int $sampleSize): void
    {
        $this->sectionHeader('[2] SalesReportingService::applyFilters() — "paid" bucket order inclusion (shared base query for most reports)');

        $userClass = User::class;

        $oldIds = DB::table('orders as o')
            ->whereNull('o.deleted_at')
            ->whereBetween('o.order_date', [$start->toDateString(), $end->toDateString()])
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('order_payments')
                    ->whereColumn('order_payments.order_id', 'o.id')
                    ->whereRaw('order_payments.id = (SELECT MAX(id) FROM order_payments WHERE order_id = o.id)')
                    ->where('order_payments.payment_method', '!=', 'Account')
                    ->whereNotIn('order_payments.status', ['Voided', 'Failed', 'Pending'])
                    ->where(function ($qq) {
                        $qq->where('order_payments.payment_method', '!=', 'COD')
                           ->orWhere(function ($q2) {
                               $q2->where('order_payments.payment_method', 'COD')->where('order_payments.status', 'Paid');
                           });
                    });
            })
            ->pluck('o.id');

        $newIds = DB::table('orders as o')
            ->whereNull('o.deleted_at')
            ->whereBetween('o.order_date', [$start->toDateString(), $end->toDateString()])
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('order_payments')
                    ->whereColumn('order_payments.order_id', 'o.id')
                    ->where('order_payments.payment_method', '!=', 'Account')
                    ->whereNotIn('order_payments.status', ['Voided', 'Failed', 'Pending'])
                    ->where(function ($qq) {
                        $qq->where('order_payments.payment_method', '!=', 'COD')
                           ->orWhere(function ($q2) {
                               $q2->where('order_payments.payment_method', 'COD')->where('order_payments.status', 'Paid');
                           });
                    });
            })
            ->pluck('o.id');

        $newlyIncluded = $newIds->diff($oldIds);
        $newlyExcluded = $oldIds->diff($newIds);

        $this->comparisonTable('Orders in "paid" bucket', $oldIds->count(), $newIds->count(), isCurrency: false);
        $this->line("Newly included (previously wrongly excluded — split-payment orders where the LAST row alone didn't qualify): <options=bold>{$newlyIncluded->count()}</>");
        $this->line("Newly excluded (order qualified under the old MAX(id) check but not under the new any-row check): <options=bold>{$newlyExcluded->count()}</>");

        if ($newlyIncluded->isNotEmpty()) {
            $this->table(['order_id'], $newlyIncluded->take($sampleSize)->map(fn ($id) => [$id])->all());
        }

        $this->interpretation('"Newly included" is the expected direction of the fix — those are split-payment orders that were silently invisible to every report using this bucket. Given the OLD condition is a strict special case of the NEW one (a row satisfying OLD\'s "id = MAX(id) AND criteria" trivially satisfies NEW\'s "criteria on any row"), "Newly excluded" should ordinarily be 0 for orders whose data hasn\'t changed between the two queries; a nonzero count is worth inspecting first for a timing race (the two queries run at different moments against a live database) before treating it as a logic regression.');
    }

    // ─── Section: Sales Tax Report Stream A (salesRows) ──────────────────

    private function salesTaxSection(array $filters, Carbon $start, Carbon $end, int $sampleSize): void
    {
        $this->sectionHeader('[3] Sales Tax Report — Stream A (SalesTaxReportEngine::salesRows)');

        $oldRows = DB::select("
            SELECT o.id, o.subtotal, o.tax_amount, o.grand_total
            FROM orders o
            WHERE o.deleted_at IS NULL
              AND o.order_date BETWEEN ? AND ?
              AND EXISTS (SELECT 1 FROM order_products op WHERE op.order_id = o.id AND op.deleted_at IS NULL)
              AND EXISTS (
                  SELECT 1 FROM order_payments lp
                  WHERE lp.order_id = o.id
                    AND lp.id = (SELECT MAX(id) FROM order_payments WHERE order_id = o.id)
                    AND lp.status NOT IN ('Voided', 'Failed', 'Pending')
                    AND lp.payment_method != 'Account'
                    AND (lp.payment_method != 'COD' OR lp.status = 'Paid')
              )
        ", [$start->toDateString(), $end->toDateString()]);

        $oldSubtotal = array_sum(array_column($oldRows, 'subtotal'));
        $oldTax      = array_sum(array_column($oldRows, 'tax_amount'));
        $oldGrand    = array_sum(array_column($oldRows, 'grand_total'));

        $newRows = app(SalesTaxReportEngine::class)->salesRows($filters);
        $newSubtotal = round((float) $newRows->sum('subtotal'), 2);
        $newTax      = round((float) $newRows->sum('tax_amount'), 2);
        $newGrand    = round((float) $newRows->sum('grand_total'), 2);

        $this->table(
            ['Metric', 'OLD (lastPayment, 1 row/order)', 'NEW (1 row/qualifying payment)', 'Delta'],
            [
                ['Row/order count', count($oldRows), $newRows->count(), $newRows->count() - count($oldRows)],
                ['Subtotal', number_format($oldSubtotal, 2), number_format($newSubtotal, 2), number_format($newSubtotal - $oldSubtotal, 2)],
                ['Tax amount', number_format($oldTax, 2), number_format($newTax, 2), number_format($newTax - $oldTax, 2)],
                ['Grand total', number_format($oldGrand, 2), number_format($newGrand, 2), number_format($newGrand - $oldGrand, 2)],
            ]
        );

        $oldByOrder = collect($oldRows)->keyBy('id');
        $newByOrder = $newRows->groupBy(fn ($r) => $this->orderIdFromUniqueId($r->unique_id));

        $diffs = collect();
        foreach ($newByOrder as $orderId => $rows) {
            $newTotal = round((float) $rows->sum('grand_total'), 2);
            $oldTotal = (float) ($oldByOrder[$orderId]->grand_total ?? 0);
            if (abs($newTotal - $oldTotal) > 0.01) {
                $diffs->push(['order_id' => $orderId, 'old_grand_total' => $oldTotal, 'new_grand_total' => $newTotal, 'new_row_count' => $rows->count()]);
            }
        }

        $this->affectedOrders(
            $diffs->count(),
            $diffs->take($sampleSize)->map(fn ($d) => array_values($d))->all(),
            ['order_id', 'OLD grand_total', 'NEW grand_total (sum of rows)', 'NEW row count']
        );
        $this->interpretation('Affected orders are typically split-payment or partially-collected orders. NEW row count > 1 for an order is expected (one row per qualifying payment). An order with NEW row count = 1 that still shows a swing is unexpected for the single-payment case and warrants inspection — though check first whether rounding on the tax/discount proportional split, or a legacy Invoice*-status row recovering its implied method, explains it before assuming a defect.');
    }

    private function orderIdFromUniqueId(string $uniqueId): ?int
    {
        static $cache = [];
        if (! array_key_exists($uniqueId, $cache)) {
            $cache[$uniqueId] = DB::table('orders')->where('unique_id', $uniqueId)->value('id');
        }
        return $cache[$uniqueId];
    }

    // ─── Section: Product Sales Ranking ───────────────────────────────────

    private function productRankingSection(array $filters): void
    {
        $this->sectionHeader('[4] Product Sales Ranking (ProductSalesRankingReport)');

        $oldTotal = $this->oldUnnettedDemandRevenue($filters['start_date'], $filters['end_date']);
        $newTotal = (float) app(ProductSalesRankingReport::class)->rankingData($filters)['totals']['revenue'];

        $this->comparisonTable('Total revenue', $oldTotal, $newTotal);
        $this->interpretation('NEW is typically <= OLD: the gap is the sum of every fully-refunded order\'s original revenue (now excluded entirely) plus the netted portion of every partial refund. This isn\'t an absolute guarantee on real data, though — OLD here uses the pre-fix "paid" bucket (section 2\'s old MAX(id) inclusion check), so an order that section 2 shows as "newly included" contributes to NEW but not OLD, which can push NEW above OLD in a dataset where that effect outweighs the netting. Treat NEW > OLD as a prompt to check whether it\'s explained by newly-included orders, not as an automatic regression.');
    }

    // ─── Section: Sales By Store ──────────────────────────────────────────

    private function salesByStoreSection(array $filters): void
    {
        $this->sectionHeader('[5] Sales By Store (SalesByStoresReport)');

        $oldTotal = $this->oldUnnettedDemandRevenue($filters['start_date'], $filters['end_date']);
        $newTotal = (float) app(SalesByStoresReport::class)->storeData($filters)['totals']['revenue'];

        $this->comparisonTable('Total revenue', $oldTotal, $newTotal);
        $this->interpretation('Same direction, cause, and caveat as Product Sales Ranking above — this report shares the identical netting fix and the same interaction with section 2\'s inclusion fix.');
    }

    /** Shared OLD-formula reproduction: raw SUM(order_products.sub_total), 'paid' bucket, zero refund netting. Used by both Product Ranking and Sales By Store — they used the exact same unnetted formula before this fix. */
    private function oldUnnettedDemandRevenue(string $start, string $end): float
    {
        return (float) DB::table('order_products as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->whereNull('o.deleted_at')->whereNull('op.deleted_at')
            ->whereBetween('o.order_date', [$start, $end])
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('order_payments')
                    ->whereColumn('order_payments.order_id', 'o.id')
                    ->whereRaw('order_payments.id = (SELECT MAX(id) FROM order_payments WHERE order_id = o.id)')
                    ->where('order_payments.payment_method', '!=', 'Account')
                    ->whereNotIn('order_payments.status', ['Voided', 'Failed', 'Pending'])
                    ->where(function ($qq) {
                        $qq->where('order_payments.payment_method', '!=', 'COD')
                           ->orWhere('order_payments.status', 'Paid');
                    });
            })
            ->sum('op.sub_total');
    }

    // ─── Section: Product Sales Performance ──────────────────────────────

    private function productSalesPerformanceSection(array $filters): void
    {
        $this->sectionHeader('[6] Product Sales Performance (ProductSalesPerformanceEngine::buildKpis)');

        // OLD: paid_and_account bucket (this engine's default), MAX(id)-based,
        // zero refund netting/exclusion.
        [$start, $end] = [$filters['start_date'], $filters['end_date']];
        $oldTotal = (float) DB::table('order_products as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->whereNull('o.deleted_at')->whereNull('op.deleted_at')
            ->whereBetween('o.order_date', [$start, $end])
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('order_payments')
                    ->whereColumn('order_payments.order_id', 'o.id')
                    ->whereRaw('order_payments.id = (SELECT MAX(id) FROM order_payments WHERE order_id = o.id)')
                    ->whereNotIn('order_payments.status', ['Voided', 'Failed']);
            })
            ->sum('op.sub_total');

        $newTotal = (float) app(ProductSalesPerformanceEngine::class)->buildKpis($filters)['total_revenue'];

        $this->comparisonTable('Total demand revenue (paid + account)', $oldTotal, $newTotal);
        $this->interpretation('This engine already had fully-refunded exclusion and partial-refund netting before this project — this fix only made the refund SUM itself allocation-aware. The delta here is typically small or zero unless real orders have a multi-source or partially-failed refund in this window, in which case NEW can legitimately land on either side of OLD depending on whether the legacy refund_amount had over- or under-stated the true allocated amount. A large delta is a prompt to pull the specific order\'s allocation rows, not an automatic sign of a defect.');
    }

    // ─── Section: Billing Revenue Attribution (extensions) ───────────────

    private function billingAttributionSection(array $filters): void
    {
        $this->sectionHeader('[7] Billing Revenue Attribution — extensions (BillingRevenueAttributionService)');

        [$start, $end] = [$filters['start_date'], $filters['end_date']];
        $old = DB::table('billing_charges as bc')
            ->join('order_products as pop', 'pop.id', '=', DB::raw(
                '(SELECT op2.id FROM order_products op2 JOIN products p2 ON p2.id = op2.product_id
                  WHERE op2.order_id = bc.parent_order_id AND op2.deleted_at IS NULL
                  ORDER BY (p2.product_type = \'Rental\') DESC, op2.id ASC LIMIT 1)'
            ))
            ->leftJoinSub(
                DB::table('order_payments')
                    ->selectRaw('order_id, SUM(refund_amount - COALESCE(tax_refunded, 0)) AS refunded_ex_tax')
                    ->whereIn('status', ['Refunded', 'Partial Refund'])
                    ->whereNull('deleted_at')
                    ->groupBy('order_id'),
                'bcr', 'bcr.order_id', '=', 'bc.child_order_id'
            )
            ->whereIn('bc.billing_charge_type', ['extension'])
            ->where('bc.status', 'paid')
            ->whereNull('bc.customer_account_id')
            ->whereNull('bc.deleted_at')
            ->whereExists(function ($sub) {
                $sub->selectRaw('1')->from('order_payments as op_paid')
                    ->whereColumn('op_paid.order_id', 'bc.child_order_id')
                    ->whereNull('op_paid.deleted_at')
                    ->where('op_paid.status', 'Paid');
            })
            ->whereBetween(DB::raw('DATE(bc.paid_at)'), [$start, $end])
            ->selectRaw('SUM(GREATEST(0, bc.amount - COALESCE(bcr.refunded_ex_tax, 0))) as total')
            ->value('total');

        $oldTotal = (float) ($old ?? 0);
        $newTotal = app(BillingRevenueAttributionService::class)->totalRevenue($filters);

        $this->comparisonTable('Extension revenue', $oldTotal, $newTotal);
        $this->interpretation('A delta here only appears on extensions with a multi-source or fee-retained refund (where allocated_amount/allocated_tax_amount differs from the refund row\'s raw refund_amount/tax_refunded). Zero delta is expected and fine on a dataset with no such refunds in this window — it does not mean the fix is untested, see BillingRevenueAttributionServiceTest.php for direct coverage.');
    }

    // ─── Section: Employee Performance — POD stats ────────────────────────

    private function employeePodStatsSection(Carbon $start, Carbon $end): void
    {
        $this->sectionHeader('[8] Employee Performance — COD (POD) stats, all employees combined');

        $userClass = User::class;

        $oldTotal = (int) DB::table('orders')
            ->join('order_payments', 'order_payments.order_id', '=', 'orders.id')
            ->whereNull('orders.deleted_at')
            ->where('orders.created_by_type', $userClass)
            ->where('order_payments.payment_method', 'COD')
            ->whereRaw('order_payments.id = (SELECT MAX(op2.id) FROM order_payments op2 WHERE op2.order_id = orders.id)')
            ->whereBetween('orders.order_date', [$start->toDateString(), $end->toDateString()])
            ->count();

        $oldConverted = (int) DB::table('orders')
            ->join('order_payments', 'order_payments.order_id', '=', 'orders.id')
            ->whereNull('orders.deleted_at')
            ->where('orders.created_by_type', $userClass)
            ->where('order_payments.payment_method', 'COD')
            ->where('order_payments.status', 'Paid')
            ->whereRaw('order_payments.id = (SELECT MAX(op2.id) FROM order_payments op2 WHERE op2.order_id = orders.id)')
            ->whereBetween('orders.order_date', [$start->toDateString(), $end->toDateString()])
            ->count();

        $newTotal = (int) DB::table('orders')
            ->join('order_payments', 'order_payments.order_id', '=', 'orders.id')
            ->whereNull('orders.deleted_at')
            ->where('orders.created_by_type', $userClass)
            ->where('order_payments.payment_method', 'COD')
            ->whereBetween('orders.order_date', [$start->toDateString(), $end->toDateString()])
            ->distinct()
            ->count('orders.id');

        $newConverted = (int) DB::table('orders')
            ->join('order_payments', 'order_payments.order_id', '=', 'orders.id')
            ->whereNull('orders.deleted_at')
            ->where('orders.created_by_type', $userClass)
            ->where('order_payments.payment_method', 'COD')
            ->where('order_payments.status', 'Paid')
            ->whereBetween('orders.order_date', [$start->toDateString(), $end->toDateString()])
            ->distinct()
            ->count('orders.id');

        $this->table(
            ['Metric', 'OLD (MAX(id) join)', 'NEW (direct COD filter)', 'Delta'],
            [
                ['COD orders total', $oldTotal, $newTotal, $newTotal - $oldTotal],
                ['COD orders converted', $oldConverted, $newConverted, $newConverted - $oldConverted],
            ]
        );
        $this->interpretation('NEW total is expected to be >= OLD total: the OLD condition (id = MAX(id) AND payment_method = COD) is a strict special case of NEW\'s (any row is COD), so every order OLD counted, NEW counts too, plus whatever OLD was silently dropping. NEW < OLD would be inconsistent with that relationship for the same snapshot of data and is worth investigating — first ruling out a timing race between the two separate queries on a live database, then a genuine defect.');
    }

    // ─── Section: Employee category breakdown ─────────────────────────────

    private function employeeCategoryBreakdownSection(array $filters): void
    {
        $this->sectionHeader('[9] Employee Performance — category breakdown (EmployeePerformanceEngine::categoryBreakdown)');

        [$start, $end] = [$filters['start_date'], $filters['end_date']];

        // OLD: this engine's own pre-fix formula — raw sub_total, MAX(id)-based
        // paid_and_account bucket, zero refund netting. Company-wide (no
        // employee_id filter — equivalent to summing every employee's row).
        $oldTotal = (float) DB::table('order_products as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->whereNull('o.deleted_at')->whereNull('op.deleted_at')
            ->whereBetween('o.order_date', [$start, $end])
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('order_payments')
                    ->whereColumn('order_payments.order_id', 'o.id')
                    ->whereRaw('order_payments.id = (SELECT MAX(id) FROM order_payments WHERE order_id = o.id)')
                    ->whereNotIn('order_payments.status', ['Voided', 'Failed']);
            })
            ->sum('op.sub_total');

        // NEW: invoke the REAL private method via reflection (company-wide,
        // no employee_id filter) so this is never a second hand-written copy
        // that could drift from production code.
        $engine = app(EmployeePerformanceEngine::class);
        $method = new ReflectionMethod(EmployeePerformanceEngine::class, 'categoryBreakdown');
        $method->setAccessible(true);
        $rows = $method->invoke($engine, $filters);
        $newTotal = array_sum(array_column($rows, 'revenue'));

        $this->comparisonTable('Category revenue total (top 10 categories only — see note)', $oldTotal, $newTotal);
        $this->interpretation('NEW is limited to the top 10 categories by revenue (this method\'s own LIMIT 10, unchanged by this fix) while OLD above is unfiltered — so some of the gap is normal truncation, not the fix. Compare row-by-row category names if you need an exact reconciliation; do not treat this section\'s total delta alone as conclusive.');
    }

    // ─── Section: Dashboard sales preview (Sales By Store consumer) ──────

    private function dashboardSalesPreviewSection(): void
    {
        $this->sectionHeader('[10] Dashboard sales preview — consistency check (DashboardFinancialPresenter::salesByStore delegates to SalesByStoresReport)');

        $presenterRows = app(DashboardFinancialPresenter::class)->salesByStore(5);

        $fullStoreData = app(SalesByStoresReport::class)->storeData([
            'date_range' => 'mtd', 'payment_status' => 'paid',
        ])['stores'];
        $fullByName = collect($fullStoreData)->keyBy('name');

        $mismatches = [];
        foreach ($presenterRows as $row) {
            $match = $fullByName->get($row['name']);
            if (! $match || abs((float) $match['revenue'] - (float) $row['revenue']) > 0.01) {
                $mismatches[] = [$row['name'], $row['revenue'], $match['revenue'] ?? 'NOT FOUND'];
            }
        }

        $this->line('Dashboard preview rows: ' . count($presenterRows) . ' | Mismatches vs. full Sales By Store report: ' . count($mismatches));
        if (! empty($mismatches)) {
            $this->table(['Store', 'Dashboard revenue', 'Sales By Store revenue'], $mismatches);
        }
        $this->interpretation('This is a pure delegation check, not an old-vs-new comparison — the dashboard has never had its own formula (per DashboardFinancialPresenter\'s own docblock), so the two calls should read the identical underlying figures. Mismatches count is expected to be 0; a nonzero count needs prompt review, since it would mean the dashboard has diverged from the report it claims to mirror (though rule out a timing race between the two separate calls before concluding it\'s a code defect).');
    }

    // ─── Section: Reconciliation Ledger self-check (unchanged, sanity only) ──

    private function reconciliationLedgerSelfCheck(array $filters): void
    {
        $this->sectionHeader('[11] Payment Reconciliation Ledger — self-consistency check (this engine was already canonical; NOT modified by this project)');

        $ledgerRows = collect(app(PaymentReconciliationLedger::class)->rows($filters));
        $ledgerCollected = $ledgerRows->where('stream', 'order')->sum(fn ($r) => (float) ($r->grand_total ?? 0));

        [$start, $end] = [$filters['start_date'], $filters['end_date']];
        $realCollected = (float) DB::table('order_payments as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->whereNull('o.deleted_at')
            ->whereBetween('o.order_date', [$start, $end])
            ->whereIn('op.status', ['Paid', 'Partial Payment', 'Invoice Card', 'Invoice Cash', 'Invoice Online', 'Invoice Cheque', 'Invoice Other'])
            ->sum('op.amount');

        $this->table(
            ['Metric', 'Ledger sum (order stream)', 'Real order_payments.amount sum', 'Delta'],
            [['Collected', number_format($ledgerCollected, 2), number_format($realCollected, 2), number_format($ledgerCollected - $realCollected, 2)]]
        );
        $this->interpretation('Delta is expected to be 0.00 — this is not a before/after comparison, it is a sanity check that the untouched, already-canonical Ledger genuinely sums to actual collected money (this section reports collected payments; it is not the Ledger\'s net-of-refunds figure — see the reconciliation guide below for that distinction). A nonzero delta here points to something unrelated to this project\'s Tier 1 changes and should be investigated before trusting any other section, but first rule out a timing race between the two separate queries on a live database.');
    }

    // ─── Final guide ─────────────────────────────────────────────────────────

    private function printInterpretationGuide(): void
    {
        $this->line('<fg=cyan;options=bold>Interpretation guide</>');
        $this->line('These are TENDENCIES that follow from correcting a legacy single-payment/no-netting assumption, not absolute rules — real historical data, stale legacy fields, differing inclusion criteria between sections, rounding, and corrected attribution can all produce valid exceptions. Treat every item below as "expect this direction; if you see the opposite, inspect the specific orders before concluding anything," except where a section is explicitly noted as a same-data identity check.');
        $this->newLine();

        $this->line('Typically expected (the fix doing its job):');
        $this->line('  - Sections 1, 4, 5, 6: NEW tends to be <= OLD wherever refund netting or partial-collection correction applies — but section 2\'s separate inclusion fix can add newly-eligible orders to NEW that OLD never saw, so the net direction on real data depends on your dataset. See each section\'s own interpretation line.');
        $this->line('  - Section 2: "Newly included" > 0 is expected on any dataset with split-payment orders.');
        $this->line('  - Section 3: NEW row count > OLD row count is expected on any order with more than one qualifying payment.');
        $this->line('  - Section 7 (Billing Attribution) and 9 (Employee category): delta may legitimately be exactly 0.00 if no matching orders exist in the window — that does not mean untested, see the dedicated unit tests.');
        $this->line('  - Section 8: NEW total/converted tends to be >= OLD, following from the OLD condition being a strict special case of the NEW one.');
        $this->newLine();

        $this->line('Worth inspecting before sign-off (not automatically a regression, but not expected either):');
        $this->line('  - Section 1: NEW > OLD.');
        $this->line('  - Section 2: any nonzero "Newly excluded" count.');
        $this->line('  - Sections 4, 5, 6: NEW > OLD.');
        $this->line('  - Section 8: NEW < OLD.');
        $this->newLine();

        $this->line('Same-data identity checks — these compare two readings of what should be the same underlying figure, not an old-vs-new formula, so a difference here is a stronger signal:');
        $this->line('  - Section 10: any nonzero mismatch count between the dashboard preview and the full Sales By Store report.');
        $this->line('  - Section 11: any nonzero delta between the Ledger\'s collected total and a direct sum of settled order_payments.amount.');
        $this->line('  - For both: rule out a timing race (the two reads happen as separate queries, moments apart, against a live database) before treating a small, one-off difference as a defect.');
        $this->newLine();

        $this->line('<fg=cyan;options=bold>What should reconcile, and at what level</>');
        $this->line('These figures are NOT expected to equal each other as raw totals — collected funds can include sales tax, non-taxable amounts, retained processing fees, deposits, or other components outside taxable sales, so "Sales Tax Report taxable total = Ledger collected total" is not a valid check and this command does not assert it. Reconciliation instead means:');
        $this->line('  - The Payment Reconciliation Ledger (section 11) should reconcile to actual completed payment events, less completed refunds, where the Ledger is reporting net collections.');
        $this->line('  - Payment-method totals (e.g. Cash, Card, Check breakdowns) should reconcile to the Ledger\'s own collected-payment components for that method — not to any other report\'s total.');
        $this->line('  - The Sales Tax Report (section 3) should reconcile taxable sales, tax collected, tax refunded, and net tax independently, using allocation-level values (Stream B\'s refund rows) rather than assuming any fixed ratio to total cash collected.');
        $this->line('  - For a single sampled order: its taxable base, plus tax, plus any non-taxable components, should explain that order\'s collected amount — once discounts, fees, deposits, and refunds are accounted for. This is a per-order component check, not a report-total equality.');
        $this->line('  - Receipt, Order Details, Ledger, and Sales Tax values for the same order should agree at the component level (same base amount, same tax amount, same fee retained, same refund amount) — not by requiring taxable sales to equal total cash collected across the report as a whole.');
        $this->line('  - Practical spot-check: pick one real order with a card-fee-retained or multi-source refund from section 3 or 7\'s affected-orders sample, and confirm its receipt, Order Details page, Ledger row, and Sales Tax Report row all show the same base/tax/fee/refund components for that order.');
    }
}
