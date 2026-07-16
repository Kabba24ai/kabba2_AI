<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Enums\Orders\OrderPaymentStatus;
use App\Enums\Orders\RefundOperationStatus;
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
use App\Services\CustomerCreditService;
use App\Services\Orders\PaymentAllocationService;

/**
 * Phase 3C — Employee-Selected Refund Sources and Multi-Source Refund
 * Processing.
 *
 * Every allocation-aware decision (eligibility, suggestion, validation,
 * base/tax/fee splitting, card-fee lifetime caps, remaining-refundable
 * math, parent-pointer derivation, refund-operation outcome) is owned by
 * PaymentAllocationService — this controller only orchestrates: resolves
 * the request, calls the gateway once per card source, and persists the
 * outcome each call returns. See that service's class docblock for the
 * full allocation model.
 */
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
     * Resolves the FIXED requested total for calc types that have one.
     * Standard uses the client-submitted amount (unchanged Phase 3A/3B
     * behavior); Sales Tax Only computes the order's remaining refundable
     * sales tax server-side, same formula as before, now expressed at the
     * order level rather than against one payment. Card Processing Fee
     * Retained has no single fixed total to resolve up front — its total
     * is a derived OUTPUT of PaymentAllocationService::calculateAllocationSplits()
     * (gross draw per source minus that source's own capped fee), so this
     * returns null for it and validateAllocationSet() skips the
     * sum-equals-requested-total rule for that case.
     *
     * @return array{0: ?float, 1: ?string} [requestedTotal, errorMessage]
     */
    private function resolveRequestedTotal(Order $order, RefundCalculationType $calcType, float $clientAmount): array
    {
        if ($calcType === RefundCalculationType::SalesTaxOnly) {
            $alreadyRefundedTax = (float) $order->payments()
                ->whereIn('status', [OrderPaymentStatus::PartialRefund, OrderPaymentStatus::Refund])
                ->sum('tax_refunded');

            $remainingRefundableTax = max(0.0, round((float) $order->tax_amount - $alreadyRefundedTax, 2));
            $remainingRefundableTax = round(min($remainingRefundableTax, (float) $order->remaining_amount), 2);

            if ($remainingRefundableTax <= 0) {
                return [null, 'No refundable sales tax remains for this order.'];
            }

            return [$remainingRefundableTax, null];
        }

        if ($calcType === RefundCalculationType::CardProcessingFeeRetained) {
            return [null, null];
        }

        if ($clientAmount <= 0) {
            return [null, 'Refund amount must be greater than zero.'];
        }

        return [$clientAmount, null];
    }

    /**
     * Handle refunding of orders — one or more original payments in a
     * single refund event.
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

        // Refund idempotency — one client-generated token per modal-open,
        // reused across retries of that same submission (see
        // edit.blade.php's openRefundModal()). The lock closes the
        // concurrent-duplicate race BEFORE any gateway call or financial
        // write is attempted. Phase 3C: a token now also identifies a
        // resumable multi-source refund EVENT — see the retry handling
        // below, where an existing PartiallyCompleted/Failed row under the
        // same token is reused rather than starting a new refund row.
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
            $order = Order::with('customer')->where('unique_id', $uniqueId)->firstOrFail();

            // Phase 3C: the old "more than one settled payment blocks every
            // refund" gate is gone — an order may now have several eligible
            // original payments, selected explicitly below. The ONLY thing
            // that still blocks a new refund outright is a legacy row this
            // system genuinely cannot attribute (see PaymentAllocationService
            // class docblock) — every per-payment remaining-refundable
            // figure on the order is unsafe to trust while one exists.
            if (PaymentAllocationService::orderHasAmbiguousRefundAttribution($order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order has a legacy refund whose original payment cannot be determined ("Allocation unavailable for legacy transaction"). New refunds are blocked until that transaction is resolved.',
                ], 422);
            }

            $existingRefund = $idempotencyToken
                ? OrderPayment::where('order_id', $order->id)->where('idempotency_token', $idempotencyToken)->first()
                : null;

            if ($existingRefund && $existingRefund->refund_operation_status === RefundOperationStatus::Completed) {
                // Same submission, already fully completed — return the
                // same outcome rather than touching the gateway or ledger
                // again.
                return response()->json([
                    'success' => true,
                    'message' => 'Refund processed successfully!',
                ]);
            }

            // A PartiallyCompleted or Failed row under this exact token is a
            // RETRY — resume it rather than creating a second refund event.
            $isRetry = $existingRefund !== null;

            $refundPaymentType = $validated['payment_type'];
            $calcType = RefundCalculationType::tryFrom($validated['refund_calculation_type'] ?? '')
                ?? RefundCalculationType::Standard;

            if ($calcType === RefundCalculationType::CardProcessingFeeRetained
                && $refundPaymentType !== PaymentMethod::CreditCard->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'Full Amount Less Card Processing Fee must be refunded to Credit / Debit Card.',
                ], 422);
            }

            // Resolve the allocation set: explicit from the request, or —
            // for backward compatibility with a caller that only ever knew
            // about a single-payment order — auto-derived when there is
            // exactly one eligible original payment. A genuinely
            // multi-payment order has no safe default and must select
            // explicitly (see the Phase 3C mission's request contract).
            $allocationsInput = $validated['allocations'] ?? null;

            if (!$allocationsInput) {
                $eligible = PaymentAllocationService::eligibleOriginalPayments($order);

                if ($eligible->count() === 1) {
                    $allocationsInput = [[
                        'original_order_payment_id' => $eligible->first()->id,
                        'amount' => (float) $validated['amount'],
                    ]];
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $eligible->count() === 0
                            ? 'No refundable payment was found for this order.'
                            : 'This order was paid using more than one payment. Select which payment(s) to refund from.',
                    ], 422);
                }
            }

            $allocationsInput = array_map(fn ($row) => [
                'original_order_payment_id' => (int) ($row['original_order_payment_id'] ?? 0),
                'amount' => (float) ($row['amount'] ?? 0),
            ], $allocationsInput);

            [$requestedTotal, $calcError] = $this->resolveRequestedTotal($order, $calcType, (float) $validated['amount']);
            if ($calcError !== null) {
                return response()->json(['success' => false, 'message' => $calcError], 422);
            }

            $validationErrors = PaymentAllocationService::validateAllocationSet(
                $order,
                $allocationsInput,
                $requestedTotal,
                $calcType,
                $isRetry ? $existingRefund : null,
            );

            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => $validationErrors[0],
                    'errors' => $validationErrors,
                ], 422);
            }

            $originalsById = OrderPayment::whereIn('id', array_column($allocationsInput, 'original_order_payment_id'))
                ->get()->keyBy('id');

            $feePct = 0.0;
            if ($calcType === RefundCalculationType::CardProcessingFeeRetained) {
                $feePct = (float) (ConfigurationHelper::getSettings('Product Settings', 'credit_card_processing_fee') ?? 0);
                if ($feePct <= 0) {
                    return response()->json(['success' => false, 'message' => 'No Credit Card Processing Fee is configured.'], 422);
                }
            }

            $splits = PaymentAllocationService::calculateAllocationSplits(
                $order, $originalsById, $allocationsInput, $calcType, $feePct
            );

            if ($calcType === RefundCalculationType::CardProcessingFeeRetained && $splits['total_net'] <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'The Credit Card Processing Fee equals or exceeds the refundable amount.',
                ], 422);
            }

            // remaining_amount captured now — for a fresh refund this is the
            // order's balance before any of THIS event's allocations; for a
            // retry it already reflects whatever this same event previously
            // succeeded, which is exactly the right anchor for "how much
            // MORE can still be refunded" (see
            // PaymentAllocationService::syncRefundOperationOutcome()).
            $remainingBeforeOperation = (float) $order->remaining_amount;

            if (!$isRetry) {
                $refund = $order->payments()->create([
                    'payment_datetime'        => now(),
                    'refunded_at'             => now(),
                    'payment_method'          => $this->mapPaymentMethod($refundPaymentType),
                    // Provisional — finalized by syncRefundOperationOutcome()
                    // below once every allocation attempt has resolved.
                    'status'                  => OrderPaymentStatus::Pending->value,
                    'refund_operation_status' => RefundOperationStatus::Pending->value,
                    // The refund row represents the TOTAL REQUESTED refund
                    // (Phase 3C mission §11) — for Card Processing Fee
                    // Retained that's the customer-facing net total;
                    // otherwise the gross total, which for those two calc
                    // types has no separate fee to subtract.
                    'refund_amount'           => $calcType === RefundCalculationType::CardProcessingFeeRetained
                        ? $splits['total_net'] : $splits['total_amount'],
                    'tax_refunded'            => $splits['total_tax'],
                    'refund_calculation_type' => $calcType->value,
                    'cc_fee_retained'         => $splits['total_fee'] > 0 ? $splits['total_fee'] : null,
                    'idempotency_token'       => $idempotencyToken,
                    'refund_note'             => $reasonText,
                    'payment_note'            => $validated['payment_note'] ?? null,
                    'cheque_number'           => $validated['cheque_number'] ?? null,
                    'created_by_type'         => get_class($user),
                    'created_by_id'           => $user->id,
                    'processed_by_id'         => $processedBy->id,
                    'processed_by_name'       => $processedBy->full_name,
                    'processed_reason_code'   => $reason->value,
                    'processed_reason_label'  => $reason->label(),
                    'processed_reason_other'  => $validated['reason_other'] ?? null,
                ]);
            } else {
                $refund = $existingRefund;
            }

            // Phase 3C partial-failure recovery: if this is a retry and the
            // employee dropped a previously-Failed source from the
            // resubmission (reallocating that money to a different source
            // instead — see $splits below), relabel the old row Superseded
            // so it stops reading as an ongoing failure. Idempotent/no-op
            // on a fresh refund (no Failed rows exist yet).
            PaymentAllocationService::supersedeAbandonedFailures(
                $refund,
                array_column($allocationsInput, 'original_order_payment_id'),
            );

            /*
             * ── Persistence and gateway-call sequence (documented per request) ──
             *
             * For each source, in order:
             *   1. If this (refund, original) pair already has an Allocated
             *      allocation row, skip entirely — no gateway call, no write.
             *      This is what makes a retry safe to call repeatedly.
             *   2. Otherwise, if this is a card-to-card refund: call the
             *      gateway for THIS source's own amount.
             *   3. IMMEDIATELY after the gateway call returns (success or
             *      failure) — before moving to the next source — persist the
             *      outcome as one atomic write via
             *      PaymentAllocationService::allocateSingleSource(), which
             *      itself performs a single updateOrCreate() statement.
             *
             * Every source's gateway-call-then-persist pair is wrapped in
             * its OWN try/catch (below), not one try/catch around the whole
             * loop. This matters for exactly the property this refinement
             * asked to document: a successful gateway refund is never lost
             * track of because a LATER source's persist (or gateway call)
             * throws — each source's own persist has already committed to
             * the database as its own statement before the loop even
             * reaches the next source, so an exception on source #2 cannot
             * undo or hide source #1's already-durable success.
             *
             * The one residual risk this cannot fully close: if the
             * gateway call for a given source succeeds but the immediately-
             * following persist throws (e.g. a transient DB error in that
             * exact window), this system has real money moved at the
             * gateway with no local record of it — and because
             * AuthorizeNetService::refundOrder() has no idempotency-key
             * parameter to make a blind retry provably safe, a naive retry
             * of that SAME source could double-refund it at the gateway.
             * This is therefore treated as a "stop digging" case, not a
             * silent one: the catch block below does NOT attempt to
             * persist a normal Failed allocation for it (that would make
             * it retryable, and retrying would re-call the gateway) —
             * instead it logs CRITICAL with the gateway_refund_id and every
             * figure needed for manual reconciliation, and the source is
             * surfaced to the employee as needing manual review rather
             * than either "succeeded" or "safely retryable." See the
             * inline comment at the catch block below.
             */
            $isCardDestination = $refundPaymentType === PaymentMethod::CreditCard->value;
            $anet = $isCardDestination ? app(AuthorizeNetService::class) : null;
            $results = [];

            foreach ($splits['rows'] as $split) {
                $original = $originalsById->get($split['original_order_payment_id']);

                $existingAllocation = $refund->refundAllocations()
                    ->where('original_order_payment_id', $original->id)
                    ->first();

                if ($existingAllocation && $existingAllocation->status === OrderPaymentRefundAllocationStatus::Allocated) {
                    // Already succeeded on a prior attempt of this exact
                    // refund event — never re-call the gateway for it.
                    $results[] = [
                        'payment' => $original, 'amount' => (float) $existingAllocation->allocated_amount,
                        'success' => true, 'already' => true, 'failure_reason' => null,
                    ];
                    continue;
                }

                $isCardSource = $original->payment_method === OrderPaymentMethod::Card;
                $gatewayRefundId = null;
                $failureReason = null;
                $success = true;
                $gatewaySucceededButUnrecorded = false;

                try {
                    if ($isCardDestination && $isCardSource) {
                        // Routed to THIS original payment's own gateway
                        // transaction — never the last payment, never another
                        // allocation's transaction (Phase 3C mission §10).
                        if (!$original->transaction_id) {
                            $success = false;
                            $failureReason = 'No gateway transaction found for this original payment.';
                        } else {
                            $details = $anet->getTransactionDetails($original->transaction_id);

                            if (!$details || !in_array($details->status, ['settledSuccessfully', 'refundSettledSuccessfully'], true)) {
                                $success = false;
                                $failureReason = 'Original transaction not settled; retry after it settles.';
                            } else {
                                $response = $anet->refundOrder($original->transaction_id, $split['amount'], [
                                    'order_number' => $order->order_number,
                                    'refund_note' => $reasonText,
                                ]);

                                if (($response['status'] ?? null) !== 'success') {
                                    $success = false;
                                    $failureReason = $response['message'] ?? 'Gateway declined the refund.';
                                    logger()->error("Refund allocation failed for Order {$order->unique_id}, original payment {$original->id}: {$failureReason}");
                                } else {
                                    $gatewayRefundId = $response['gateway_refund_id'] ?? null;
                                }
                            }
                        }
                    }
                    // Every other combination — a manual destination (Cash,
                    // Cheque, Store Credit, Tap to Pay, Gift Card, Zelle/Venmo,
                    // Other), or a card destination drawing from a non-card
                    // original (no gateway relationship exists to route to) —
                    // is a local allocation: no gateway call, always succeeds.

                    // The persist — see the sequence note above: this is the
                    // very next statement after the gateway call resolves,
                    // and it is this source's own complete, atomic write.
                    PaymentAllocationService::allocateSingleSource(
                        refund: $refund,
                        original: $original,
                        allocatedAmount: $split['amount'],
                        allocatedBaseAmount: $split['base'],
                        allocatedTaxAmount: $split['tax'],
                        processingFeeRetained: $split['fee'] > 0 ? $split['fee'] : null,
                        gatewayTransactionId: $gatewayRefundId,
                        status: $success ? OrderPaymentRefundAllocationStatus::Allocated : OrderPaymentRefundAllocationStatus::Failed,
                        failureReason: $failureReason,
                    );
                } catch (\Throwable $e) {
                    if ($gatewayRefundId !== null) {
                        // The gateway call for THIS source already succeeded
                        // — money already moved (or fee already retained) —
                        // and the persist that was supposed to record it
                        // just failed. Do not mark this Failed (that would
                        // invite a retry that re-calls the gateway for
                        // money that already moved). Log everything needed
                        // for a human to reconcile via the gateway
                        // dashboard, and surface it to the employee as
                        // needing manual review rather than a normal retry.
                        logger()->critical(
                            "REFUND RECONCILIATION REQUIRED: gateway refund succeeded but could not be recorded. "
                            ."Order: {$order->unique_id} (id {$order->id}). Refund row id: {$refund->id}. "
                            ."Original payment id: {$original->id}, transaction_id: {$original->transaction_id}. "
                            ."Gateway refund id: {$gatewayRefundId}. Amount: {$split['amount']}. "
                            .'Persist error: '.$e->getMessage()
                        );
                        $success = false;
                        $failureReason = "Refund succeeded at the gateway (ref {$gatewayRefundId}) but could not be recorded — do not retry this source; contact support for manual reconciliation.";
                        $gatewaySucceededButUnrecorded = true;
                    } else {
                        // No gateway call happened, or it happened and
                        // failed cleanly, or this was a local allocation —
                        // safe to log and record as an ordinary failure,
                        // retryable like any other declined source.
                        logger()->error("Refund allocation error for Order {$order->unique_id}, original payment {$original->id}: ".$e->getMessage());
                        $success = false;
                        $failureReason = $failureReason ?? 'An unexpected error occurred processing this source.';

                        try {
                            PaymentAllocationService::allocateSingleSource(
                                refund: $refund, original: $original,
                                allocatedAmount: $split['amount'], allocatedBaseAmount: $split['base'], allocatedTaxAmount: $split['tax'],
                                processingFeeRetained: $split['fee'] > 0 ? $split['fee'] : null,
                                status: OrderPaymentRefundAllocationStatus::Failed, failureReason: $failureReason,
                            );
                        } catch (\Throwable $inner) {
                            // The persist itself is failing even for a clean
                            // failure record (e.g. DB is genuinely down) —
                            // nothing more this request can do for this
                            // source; it will show as unresolved via
                            // refund_operation_status staying short of
                            // Completed once syncRefundOperationOutcome()
                            // runs below, since this row records neither
                            // Allocated nor a fresh Failed for it.
                            logger()->critical("Refund allocation could not be persisted at all for Order {$order->unique_id}, original payment {$original->id}: ".$inner->getMessage());
                        }
                    }
                }

                $results[] = [
                    'payment' => $original, 'amount' => $split['amount'],
                    'success' => $success, 'already' => false, 'failure_reason' => $failureReason,
                    'needs_manual_review' => $gatewaySucceededButUnrecorded,
                ];
            }

            // Refunding to Store Credit must actually grant the credit —
            // never just a label. Store Credit is always a local
            // destination (no gateway dependency), so every allocation
            // above always "succeeds" for it — the granted amount is the
            // actual successful total, which for Store Credit is always
            // the full requested total. createFinancialCredit()'s own
            // idempotencyKey dedup (unchanged from Phase 3A/3B) makes this
            // call safe to repeat on a retry without granting twice.
            if ($refundPaymentType === PaymentMethod::StoreCredit->value) {
                $actualSucceeded = (float) $refund->refundAllocations()
                    ->where('status', OrderPaymentRefundAllocationStatus::Allocated->value)
                    ->sum('allocated_amount');

                if ($actualSucceeded > 0) {
                    $idempotencyKey = $idempotencyToken ? "refund:{$order->id}:{$idempotencyToken}" : null;

                    $storeCreditGrant = CustomerCreditService::createFinancialCredit(
                        customerId: $order->customer_id,
                        amount: $actualSucceeded,
                        reason: "Refund on Order {$order->order_number}: {$reasonText}",
                        responsibleUserId: $processedBy->id,
                        idempotencyKey: $idempotencyKey,
                        orderId: $order->id,
                    );

                    if ($storeCreditGrant->order_payment_id !== $refund->id) {
                        $storeCreditGrant->update(['order_payment_id' => $refund->id]);
                    }
                }
            }

            PaymentAllocationService::syncRefundOperationOutcome($refund, $remainingBeforeOperation);
            $refund->refresh();

            event(new RefundInitiateEvent($order, $user, $refund));

            $operationStatus = $refund->refund_operation_status;
            $httpStatus = $operationStatus === RefundOperationStatus::Failed ? 422 : 200;
            $message = match ($operationStatus) {
                RefundOperationStatus::Completed => 'Refund processed successfully!',
                RefundOperationStatus::PartiallyCompleted => 'Refund partially completed — some sources could not be refunded. Review the details and retry.',
                RefundOperationStatus::Failed => 'Refund failed — none of the selected sources could be refunded.',
                default => 'Refund is being processed.',
            };

            return response()->json([
                'success' => $operationStatus !== RefundOperationStatus::Failed,
                'message' => $message,
                'refund_operation_status' => $operationStatus?->value,
                // The figure the confirmation/results UI must lead with —
                // Phase 3C refinement: "not just a status label."
                'outstanding_amount' => PaymentAllocationService::outstandingRefundAmount($refund),
                'sources' => collect($results)->map(fn ($r) => [
                    'original_order_payment_id' => $r['payment']->id,
                    'method' => $r['payment']->payment_method?->label(),
                    'amount' => $r['amount'],
                    'success' => $r['success'],
                    'failure_reason' => $r['failure_reason'],
                    'needs_manual_review' => $r['needs_manual_review'] ?? false,
                ])->values(),
            ], $httpStatus);
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
