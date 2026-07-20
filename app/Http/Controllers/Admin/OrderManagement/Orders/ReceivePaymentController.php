<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Support\Facades\Cache;


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

        // Idempotency — same pattern as RefundPaymentController: one
        // client-generated token per modal-open, reused across retries of
        // that same submission. The lock closes the concurrent-duplicate
        // race BEFORE any gateway call or financial write; the DB-unique
        // order_payments.idempotency_token column is the storage-level
        // backstop. Applies to every payment method, not just Store
        // Credit (which previously was the only method this token
        // actually protected).
        $idempotencyToken = $validated['idempotency_token'] ?? null;
        $lock = $idempotencyToken
            ? Cache::lock("receive-payment-idempotency:{$uniqueId}:{$idempotencyToken}", 30)
            : null;

        if ($lock && !$lock->get()) {
            return response()->json([
                'success' => false,
                'message' => 'This payment is already being processed. Please wait a moment and refresh the order.',
            ], 409);
        }

        DB::beginTransaction();

        try {
            $order = Order::where('unique_id', $uniqueId)->with('customer')->firstOrFail();
            $customer = $order->customer;
            $customer->load('billingAddress', 'shippingAddress');

            if ($idempotencyToken) {
                $alreadyProcessed = $order->payments()
                    ->where('idempotency_token', $idempotencyToken)
                    ->exists();

                if ($alreadyProcessed) {
                    // Same submission, already completed — return the same
                    // outcome rather than touching the gateway or ledger
                    // again. A prior failed attempt never reaches this
                    // point (a payment row is only ever created on
                    // success below), so a retry after a genuine failure
                    // proceeds normally.
                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Order payment confirmed!',
                    ]);
                }
            }

            $isPartial = !empty($validated['partial_payment']);
            if ($isPartial) {
                $amount = (float) ($validated['payment_amount'] ?? 0);
                if ($amount <= 0) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Payment amount must be greater than zero.'], 422);
                }
                // Canonical settled-payments total (Order::getTotalPaidAttribute(),
                // via OrderPayment::scopeSettled()) — was a manually
                // duplicated [PartialPayment, Paid]-only query here, which
                // (like the is_paid bug fixed elsewhere this phase) silently
                // undercounted an order with a legacy Invoice*-status payment.
                $completesTotal = ($order->total_paid + $amount) >= ((float) $order->grand_total - 0.005);
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

                    $authorizeNetService = app(AuthorizeNetService::class);

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
                        'idempotency_token' => $idempotencyToken,
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
                    $authorizeNetService = app(AuthorizeNetService::class);
                    if (!$authorizeNetService->validateOpaqueData(['dataValue' => $opaqueDataValue, 'dataDescriptor' => $opaqueDataDescriptor])) {
                        DB::rollback();
                        return redirect()->back()->withInput()->with('error', 'Payment token invalid.');
                    }

                    $cardData = [
                        'card_number' => $validated['card_number'] ?? null,
                        'mm_yy' => $validated['mm_yy'] ?? null,
                    ];

                    $paymentResult = $authorizeNetService->createOpaqueDataTransaction($opaqueDataValue, $amount, ['order_number' => $order->order_number, 'customer' => $customer->toArray(), 'card_data' => $cardData]);
                    if (($paymentResult['status'] ?? null) !== 'success') {
                        // Phase 3A fix: this previously logged the failure
                        // and fell through anyway, creating a payment row
                        // and reporting success for a declined card — a
                        // live-money defect, not a cosmetic one. A failed
                        // gateway attempt must never be recorded as a
                        // payment.
                        logger()->error('Payment failed for Order ID: ' . $order->unique_id . ' - ' . ($paymentResult['message'] ?? 'Unknown error'));
                        DB::rollback();
                        return redirect()->back()->withInput()->with('error', $paymentResult['message'] ?? 'Payment failed.');
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
                        'idempotency_token' => $idempotencyToken,
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
                $storeCreditRedemption = null;
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
                        $storeCreditRedemption = \App\Services\CustomerCreditService::redeem(
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
                    'idempotency_token' => $idempotencyToken,
                    'created_by_id' => $user->id,
                    'created_by_type' => User::class,
                ]);

                if ($storeCreditRedemption) {
                    $storeCreditRedemption->update(['order_payment_id' => $payment->id]);
                }

            }
            // When this payment fully settles the order, close out any stale
            // COD "Pay on Delivery" placeholder row — otherwise
            // SendPodPaymentReminderJob still sees a COD+Pending row and
            // keeps sending payment-link/reminder SMS after the customer has
            // already paid. Target status is Superseded, not Paid: the
            // placeholder's amount is a checkout-time stand-in for the full
            // grand_total, and scopeSettled() sums by status — marking it
            // Paid would double-count that amount against the order.
            // is_paid re-reads total_paid fresh, so a partial payment
            // (order not yet fully paid) correctly leaves this a no-op.
            if ($order->is_paid) {
                $order->payments()
                    ->where('payment_method', OrderPaymentMethod::COD->value)
                    ->where('status', OrderPaymentStatus::Pending->value)
                    ->update(['status' => OrderPaymentStatus::Superseded->value]);
            }

            DB::commit();

            event(new PaymentInitiateEvent($order, $user, $payment));

            return response()->json([
                'success' => true,
                'message' => 'Order payment confirmed!',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            // Backstop for the narrow race the cache lock doesn't cover
            // (e.g. lock driver unavailable): the DB-unique
            // idempotency_token column rejected a second insert for a
            // token that just completed on another request. Report the
            // same success outcome rather than a scary 500.
            if ($idempotencyToken && str_contains(strtolower($e->getMessage()), 'idempotency_token')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order payment confirmed!',
                ]);
            }

            logger()->error('Payment error for Order ID: ' . $uniqueId . ' - ' . $e->getMessage());
            return response()->json(
                [
                    'success' => false,
                    'message' => 'An error occurred while processing the payment. Please try again.',
                ],
                500,
            );
        } catch (\Exception $e) {
            DB::rollBack();

            logger()->error('Payment error for Order ID: ' . $uniqueId . ' - ' . $e->getMessage());
            return response()->json(
                [
                    'success' => false,
                    'message' => 'An error occurred while processing the payment. Please try again.',
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
