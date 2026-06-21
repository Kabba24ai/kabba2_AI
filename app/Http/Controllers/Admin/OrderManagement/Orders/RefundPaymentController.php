<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Enums\Customers\PaymentMethod;
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
     * Map Customers\PaymentMethod to Orders\OrderPaymentMethod
     */
    private function mapPaymentMethod(string $customerPaymentMethod): string
    {
        return match ($customerPaymentMethod) {
            PaymentMethod::CreditCard->value => OrderPaymentMethod::Card->value,
            PaymentMethod::Cash->value => OrderPaymentMethod::Cash->value,
            PaymentMethod::Cheque->value => OrderPaymentMethod::Cheque->value,
            PaymentMethod::BankTransfer->value => OrderPaymentMethod::Online->value,
            PaymentMethod::Other->value => OrderPaymentMethod::Other->value,
            default => OrderPaymentMethod::Other->value,
        };
    }
    /**
     * Handle refunding of orders.
     */
    public function __invoke($uniqueId, RefundRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();


        try {
            $order = Order::has('lastPaidPayment')->with('customer')->where('unique_id', $uniqueId)->firstOrFail();

            $lastPayment = $order->lastPaidPayment;
            $lastPaymentId = $lastPayment->transaction_id;

            $refundPaymentType = $validated['payment_type'];
            $isCardRefund = $refundPaymentType === PaymentMethod::CreditCard->value;
            $isOriginalCard = $lastPayment->payment_method === OrderPaymentMethod::Card;

            if ($isCardRefund && $isOriginalCard) {
                // Here you would integrate with your payment gateway to process the refund.
                $authorizeNetService = new AuthorizeNetService();

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

                $transactionId   = $response['transaction_id']   ?? null;
                $gatewayRefundId = $response['gateway_refund_id'] ?? null;
                $cardNumber      = $response['card_number']       ?? null;
                $authCode        = $response['auth_code']         ?? null;
            } else {
                // For non-card refunds or if original was not card, just log the refund without processing through gateway
                logger()->info('Refund logged for Order ID: ' . $order->unique_id . ' - Refund Payment Method: ' . $refundPaymentType . ' - Amount: ' . $validated['amount'] . ' - Reason: ' . $validated['reason']);

                $transactionId   = null;
                $gatewayRefundId = null;
                $cardNumber      = null;
                $authCode        = null;
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

            // Tax refunded: extract tax from the tax-inclusive refund_amount.
            // refund_amount is grand-total-basis (= grand_total − already_refunded), so we
            // use the "extract" formula: amount − amount/(1+rate), which matches
            // CustomHelper::calculateRefundSalesTax() used as the historic-record fallback.
            $originalTaxRate = ((float) $order->subtotal > 0 && (float) $order->tax_amount > 0)
                ? (float) $order->tax_amount / (float) $order->subtotal
                : 0.0;
            $taxRefunded = ($originalTaxRate > 0)
                ? round($currentRefundAmount - ($currentRefundAmount / (1 + $originalTaxRate)), 2)
                : 0.0;

            // Decide status using accessor
            $willRemain = $remaining - $currentRefundAmount;
            $refundStatus = $willRemain <= 0 ? OrderPaymentStatus::Refund : OrderPaymentStatus::PartialRefund;
            $payment = $order->payments()->create([
                'payment_datetime'        => now(),
                'refunded_at'             => now(),
                'parent_order_payment_id' => $order->lastPayment->id,
                'payment_method'          => $this->mapPaymentMethod($refundPaymentType),
                'transaction_id'          => $transactionId,
                'gateway_refund_id'       => $gatewayRefundId,
                'card_number'             => $cardNumber,
                'auth_code'               => $authCode,
                'status'                  => $refundStatus->value,
                'refund_amount'           => $currentRefundAmount,
                'tax_refunded'            => $taxRefunded,
                'refund_note'             => $validated['reason'],
                'cheque_number'           => $validated['cheque_number'] ?? null,
                'created_by_type'         => get_class($user),
                'created_by_id'           => $user->id,
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
