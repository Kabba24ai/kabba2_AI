<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Enums\Orders\ProcessedReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderManagement\Orders\VoidRequest;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\AuthorizeNetService;

class VoidPaymentController extends Controller
{
    public function __invoke($uniqueId, VoidRequest $request)
    {
        $user = auth()->user();

        // Processed By audit layer: the employee physically at the terminal
        // (code already verified by VoidRequest) — recorded alongside the
        // logged-in user, never instead of it.
        $validated   = $request->validated();
        $processedBy = User::findOrFail($validated['processed_by']);
        $reason      = ProcessedReason::from($validated['reason']);
        $reasonText  = $reason->label()
            . ($reason === ProcessedReason::Other && filled($validated['reason_other'] ?? null)
                ? ' — ' . $validated['reason_other'] : '');
        $processedAudit = [
            'processed_by_id'        => $processedBy->id,
            'processed_by_name'      => $processedBy->full_name,
            'processed_reason_code'  => $reason->value,
            'processed_reason_label' => $reason->label(),
            'processed_reason_other' => $validated['reason_other'] ?? null,
        ];

        try {
            $order = Order::with(['lastPaidPayment', 'customer'])
                ->where('unique_id', $uniqueId)
                ->firstOrFail();

            $payment = $order->lastPaidPayment;

            if (!$payment) {
                return response()->json(['success' => false, 'message' => 'No paid transaction found.'], 422);
            }

            if ($payment->payment_method !== OrderPaymentMethod::Card) {
                return response()->json(['success' => false, 'message' => 'Only card payments can be voided through this system.'], 422);
            }

            $transactionId = $payment->transaction_id;

            if (!$transactionId) {
                return response()->json(['success' => false, 'message' => 'No Authorize.net transaction ID found for this payment.'], 422);
            }

            // Confirm the transaction is still unsettled via the gateway before attempting void
            $anet = app(AuthorizeNetService::class);
            $details = $anet->getTransactionDetails($transactionId);

            if (!$details) {
                return response()->json(['success' => false, 'message' => 'Could not retrieve transaction status from the payment gateway.'], 422);
            }

            $voidableStatuses = [
                'authorizedPendingCapture',
                'capturedPendingSettlement',
                'FDSPendingReview',
                'FDSAuthorizedPendingReview',
            ];

            // Gateway already shows voided — sync DB if our record is still Paid (prior void succeeded at gateway but DB update failed)
            if ($details->status === 'voided') {
                if ($payment->status === OrderPaymentStatus::Voided) {
                    return response()->json(['success' => false, 'message' => 'This payment has already been voided.'], 422);
                }

                $payment->update(array_merge([
                    'status'    => OrderPaymentStatus::Voided,
                    'voided_at' => now(),
                ], $processedAudit));

                $order->history()->create([
                    'user_id'     => $user->id,
                    'customer_id' => $order->customer_id,
                    'action_date' => now(),
                    'action_by'   => OrderHistoryActionBy::User,
                    'action'      => OrderHistoryAction::TransactionVoided,
                    'description' => 'Payment of $' . number_format((float) $payment->amount, 2) . ' voided by ' . $user->full_name
                        . '. Processed by ' . $processedBy->full_name . ' (Employee ID verified). Reason: ' . $reasonText . '.',
                    'extras'      => json_encode($processedAudit),
                ]);

                return response()->json(['success' => true, 'message' => 'Payment voided successfully.']);
            }

            if (!in_array($details->status, $voidableStatuses, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This transaction cannot be voided (gateway status: ' . $details->status . '). If it has already settled, use Refund instead.',
                ], 422);
            }

            $result = $anet->voidOrder($transactionId, ['order_number' => $order->order_number]);

            if (($result['status'] ?? null) !== 'success') {
                return response()->json([
                    'success' => false,
                    'message' => 'Void failed: ' . ($result['message'] ?? 'Unknown error'),
                ], 500);
            }

            // Mark original payment as voided — no new row, void is an in-place reversal
            $payment->update(array_merge([
                'status'    => OrderPaymentStatus::Voided,
                'voided_at' => now(),
            ], $processedAudit));

            $order->history()->create([
                'user_id'     => $user->id,
                'customer_id' => $order->customer_id,
                'order_payment_id' => $payment->id,
                'action_date' => now(),
                'action_by'   => OrderHistoryActionBy::User,
                'action'      => OrderHistoryAction::TransactionVoided,
                'description' => 'Payment of $' . number_format((float) $payment->amount, 2) . ' voided by ' . $user->full_name
                    . '. Processed by ' . $processedBy->full_name . ' (Employee ID verified). Reason: ' . $reasonText . '.',
                'extras'      => json_encode($processedAudit),
            ]);

            return response()->json(['success' => true, 'message' => 'Payment voided successfully.']);

        } catch (\Exception $e) {
            logger()->error('Void payment error for order ' . $uniqueId . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred while voiding the payment.'], 500);
        }
    }
}
