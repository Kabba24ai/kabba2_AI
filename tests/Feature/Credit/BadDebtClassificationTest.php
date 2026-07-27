<?php

namespace Tests\Feature\Credit;

use App\Helpers\CustomHelper;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Services\Credit\CreditAccountSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Canonical Bad Debt classification (business-approved):
 *
 *     is_bad_debt = outstanding_balance > 0 AND oldest_outstanding_age_days >= 60
 *
 * Credit-limit configuration is NOT part of Bad Debt; over-limit and past-due
 * are separate facts. This proves the single rule and that every migrated
 * reader (read model, CustomHelper predicate, account-status helper, and the
 * SQL scope used by the CRM/API index sorts + Billing Summary filter) agrees.
 */
class BadDebtClassificationTest extends TestCase
{
    use RefreshDatabase;

    private function customer(float $balance, array $overrides = []): Customer
    {
        $c = Customer::create(array_merge([
            'first_name' => 'Bad', 'last_name' => 'Debt',
            'email' => 'bd-' . uniqid() . '@test.local', 'status' => 'Active',
        ], $overrides));
        $c->forceFill(['available_credit_balance' => $balance])->save();

        return $c->fresh();
    }

    private function debit(Customer $c, float $amount, int $daysAgo): void
    {
        $row = new CustomerAccount();
        $row->customer_id = $c->id;
        $row->amount = $amount;
        $row->type = 'charge';
        $row->sales_tax = 0;
        $row->sales_tax_type = 'free';
        $row->date = now()->subDays($daysAgo);
        $row->save();
    }

    private function isBadDebt(Customer $c): bool
    {
        return CreditAccountSummary::for($c->fresh())->badDebt()['is_bad_debt'];
    }

    // ── 1–3 boundary ─────────────────────────────────────────────────────

    public function test_01_positive_balance_59_days_not_bad_debt(): void
    {
        $c = $this->customer(500);
        $this->debit($c, 500, 59);
        $this->assertFalse($this->isBadDebt($c));
    }

    public function test_02_positive_balance_exactly_60_days_bad_debt(): void
    {
        $c = $this->customer(500);
        $this->debit($c, 500, 60);
        $this->assertTrue($this->isBadDebt($c));
    }

    public function test_03_positive_balance_61_days_bad_debt(): void
    {
        $c = $this->customer(500);
        $this->debit($c, 500, 61);
        $this->assertTrue($this->isBadDebt($c));
    }

    // ── 4–6 balance / exposure guards ──────────────────────────────────────

    public function test_04_zero_balance_60plus_not_bad_debt(): void
    {
        $c = $this->customer(0);
        $this->debit($c, 500, 90);
        $this->assertFalse($this->isBadDebt($c));
    }

    public function test_05_negative_balance_credit_60plus_not_bad_debt(): void
    {
        $c = $this->customer(-100);
        $this->debit($c, 500, 90);
        $this->assertFalse($this->isBadDebt($c));
    }

    public function test_06_positive_balance_no_aged_exposure_not_bad_debt(): void
    {
        $c = $this->customer(500); // balance but no debit postings at all
        $this->assertFalse($this->isBadDebt($c));
    }

    // ── 7–9 credit-limit is irrelevant to Bad Debt ──────────────────────────

    public function test_07_positive_balance_null_limit_under_60_not_bad_debt(): void
    {
        $c = $this->customer(500, ['credit_limit' => null, 'is_credit_account' => 0]);
        $this->debit($c, 500, 30);
        $this->assertFalse($this->isBadDebt($c), 'null limit does not make it bad debt under 60 days');
    }

    public function test_08_positive_balance_zero_limit_under_60_not_bad_debt(): void
    {
        $c = $this->customer(500, ['credit_limit' => 0, 'is_credit_account' => 0]);
        $this->debit($c, 500, 30);
        $this->assertFalse($this->isBadDebt($c));
    }

    public function test_09_positive_balance_negative_limit_under_60_not_bad_debt(): void
    {
        $c = $this->customer(500, ['credit_limit' => -50, 'is_credit_account' => 0]);
        $this->debit($c, 500, 30);
        $this->assertFalse($this->isBadDebt($c));
    }

    // ── 10 canonical positive case ──────────────────────────────────────────

    public function test_10_positive_balance_60plus_normal_limit_bad_debt(): void
    {
        $c = $this->customer(500, ['credit_limit' => 1000, 'is_credit_account' => 1]);
        $this->debit($c, 500, 75);
        $this->assertTrue($this->isBadDebt($c));
    }

    // ── 11–12 over-limit is separate from bad debt ──────────────────────────

    public function test_11_over_limit_under_60_is_over_limit_not_bad_debt(): void
    {
        $c = $this->customer(1200, ['credit_limit' => 1000, 'is_credit_account' => 1]);
        $this->debit($c, 1200, 10);
        $s = CreditAccountSummary::for($c->fresh());
        $this->assertTrue($s->isOverLimit(), 'over limit');
        $this->assertFalse($s->badDebt()['is_bad_debt'], 'but not bad debt (under 60 days)');
    }

    public function test_12_aged_but_not_over_limit_is_bad_debt(): void
    {
        $c = $this->customer(300, ['credit_limit' => 1000, 'is_credit_account' => 1]);
        $this->debit($c, 300, 70);
        $s = CreditAccountSummary::for($c->fresh());
        $this->assertFalse($s->isOverLimit(), 'not over limit');
        $this->assertTrue($s->badDebt()['is_bad_debt'], 'but is bad debt (>= 60 days)');
    }

    // ── 13–14 structured result ─────────────────────────────────────────────

    public function test_13_rule_approved_is_true(): void
    {
        $c = $this->customer(500);
        $this->debit($c, 500, 70);
        $this->assertTrue(CreditAccountSummary::for($c->fresh())->badDebt()['rule_approved']);
    }

    public function test_14_threshold_exposed_as_60(): void
    {
        $c = $this->customer(500);
        $this->debit($c, 500, 70);
        $this->assertEquals(60, CreditAccountSummary::for($c->fresh())->badDebt()['threshold_days']);
    }

    // ── 15–17 every migrated reader agrees ──────────────────────────────────

    public function test_15_16_17_all_readers_agree_for_bad_debt_customer(): void
    {
        $c = $this->customer(500, ['credit_limit' => 1000, 'is_credit_account' => 1]);
        $this->debit($c, 500, 75);
        $c = $c->fresh();

        // Read model (Reports/Billing-facing) + CustomHelper predicate + account
        // status helper (CRM tabs + Portal + checkout) + SQL scope (CRM/API
        // index sorts + Billing Summary filter) all agree = bad debt.
        $this->assertTrue(CreditAccountSummary::for($c)->badDebt()['is_bad_debt']);
        $this->assertTrue(CustomHelper::isBadDebitCustomer($c));
        $this->assertEquals('Bad Debt', CustomHelper::getCustomerAccountStatus($c)['badge']['label']);
        $this->assertTrue(Customer::query()->badDebt()->whereKey($c->id)->exists(), 'SQL scope agrees');
    }

    public function test_15_16_17_all_readers_agree_for_non_bad_debt_customer(): void
    {
        $c = $this->customer(500, ['credit_limit' => 1000, 'is_credit_account' => 1]);
        $this->debit($c, 500, 20); // recent → not bad debt
        $c = $c->fresh();

        $this->assertFalse(CreditAccountSummary::for($c)->badDebt()['is_bad_debt']);
        $this->assertFalse(CustomHelper::isBadDebitCustomer($c));
        $this->assertNotEquals('Bad Debt', CustomHelper::getCustomerAccountStatus($c)['badge']['label']);
        $this->assertFalse(Customer::query()->badDebt()->whereKey($c->id)->exists());
    }

    public function test_sql_scope_matches_php_across_the_boundary(): void
    {
        // Parity is the requirement: the SQL scope and the PHP read model must
        // agree at every boundary day (anchored to the same PHP "now").
        foreach ([58, 59, 60, 61, 62] as $days) {
            $c = $this->customer(500);
            $this->debit($c, 500, $days);
            $c = $c->fresh();

            $php = CreditAccountSummary::for($c)->badDebt()['is_bad_debt'];
            $sql = Customer::query()->badDebt()->whereKey($c->id)->exists();

            $this->assertSame($php, $sql, "PHP and SQL Bad Debt agree at {$days} days");
            $this->assertSame($days >= 60, $sql, "SQL scope correct at {$days} days");
        }
    }

    // ── 18–19 no production reader uses the old rule ────────────────────────

    public function test_18_19_no_migrated_reader_uses_old_rule_or_credit_limit(): void
    {
        $base = base_path();
        $files = [
            '/app/Http/Controllers/Admin/Crm/Customers/IndexController.php',
            '/app/Http/Controllers/Api/Admin/V1/Customers/IndexController.php',
            '/app/Http/Controllers/Admin/Crm/BillingSummary/IndexController.php',
        ];
        foreach ($files as $rel) {
            $src = file_get_contents($base . $rel);
            $this->assertStringContainsString('badDebt', $src, "$rel routes bad debt through the canonical rule");
            // No leftover 45-day rule or credit-limit-in-bad-debt in these readers.
            $this->assertStringNotContainsString('> 45', $src, "$rel still uses the old 45-day rule");
            $this->assertStringNotContainsString("DATEDIFF(CURDATE(), MAX(date)", $src, "$rel still uses days-since-last-payment bad-debt rule");
        }

        // The canonical predicate delegates to the read model (no inline rule).
        $helper = file_get_contents($base . '/app/Helpers/CustomHelper.php');
        $this->assertStringContainsString('CreditAccountSummary::for($customer)->badDebt()', $helper);
    }
}
