<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Support\Facades\Cache;


// Services
use App\Services\AuthorizeNetService;
use App\Services\GiftCards\GiftCardException;
use App\Services\GiftCards\GiftCardService;

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
            } elseif ($paymentMethod === 'GiftCard') {
                // ── Gift Card redemption ──────────────────────────────────
                //
                // A gift card is stored value with its own ledger, so it
                // cannot be settled by writing a payment row here the way
                // cash can. GiftCardService::redeem() is the only thing
                // permitted to move that value: it locks the card, re-reads
                // the balance from the LEDGER under that lock, bounds the
                // amount by both the card and the order, writes the ordinary
                // OrderPayment, and appends the linked ledger entry — all in
                // one transaction.
                //
                // That link is what lets reporting classify this payment as
                // non-cash later. Recording a bare GiftCard payment row here
                // (as this branch used to, with the number typed into
                // payment_note) would produce a payment that looks like cash
                // to every downstream report and draws down no card at all.
                //
                // The service runs its own transaction. This controller's
                // surrounding one is still open, which is safe — nested
                // transactions become savepoints — but the service is the
                // authority on the ordering and the locks.
                try {
                    $giftCardTxn = GiftCardService::redeem(
                        card: trim($validated['gift_card_number']),
                        order: $order,
                        amount: $amount,
                        idempotencyKey: $idempotencyToken ? 'gc-redeem-'.$idempotencyToken : null,

                        // ATTRIBUTION vs AUTHORITY — two different people.
                        //
                        // $user here is the "responsible person" chosen in the
                        // dropdown: an attribution field on the payment record,
                        // saying whose sale this was. The person actually
                        // performing the redemption is whoever is signed in.
                        //
                        // Authorization must therefore be checked against the
                        // authenticated user. Checking the named employee
                        // instead would fail both ways: a clerk could escalate
                        // by naming a manager as responsible, and a legitimate
                        // redemption would be refused whenever the named
                        // employee happened not to hold a permission they never
                        // needed.
                        createdById: $user->id,
                        note: $paymentNote,
                        actor: auth()->user(),
                    );
                } catch (GiftCardException $e) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'code' => $e->failure?->value,
                        'message' => $e->getMessage(),
                    ], $e->failure?->httpStatus() ?? 422);
                }

                // redeem() created it; this controller does not create a
                // second one.
                $payment = $giftCardTxn->orderPayment;

                // The service does not know about responsible_person or the
                // client idempotency token — those belong to this screen, not
                // to the gift card domain. Attached after the fact so the
                // payment row is indistinguishable from any other tender in
                // payment history.
                $payment?->forceFill([
                    'idempotency_token' => $idempotencyToken,
                    'created_by_id' => $user->id,
                    'created_by_type' => User::class,
                ])->saveQuietly();
            } else {
                // Store Credit is NO LONGER a tender (it is a pre-tax discount
                // via the discount engine). It is excluded from PaymentMethod
                // options() so it can never reach this controller; its former
                // redemption+payment branch has been removed.
                $orderPaymentMethod = match ($paymentMethod) {
                    'Cash' => OrderPaymentMethod::Cash->value,
                    'Cheque' => OrderPaymentMethod::Cheque->value,
                    'TapToPay' => OrderPaymentMethod::TapToPay->value,
                    // GiftCard is absent by design — it is intercepted above
                    // and settled through the gift card ledger. A bare
                    // payment row here would look like cash to reporting and
                    // would draw down no card.
                    'ZelleVenmo' => OrderPaymentMethod::ZelleVenmo->value,
                    'Other' => OrderPaymentMethod::Other->value,
                    default => null,
                };

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
