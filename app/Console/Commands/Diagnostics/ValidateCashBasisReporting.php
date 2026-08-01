<?php

namespace App\Console\Commands\Diagnostics;

use App\Services\Dashboard\DashboardFinancialPresenter;
use App\Services\Reports\CollectedRevenueQuery;
use App\Services\Reports\PaymentReconciliationLedger;
use App\Services\Reports\SalesReportEngineV2;
use App\Services\Reports\SalesTaxReportEngine;
use App\Services\Reports\Transactions\TransactionEnumerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read-only regression health check for payment-date (cash-basis) financial
 * reporting. Promoted from the one-off validate-cash-basis.php harness that
 * production-validated the 2026-08 conversion (and caught the pre-existing
 * account-payment daily-keying defect that ordinary tests never had).
 *
 * Run BEFORE and AFTER any change touching the reporting engines:
 *
 *   php artisan reports:validate-cash-basis
 *   php artisan reports:validate-cash-basis --cross=2026-07 --months=6 --order=2829
 *
 * Exit code 0 = every invariant holds; 1 = at least one FAIL (safe for CI or
 * a scheduled health check). Executes ONLY the live reporting engines and
 * SELECT queries — never writes.
 */
class ValidateCashBasisReporting extends Command
{
    protected $signature = 'reports:validate-cash-basis
        {--cross=  : YYYY-MM month for the cross-surface agreement checks (default: last full month)}
        {--months=6 : how many trailing months the historical sweep and invariant scan cover}
        {--order=  : optional order number to spot-check (e.g. 2829)}';

    protected $description = 'Read-only cash-basis reporting health check: cross-surface identities, daily-series reconciliation, payment-date attribution, data-quality counts.';

    private const EPS = 0.011; // one-cent float tolerance

    private int $failures = 0;
    private int $passes   = 0;

    public function handle(
        SalesReportEngineV2 $engine,
        SalesTaxReportEngine $tax,
        PaymentReconciliationLedger $ledger,
        CollectedRevenueQuery $crq,
        TransactionEnumerator $txn,
    ): int {
        $crossYm = $this->option('cross') ?: now()->subMonthNoOverflow()->format('Y-m');
        $months  = max(1, (int) $this->option('months'));

        $this->section('ENVIRONMENT');
        $this->line('  time     : ' . now()->toDateTimeString());
        $this->line('  database : ' . DB::connection()->getDatabaseName());
        $this->check('cash-basis engine keys present',
            array_key_exists('net_collections', $engine->kpis($this->v2($this->window($crossYm)))));

        if ($orderNumber = $this->option('order')) {
            $this->orderSpotCheck($crq, $tax, $orderNumber);
        }

        $this->crossSurface($engine, $tax, $ledger, $txn, $crossYm);
        $this->dashboard($engine);
        $this->invariants($engine, $crq, $months);
        $this->dataQuality();

        $this->newLine();
        $this->line("RESULT: {$this->passes} passed, {$this->failures} failed.");
        if ($this->failures > 0) {
            $this->error('Cash-basis reporting invariants FAILED — investigate before shipping reporting changes.');
            return self::FAILURE;
        }
        $this->info('All cash-basis reporting invariants hold.');
        return self::SUCCESS;
    }

    // ─── Sections ─────────────────────────────────────────────────────────────

    /** Spot-check one order: money reports in its payment months, never its order month. */
    private function orderSpotCheck(CollectedRevenueQuery $crq, SalesTaxReportEngine $tax, string $orderNumber): void
    {
        $this->section("ORDER SPOT-CHECK #{$orderNumber}");
        $order = DB::table('orders')->where('order_number', 'LIKE', "%{$orderNumber}%")->whereNull('deleted_at')->first();
        if (!$order) {
            $this->warn("  order matching '{$orderNumber}' not found — skipped");
            return;
        }

        $this->line("  id {$order->id} order_date {$order->order_date} grand " . $this->money($order->grand_total));

        $payMonths = DB::table('order_payments')->where('order_id', $order->id)
            ->whereIn('status', $this->settled())
            ->where('payment_method', '!=', 'Account')
            ->selectRaw("DISTINCT DATE_FORMAT(COALESCE(payment_datetime, created_at), '%Y-%m') as ym")
            ->pluck('ym');

        if ($payMonths->isEmpty()) {
            $this->line('  no settled payments — cash-basis reports must show NOTHING for this order');
        }

        foreach ($payMonths as $ym) {
            [$s, $e] = $this->window($ym);
            $rows = $crq->rows([], $s, $e)->where('order_id', $order->id);
            $this->check("reports in payment month {$ym}", $rows->isNotEmpty(),
                'applied Σ ' . $this->money($rows->sum('applied')));
        }

        $orderYm = substr((string) $order->order_date, 0, 7);
        if (!$payMonths->contains($orderYm)) {
            [$s, $e] = $this->window($orderYm);
            $this->check("does NOT report in order month {$orderYm} (no money moved then)",
                $crq->rows([], $s, $e)->where('order_id', $order->id)->isEmpty());
        }
    }

    /** The four surfaces must agree for one identical window. */
    private function crossSurface(
        SalesReportEngineV2 $engine,
        SalesTaxReportEngine $tax,
        PaymentReconciliationLedger $ledger,
        TransactionEnumerator $txn,
        string $ym,
    ): void {
        $this->section("CROSS-SURFACE AGREEMENT ({$ym})");
        [$s, $e] = $this->window($ym);

        $k       = $engine->kpis($this->v2([$s, $e]));
        $taxRows = $tax->salesRows(['start_date' => $s, 'end_date' => $e]);
        $ledRows = $ledger->rows($this->v2([$s, $e]));
        $ledOrd  = $ledRows->where('stream', 'order');

        $this->line('  Summary: gross ' . $this->money($k['gross_sales'])
            . '  tax ' . $this->money($k['tax_collected'])
            . '  gross_collections ' . $this->money($k['gross_collections'])
            . '  net_collections ' . $this->money($k['net_collections'])
            . '  overpayments ' . $this->money($k['overpayments']));

        $this->check('Ledger Σ grand_total == net_collections',
            $this->eq((float) $ledRows->sum('grand_total'), (float) $k['net_collections']),
            $this->money($ledRows->sum('grand_total')) . ' vs ' . $this->money($k['net_collections']));

        $this->check('Sales Tax Σ grand == Ledger order-stream Σ grand',
            $this->eq((float) $taxRows->sum('grand_total'), (float) $ledOrd->sum('grand_total')),
            $this->money($taxRows->sum('grand_total')) . ' vs ' . $this->money($ledOrd->sum('grand_total')));

        $this->check('Sales Tax Σ tax == Ledger order-stream Σ tax',
            $this->eq((float) $taxRows->sum('tax_amount'), (float) $ledOrd->sum('tax_amount')));

        $this->check('collection terminology identity: gross_collections − refunds == net_collections == total_collected',
            $this->eq((float) $k['gross_collections'] - (float) $k['refunds'], (float) $k['net_collections'])
            && $this->eq((float) $k['net_collections'], (float) $k['total_collected']));

        // Transaction Report is deliberately UNCAPPED raw cash and includes
        // extension children + Account rows; restrict to a like-for-like slice.
        $recs = $txn->records(['date_range' => 'custom', 'start_date' => $s, 'end_date' => $e]);
        $withLines = DB::table('order_products')->whereNull('deleted_at')
            ->whereIn('order_id', $recs->pluck('order_id')->unique()->values())
            ->distinct()->pluck('order_id')->flip();
        $charges = $recs->filter(fn ($r) => $r['is_collected'] && $r['transaction_type'] === 'charge'
            && $r['payment_method'] !== 'Account' && !$r['deleted'] && isset($withLines[$r['order_id']]));
        $expected = (float) $ledOrd->sum('grand_total') + (float) $k['overpayments'];
        $this->check('Transactions (collected charges, non-Account, product-line orders) == ledger order-stream + overpayments',
            $this->eq((float) $charges->sum('payment_amount'), $expected),
            $this->money($charges->sum('payment_amount')) . ' vs ' . $this->money($expected));
    }

    /** Dashboard tiles must equal the Sales Summary for the same windows. */
    private function dashboard(SalesReportEngineV2 $engine): void
    {
        $this->section('DASHBOARD RECONCILIATION');
        $dash = app(DashboardFinancialPresenter::class)->salesData();

        $lm  = now()->subMonthNoOverflow();
        $kLM = $engine->kpis($this->v2([$lm->copy()->startOfMonth()->toDateString(), $lm->copy()->endOfMonth()->toDateString()]));
        $this->check('lastMonth tile == Sales Summary net_sales',
            $this->eq((float) $dash['lastMonth']['totalSales'], (float) $kLM['net_sales']),
            $this->money($dash['lastMonth']['totalSales']) . ' vs ' . $this->money($kLM['net_sales']));

        $kMtd = $engine->kpis($this->v2([now()->startOfMonth()->toDateString(), now()->toDateString()]));
        $this->check('mtd tile == Sales Summary net_sales',
            $this->eq((float) $dash['mtd']['totalSales'], (float) $kMtd['net_sales']),
            $this->money($dash['mtd']['totalSales']) . ' vs ' . $this->money($kMtd['net_sales']));
    }

    /** The by-construction invariants, per trailing month. */
    private function invariants(SalesReportEngineV2 $engine, CollectedRevenueQuery $crq, int $months): void
    {
        $this->section("INTERNAL INVARIANTS (last {$months} months)");
        for ($i = $months - 1; $i >= 0; $i--) {
            $ym = now()->subMonthsNoOverflow($i)->format('Y-m');
            [$s, $e] = $this->window($ym);

            $t = $engine->trendData($this->v2([$s, $e]));
            $this->check("{$ym}: Σ daily series == snapshot net_sales",
                $this->eq((float) array_sum($t['current']), (float) $t['netSales']),
                $this->money(array_sum($t['current'])) . ' vs ' . $this->money($t['netSales']));

            $rows = $crq->rows([], $s, $e);
            $bad  = 0;
            foreach ($rows as $r) {
                foreach (['base', 'tax', 'discount'] as $f) {
                    $sum = 0.0;
                    foreach ($r->lines as $l) {
                        $sum += $l->{$f};
                    }
                    if (!$this->eq($sum, (float) $r->{$f})) {
                        $bad++;
                    }
                }
            }
            $this->check("{$ym}: line partitions sum to canonical payment figures", $bad === 0,
                "violations {$bad} / rows " . $rows->count());
        }
    }

    /** Pre-existing data conditions that shape (but don't break) cash numbers. */
    private function dataQuality(): void
    {
        $this->section('DATA QUALITY (informational — pre-existing conditions, not failures)');
        $settledIn = $this->settled();

        $this->line('  settled payments with NULL payment_datetime (date falls back to created_at): '
            . DB::table('order_payments as op')->join('orders as o', 'o.id', '=', 'op.order_id')
                ->whereNull('o.deleted_at')->whereIn('op.status', $settledIn)->whereNull('op.payment_datetime')->count());
        $this->line('  refund rows with NULL refunded_at: '
            . DB::table('order_payments')->whereIn('status', ['Refunded', 'Partial Refund'])->whereNull('refunded_at')->count());
        $this->line('  orders where subtotal+tax−discount ≠ grand_total (allocation caps use grand_total): '
            . DB::table('orders')->whereNull('deleted_at')
                ->whereRaw('ABS(COALESCE(subtotal,0) + COALESCE(tax_amount,0) - COALESCE(discount_amount,0) - COALESCE(grand_total,0)) > 0.01')->count());
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function settled(): array
    {
        return collect(\App\Enums\Orders\OrderPaymentStatus::cases())
            ->filter(fn ($s) => $s->isSettled() || $s === \App\Enums\Orders\OrderPaymentStatus::PartialPayment)
            ->map(fn ($s) => $s->value)
            ->all();
    }

    /** @return array{0: string, 1: string} [first, last] day of the month */
    private function window(string $ym): array
    {
        $start = Carbon::parse($ym . '-01');
        return [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()];
    }

    private function v2(array $window): array
    {
        return ['date_range' => 'custom', 'start_date' => $window[0], 'end_date' => $window[1], 'payment_status' => 'paid'];
    }

    private function check(string $label, bool $ok, string $detail = ''): void
    {
        $ok ? $this->passes++ : $this->failures++;
        $prefix = $ok ? '  <info>[PASS]</info> ' : '  <error>[FAIL]</error> ';
        $this->line($prefix . $label . ($detail !== '' ? " — {$detail}" : ''));
    }

    private function eq(float $a, float $b): bool
    {
        return abs($a - $b) < self::EPS;
    }

    private function money($v): string
    {
        return number_format((float) $v, 2);
    }

    private function section(string $title): void
    {
        $this->newLine();
        $this->line("<comment>══ {$title} ══</comment>");
    }
}
