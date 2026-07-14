<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Http\Controllers\Controller;
use DB;


// Services
use App\Services\AuthorizeNetService;

// Requests
use App\Http\Requests\Admin\OrderManagement\Orders\ReceivePaymentRequest;

// Events
use App\Events\Admin\Orders\PaymentInitiateEvent;

// Models
use App\Models\Orders\Order;
use App\Models\Iam\Personnel\User;

class ReceivePaymentController extends Controller
{
    /**
     * Handle updating of orders.
     */
    public function __invoke($uniqueId, ReceivePaymentRequest $request)
    {

        $validated = $request->validated();
        $user = User::find($validated['responsible_person']);

        DB::beginTransaction();

        try {
            $order = Order::where('unique_id', $uniqueId)->with('customer')->firstOrFail();
            $customer = $order->customer;
            $customer->load('billingAddress', 'shippingAddress');

            $isPartial = !empty($validated['partial_payment']);
            if ($isPartial) {
                $amount = (float) ($validated['payment_amount'] ?? 0);
                if ($amount <= 0) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Payment amount must be greater than zero.'], 422);
                }
                $alreadyPaid = $order->payments()
                    ->whereIn('status', [OrderPaymentStatus::PartialPayment->value, OrderPaymentStatus::Paid->value])
                    ->sum('amount');
                $completesTotal = ($alreadyPaid + $amount) >= ((float) $order->grand_total - 0.005);
                $targetStatus = $completesTotal ? OrderPaymentStatus::Paid : OrderPaymentStatus::PartialPayment;
            } else {
                $amount = (float) $order->balance_due;
                $targetStatus = OrderPaymentStatus::Paid;
            }

            $paymentMethod = $validated['payment_type'] ?? null;
            $paymentNote = $validated['payment_note'] ?? null;

            if ($paymentMethod == "CreditCard") {
                if (isset($validated['customer_card']) && $validated['customer_card']) {
                    $cardDetail = $customer->cards()->where('unique_id', $validated['customer_card'])->firstOrFail();
                    $paymentProfileId = $cardDetail->payment_profile_id;
                    $customerProfileId = $customer->authorize_profile_id;

                    if (!$customerProfileId) {
                        DB::rollBack();
                        return back()->withInput()->with('error', 'Customer profile not found for saved card.');
                    }

                    $authorizeNetService = new AuthorizeNetService();

                    $paymentResult = $authorizeNetService->chargeCustomerProfile($customerProfileId, $paymentProfileId, $amount, [
                        'order_number' => $order->order_number,
                        'customer' => $customer->toArray(),
                    ]);

                    if (($paymentResult['status'] ?? null) !== 'success') {
                        logger()->error('Profile payment failed for Order ID: ' . $order->unique_id . ' - ' . ($paymentResult['message'] ?? 'Unknown error'));
                        DB::rollBack();
                        return back()
                            ->withInput()
                            ->with('error', $paymentResult['message'] ?? 'Payment failed.');
                    }

                    $payment = $order->payments()->create([
                        'payment_datetime' => now(),
                        'payment_method' => OrderPaymentMethod::Card->value,
                        'amount' => $amount,
                        'transaction_id' => $paymentResult['transaction_id'] ?? null,
                        'auth_code' => $paymentResult['auth_code'] ?? null,
                        'customer_profile_id' => $customerProfileId,
                        'payment_profile_id' => $paymentProfileId,
                        'card_number' => $paymentResult['card_number'] ?? null,
                        'card_first_name' => $cardDetail->first_name ?? null,
                        'card_last_name' => $cardDetail->last_name ?? null,
                        'status' => ($paymentResult['payment_status'] ?? 'Pending') === 'Paid' ? $targetStatus->value : ($paymentResult['payment_status'] ?? 'Pending'),
                        'payment_note' => $paymentNote,
                        'created_by_id' => $user->id,
                        'created_by_type' => User::class,
                    ]);
                } else {
                    $opaqueDataValue = $validated['opaqueDataValue'] ?? null;
                    $opaqueDataDescriptor = $validated['opaqueDataDescriptor'] ?? null;

                    if (!$opaqueDataValue || !$opaqueDataDescriptor) {
                        DB::rollback();
                        return redirect()->back()->withInput()->with('error', 'Payment data missing or invalid.');
                    }
                    $authorizeNetService = new AuthorizeNetService();
                    if (!$authorizeNetService->validateOpaqueData(['dataValue' => $opaqueDataValue, 'dataDescriptor' => $opaqueDataDescriptor])) {
                        DB::rollback();
                        return redirect()->back()->withInput()->with('error', 'Payment token invalid.');
                    }

                    $cardData = [
                        'card_number' => $validated['card_number'] ?? null,
                        'mm_yy' => $validated['mm_yy'] ?? null,
                    ];

                    $paymentResult = $authorizeNetService->createOpaqueDataTransaction($opaqueDataValue, $amount, ['order_number' => $order->order_number, 'customer' => $customer->toArray(), 'card_data' => $cardData]);
                    if ($paymentResult['status'] !== 'success') {
                        logger()->error('Payment failed for Order ID: ' . $order->unique_id . ' - ' . $paymentResult['message']);
                        // DB::rollback();
                        // return redirect()->back()->withInput()->with('error', $paymentResult['message'] ?? 'Payment failed.');
                    }
                    $payment = $order->payments()->create([
                        'payment_datetime' => now(),
                        'payment_method' => OrderPaymentMethod::Card->value,
                        'amount' => $amount,
                        'transaction_id' => $paymentResult['transaction_id'] ?? null,
                        'auth_code' => $paymentResult['auth_code'] ?? null,
                        'customer_profile_id' => $paymentResult['customer_profile_id'] ?? null,
                        'payment_profile_id' => $paymentResult['payment_profile_id'] ?? null,
                        'card_number' => $paymentResult['card_number'] ?? null,
                        'card_first_name' => $validated['firstName'] ?? null,
                        'card_last_name' => $validated['lastName'] ?? null,
                        'status' => ($paymentResult['payment_status'] ?? 'Pending') === 'Paid' ? $targetStatus->value : ($paymentResult['payment_status'] ?? 'Pending'),
                        'payment_note' => $paymentNote,
                        'created_by_id' => $user->id,
                        'created_by_type' => User::class,
                    ]);

                    if (empty($customer->authorize_profile_id) && !empty($paymentResult['customer_profile_id'])) {
                        // If payment profile is created, save it to customer's cards
                        $customer->authorize_profile_id = $paymentResult['customer_profile_id'];
                        $customer->saveQuietly();
                    }

                    if (!empty($paymentResult['payment_profile_id'])) {
                        $customer->cards()->updateOrCreate(
                            [
                                'payment_profile_id' => $paymentResult['payment_profile_id'],
                            ],
                            [
                                'first_name' => $validated['firstName'] ?? null,
                                'last_name' => $validated['lastName'] ?? null,
                                'card_number' => $paymentResult['card_number'] ?? null,
                                'card_type' => $paymentResult['card_type'] ?? null,
                            ],
                        );
                    }
                }
            }else{
                $orderPaymentMethod = match ($paymentMethod) {
                    'Cash' => OrderPaymentMethod::Cash->value,
                    'Cheque' => OrderPaymentMethod::Cheque->value,
                    'TapToPay' => OrderPaymentMethod::TapToPay->value,
                    'StoreCredit' => OrderPaymentMethod::StoreCredit->value,
                    'GiftCard' => OrderPaymentMethod::GiftCard->value,
                    'ZelleVenmo' => OrderPaymentMethod::ZelleVenmo->value,
                    'Other' => OrderPaymentMethod::Other->value,
                    default => null,
                };

                // Store Credit actually deducts from the customer's real
                // credit balance — never just a label, per the "Cash means
                // cash" principle applied to every method: selecting Store
                // Credit must mean the balance genuinely decreased.
                if ($orderPaymentMethod === OrderPaymentMethod::StoreCredit->value) {
                    // Namespaced so a duplicate submit of *this* payment is
                    // recognized (redeem() returns the existing row instead
                    // of redeeming twice) without colliding with an
                    // unrelated redeem()/createFinancialCredit() call that
                    // happened to reuse the same raw client token.
                    $idempotencyKey = !empty($validated['idempotency_token'])
                        ? "receive-payment:{$order->id}:{$validated['idempotency_token']}"
                        : null;

                    try {
                        \App\Services\CustomerCreditService::redeem(
                            customerId: $customer->id,
                            amount: $amount,
                            reason: "Applied to Order {$order->order_number}",
                            responsibleUserId: $user->id,
                            idempotencyKey: $idempotencyKey,
                            orderId: $order->id,
                        );
                    } catch (\RuntimeException $e) {
                        DB::rollBack();

                        return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
                    }
                }

                $payment = $order->payments()->create([
                    'payment_datetime' => now(),
                    'payment_method' => $orderPaymentMethod,
                    'amount' => $amount,
                    'transaction_id' => null,
                    'auth_code' => null,
                    'customer_profile_id' => null,
                    'payment_profile_id' => null,
                    'card_number' => null,
                    'card_first_name' => null,
                    'card_last_name' => null,
                    'status' => $targetStatus->value,
                    'payment_note' => $paymentNote,
                    'cheque_number' => $validated['cheque_number'] ?? null,
                    'created_by_id' => $user->id,
                    'created_by_type' => User::class,
                ]);

            }
            // When full payment is recorded via any method other than COD itself,
            // the original COD Pending row must also be closed — otherwise the
            // POD SMS reminder job still sees a COD+Pending row and keeps firing.
            // if ($targetStatus === OrderPaymentStatus::Paid) {
            //     $order->payments()
            //         ->where('payment_method', OrderPaymentMethod::COD->value)
            //         ->where('status', OrderPaymentStatus::Pending->value)
            //         ->update(['status' => OrderPaymentStatus::Paid->value]);
            // }

            DB::commit();

            event(new PaymentInitiateEvent($order, $user, $payment));

            return response()->json([
                'success' => true,
                'message' => 'Order payment confirmed!',
            ]);
        } catch (\Exception $e) {

            logger()->error('Payment error for Order ID: ' . $uniqueId . ' - ' . $e->getMessage());
            return response()->json(
                [
                    'success' => false,
                    'message' => 'An error occurred while processing the payment. Please try again.',
                ],
                500,
            );
        }
    }
}
