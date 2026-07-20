<?php

namespace Tests\Unit;

use App\Enums\Billing\BillingChargeStatus;
use App\Models\Orders\BillingCharge;
use App\Services\BillingChargePresenter;
use Tests\TestCase;

/**
 * Billing Charge Operations Commonization — the canonical presentation
 * vocabulary. Pure presentation tests, no database: pins the approved
 * status palette (Pending=orange, Paid/Completed=green, Resolved=blue,
 * Uncollectible=gray, Voided=gray struck through) across BOTH worlds
 * (BillingChargeStatus enum and legacy alert-status strings), and the
 * origin rules (Checklist only when order_product_id proves it; Manual
 * otherwise; extension rows get no origin badge).
 */
class BillingChargePresenterTest extends TestCase
{
    public function test_enum_statuses_present_the_canonical_palette(): void
    {
        $expected = [
            BillingChargeStatus::Pending->value       => ['Pending', 'orange'],
            BillingChargeStatus::Paid->value          => ['Paid', 'green'],
            BillingChargeStatus::Resolved->value      => ['Resolved', 'blue'],
            BillingChargeStatus::Uncollectible->value => ['Uncollectible', 'gray'],
            BillingChargeStatus::Voided->value        => ['Voided', 'gray'],
        ];

        foreach ($expected as $value => [$label, $hue]) {
            $badge = BillingChargePresenter::statusBadge(BillingChargeStatus::from($value));
            $this->assertSame($label, $badge['label'], $value);
            $this->assertStringContainsString($hue, $badge['classes'], $value);
        }

        // Voided is additionally struck through (inactive treatment).
        $this->assertStringContainsString(
            'line-through',
            BillingChargePresenter::statusBadge(BillingChargeStatus::Voided)['classes']
        );
    }

    public function test_alert_status_strings_map_onto_the_same_palette(): void
    {
        // 'completed' (alert world) is the same collected-money state Paid
        // represents on BillingCharge — identical green, its own label.
        $completed = BillingChargePresenter::statusBadge('completed');
        $this->assertSame('Completed', $completed['label']);
        $this->assertSame(BillingChargeStatus::Paid->badgeClass(), $completed['classes']);

        $this->assertSame(BillingChargeStatus::Resolved->badgeClass(), BillingChargePresenter::statusBadge('resolved')['classes']);
        $this->assertSame(BillingChargeStatus::Uncollectible->badgeClass(), BillingChargePresenter::statusBadge('uncollectible')['classes']);

        // Outstanding aliases — pending, null, unknown — all read Pending/orange.
        foreach (['pending', null, 'anything-else'] as $alias) {
            $badge = BillingChargePresenter::statusBadge($alias);
            $this->assertSame('Pending', $badge['label']);
            $this->assertSame(BillingChargeStatus::Pending->badgeClass(), $badge['classes']);
        }
    }

    public function test_origin_is_checklist_only_when_order_product_proves_it(): void
    {
        $checklist = new BillingCharge([
            'billing_charge_type' => 'fuel',
            'order_product_id' => 42,
        ]);
        $origin = BillingChargePresenter::originForCharge($checklist);
        $this->assertSame('Checklist', $origin['label']);

        $manual = new BillingCharge([
            'billing_charge_type' => 'fuel',
            'metadata' => ['source_context' => 'crm'],
        ]);
        $origin = BillingChargePresenter::originForCharge($manual);
        $this->assertSame('Manual', $origin['label']);
        $this->assertStringContainsString('CRM', $origin['title']);

        // Provably manual, surface unknown (pre-metadata bridge rows) —
        // documented limitation: label only, no invented surface.
        $unknown = new BillingCharge(['billing_charge_type' => 'damage']);
        $origin = BillingChargePresenter::originForCharge($unknown);
        $this->assertSame('Manual', $origin['label']);
        $this->assertSame('Created manually', $origin['title']);
    }

    public function test_extension_rows_carry_no_origin_badge(): void
    {
        $extension = new BillingCharge(['billing_charge_type' => 'extension']);
        $this->assertNull(BillingChargePresenter::originForCharge($extension));
    }

    public function test_money_and_age_formatting(): void
    {
        $this->assertSame('$1,234.50', BillingChargePresenter::money(1234.5));
        $this->assertSame('$0.00', BillingChargePresenter::money(null));

        $this->assertNull(BillingChargePresenter::ageDays(null));
        $this->assertNull(BillingChargePresenter::ageDays(0));
        $this->assertSame(0, BillingChargePresenter::ageDays(now()->timestamp));
        $this->assertSame(3, BillingChargePresenter::ageDays(now()->subDays(3)->subMinutes(5)->timestamp));
    }
}
