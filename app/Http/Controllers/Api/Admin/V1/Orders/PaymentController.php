<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Events\Admin\Orders\PaymentInitiateEvent;
use App\Http\Controllers\Api\BaseController;
use DB;
use Illuminate\Support\Facades\Cache;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\PaymentRequest;

// Model
use App\Models\Orders\Order;
use App\Models\Iam\Personnel\User;

// Services
use App\Services\AuthorizeNetService;

class PaymentController extends BaseController
{
    /**
     * Order Payment
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(PaymentRequest $request)
    {
        $validated = $request->validated();

        $user            = auth('api_user')->user();
        $paymentMethod   = $validated['payment_type'];
        $paymentNote     = $validated['payment_note'] ?? null;
        $responsibleUser = User::find($validated['responsible_person']);

        // Idempotency — same pattern as the admin Receive Payment and
        // Refund flows: one client-generated token per distinct payment
        // attempt, reused across retries (e.g. a mobile client retrying
        // after a timeout on a poor connection). The lock closes the
        // concurrent-duplicate race BEFORE any gateway call or financial
        // write; the DB-unique order_payments.idempotency_token column is
        // the storage-level backstop.
        $idempotencyToken = $validated['idempotency_token'] ?? null;
        $lock = $idempotencyToken
            ? Cache::lock("api-payment-idempotency:{$validated['order_unique_id']}:{$idempotencyToken}", 30)
            : null;

        if ($lock && !$lock->get()) {
            return response()->json([
                'success' => false,
                'message' => 'This payment is already being processed. Please retry shortly.',
            ], 409);
        }

        DB::beginTransaction();

        try {
            $order    = Order::where('unique_id', $validated['order_unique_id'])->with('customer')->firstOrFail();
            $customer = $order->customer;

            if ($idempotencyToken) {
                $alreadyProcessed = $order->payments()
                    ->where('idempotency_token', $idempotencyToken)
                    ->exists();

                if ($alreadyProcessed) {
                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Order payment confirmed!',
                    ]);
                }
            }

            // Phase 3A fix: previously always the order's full grand_total,
            // regardless of any payment already recorded on the order via
            // this or another channel (admin manual payment, a prior
            // partial, another mobile submit) — a confirmed double-payment/
            // double-record defect. Charge only what's actually still owed,
            // same as the admin Receive Payment flow.
            $amount = (float) $order->balance_due;

            if ($amount <= 0) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'This order is already fully paid.',
                ], 422);
            }

            if ($paymentMethod === 'CreditCard') {

                // ── existing card flow (unchanged) ────────────────────────
                if (isset($validated['customer_card']) && $validated['customer_card']) {

                    $cardDetail        = $customer->cards()->where('unique_id', $validated['customer_card'])->firstOrFail();
                    $paymentProfileId  = $cardDetail->payment_profile_id;
                    $customerProfileId = $customer->authorize_profile_id;

                    if (!$customerProfileId) {
                        DB::rollBack();
                        return back()->withInput()->with('error', 'Customer profile not found for saved card.');
                    }

                    $authorizeNetService = new AuthorizeNetService();

                    $paymentResult = $authorizeNetService->chargeCustomerProfile($customerProfileId, $paymentProfileId, $amount, [
                        'order_number' => $order->order_number,
                        'customer'     => $customer->toArray(),
                    ]);

                    if (($paymentResult['status'] ?? null) !== 'success') {
                        logger()->error('Profile payment failed for Order ID: ' . $order->unique_id . ' - ' . ($paymentResult['message'] ?? 'Unknown error'));
                        DB::rollBack();
                        return back()
                            ->withInput()
                            ->with('error', $paymentResult['message'] ?? 'Payment failed.');
                    }

                    $payment = $order->payments()->create([
                        'payment_datetime'   => now(),
                        'payment_method'     => OrderPaymentMethod::Card->value,
                        'amount'             => $amount,
                        'transaction_id'     => $paymentResult['transaction_id'] ?? null,
                        'auth_code'          => $paymentResult['auth_code'] ?? null,
                        'customer_profile_id'=> $customerProfileId,
                        'payment_profile_id' => $paymentProfileId,
                        'card_number'        => $paymentResult['card_number'] ?? null,
                        'card_first_name'    => $cardDetail->first_name ?? null,
                        'card_last_name'     => $cardDetail->last_name ?? null,
                        'status'             => $paymentResult['payment_status'] ?? 'Pending',
                        'payment_note'       => $paymentNote,
                        'idempotency_token'  => $idempotencyToken,
                        'created_by_id'      => $responsibleUser->id,
                        'created_by_type'    => User::class,
                    ]);

                } else {

                    $authorizeNetService = new AuthorizeNetService();

                    $cardData = [
                        'card_number' => $validated['card_number'] ?? null,
                        'mm_yy'       => $validated['mm_yy'] ?? null,
                        'card_cvv'    => $validated['cvc'] ?? null,
                    ];

                    $paymentResult = $authorizeNetService->createCardDataTransaction($cardData, $amount, [
                        'order_number' => $order->order_number,
                        'customer'     => $customer->toArray(),
                    ]);

                    if (($paymentResult['status'] ?? null) !== 'success') {
                        // Phase 3A fix: this previously logged the failure
                        // and fell through anyway, creating a payment row
                        // and reporting success for a declined card — a
                        // live-money defect. A failed gateway attempt must
                        // never be recorded as a payment.
                        logger()->error('Payment failed for Order ID: ' . $order->unique_id . ' - ' . ($paymentResult['message'] ?? 'Unknown error'));
                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' => $paymentResult['message'] ?? 'Payment failed.',
                        ], 400);
                    }

                    $payment = $order->payments()->create([
                        'payment_datetime'   => now(),
                        'payment_method'     => OrderPaymentMethod::Card->value,
                        'amount'             => $amount,
                        'transaction_id'     => $paymentResult['transaction_id'] ?? null,
                        'auth_code'          => $paymentResult['auth_code'] ?? null,
                        'customer_profile_id'=> $paymentResult['customer_profile_id'] ?? null,
                        'payment_profile_id' => $paymentResult['payment_profile_id'] ?? null,
                        'card_number'        => $paymentResult['card_number'] ?? null,
                        'card_first_name'    => $validated['firstName'] ?? null,
                        'card_last_name'     => $validated['lastName'] ?? null,
                        'status'             => $paymentResult['payment_status'] ?? 'Pending',
                        'payment_note'       => $paymentNote,
                        'idempotency_token'  => $idempotencyToken,
                        'created_by_id'      => $responsibleUser->id,
                        'created_by_type'    => User::class,
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
                                'first_name'  => $validated['firstName'] ?? null,
                                'last_name'   => $validated['lastName'] ?? null,
                                'card_number' => $paymentResult['card_number'] ?? null,
                                'card_type'   => $paymentResult['card_type'] ?? null,
                            ],
                        );
                    }
                }

            } else {

                // ── Cash / Cheque / Other / new canonical methods ──
                // Store Credit is NO LONGER a tender (it is a pre-tax discount
                // via the discount engine) and is excluded from the payment
                // enums, so it can never reach this branch; its former
                // redemption+payment code has been removed.
                $orderPaymentMethod = match ($paymentMethod) {
                    'Cash'         => OrderPaymentMethod::Cash->value,
                    'Cheque'       => OrderPaymentMethod::Cheque->value,
                    'TapToPay'     => OrderPaymentMethod::TapToPay->value,
                    'GiftCard'     => OrderPaymentMethod::GiftCard->value,
                    'ZelleVenmo'   => OrderPaymentMethod::ZelleVenmo->value,
                    'Other'        => OrderPaymentMethod::Other->value,
                    default        => null,
                };

                $payment = $order->payments()->create([
                    'payment_datetime'   => now(),
                    'payment_method'     => $orderPaymentMethod,
                    'amount'             => $amount,
                    'transaction_id'     => null,
                    'auth_code'          => null,
                    'customer_profile_id'=> null,
                    'payment_profile_id' => null,
                    'card_number'        => null,
                    'card_first_name'    => null,
                    'card_last_name'     => null,
                    'status'             => OrderPaymentStatus::Paid->value,
                    'payment_note'       => $paymentNote,
                    'cheque_number'      => $validated['cheque_number'] ?? null,
                    'idempotency_token'  => $idempotencyToken,
                    'created_by_id'      => $responsibleUser->id,
                    'created_by_type'    => User::class,
                ]);
            }

            // Same cross-method settlement fix as the admin Receive Payment
            // flow — close out a stale COD placeholder row so
            // SendPodPaymentReminderJob stops treating this order as still
            // unpaid. See the matching comment in ReceivePaymentController
            // for why the target status is Superseded, not Paid.
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

            // Backstop for the narrow race the cache lock doesn't cover:
            // the DB-unique idempotency_token column rejected a second
            // insert for a token that just completed on another request.
            if ($idempotencyToken && str_contains(strtolower($e->getMessage()), 'idempotency_token')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order payment confirmed!',
                ]);
            }

            logger()->error('Payment error for Order ID: ' . $validated['order_unique_id'] . ' - ' . $e->getMessage());
            return response()->json(
                [
                    'success' => false,
                    'message' => 'An error occurred while processing the payment. Please try again.',
                ],
                500,
            );
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Payment error for Order ID: ' . $validated['order_unique_id'] . ' - ' . $e->getMessage());
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
