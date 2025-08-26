<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Http\Controllers\Controller;
use DB;

// Enums
use App\Enums\Orders\OrderPaymentStatus;

// Services
use App\Services\AuthorizeNetService;

// Requests
use App\Http\Requests\Admin\OrderManagement\Orders\ChargeCreditCardRequest;

// Events
use App\Events\Admin\Orders\PaymentInitiateEvent;

// Models
use App\Models\Orders\Order;
use App\Models\Iam\Personnel\User;

class ChargeCreditCardController extends Controller
{
    /**
     * Handle updating of orders.
     */
    public function __invoke($uniqueId, ChargeCreditCardRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();

        DB::beginTransaction();

        try {
            $order = Order::where('unique_id', $uniqueId)->with('customer')->firstOrFail();
            $customer = $order->customer;
            $amount = $order->grand_total;

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
                    'card_first_name' => $cardDetail->first_name ?? null, // optional
                    'card_last_name' => $cardDetail->last_name ?? null, // optional
                    'status' => $paymentResult['payment_status'] ?? 'Pending',
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
                $paymentResult = $authorizeNetService->createOpaqueDataTransaction($opaqueDataValue, $amount, ['order_number' => $order->order_number, 'customer' => $customer->toArray()]);
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
                    'status' => $paymentResult['payment_status'] ?? 'Pending',
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
            DB::commit();

            event(new PaymentInitiateEvent($order, $user, $payment));

            return response()->json([
                'success' => true,
                'message' => 'Order payment confirmed!',
            ]);
        } catch (\Exception $e) {

            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
