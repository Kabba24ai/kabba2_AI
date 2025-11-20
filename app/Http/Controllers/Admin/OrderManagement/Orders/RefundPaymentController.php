<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Events\Admin\Orders\RefundInitiateEvent;
use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\OrderManagement\Orders\RefundRequest;

// Models
use App\Models\Orders\Order;
use App\Services\AuthorizeNetService;

class RefundPaymentController extends Controller
{
    /**
     * Handle refunding of orders.
     */
    public function __invoke($uniqueId, RefundRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();


        try {
            $order = Order::has('lastPaidPayment')->with('customer')->where('unique_id', $uniqueId)->firstOrFail();

            $lastPaymentId = $order->lastPaidPayment->transaction_id;
            $lastRefundPaymentId = $order?->lastRefundPayment?->transaction_id;

            // Here you would integrate with your payment gateway to process the refund.
            $authorizeNetService = new AuthorizeNetService();

            if($lastRefundPaymentId){
                $transactionDetails = $authorizeNetService->getTransactionDetails($lastRefundPaymentId);
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

            $response = $authorizeNetService->refundOrder($lastPaymentId, $validated['amount'], [
                'order_number' => $order->order_number,
                'refund_note' => $validated['reason'],
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

            // Use accessor instead of manual sum
            $remaining = $order->remaining_amount;
            $currentRefundAmount = (float) $validated['amount'];

            if ($currentRefundAmount <= 0) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Refund amount must be greater than zero.',
                    ],
                    400,
                );
            }

            if ($currentRefundAmount > $remaining) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => "Refund exceeds remaining refundable amount. Remaining: {$remaining}.",
                        'remaining' => $remaining,
                    ],
                    400,
                );
            }

            // Decide status using accessor
            $willRemain = $remaining - $currentRefundAmount;
            $refundStatus = $willRemain <= 0 ? OrderPaymentStatus::Refund : OrderPaymentStatus::PartialRefund;
            $payment = $order->payments()->create([
                'payment_datetime' => now(),
                'parent_order_payment_id' => $order->lastPayment->id,
                'payment_method' => OrderPaymentMethod::Card->value,
                'transaction_id' => $response['transaction_id'] ?? null,
                'card_number' => $response['card_number'] ?? null,
                'auth_code' => $response['auth_code'] ?? null,
                'status' => $refundStatus->value,
                'refund_amount' => $currentRefundAmount,
                'refund_note' => $validated['reason'],
            ]);

            event(new RefundInitiateEvent($order, $user, $payment));

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully!',
            ]);
        } catch (\Exception $e) {

            logger()->error('Refund error for Order ID: ' . $uniqueId . ' - ' . $e->getMessage());
            return response()->json(
                [
                    'success' => false,
                    'message' => 'An error occurred while processing the refund. Please try again.',
                ],
                500,
            );
        }
    }
}
