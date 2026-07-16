<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Enums\Customers\PaymentMethod;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\RefundCalculationType;
use App\Helpers\ConfigurationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderManagement\Orders\RefundPaymentPreviewRequest;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Services\Orders\PaymentAllocationService;
use App\Services\PaymentDescriptionPresenter;

/**
 * Phase 3D — Server-Authoritative Confirmation Preview.
 *
 * Before this endpoint existed, the refund modal's confirmation step was
 * explicitly documented as a client-side ESTIMATE only (see edit.blade.php's
 * showConfirmationStep()) — this controller runs the exact same validation
 * and split math RefundPaymentController uses, but never calls a gateway
 * and never writes anything, so the employee sees real numbers before
 * committing to anything irreversible.
 *
 * Deliberately mirrors RefundPaymentController's allocation resolution
 * (auto-derive for a single eligible source, resolveRequestedTotal(),
 * validateAllocationSet(), calculateAllocationSplits()) so a preview can
 * never show a total that the real submission would then reject.
 */
class RefundPaymentPreviewController extends Controller
{
    public function __invoke($uniqueId, RefundPaymentPreviewRequest $request)
    {
        $validated = $request->validated();

        $order = Order::where('unique_id', $uniqueId)->firstOrFail();

        if (PaymentAllocationService::orderHasAmbiguousRefundAttribution($order)) {
            return response()->json([
                'success' => false,
                'message' => 'This order has a legacy refund whose original payment cannot be determined. New refunds are blocked until that transaction is resolved.',
            ], 422);
        }

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

        $idempotencyToken = $validated['idempotency_token'] ?? null;
        $existingRefund = $idempotencyToken
            ? OrderPayment::where('order_id', $order->id)->where('idempotency_token', $idempotencyToken)->first()
            : null;

        $allocationsInput = $validated['allocations'] ?? null;

        if (!$allocationsInput) {
            $eligible = PaymentAllocationService::eligibleOriginalPayments($order);

            if ($eligible->count() === 1) {
                $allocationsInput = [[
                    'original_order_payment_id' => $eligible->first()->id,
                    'amount' => (float) ($validated['amount'] ?? 0),
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

        [$requestedTotal, $calcError] = PaymentAllocationService::resolveRequestedTotal($order, $calcType, (float) ($validated['amount'] ?? 0));
        if ($calcError !== null) {
            return response()->json(['success' => false, 'message' => $calcError], 422);
        }

        $validationErrors = PaymentAllocationService::validateAllocationSet(
            $order,
            $allocationsInput,
            $requestedTotal,
            $calcType,
            $existingRefund,
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

        $rows = collect($splits['rows'])->map(function ($split) use ($originalsById, $existingRefund) {
            $original = $originalsById->get($split['original_order_payment_id']);

            $alreadyCompleted = $existingRefund
                && PaymentAllocationService::hasSuccessfulAllocation($existingRefund, $original->id);

            $remainingBefore = round(PaymentAllocationService::remainingRefundable($original), 2);
            $previouslyRefunded = round((float) $original->amount - $remainingBefore, 2);
            $remainingAfter = $alreadyCompleted
                ? $remainingBefore
                : max(0.0, round($remainingBefore - $split['amount'], 2));

            $label = PaymentDescriptionPresenter::methodLabel($original->payment_method);
            if ($original->payment_method === OrderPaymentMethod::Card && $original->card_number) {
                $label .= ' •••• '.$original->card_number;
            }

            return [
                'original_order_payment_id' => $original->id,
                'method' => $label,
                'original_amount' => (float) $original->amount,
                'previously_refunded' => $previouslyRefunded,
                'remaining_before' => $remainingBefore,
                'refund_now' => $split['amount'],
                'fee_retained' => $split['fee'],
                'remaining_after' => $remainingAfter,
                'already_completed' => $alreadyCompleted,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'rows' => $rows,
            'total_amount' => $splits['total_amount'],
            'total_base' => $splits['total_base'],
            'total_tax' => $splits['total_tax'],
            'total_fee' => $splits['total_fee'],
            'total_net' => $splits['total_net'],
        ]);
    }
}
