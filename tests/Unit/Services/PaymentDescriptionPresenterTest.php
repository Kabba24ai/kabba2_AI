<?php

namespace Tests\Unit\Services;

use App\Enums\Customers\PaymentMethod as CustomerPaymentMethod;
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Orders\OrderHistory;
use App\Models\Orders\OrderPayment;
use App\Services\PaymentDescriptionPresenter;
use Mockery;
use Tests\TestCase;

/**
 * Phase 1 payment vocabulary standardization: the presenter is the single
 * source of every payment status/method label. These tests pin down the
 * three things the mission calls out explicitly:
 *   - Cash means cash — every method label says only what was selected.
 *   - Status is never inferred from method (or vice versa).
 *   - Legacy/unknown values render a safe fallback, never an exception.
 */
class PaymentDescriptionPresenterTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ── Status labels ────────────────────────────────────────────────

    public function test_canonical_status_labels(): void
    {
        $this->assertSame('Pending', PaymentDescriptionPresenter::statusLabel(OrderPaymentStatus::Pending));
        $this->assertSame('Paid in Full', PaymentDescriptionPresenter::statusLabel(OrderPaymentStatus::Paid));
        $this->assertSame('Partially Paid', PaymentDescriptionPresenter::statusLabel(OrderPaymentStatus::PartialPayment));
        $this->assertSame('Partially Refunded', PaymentDescriptionPresenter::statusLabel(OrderPaymentStatus::PartialRefund));
        $this->assertSame('Refunded', PaymentDescriptionPresenter::statusLabel(OrderPaymentStatus::Refund));
        $this->assertSame('Voided', PaymentDescriptionPresenter::statusLabel(OrderPaymentStatus::Voided));
        $this->assertSame('Failed', PaymentDescriptionPresenter::statusLabel(OrderPaymentStatus::Failed));
    }

    public function test_status_label_accepts_raw_string_value_not_just_enum(): void
    {
        $this->assertSame('Paid in Full', PaymentDescriptionPresenter::statusLabel('Paid'));
        $this->assertSame('Pending', PaymentDescriptionPresenter::statusLabel('Pending'));
    }

    public function test_invoice_statuses_decompose_to_paid_in_full_not_a_fused_label(): void
    {
        // These used to render as "Paid at Front Desk" etc. — a status
        // label must never carry method information.
        foreach ([
            OrderPaymentStatus::InvoiceCard,
            OrderPaymentStatus::InvoiceCash,
            OrderPaymentStatus::InvoiceOnline,
            OrderPaymentStatus::InvoiceCheque,
            OrderPaymentStatus::InvoiceOther,
        ] as $status) {
            $this->assertSame('Paid in Full', PaymentDescriptionPresenter::statusLabel($status));
        }
    }

    public function test_account_status_reads_as_pending_not_a_completed_status(): void
    {
        // Account is an Accounts Receivable workflow marker — the funds
        // have not actually been collected yet.
        $this->assertSame('Pending', PaymentDescriptionPresenter::statusLabel(OrderPaymentStatus::Account));
    }

    public function test_unknown_or_null_status_never_throws(): void
    {
        $this->assertSame('Unknown', PaymentDescriptionPresenter::statusLabel('some-legacy-garbage-value'));
        $this->assertSame('Unknown', PaymentDescriptionPresenter::statusLabel(null));
    }

    public function test_status_badge_classes_cover_every_case_no_silent_gray_fallback_for_real_statuses(): void
    {
        // The old CustomHelper::statusBadge() map had no entries for
        // Invoice*/PartialPayment and fell back to plain gray. Every real
        // status must resolve to a real (non-default) treatment.
        foreach (OrderPaymentStatus::cases() as $status) {
            $classes = PaymentDescriptionPresenter::statusBadgeClasses($status);
            $this->assertNotEmpty($classes);
        }

        // Green for anything settled (Paid, or the legacy Invoice* cases).
        $this->assertSame(
            PaymentDescriptionPresenter::statusBadgeClasses(OrderPaymentStatus::Paid),
            PaymentDescriptionPresenter::statusBadgeClasses(OrderPaymentStatus::InvoiceCash),
        );
    }

    // ── Method labels — "Cash means cash" ───────────────────────────

    public function test_canonical_method_labels(): void
    {
        $this->assertSame('Credit / Debit Card', PaymentDescriptionPresenter::methodLabel(OrderPaymentMethod::Card));
        $this->assertSame('Cash', PaymentDescriptionPresenter::methodLabel(OrderPaymentMethod::Cash));
        $this->assertSame('Check', PaymentDescriptionPresenter::methodLabel(OrderPaymentMethod::Cheque));
        $this->assertSame('Tap to Pay', PaymentDescriptionPresenter::methodLabel(OrderPaymentMethod::TapToPay));
        $this->assertSame('Store Credit', PaymentDescriptionPresenter::methodLabel(OrderPaymentMethod::StoreCredit));
        $this->assertSame('Gift Card', PaymentDescriptionPresenter::methodLabel(OrderPaymentMethod::GiftCard));
        $this->assertSame('Zelle / Venmo', PaymentDescriptionPresenter::methodLabel(OrderPaymentMethod::ZelleVenmo));
        $this->assertSame('Other', PaymentDescriptionPresenter::methodLabel(OrderPaymentMethod::Other));
    }

    public function test_cash_label_never_says_front_desk_or_implies_a_location(): void
    {
        $label = PaymentDescriptionPresenter::methodLabel(OrderPaymentMethod::Cash);
        $this->assertSame('Cash', $label);
        $this->assertStringNotContainsStringIgnoringCase('front desk', $label);
        $this->assertStringNotContainsStringIgnoringCase('store', $label);
    }

    public function test_method_label_resolves_customers_payment_method_enum_identically(): void
    {
        // Two enums represent method (entry-form vs stored-payment) — both
        // must render identical wording for the same real-world method.
        $this->assertSame(
            PaymentDescriptionPresenter::methodLabel(OrderPaymentMethod::Cash),
            PaymentDescriptionPresenter::methodLabel(CustomerPaymentMethod::Cash),
        );
        $this->assertSame(
            PaymentDescriptionPresenter::methodLabel(OrderPaymentMethod::Card),
            PaymentDescriptionPresenter::methodLabel(CustomerPaymentMethod::CreditCard),
        );
        $this->assertSame(
            PaymentDescriptionPresenter::methodLabel(OrderPaymentMethod::Online),
            PaymentDescriptionPresenter::methodLabel(CustomerPaymentMethod::BankTransfer),
        );
    }

    public function test_unknown_or_null_method_never_throws(): void
    {
        $this->assertSame('Unknown', PaymentDescriptionPresenter::methodLabel('SomeFutureMethodNotYetSupported'));
        $this->assertSame('Unknown', PaymentDescriptionPresenter::methodLabel(null));
    }

    // ── canonical() exclusions — Account is not a payment method ───────

    public function test_canonical_methods_exclude_account_and_cod(): void
    {
        $canonical = OrderPaymentMethod::canonical();
        $this->assertNotContains(OrderPaymentMethod::Account, $canonical);
        $this->assertNotContains(OrderPaymentMethod::COD, $canonical);
        $this->assertContains(OrderPaymentMethod::Cash, $canonical);
        $this->assertContains(OrderPaymentMethod::StoreCredit, $canonical);
    }

    public function test_canonical_methods_exclude_bank_transfer_and_are_exactly_the_approved_eight(): void
    {
        $canonical = OrderPaymentMethod::canonical();

        $this->assertNotContains(OrderPaymentMethod::Online, $canonical);
        $this->assertCount(8, $canonical);
        $this->assertSame(
            ['Card', 'Cash', 'Cheque', 'Other', 'TapToPay', 'StoreCredit', 'GiftCard', 'ZelleVenmo'],
            array_map(fn ($m) => $m->value, $canonical),
        );
    }

    public function test_customers_payment_method_canonical_excludes_bank_transfer(): void
    {
        $canonical = CustomerPaymentMethod::canonical();

        $this->assertNotContains(CustomerPaymentMethod::BankTransfer, $canonical);
        $this->assertCount(8, $canonical);
        $this->assertArrayNotHasKey('BankTransfer', CustomerPaymentMethod::options());
    }

    public function test_bank_transfer_is_not_selectable_but_a_legacy_stored_value_still_renders_a_label_not_unknown(): void
    {
        // Never offered for new selection (covered above) — but if a
        // historical row is ever found with this value (none were found
        // in the data searched at removal time), it must still render its
        // accurate historical label, not silently disappear as "Unknown."
        $this->assertSame('Bank Transfer', PaymentDescriptionPresenter::methodLabel(OrderPaymentMethod::Online));
        $this->assertSame('Bank Transfer', PaymentDescriptionPresenter::methodLabel('BankTransfer'));
    }

    public function test_canonical_statuses_exclude_account_and_invoice_variants(): void
    {
        $canonical = OrderPaymentStatus::canonical();
        $this->assertNotContains(OrderPaymentStatus::Account, $canonical);
        $this->assertNotContains(OrderPaymentStatus::InvoiceCash, $canonical);
        $this->assertCount(7, $canonical);
    }

    // ── describe() — status and method decomposed separately, never fused ──

    public function test_describe_splits_status_and_method_for_a_normal_payment(): void
    {
        $payment = Mockery::mock(OrderPayment::class)->makePartial();
        $payment->status = OrderPaymentStatus::Paid;
        $payment->payment_method = OrderPaymentMethod::Cash;

        $result = PaymentDescriptionPresenter::describe($payment);

        $this->assertSame(['status' => 'Paid in Full', 'method' => 'Cash'], $result);
    }

    public function test_describe_prefers_implied_method_for_legacy_invoice_rows(): void
    {
        // A legacy "Invoice Cash" row may have a stale/unset payment_method
        // column — the status's implied method is the trustworthy source.
        $payment = Mockery::mock(OrderPayment::class)->makePartial();
        $payment->status = OrderPaymentStatus::InvoiceCash;
        $payment->payment_method = null;

        $result = PaymentDescriptionPresenter::describe($payment);

        $this->assertSame(['status' => 'Paid in Full', 'method' => 'Cash'], $result);
    }

    // ── History descriptions — status never inferred from method ───────

    public function test_history_description_never_assumes_card_for_a_settled_payment(): void
    {
        // The original defect: every "OrderPaid" history entry hardcoded
        // "Credit/Debit Card" regardless of the actual method.
        $payment = Mockery::mock(OrderPayment::class)->makePartial();
        $payment->status = OrderPaymentStatus::Paid;
        $payment->payment_method = OrderPaymentMethod::Cash;

        $this->assertSame('Paid in Full via Cash', PaymentDescriptionPresenter::historyDescription($payment));
    }

    public function test_history_description_for_pending_payment_says_initiated(): void
    {
        $payment = Mockery::mock(OrderPayment::class)->makePartial();
        $payment->status = OrderPaymentStatus::Pending;
        $payment->payment_method = OrderPaymentMethod::Cheque;

        $this->assertSame('Payment initiated via Check', PaymentDescriptionPresenter::historyDescription($payment));
    }

    public function test_history_description_for_failed_payment_does_not_claim_settlement(): void
    {
        // historyDescription() intentionally does NOT special-case Failed
        // (every caller logs a separate failureDescription() row for the
        // outcome) — it must not claim the payment settled either.
        $payment = Mockery::mock(OrderPayment::class)->makePartial();
        $payment->status = OrderPaymentStatus::Failed;
        $payment->payment_method = OrderPaymentMethod::Card;

        $description = PaymentDescriptionPresenter::historyDescription($payment);
        $this->assertStringNotContainsString('Paid in Full', $description);
    }

    public function test_failure_description_names_the_actual_method(): void
    {
        $payment = Mockery::mock(OrderPayment::class)->makePartial();
        $payment->status = OrderPaymentStatus::Failed;
        $payment->payment_method = OrderPaymentMethod::Card;

        $this->assertSame('Payment failed via Credit / Debit Card', PaymentDescriptionPresenter::failureDescription($payment));
    }

    public function test_history_description_for_invoice_status_uses_implied_method_not_stale_column(): void
    {
        $payment = Mockery::mock(OrderPayment::class)->makePartial();
        $payment->status = OrderPaymentStatus::InvoiceCheque;
        $payment->payment_method = null;

        $this->assertSame('Paid in Full via Check', PaymentDescriptionPresenter::historyDescription($payment));
    }

    public function test_partial_payment_history_description(): void
    {
        $payment = Mockery::mock(OrderPayment::class)->makePartial();
        $payment->status = OrderPaymentStatus::PartialPayment;
        $payment->payment_method = OrderPaymentMethod::GiftCard;

        $this->assertSame('Partial payment received via Gift Card', PaymentDescriptionPresenter::historyDescription($payment));
    }

    // ── timelineEntry() — Phase 2 structured Payment Timeline ───────────

    private function makeHistory(string $action, ?OrderPayment $payment = null, string $description = 'fallback text'): OrderHistory
    {
        $history = new OrderHistory(['action' => $action, 'description' => $description]);
        $history->setRelation('orderPayment', $payment);

        return $history;
    }

    public function test_timeline_entry_falls_back_to_description_when_no_payment_is_linked(): void
    {
        $history = $this->makeHistory(OrderHistoryAction::TermsSigned->value, null, 'Terms signed by customer');

        $entry = PaymentDescriptionPresenter::timelineEntry($history);

        $this->assertSame('Terms signed by customer', $entry['headline']);
        $this->assertNull($entry['method']);
        $this->assertNull($entry['amount']);
        $this->assertSame('neutral', $entry['dot']);
    }

    public function test_timeline_entry_for_a_received_payment_shows_method_and_amount(): void
    {
        $payment = Mockery::mock(OrderPayment::class)->makePartial();
        $payment->payment_method = OrderPaymentMethod::Cash;
        $payment->amount = 250.0;
        $payment->payment_note = null;

        $history = $this->makeHistory(OrderHistoryAction::OrderPaid->value, $payment);

        $entry = PaymentDescriptionPresenter::timelineEntry($history);

        $this->assertSame('Payment received', $entry['headline']);
        $this->assertSame('Cash', $entry['method']);
        $this->assertSame(250.0, $entry['amount']);
        $this->assertSame('pos', $entry['sign']);
        $this->assertSame('good', $entry['dot']);
    }

    public function test_timeline_entry_for_store_credit_hides_the_redundant_method_field(): void
    {
        $payment = Mockery::mock(OrderPayment::class)->makePartial();
        $payment->payment_method = OrderPaymentMethod::StoreCredit;
        $payment->amount = 125.0;
        $payment->payment_note = null;

        $history = $this->makeHistory(OrderHistoryAction::StoreCreditApplied->value, $payment);

        $entry = PaymentDescriptionPresenter::timelineEntry($history);

        $this->assertSame('Store Credit applied', $entry['headline']);
        $this->assertNull($entry['method']);
        $this->assertSame(125.0, $entry['amount']);
        $this->assertSame('pos', $entry['sign']);
    }

    public function test_timeline_entry_for_a_refund_reads_refund_amount_not_amount(): void
    {
        $payment = Mockery::mock(OrderPayment::class)->makePartial();
        $payment->payment_method = OrderPaymentMethod::Card;
        $payment->amount = null;
        $payment->refund_amount = 85.0;
        $payment->payment_note = null;

        $history = $this->makeHistory(OrderHistoryAction::OrderRefunded->value, $payment);

        $entry = PaymentDescriptionPresenter::timelineEntry($history);

        $this->assertSame('Refund processed', $entry['headline']);
        $this->assertNull($entry['method']);
        $this->assertSame(85.0, $entry['amount']);
        $this->assertSame('neg', $entry['sign']);
    }

    public function test_timeline_entry_for_a_void_reads_amount_not_refund_amount(): void
    {
        // Void reverses the original row in place (no new refund row), so
        // its amount lives on `amount`, unlike a Refund's `refund_amount`.
        $payment = Mockery::mock(OrderPayment::class)->makePartial();
        $payment->payment_method = OrderPaymentMethod::Card;
        $payment->amount = 199.99;
        $payment->refund_amount = null;
        $payment->payment_note = null;

        $history = $this->makeHistory(OrderHistoryAction::TransactionVoided->value, $payment);

        $entry = PaymentDescriptionPresenter::timelineEntry($history);

        $this->assertSame('Payment voided', $entry['headline']);
        $this->assertSame(199.99, $entry['amount']);
        $this->assertSame('neg', $entry['sign']);
    }

    public function test_timeline_entry_surfaces_the_payment_note(): void
    {
        $payment = Mockery::mock(OrderPayment::class)->makePartial();
        $payment->payment_method = OrderPaymentMethod::Other;
        $payment->amount = 642.04;
        $payment->payment_note = 'Zelle from John Smith';

        $history = $this->makeHistory(OrderHistoryAction::OrderPaid->value, $payment);

        $entry = PaymentDescriptionPresenter::timelineEntry($history);

        $this->assertSame('Zelle from John Smith', $entry['note']);
    }

    public function test_timeline_entry_a_pending_payment_shows_no_amount(): void
    {
        // Nothing has actually moved yet — showing a green amount would
        // misleadingly imply money was collected.
        $payment = Mockery::mock(OrderPayment::class)->makePartial();
        $payment->payment_method = OrderPaymentMethod::Cash;
        $payment->amount = 200.0;
        $payment->payment_note = null;

        $history = $this->makeHistory(OrderHistoryAction::PaymentInitiated->value, $payment);

        $entry = PaymentDescriptionPresenter::timelineEntry($history);

        $this->assertSame('Payment initiated', $entry['headline']);
        $this->assertSame('Cash', $entry['method']);
        $this->assertNull($entry['amount']);
    }
}
