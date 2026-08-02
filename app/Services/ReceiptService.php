<?php

namespace App\Services;

use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Orders\Order;
use App\Models\Customers\Receipt;
use App\Services\Orders\OrderPaymentSummary;
use App\Services\PaymentDescriptionPresenter;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReceiptService
{
    public static function getOrCreateReceipt(Order $order)
    {
        try {
            // Try fetching latest receipt
            // Latest by ID, not by created_at. The supersession chain is
            // ordered by insertion, and a superseding receipt is frequently
            // written in the same second as the one it replaces — `latest()`
            // on a second-resolution timestamp could return either.
            $receipt = Receipt::with([
                'invoice',
                'items',
                'items.orderProduct',
                'customer.addresses.state',
                'customer.billingAddress',
                'customer.shippingAddress',
            ])
                ->where('order_id', $order->id)
                ->latest('id')
                ->first();

            if ($receipt) {
                return $receipt;
            }

            // Map payment status & method from last payment
            $paymentStatus = self::mapPaymentStatus($order);
            $paymentMethod = self::mapPaymentMethod($order);

            // Create new receipt
            $receipt = Receipt::create([
                'customer_id'    => $order->customer_id,
                'order_id'       => $order->id,
                'payment_method' => $paymentMethod,
                'receipt_date'   => now(),
                'order_date'     => $order->order_date,
                'payment_status' => $paymentStatus,
                'subtotal'       => $order->subtotal,
                'sales_tax'      => $order->tax_amount,
                // An original receipt states no concession. Written explicitly
                // rather than left to the column default so the identity
                // subtotal − goodwill + tax = total holds on the returned
                // model, not merely in the database.
                'goodwill_amount' => 0,
                'total'          => $order->grand_total,
            ]);

            // Add receipt items from order products
            foreach ($order->products as $invItem) {
                $receipt->items()->create([
                    'type'      => 'order',
                    'item_name' => $invItem->product_name,
                    'unit'      => $invItem->price,
                    'qty'       => $invItem->quantity,
                    'tax'       => $invItem->tax,
                    'total'     => $invItem->total,
                    'item_id'   => $invItem->unique_id,
                ]);
            }

            // Mark order receipt as created
            $order->receipt_status = 'created';
            $order->saveQuietly();

            Log::info('Receipt Created for Order', [
                'order_id'   => $order->id,
                'order_num'  => $order->order_number,
                'receipt_id' => $receipt->id,
            ]);

            return $receipt;
        } catch (Throwable $e) {
            Log::error('Failed to get or create receipt', [
                'order_id' => $order->id,
                'order_num' => $order->order_number,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null; // Return null if failed
        }
    }

    /**
     * The current receipt for an order, or null. Never creates one.
     *
     * "Current" is the newest row by id. Superseded receipts are kept forever
     * and never edited — they record what the customer was originally told —
     * but only the newest is the live document.
     */
    public static function currentReceipt(Order $order): ?Receipt
    {
        return Receipt::where('order_id', $order->id)->latest('id')->first();
    }

    /**
     * Issue a receipt that REPLACES the order's current one.
     *
     * Called from inside {@see \App\Services\Orders\GoodwillAdjustmentService}'s
     * transaction, after the order totals have been rewritten and while the
     * order row is still locked — so the new receipt cannot capture a
     * half-applied state, and a rollback discards the receipt along with the
     * adjustment.
     *
     * NOTHING IS OVERWRITTEN. The prior receipt and every one of its items are
     * left exactly as they were; a new row is inserted that points back at it
     * via `superseded_receipt_id`. Rewriting the old document would destroy
     * the evidence of what the customer was originally handed.
     *
     * Returns null when the order has no receipt yet — there is nothing to
     * supersede, and the eventual `getOrCreateReceipt()` will build a correct
     * one from the already-adjusted order.
     *
     * @param  float  $goodwillAmount  The concession to state on the document.
     * @param  float|null  $originalSubtotal  The pre-adjustment merchandise
     *         figure. A receipt's `subtotal` describes the GOODS SUPPLIED,
     *         which a concession does not change; the reduction belongs on its
     *         own line, not folded silently into the merchandise total. Null
     *         (a reversal) uses the order's own current subtotal.
     */
    public static function supersede(
        Order $order,
        ?int $goodwillAdjustmentId = null,
        float $goodwillAmount = 0.0,
        ?float $originalSubtotal = null,
    ): ?Receipt {
        $current = self::currentReceipt($order);

        if (! $current) {
            return null;
        }

        $receipt = Receipt::create([
            'customer_id'            => $order->customer_id,
            'order_id'               => $order->id,
            'superseded_receipt_id'  => $current->id,
            'goodwill_adjustment_id' => $goodwillAdjustmentId,
            'invoice_id'             => $current->invoice_id,
            'payment_method'         => self::mapPaymentMethod($order),
            'receipt_date'           => now(),
            'order_date'             => $order->order_date,
            'payment_status'         => self::mapPaymentStatus($order),
            'subtotal'               => $originalSubtotal ?? $order->subtotal,
            'sales_tax'              => $order->tax_amount,
            'goodwill_amount'        => $goodwillAmount,
            'total'                  => $order->grand_total,
        ]);

        // Items are rebuilt from the order's CURRENT lines. `price` is
        // untouched by an adjustment, so the itemized list still shows what
        // was ordered at what unit price; only the tax and line totals move.
        foreach ($order->products as $invItem) {
            $receipt->items()->create([
                'type'      => 'order',
                'item_name' => $invItem->product_name,
                'unit'      => $invItem->price,
                'qty'       => $invItem->quantity,
                'tax'       => $invItem->tax,
                'total'     => $invItem->total,
                'item_id'   => $invItem->unique_id,
            ]);
        }

        Log::info('Receipt superseded', [
            'order_id'       => $order->id,
            'superseded_id'  => $current->id,
            'new_receipt_id' => $receipt->id,
            'goodwill'       => $goodwillAmount,
        ]);

        return $receipt;
    }

    /**
     * Map the order's current canonical financial state to the narrow
     * paid|pending|failed snapshot stored on the receipt row at creation
     * time. Refunds/partial payments collapse to 'pending' here since the
     * DB column only supports 3 values — the richer, always-live label
     * actually shown on the receipt comes from currentPaymentStatusLabel()
     * below, never from this stored snapshot.
     *
     * Payment Architecture Finalization (Phase 4A): sourced from
     * OrderPaymentSummary's aggregate collectionStatus/refundStatus and
     * unresolvedPaymentAttempts (not Order::last_payment_status directly)
     * so this can never call an order "failed" because of a stale failed
     * attempt that a later payment already resolved — same precedence
     * rule the Order Details header uses.
     */
    private static function mapPaymentStatus(Order $order): string
    {
        $summary = OrderPaymentSummary::for($order);

        if ($summary->collectionStatus === OrderPaymentSummary::COLLECTION_PAID_IN_FULL
            && $summary->refundStatus === OrderPaymentSummary::REFUND_NONE) {
            return 'paid';
        }

        if ($summary->unresolvedPaymentAttempts->contains(fn ($p) => $p->status === OrderPaymentStatus::Failed)) {
            return 'failed';
        }

        return 'pending';
    }

    /**
     * The receipt's payment status as it should read RIGHT NOW — derived
     * live from the order's current aggregate financial state rather than
     * a value captured once when the receipt row was first created. This
     * is the single source of truth for what the receipt displays; the
     * print_receipt view calls this directly instead of reading the
     * (potentially stale) stored Receipt::payment_status.
     *
     * Payment Architecture Finalization (Phase 4A): delegates to
     * PaymentDescriptionPresenter::orderStatusLabel() — the same aggregate
     * status text every other screen (Order Details, CRM, Dispatch,
     * Schedules, API) now shows, so a receipt never disagrees with the
     * order's status shown anywhere else in the app. This intentionally
     * changes wording for a refunded order — e.g. "Refunded" becomes
     * "Paid in Full · Fully Refunded" — which is a correctness
     * improvement, not a cosmetic one: the old bare "Refunded"/"Partially
     * Refunded" text didn't distinguish "was paid in full, then refunded"
     * from "was only ever partially paid before being refunded," which
     * the canonical label now does.
     *
     * The one thing orderStatusLabel() doesn't distinguish — a genuinely
     * still-unpaid order where the most relevant collection attempt
     * failed, vs. one that simply hasn't been attempted yet — is preserved
     * below via unresolvedPaymentAttempts (the precedence-safe field: a
     * resolved order never reaches this branch at all, since
     * collectionStatus would already read Paid/Partially Paid).
     *
     * Voided: customers routinely need printable proof that a charge was
     * cancelled, so an order whose money was authorized/captured and then
     * voided before settlement reads "Voided" — sourced from
     * OrderPaymentSummary::voidedPayments (the actual Voided rows stamped
     * by VoidPaymentController), never inferred from a Failed/declined
     * attempt, which is a different thing entirely (the charge never
     * succeeded, nothing existed to cancel). This branch is only reachable
     * when nothing on the order is settled or refunded (label 'Unpaid'):
     * a voided row alongside settled money correctly keeps reading
     * Paid in Full / Partially Paid above. Voided outranks Failed here —
     * when both rows exist, the void is the operationally meaningful
     * event, and a mere declined attempt must never be presented as a
     * void (nor vice versa).
     */
    public static function currentPaymentStatusLabel(Order $order): string
    {
        $summary = OrderPaymentSummary::for($order);
        $label = PaymentDescriptionPresenter::orderStatusLabel($summary);

        if ($label !== 'Unpaid') {
            return $label;
        }

        if ($summary->voidedPayments->isNotEmpty()) {
            return 'Voided';
        }

        if ($summary->unresolvedPaymentAttempts->contains(fn ($p) => $p->status === OrderPaymentStatus::Failed)) {
            return 'Failed';
        }

        return 'Pending';
    }

    /**
     * The receipt's payment method as it should read RIGHT NOW — live from
     * every settled original payment on the order, the same "always live,
     * never the frozen creation-time snapshot" pattern as
     * currentPaymentStatusLabel(). This is what actually gets printed; the
     * stored Receipt::payment_method column is kept for audit only.
     *
     * Payment Architecture Finalization (Phase 4A): previously read
     * $order->lastPaidPayment ?? $order->lastPayment — a single row. On a
     * split-payment order that silently showed only whichever method was
     * entered most recently, exactly the bug class
     * PaymentDescriptionPresenter::methodsUsedLabel() exists to fix
     * everywhere else in the app. Sourced from
     * OrderPaymentSummary::paymentMethodsUsed (every settled — Paid,
     * PartialPayment, or legacy Invoice* — original payment) instead, so a
     * two-method order now reads "Multiple Methods" here exactly as it
     * already does on the Order Details header, CRM, Dispatch, and
     * Schedules. This also means a payment attempt that only ever reached
     * Pending/Failed (never actually settled) can no longer be shown as
     * "the" payment method — a correctness fix, not just a rewording,
     * since the old fallback-to-lastPayment could surface a declined
     * card's method as if it had been charged.
     *
     * Returns null when there's no settled payment to describe yet (e.g. a
     * Pay on Delivery order still pending) — callers should show Payment
     * Terms instead in that case, via PaymentDescriptionPresenter::termsLabel().
     */
    public static function currentPaymentMethodLabel(Order $order): ?string
    {
        $methodsUsed = OrderPaymentSummary::for($order)->paymentMethodsUsed;

        if ($methodsUsed->isEmpty()) {
            return null;
        }

        return PaymentDescriptionPresenter::methodsUsedLabel($methodsUsed);
    }

    /**
     * Per-method amount breakdown across every Paid/PartialPayment row on
     * the order — e.g. "Cash $100.00 / Credit Card $542.04". Written in
     * Phase 2 (architecture only, not yet wired to a view); Payment
     * Architecture Finalization (Phase 4A) wires it into
     * print_receipt.blade.php's Payment Method line, shown only when more
     * than one method was used (currentPaymentMethodLabel() already says
     * "Multiple Methods" in that case — this supplies the itemized amounts
     * behind that label). Reads live from the existing payment ledger — no
     * new table, no duplicated data.
     *
     * @return array<int, array{method: string, amount: float}>
     */
    public static function paymentMethodBreakdown(Order $order): array
    {
        return $order->payments()
            ->whereIn('status', [
                \App\Enums\Orders\OrderPaymentStatus::Paid->value,
                \App\Enums\Orders\OrderPaymentStatus::PartialPayment->value,
            ])
            ->get()
            ->groupBy(fn ($p) => $p->payment_method?->value ?? 'unknown')
            ->map(fn ($rows) => [
                'method' => PaymentDescriptionPresenter::methodLabel($rows->first()->payment_method),
                'amount' => (float) $rows->sum('amount'),
            ])
            ->values()
            ->all();
    }

    /**
     * Phase 3D — the itemized refund list for the receipt's "Refunds"
     * section: one line per successfully-Allocated allocation, grouped by
     * the original payment's method (mission §5's "mixed payment methods
     * must display as separate lines"). Deliberately mirrors
     * paymentMethodBreakdown()'s shape above, but for refunds.
     *
     * Only Allocated allocations are ever included — Pending/Failed never
     * reach the customer-facing receipt, and no internal field (gateway
     * transaction id, failure_reason, allocation id) is exposed here.
     * Legacy refunds with no allocation rows yet fall back to one line per
     * refund row (whole-row date/amount/method), the same fallback shape
     * every other legacy path in PaymentAllocationService uses.
     *
     * @return array<int, array{method: string, date: ?\Carbon\Carbon, amount: float, calc_type: ?string, fee_retained: float, tax_refunded: float}>
     */
    public static function refundDetails(Order $order): array
    {
        $refunds = $order->payments()->refund()->with('refundAllocations.originalPayment')->get();

        $lines = [];

        foreach ($refunds as $refund) {
            $allocated = $refund->refundAllocations->where('status', OrderPaymentRefundAllocationStatus::Allocated);

            if ($allocated->isNotEmpty()) {
                foreach ($allocated as $allocation) {
                    $original = $allocation->originalPayment;
                    $lines[] = [
                        'method' => PaymentDescriptionPresenter::methodLabel($original?->payment_method),
                        'date' => $refund->refunded_at ?? $refund->created_at,
                        'amount' => (float) $allocation->allocated_amount - (float) ($allocation->processing_fee_retained ?? 0),
                        'calc_type' => $refund->refund_calculation_type?->value,
                        'fee_retained' => (float) ($allocation->processing_fee_retained ?? 0),
                        'tax_refunded' => (float) $allocation->allocated_tax_amount,
                    ];
                }
            } elseif ($refund->refundAllocations->isEmpty()) {
                $lines[] = [
                    'method' => PaymentDescriptionPresenter::methodLabel($refund->payment_method),
                    'date' => $refund->refunded_at ?? $refund->created_at,
                    'amount' => (float) $refund->refund_amount,
                    'calc_type' => $refund->refund_calculation_type?->value,
                    'fee_retained' => (float) ($refund->cc_fee_retained ?? 0),
                    'tax_refunded' => (float) $refund->tax_refunded,
                ];
            }
        }

        return $lines;
    }

    /**
     * Map a settled payment method to the narrow, single-value legacy
     * Receipt::payment_method column (audit snapshot only — see
     * currentPaymentMethodLabel() for the live, multi-method-aware label
     * actually printed). Cash means cash: COD (a payment-terms placeholder,
     * never itself a completed method) and Account (an Accounts Receivable
     * workflow marker, not a way funds were transferred) must never guess
     * their way into 'cash' — they resolve to null (method not yet
     * actually known) instead.
     *
     * Payment Architecture Finalization (Phase 4A): sourced from the
     * oldest SETTLED original payment (OrderPaymentSummary::
     * paymentMethodsUsed->first()) rather than Order::lastPayment (any
     * status, most recent row) — a Pending/Failed row's method can no
     * longer be recorded here as if it had actually settled. This legacy
     * column has no way to represent "multiple methods," so a
     * split-payment order's snapshot is intentionally left as a
     * single representative method; the live receipt display never reads
     * this column for that case (see currentPaymentMethodLabel()).
     */
    private static function mapPaymentMethod(Order $order): string|null
    {
        $method = OrderPaymentSummary::for($order)->paymentMethodsUsed->first();

        if (!$method) {
            return null;
        }

        return match ($method) {
            \App\Enums\Orders\OrderPaymentMethod::Card => 'card',
            \App\Enums\Orders\OrderPaymentMethod::Cash => 'cash',
            \App\Enums\Orders\OrderPaymentMethod::Online => 'online',
            \App\Enums\Orders\OrderPaymentMethod::Cheque => 'cheque',
            \App\Enums\Orders\OrderPaymentMethod::TapToPay => 'tap_to_pay',
            \App\Enums\Orders\OrderPaymentMethod::StoreCredit => 'store_credit',
            \App\Enums\Orders\OrderPaymentMethod::GiftCard => 'gift_card',
            \App\Enums\Orders\OrderPaymentMethod::ZelleVenmo => 'zelle_venmo',
            \App\Enums\Orders\OrderPaymentMethod::Other => 'other',
            \App\Enums\Orders\OrderPaymentMethod::COD, \App\Enums\Orders\OrderPaymentMethod::Account => null,
            default => 'other',
        };
    }
}
