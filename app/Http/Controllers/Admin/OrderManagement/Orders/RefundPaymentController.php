<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Enums\Customers\PaymentMethod;
use App\Events\Admin\Orders\RefundInitiateEvent;
use App\Helpers\ConfigurationHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

// Requests
use App\Http\Requests\Admin\OrderManagement\Orders\RefundRequest;

// Models
use App\Enums\Orders\ProcessedReason;
use App\Enums\Orders\RefundCalculationType;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Services\AuthorizeNetService;
use App\Services\Orders\PaymentAllocationService;

class RefundPaymentController extends Controller
{
    /**
     * Map Customers\PaymentMethod to Orders\OrderPaymentMethod
     */
    private function mapPaymentMethod(string $customerPaymentMethod): string
    {
        return match ($customerPaymentMethod) {
            PaymentMethod::CreditCard->value => OrderPaymentMethod::Card->value,
            PaymentMethod::Cash->value => OrderPaymentMethod::Cash->value,
            PaymentMethod::Cheque->value => OrderPaymentMethod::Cheque->value,
            PaymentMethod::TapToPay->value => OrderPaymentMethod::TapToPay->value,
            PaymentMethod::StoreCredit->value => OrderPaymentMethod::StoreCredit->value,
            PaymentMethod::GiftCard->value => OrderPaymentMethod::GiftCard->value,
            PaymentMethod::ZelleVenmo->value => OrderPaymentMethod::ZelleVenmo->value,
            PaymentMethod::Other->value => OrderPaymentMethod::Other->value,
            default => OrderPaymentMethod::Other->value,
        };
    }

    /**
     * Resolves the authoritative refund amount/tax/fee for the requested
     * calculation type. For the two special types the server computes the
     * figures itself and never trusts the client-submitted `amount` — a
     * stale or tampered value can never reach the gateway or the ledger.
     *
     * @return array{0: ?float, 1: ?float, 2: ?float, 3: ?string} [amount, taxRefunded, feeRetained, errorMessage]
     */
    private function resolveRefundCalculation(
        Order $order,
        \App\Services\Orders\OrderPaymentSummary $summary,
        RefundCalculationType $calcType,
        float $requestedAmount,
        float $remaining,
        string $paymentType
    ): array {
        if ($calcType === RefundCalculationType::CardProcessingFeeRetained) {
            // Filtered in-memory from the already-loaded settled-payments
            // collection (one query, done once in __invoke()) rather than a
            // fresh query here — narrower than "settled" on purpose: the
            // fee-retained shortcut only ever applies to a genuine, fully
            // Paid card charge, never a PartialPayment/Invoice* row.
            $paidPayments = $summary->originalSettledPayments->where('status', OrderPaymentStatus::Paid);

            if ($paidPayments->count() !== 1) {
                return [null, null, null, 'Full Amount Less Card Processing Fee is only available for orders with exactly one payment.'];
            }

            $payment = $paidPayments->first();

            if ($payment->payment_method !== OrderPaymentMethod::Card || !$payment->transaction_id) {
                return [null, null, null, 'Full Amount Less Card Processing Fee is only available when the original payment was made by Credit / Debit Card.'];
            }

            if ($paymentType !== PaymentMethod::CreditCard->value) {
                return [null, null, null, 'Full Amount Less Card Processing Fee must be refunded to Credit / Debit Card.'];
            }

            if ($remaining <= 0) {
                return [null, null, null, 'No refundable balance remains.'];
            }

            $feePct = (float) (ConfigurationHelper::getSettings('Product Settings', 'credit_card_processing_fee') ?? 0);

            if ($feePct <= 0) {
                return [null, null, null, 'No Credit Card Processing Fee is configured.'];
            }

            $eligibleAmount = round(min((float) $payment->amount, $remaining), 2);
            $feeRetained = round($eligibleAmount * $feePct / 100, 2);

            if ($feeRetained >= $eligibleAmount) {
                return [null, null, null, 'The Credit Card Processing Fee equals or exceeds the refundable amount.'];
            }

            $refundAmount = round($eligibleAmount - $feeRetained, 2);

            return [$refundAmount, $this->proportionalTaxRefund($order, $refundAmount), $feeRetained, null];
        }

        if ($calcType === RefundCalculationType::SalesTaxOnly) {
            $alreadyRefundedTax = (float) $order->payments()
                ->whereIn('status', [OrderPaymentStatus::PartialRefund, OrderPaymentStatus::Refund])
                ->sum('tax_refunded');

            $remainingRefundableTax = max(0.0, round((float) $order->tax_amount - $alreadyRefundedTax, 2));
            $remainingRefundableTax = round(min($remainingRefundableTax, $remaining), 2);

            if ($remainingRefundableTax <= 0) {
                return [null, null, null, 'No refundable sales tax remains for this order.'];
            }

            // The whole refund IS the tax — no purchase/rental revenue impact.
            return [$remainingRefundableTax, $remainingRefundableTax, null, null];
        }

        // Standard — unchanged behavior: the submitted amount, proportional tax split.
        return [$requestedAmount, $this->proportionalTaxRefund($order, $requestedAmount), null, null];
    }

    /**
     * The pre-existing proportional tax-extraction formula (unchanged),
     * factored out so Standard and Card-Processing-Fee-Retained refunds
     * (which both owe the correct tax split on their final amount) share
     * one implementation instead of two copies.
     */
    private function proportionalTaxRefund(Order $order, float $refundAmount): float
    {
        $originalTaxRate = ((float) $order->subtotal > 0 && (float) $order->tax_amount > 0)
            ? (float) $order->tax_amount / (float) $order->subtotal
            : 0.0;

        return $originalTaxRate > 0
            ? round($refundAmount - ($refundAmount / (1 + $originalTaxRate)), 2)
            : 0.0;
    }

    /**
     * Handle refunding of orders.
     */
    public function __invoke($uniqueId, RefundRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();

        // Processed By audit layer: the employee physically at the terminal
        // (code already verified by RefundRequest) — recorded alongside the
        // logged-in user, never instead of it.
        $processedBy = User::findOrFail($validated['processed_by']);
        $reason      = ProcessedReason::from($validated['reason']);
        $reasonText  = $reason->label()
            . ($reason === ProcessedReason::Other && filled($validated['reason_other'] ?? null)
                ? ' — ' . $validated['reason_other'] : '');

        // Refund idempotency — applies to every payment method and every
        // calculation type (Standard, Card Processing Fee Retained, Sales
        // Tax Only alike). One UUID per modal-open, reused across retries
        // of that same submission (see edit.blade.php's openRefundModal()).
        // The lock closes the concurrent-duplicate race BEFORE any gateway
        // call or financial write is attempted; the DB-unique column on
        // order_payments.idempotency_token is the storage-level backstop
        // if two requests somehow still interleave.
        $idempotencyToken = $validated['idempotency_token'] ?? null;
        $lock = $idempotencyToken
            ? Cache::lock("refund-idempotency:{$uniqueId}:{$idempotencyToken}", 30)
            : null;

        if ($lock && !$lock->get()) {
            return response()->json([
                'success' => false,
                'message' => 'This refund is already being processed. Please wait a moment and refresh the order.',
            ], 409);
        }

        try {
            $order = Order::has('lastPaidPayment')->with('customer')->where('unique_id', $uniqueId)->firstOrFail();

            // Phase 3A: refuse to guess which original payment a refund
            // belongs to. Today's single-source refund flow can only ever
            // safely target an order with exactly one settled original
            // payment — on a genuinely multi-payment order there is no
            // unambiguous target, and silently picking "the last paid
            // payment" (the prior behavior) is exactly the defect this
            // phase fixes. Source-payment selection across multiple
            // payments is Phase 3B/3C scope, not this one.
            $summary = $order->paymentSummary();
            if (!$summary->hasUnambiguousRefundSource()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order was paid using more than one payment, so the system cannot yet determine which payment a refund should be drawn from. Refunds on multi-payment orders are not supported in this release — selecting a specific source payment is planned for an upcoming update.',
                ], 422);
            }

            if ($idempotencyToken) {
                $alreadyProcessed = OrderPayment::where('order_id', $order->id)
                    ->where('idempotency_token', $idempotencyToken)
                    ->exists();

                if ($alreadyProcessed) {
                    // Same submission, already completed — return the same
                    // outcome rather than touching the gateway or ledger
                    // again. A prior FAILED attempt never reaches this
                    // point (the refund row is only ever created on full
                    // success, at the very end of this method), so a retry
                    // after a genuine failure proceeds normally below.
                    return response()->json([
                        'success' => true,
                        'message' => 'Refund processed successfully!',
                    ]);
                }
            }

            // The one unambiguous settled original payment confirmed above —
            // not Order::lastPaidPayment, which (being a bare "highest id
            // with status Paid" lookup) is a coincidence in the single-
            // payment case, not a guarantee. Using the already-verified
            // $summary keeps there being exactly one place that decides
            // "which payment is this."
            $lastPayment = $summary->unambiguousRefundSource();
            $lastPaymentId = $lastPayment->transaction_id;

            $refundPaymentType = $validated['payment_type'];
            $isCardRefund = $refundPaymentType === PaymentMethod::CreditCard->value;
            $isOriginalCard = $lastPayment->payment_method === OrderPaymentMethod::Card;

            $calcType = RefundCalculationType::tryFrom($validated['refund_calculation_type'] ?? '')
                ?? RefundCalculationType::Standard;

            // Resolved BEFORE the gateway call — for the two special types
            // this is the server-computed, authoritative amount; the raw
            // client-submitted `amount` is never sent to the gateway or
            // stored for those types.
            [$currentRefundAmount, $taxRefunded, $feeRetained, $calcError] = $this->resolveRefundCalculation(
                $order,
                $summary,
                $calcType,
                (float) $validated['amount'],
                $summary->orderRefundableBalance,
                $refundPaymentType,
            );

            if ($calcError !== null) {
                return response()->json(['success' => false, 'message' => $calcError], 422);
            }

            // Two independent caps: the order-level refundable balance
            // (Total Settled Payments − Total Successful Refunds — fixed in
            // Phase 3A to no longer be anchored to grand_total, which was
            // wrong for a partially-paid order) and this specific payment's
            // own remaining refundable amount. In today's guarded single-
            // source case the two are mathematically identical (this is the
            // only settled payment on the order), but both are enforced
            // explicitly so the invariant is real and testable, not just a
            // coincidence of the current single-payment scope. Reused from
            // $summary (computed once above) rather than re-querying —
            // one canonical snapshot for the whole request.
            $remaining = $summary->orderRefundableBalance;
            $paymentRemaining = $order->remainingRefundableForPayment($lastPayment);
            $effectiveCap = min($remaining, $paymentRemaining);

            if ($currentRefundAmount <= 0) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Refund amount must be greater than zero.',
                    ],
                    400,
                );
            }

            if ($currentRefundAmount > $effectiveCap) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => "Refund exceeds remaining refundable amount. Remaining: {$effectiveCap}.",
                        'remaining' => $effectiveCap,
                    ],
                    400,
                );
            }

            if ($isCardRefund && $isOriginalCard) {
                // Here you would integrate with your payment gateway to process the refund.
                $authorizeNetService = app(AuthorizeNetService::class);

                if($lastPaymentId){
                    $transactionDetails = $authorizeNetService->getTransactionDetails($lastPaymentId);

                    if (!in_array($transactionDetails->status, ['settledSuccessfully','refundSettledSuccessfully'])) {
                        return response()->json(
                            [
                                'success' => false,
                                'message' => 'Last Refund transaction not settled, retry after the transaction settles.',
                            ],
                            400,
                        );
                    }

                }

                $response = $authorizeNetService->refundOrder($lastPaymentId, $currentRefundAmount, [
                    'order_number' => $order->order_number,
                    'refund_note' => $reasonText,
                ]);

                if (($response['status'] ?? null) !== 'success') {
                    logger()->error('Refund failed for Order ID: ' . $order->unique_id . ' - ' . ($response['message'] ?? 'Unknown error'));
                    return response()->json(
                        [
                            'success' => false,
                            'message' => 'Refund failed: ' . ($response['message'] ?? 'Unknown error'),
                        ],
                        500,
                    );
                }

                $transactionId   = $response['transaction_id']   ?? null;
                $gatewayRefundId = $response['gateway_refund_id'] ?? null;
                $cardNumber      = $response['card_number']       ?? null;
                $authCode        = $response['auth_code']         ?? null;
            } else {
                // For non-card refunds or if original was not card, just log the refund without processing through gateway
                logger()->info('Refund logged for Order ID: ' . $order->unique_id . ' - Refund Payment Method: ' . $refundPaymentType . ' - Amount: ' . $currentRefundAmount . ' - Reason: ' . $reasonText);

                $transactionId   = null;
                $gatewayRefundId = null;
                $cardNumber      = null;
                $authCode        = null;
            }

            // Refunding to Store Credit must actually grant the credit —
            // never just a label — the same "Cash means cash" principle
            // applied to every method: selecting Store Credit must mean
            // the customer's real balance genuinely increased. The grant
            // row's order_payment_id is linked to the refund's own row
            // below (once created), so that customer_credits row always
            // traces back to the refund that produced it.
            $storeCreditGrant = null;
            if ($refundPaymentType === PaymentMethod::StoreCredit->value) {
                // Namespaced so a duplicate submit of *this* refund is
                // recognized without colliding with an unrelated grant/
                // redemption that happened to reuse the same raw token.
                $idempotencyKey = $idempotencyToken
                    ? "refund:{$order->id}:{$idempotencyToken}"
                    : null;

                $storeCreditGrant = \App\Services\CustomerCreditService::createFinancialCredit(
                    customerId: $order->customer_id,
                    amount: $currentRefundAmount,
                    reason: "Refund on Order {$order->order_number}: {$reasonText}",
                    responsibleUserId: $processedBy->id,
                    idempotencyKey: $idempotencyKey,
                    orderId: $order->id,
                );
            }

            // Decide status using accessor
            $willRemain = $remaining - $currentRefundAmount;
            $refundStatus = $willRemain <= 0 ? OrderPaymentStatus::Refund : OrderPaymentStatus::PartialRefund;
            $payment = $order->payments()->create([
                'payment_datetime'        => now(),
                'refunded_at'             => now(),
                // Fixed Phase 3A defect: previously $order->lastPayment->id
                // (highest-id row of ANY status), which on an order's
                // second-or-later refund resolved to the FIRST refund row
                // instead of the original payment, corrupting the audit
                // chain. $lastPayment here is the verified, unambiguous
                // original settled payment resolved above.
                'parent_order_payment_id' => $lastPayment->id,
                'payment_method'          => $this->mapPaymentMethod($refundPaymentType),
                'transaction_id'          => $transactionId,
                'gateway_refund_id'       => $gatewayRefundId,
                'card_number'             => $cardNumber,
                'auth_code'               => $authCode,
                'status'                  => $refundStatus->value,
                'refund_amount'           => $currentRefundAmount,
                'tax_refunded'            => $taxRefunded,
                'refund_calculation_type' => $calcType->value,
                'cc_fee_retained'         => $feeRetained,
                'idempotency_token'       => $idempotencyToken,
                'refund_note'             => $reasonText,
                'payment_note'            => $validated['payment_note'] ?? null,
                'cheque_number'           => $validated['cheque_number'] ?? null,
                'created_by_type'         => get_class($user),
                'created_by_id'           => $user->id,
                // Processed By audit (additive — logged-in user above unchanged)
                'processed_by_id'         => $processedBy->id,
                'processed_by_name'       => $processedBy->full_name,
                'processed_reason_code'   => $reason->value,
                'processed_reason_label'  => $reason->label(),
                'processed_reason_other'  => $validated['reason_other'] ?? null,
            ]);

            // Phase 3B: record the durable allocation for this refund
            // alongside the parent_order_payment_id set above — $lastPayment
            // is the same verified, unambiguous original payment either way,
            // so this is never a second, independent decision about "which
            // payment is this," only the allocation-table record of it.
            PaymentAllocationService::allocateSingleSource(
                refund: $payment,
                original: $lastPayment,
                allocatedAmount: $currentRefundAmount,
                allocatedBaseAmount: round($currentRefundAmount - $taxRefunded, 2),
                allocatedTaxAmount: $taxRefunded,
                processingFeeRetained: $feeRetained,
                gatewayTransactionId: $gatewayRefundId,
            );

            if ($storeCreditGrant) {
                $storeCreditGrant->update(['order_payment_id' => $payment->id]);
            }

            event(new RefundInitiateEvent($order, $user, $payment));

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully!',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Backstop for the narrow race the cache lock doesn't cover
            // (e.g. lock driver unavailable): the DB-unique
            // idempotency_token column rejected a second insert for a
            // token that just completed on another request. Report the
            // same success outcome rather than a scary 500.
            if ($idempotencyToken && str_contains(strtolower($e->getMessage()), 'idempotency_token')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Refund processed successfully!',
                ]);
            }

            logger()->error('Refund error for Order ID: ' . $uniqueId . ' - ' . $e->getMessage());
            return response()->json(
                [
                    'success' => false,
                    'message' => 'An error occurred while processing the refund. Please try again.',
                ],
                500,
            );
        } catch (\Exception $e) {

            logger()->error('Refund error for Order ID: ' . $uniqueId . ' - ' . $e->getMessage());
            return response()->json(
                [
                    'success' => false,
                    'message' => 'An error occurred while processing the refund. Please try again.',
                ],
                500,
            );
        } finally {
            if ($lock) {
                $lock->release();
            }
        }
    }
}
