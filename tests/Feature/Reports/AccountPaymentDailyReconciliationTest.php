<?php

namespace Tests\Feature\Reports;

use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Services\Reports\PaymentReconciliationLedger;
use App\Services\Reports\SalesReportEngineV2;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Account-payment daily keying + datetime window boundaries.
 *
 * customer_accounts.date is a DATETIME. Production validation (2026-08-01)
 * found the daily chart series keyed account payments by the RAW datetime, so
 * the Y-m-d chart grid never matched and every account payment silently
 * dropped from the daily series while the snapshot counted it — breaking
 * sum(daily) == net_sales. The fix: group daily by DATE(`date`) and use one
 * canonical HALF-OPEN window ([start 00:00, end+1d 00:00)) in the snapshot,
 * the daily query, and ledger Stream C. These tests pin all of that.
 */
class AccountPaymentDailyReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Account', 'last_name' => 'Payer',
            'email' => 'account-daily@example.com', 'status' => 'Active',
        ]);

        $this->actingAs(User::create([
            'first_name' => 'Acct', 'last_name' => 'Clerk',
            'email' => 'acct-clerk@example.com', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]));
    }

    /** Insert an account payment at an exact datetime (sales_tax 0 → base == amount). */
    private function acct(string $datetime, float $amount): void
    {
        DB::table('customer_accounts')->insert([
            'unique_id'    => (string) Str::uuid(),
            'customer_id'  => $this->customer->id,
            'amount'       => $amount,
            'payment_type' => 'Cash',
            'date'         => $datetime,
            'sales_tax'    => 0,
            'type'         => 'payment',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    private function july(): array
    {
        return [
            'date_range' => 'custom', 'start_date' => '2026-07-01', 'end_date' => '2026-07-31',
            'payment_status' => 'paid',
        ];
    }

    public function test_same_day_payments_at_different_times_aggregate_into_one_daily_key(): void
    {
        $this->acct('2026-07-10 09:00:00', 100);
        $this->acct('2026-07-10 16:30:00', 50);

        $trend = app(SalesReportEngineV2::class)->trendData($this->july());

        // July 10 = index 9 in a July 1-anchored series: both times land on ONE day.
        $this->assertSame(150.0, round($trend['current'][9], 2), 'both same-day payments aggregate under one calendar-date key');
        $this->assertSame(150.0, round(array_sum($trend['current']), 2));
        $this->assertSame(round($trend['netSales'], 2), round(array_sum($trend['current']), 2));
    }

    public function test_account_payment_late_on_the_final_day_is_included(): void
    {
        $this->acct('2026-07-31 23:59:59', 75);

        $engine = app(SalesReportEngineV2::class);
        $kpis   = $engine->kpis($this->july());
        $trend  = $engine->trendData($this->july());

        $this->assertSame(75.0, round($kpis['account_payments_received'], 2), 'the half-open window must include the whole final day');
        $this->assertSame(75.0, round($trend['current'][30], 2), 'July 31 = index 30 carries the payment');
        $this->assertSame(round($trend['netSales'], 2), round(array_sum($trend['current']), 2));
    }

    public function test_a_payment_exactly_at_the_next_days_midnight_is_excluded(): void
    {
        $this->acct('2026-08-01 00:00:00', 60);

        $kpis = app(SalesReportEngineV2::class)->kpis($this->july());
        $this->assertSame(0.0, round($kpis['account_payments_received'], 2), 'end boundary is EXCLUSIVE: next-day 00:00:00 belongs to August');

        $julyLedger = app(PaymentReconciliationLedger::class)->rows($this->july());
        $this->assertCount(0, $julyLedger->where('stream', 'account'));

        // …and it lands in August instead (no gap, no overlap).
        $aug = app(SalesReportEngineV2::class)->kpis([
            'date_range' => 'custom', 'start_date' => '2026-08-01', 'end_date' => '2026-08-31',
            'payment_status' => 'paid',
        ]);
        $this->assertSame(60.0, round($aug['account_payments_received'], 2));
    }

    public function test_daily_series_sums_to_snapshot_net_sales_with_account_payments_present(): void
    {
        // The exact production failure shape: multiple account payments with
        // real times of day, including a late final-day one and a next-month
        // boundary one that must stay out.
        $this->acct('2026-07-10 09:00:00', 100);
        $this->acct('2026-07-10 16:30:00', 50);
        $this->acct('2026-07-31 23:59:59', 75);
        $this->acct('2026-08-01 00:00:00', 60);

        $trend = app(SalesReportEngineV2::class)->trendData($this->july());

        $this->assertSame(225.0, round($trend['netSales'], 2));
        $this->assertSame(
            round($trend['netSales'], 2),
            round(array_sum($trend['current']), 2),
            'sum(daily series) == snapshot net_sales — the invariant that failed in production'
        );
    }

    public function test_ledger_stream_c_equals_the_snapshot_account_contribution(): void
    {
        $this->acct('2026-07-10 09:00:00', 100);
        $this->acct('2026-07-10 16:30:00', 50);
        $this->acct('2026-07-31 23:59:59', 75);
        $this->acct('2026-08-01 00:00:00', 60);

        $kpis    = app(SalesReportEngineV2::class)->kpis($this->july());
        $streamC = app(PaymentReconciliationLedger::class)->rows($this->july())->where('stream', 'account');

        $this->assertCount(3, $streamC, 'identical half-open boundaries: 3 July rows, the midnight-boundary payment excluded');
        $this->assertSame(
            round($kpis['total_account_payments'], 2),
            round($streamC->sum('grand_total'), 2),
            'ledger Stream C == snapshot account-payment contribution for the same interval'
        );
        $this->assertSame(225.0, round($streamC->sum('grand_total'), 2));
    }
}
