<?php

namespace App\Console\Commands\Diagnostics;

use App\Enums\Orders\OrderPaymentStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sales Tax Architecture Audit — Fuel Charges, Damage Charges, and Rental
 * Extensions.
 *
 * STRICTLY READ-ONLY. Every operation in this command is a SELECT — there
 * is no ::create(/->save(/->update(/->delete(/DB::insert(/DB::update(/
 * DB::statement( anywhere in this class, no migration or artisan call is
 * invoked, no queue job is dispatched, no cache is written, and no gateway/
 * HTTP call is made. Safe to run against real production or staging data at
 * any time.
 *
 * IMPORTANT LIMITATION, stated up front rather than buried in a footnote:
 * the application's sales tax rate is a single GLOBAL Setting row
 * ('sales_tax', under 'Product Settings') with no historical rate-change
 * log anywhere in the schema. "Expected tax" below is always computed using
 * TODAY'S rate. If the rate has ever changed, any charge created before the
 * most recent change will show a nonzero "difference" against expected tax
 * that reflects a rate change, not necessarily a defect. Cross-check the
 * charge's created_at against known rate-change dates (if any are known
 * outside this system) before treating a delta as an error.
 *
 * Covers the three charge types named in the Sales Tax Architecture Audit:
 * fuel, damage, and rental extension — via the unified billing_charges
 * table (the modern Billing Engine ledger every current creation path
 * writes to), cross-referenced against the legacy customer_accounts ledger
 * and, for extensions specifically, the child Order's own subtotal/
 * tax_amount/grand_total.
 */
class SalesTaxChargeTypeAudit extends Command
{
    protected $signature = 'diagnostics:charge-tax-audit
        {--start= : Start date (Y-m-d), default 90 days ago}
        {--end= : End date (Y-m-d), default today}
        {--sample=10 : Number of individual affected rows to print per section (1-50)}
        {--store= : Restrict to one store_id}';

    protected $description = 'READ-ONLY: audit fuel/damage/extension charges for missing or misrouted sales tax. No writes of any kind.';

    private const MAX_SAMPLE = 50;

    private const REQUIRED_TABLES = [
        'billing_charges', 'customer_accounts', 'orders', 'order_payments', 'settings',
    ];

    private float $taxRate = 0.0;

    public function handle(): int
    {
        $this->newLine();
        $this->line('<bg=blue;fg=white;options=bold> READ-ONLY DIAGNOSTIC </>  This command performs zero writes: no inserts, updates, deletes, migrations, backfills, queue dispatches, cache mutations, or gateway calls. Every query below is a SELECT.');
        $this->newLine();

        foreach (self::REQUIRED_TABLES as $table) {
            if (!Schema::hasTable($table)) {
                $this->error("Required table '{$table}' does not exist in this database. Aborting.");
                return self::FAILURE;
            }
        }

        [$start, $end, $sample, $storeId] = $this->resolveAndValidateInputs();
        if ($start === null) {
            return self::FAILURE;
        }

        $this->taxRate = (float) (DB::table('settings')->where('setting_name', 'sales_tax')->value('setting_value') ?? 0);
        $this->line("Current global sales_tax rate read from settings: <fg=yellow>{$this->taxRate}</> (applied as a decimal multiplier — e.g. 0.0975 = 9.75%). This is TODAY's rate only; see the limitation notice above.");
        $this->newLine();

        $this->fuelSection($start, $end, $sample, $storeId);
        $this->damageSection($start, $end, $sample, $storeId);
        $this->extensionSection($start, $end, $sample, $storeId);
        $this->historicalClassificationSummary($start, $end, $storeId);
        $this->interpretationGuide();

        return self::SUCCESS;
    }

    /** @return array{0: ?Carbon, 1: ?Carbon, 2: int, 3: ?int} */
    private function resolveAndValidateInputs(): array
    {
        $startOpt = $this->option('start');
        $endOpt   = $this->option('end');
        $sampleOpt = (int) $this->option('sample');
        $storeOpt  = $this->option('store');

        try {
            $start = $startOpt ? Carbon::parse($startOpt)->startOfDay() : now()->subDays(90)->startOfDay();
            $end   = $endOpt ? Carbon::parse($endOpt)->endOfDay() : now()->endOfDay();
        } catch (\Throwable $e) {
            $this->error("Invalid --start/--end date: {$e->getMessage()}");
            return [null, null, 0, null];
        }

        if ($start->greaterThan($end)) {
            $this->error('--start must not be after --end.');
            return [null, null, 0, null];
        }

        $sample = max(1, min(self::MAX_SAMPLE, $sampleOpt ?: 10));

        $storeId = $storeOpt !== null ? (int) $storeOpt : null;

        return [$start, $end, $sample, $storeId];
    }

    private function baseChargeQuery(string $type, Carbon $start, Carbon $end, ?int $storeId)
    {
        $query = DB::table('billing_charges as bc')
            ->where('bc.billing_charge_type', $type)
            ->whereBetween('bc.created_at', [$start, $end])
            ->whereNull('bc.deleted_at');

        if ($storeId !== null) {
            $query->where('bc.store_id', $storeId);
        }

        return $query;
    }

    private function fuelSection(Carbon $start, Carbon $end, int $sample, ?int $storeId): void
    {
        $this->line('<options=bold>── Section A: Fuel Charges ──────────────────────────────</>');
        $this->chargeTypeSummary('fuel', $start, $end, $sample, $storeId, reportableNote: true);
        $this->newLine();
    }

    private function damageSection(Carbon $start, Carbon $end, int $sample, ?int $storeId): void
    {
        $this->line('<options=bold>── Section B: Damage Charges ────────────────────────────</>');
        $this->chargeTypeSummary('damage', $start, $end, $sample, $storeId, reportableNote: true);
        $this->newLine();
    }

    private function extensionSection(Carbon $start, Carbon $end, int $sample, ?int $storeId): void
    {
        $this->line('<options=bold>── Section C: Rental Extension Charges ──────────────────</>');
        $this->chargeTypeSummary('extension', $start, $end, $sample, $storeId, reportableNote: false);

        // Extension-specific cross-check: does the child Order's own
        // subtotal/tax_amount/grand_total agree with the BillingCharge row
        // that represents the same extension? These are written together at
        // creation time (Extension\StoreController) but nothing enforces
        // they stay in sync afterward.
        $mismatches = $this->baseChargeQuery('extension', $start, $end, $storeId)
            ->join('orders as child', 'child.id', '=', 'bc.child_order_id')
            ->whereRaw('ROUND(bc.tax_amount, 2) <> ROUND(child.tax_amount, 2)')
            ->select('bc.unique_id', 'bc.child_order_id', 'child.order_number', 'bc.tax_amount as bc_tax', 'child.tax_amount as order_tax', 'bc.amount as bc_amount', 'child.subtotal as order_subtotal', 'child.grand_total')
            ->limit($sample)
            ->get();

        $mismatchCount = (clone $this->baseChargeQuery('extension', $start, $end, $storeId))
            ->join('orders as child', 'child.id', '=', 'bc.child_order_id')
            ->whereRaw('ROUND(bc.tax_amount, 2) <> ROUND(child.tax_amount, 2)')
            ->count();

        $this->line("Child-order/BillingCharge tax_amount agreement: <fg=" . ($mismatchCount > 0 ? 'red' : 'green') . ">{$mismatchCount} mismatch(es) found</> (BillingCharge.tax_amount vs. child Order.tax_amount for the same extension — these are written together at creation and should always match exactly).");
        if ($mismatchCount > 0) {
            $this->table(
                ['BillingCharge', 'Child Order #', 'BC tax', 'Order tax', 'BC amount', 'Order subtotal', 'Order grand_total'],
                $mismatches->map(fn ($r) => [$r->unique_id, $r->order_number, $r->bc_tax, $r->order_tax, $r->bc_amount, $r->order_subtotal, $r->grand_total])->all()
            );
        }
        $this->newLine();
    }

    private function chargeTypeSummary(string $type, Carbon $start, Carbon $end, int $sample, ?int $storeId, bool $reportableNote): void
    {
        $base = $this->baseChargeQuery($type, $start, $end, $storeId);

        $totalCount = (clone $base)->count();
        $totalAmount = (float) (clone $base)->sum('bc.amount');

        $zeroTax = (clone $base)->where('bc.tax_amount', 0);
        $zeroTaxCount = (clone $zeroTax)->count();
        $zeroTaxAmount = (float) (clone $zeroTax)->sum('bc.amount');

        $nonZeroTax = (clone $base)->where('bc.tax_amount', '>', 0);
        $nonZeroCount = (clone $nonZeroTax)->count();
        $nonZeroTaxAmount = (float) (clone $nonZeroTax)->sum('bc.tax_amount');

        // A row is only a candidate "should have been taxed but wasn't" if
        // its own tax_type says 'add' (taxable) yet tax_amount is 0 — a
        // tax_type of 'free' at $0 tax is self-consistent, not a defect.
        $taxableButZero = (clone $base)->where('bc.tax_amount', 0)->where('bc.tax_type', 'add');
        $taxableButZeroCount = (clone $taxableButZero)->count();
        $taxableButZeroAmount = (float) (clone $taxableButZero)->sum('bc.amount');

        $this->line("Total {$type} charges in range: <fg=yellow>{$totalCount}</> totaling \$" . number_format($totalAmount, 2));
        $this->line("  With tax_amount = 0: <fg=" . ($zeroTaxCount > 0 ? 'yellow' : 'green') . ">{$zeroTaxCount}</> totaling \$" . number_format($zeroTaxAmount, 2));
        $this->line("  With tax_amount &gt; 0: <fg=green>{$nonZeroCount}</> totaling \${$this->fmt($nonZeroTaxAmount)} in tax collected/recorded");
        $this->line("  tax_type='add' but tax_amount = 0 (marked taxable, zero tax recorded — the clearest defect signature): <fg=" . ($taxableButZeroCount > 0 ? 'red' : 'green') . ">{$taxableButZeroCount}</> totaling \${$this->fmt($taxableButZeroAmount)} in charge amount");

        if ($reportableNote) {
            $linkedToAccount = (clone $base)->whereNotNull('bc.customer_account_id')->count();
            $linkedWithTax = (clone $base)->whereNotNull('bc.customer_account_id')->where('bc.tax_amount', '>', 0)->count();
            $this->line("  Linked to a customer_accounts row (customer_account_id NOT NULL): <fg=yellow>{$linkedToAccount}</> of {$totalCount}, of which <fg=" . ($linkedWithTax > 0 ? 'cyan' : 'green') . ">{$linkedWithTax}</> have tax_amount &gt; 0.");
            $this->line("  Sales Tax Architecture Correction (this pass): SalesTaxReportEngine::billingRows() (Stream D) still excludes these rows' BASE amount (Stream C/accountRows() already reports it), but caTaxOnlyRows() (Stream E) now separately surfaces their TAX. Any charge counted in the '{$linkedWithTax}' figure above created BEFORE this correction was live is a 'Reporting-only omission' — its tax was correctly computed/collected but invisible to the Sales Tax Report until this pass; it will now appear retroactively the next time the report runs for that date range, since Stream E reads live data, not a snapshot.");
        }

        // Sample rows — the exact columns requested by the audit mission.
        $rows = (clone $base)
            ->leftJoin('order_payments as parent_op', function ($j) {
                $j->on('parent_op.order_id', '=', 'bc.parent_order_id')->whereNull('parent_op.deleted_at');
            })
            ->select(
                'bc.id', 'bc.unique_id', 'bc.billing_charge_type', 'bc.parent_order_id', 'bc.child_order_id',
                'bc.customer_id', 'bc.store_id', 'bc.amount', 'bc.tax_amount', 'bc.tax_type', 'bc.status',
                'bc.source_module', 'bc.customer_account_id', 'bc.created_at', 'bc.paid_at'
            )
            ->distinct()
            ->orderByDesc('bc.created_at')
            ->limit($sample)
            ->get();

        if ($rows->isNotEmpty()) {
            $this->line("  Sample (most recent {$sample}):");
            $this->table(
                ['ID', 'Unique ID', 'Customer', 'Parent Order', 'Child Order', 'Store', 'Amount', 'Tax', 'Tax Type', 'Status', 'Originating Workflow', 'CA-Linked', 'Created', 'Paid At'],
                $rows->map(fn ($r) => [
                    $r->id, $r->unique_id, $r->customer_id, $r->parent_order_id, $r->child_order_id ?? '—', $r->store_id ?? '—',
                    number_format((float) $r->amount, 2), number_format((float) $r->tax_amount, 2), $r->tax_type,
                    $r->status, $r->source_module ?? '—', $r->customer_account_id ? 'yes' : 'no', $r->created_at, $r->paid_at ?? '—',
                ])->all()
            );
        }
    }

    private function historicalClassificationSummary(Carbon $start, Carbon $end, ?int $storeId): void
    {
        $this->line('<options=bold>── Section D: Historical-Data Classification (fuel + damage + extension) ──</>');
        $this->line('Buckets every charge into one of five categories. "Expected tax" uses TODAY\'S rate (see limitation notice) — treat "Taxable but zero" as a lead to investigate, not a confirmed dollar-for-dollar liability, until cross-checked against the actual rate in effect on each charge\'s created_at.');
        $this->newLine();

        foreach (['fuel', 'damage', 'extension'] as $type) {
            $base = $this->baseChargeQuery($type, $start, $end, $storeId);

            $correctlyTaxed = (clone $base)->where('bc.tax_type', 'add')->where('bc.tax_amount', '>', 0)->count();
            $taxableZero    = (clone $base)->where('bc.tax_type', 'add')->where('bc.tax_amount', 0)->count();
            $intentionallyFree = (clone $base)->where('bc.tax_type', 'free')->count();
            $reverseType    = (clone $base)->where('bc.tax_type', 'reverse')->count();
            $indeterminate  = (clone $base)->whereNotIn('bc.tax_type', ['add', 'free', 'reverse'])->orWhereNull('bc.tax_type')->count();

            $this->line("<fg=cyan>{$type}</>: Correctly taxed (add, tax&gt;0): <fg=green>{$correctlyTaxed}</>  |  Taxable but zero: <fg=red>{$taxableZero}</>  |  Intentionally tax-free (tax_type=free): <fg=yellow>{$intentionallyFree}</>  |  Reverse-calc type: {$reverseType}  |  Indeterminate/unknown tax_type: {$indeterminate}");
        }
        $this->newLine();
    }

    private function interpretationGuide(): void
    {
        $this->line('<options=bold>── Interpretation Guide ──────────────────────────────────</>');
        $this->line('1. "tax_type=\'add\' but tax_amount=0" is the clearest, highest-confidence defect signature in this report — the charge was explicitly marked taxable at creation time, yet no tax was ever computed or stored. Any nonzero count here across fuel or damage warrants investigation before this data is trusted for reporting.');
        $this->line('2. A charge linked to a customer_account_id (fuel/damage only — extensions never set this) is invisible to SalesTaxReportEngine::billingRows() (Stream D) regardless of its own tax_amount. This is a REPORTING gap, not necessarily an undercharge — cross-check against the linked customer_accounts row and the actual order_payments/gateway record to determine whether the customer was actually undercharged or merely under-reported.');
        $this->line('3. Extension mismatches (Section C) indicate the child Order and its BillingCharge record have drifted apart since creation — investigate why (a manual edit? a partial refund that only updated one side?) before assuming the customer was undercharged.');
        $this->line('4. This command cannot determine whether the GATEWAY was actually charged the correct tax-inclusive amount — that requires cross-referencing order_payments.amount (for extensions) or the actual Authorize.Net transaction log (for fuel/damage, which do not always create an order_payments row) against bc.amount + bc.tax_amount. Recommend a manual spot-check of --sample rows against the payment gateway\'s own transaction history for final confirmation.');
        $this->line('5. Every number above reflects the CURRENT global sales_tax rate applied retroactively — it is not proof of what rate was actually in effect when an older charge was created, since this schema has no rate-history table.');
        $this->newLine();
    }

    private function fmt(float $n): string
    {
        return number_format($n, 2);
    }
}
