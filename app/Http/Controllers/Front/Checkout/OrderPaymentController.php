<?php

namespace App\Http\Controllers\Front\Checkout;

use App\Http\Controllers\Controller;
use App\Enums\Orders\PodPaymentLinkStatus;
use App\Helpers\ConfigurationHelper;
use App\Helpers\SignedUrlHelper;
use App\Jobs\CreateReceiptJob;
use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use App\Services\AuthorizeNetService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderPaymentController extends Controller
{
    public function show(Request $request, $unique_id)
    {
        try {
            $uniqueId = decrypt($unique_id);
        } catch (DecryptException $e) {
            abort(403, 'Invalid or tampered order parameter.');
        }

        $order = Order::with(
            'shippingAddress',
            'billingAddress',
            'products.product',
            'lastPayment',
            'customer'
        )->where('unique_id', $uniqueId)->firstOrFail();

        if ($order->balance_due <= 0) {
            abort(403, 'This order is already fully paid.');
        }

        $podLink   = $order->podPaymentLink;
        $isExpired = $podLink && $podLink->pod_status === PodPaymentLinkStatus::Expired;

        $paymentSetting = ConfigurationHelper::getSettings('Payment Settings');

        return view('front.checkout.order-payment-form', [
            'title'          => 'Complete Payment',
            'order'          => $order,
            'customer'       => $order->customer,
            'paymentSetting' => $paymentSetting,
            'encrypted_id'   => $unique_id,
            'isExpired'      => $isExpired,
        ]);
    }

    public function store(Request $request, $unique_id)
    {
        try {
            $uniqueId = decrypt($unique_id);
        } catch (DecryptException $e) {
            return response()->json(['success' => false, 'message' => 'Invalid order parameter.']);
        }

        $order    = Order::with('customer')->where('unique_id', $uniqueId)->firstOrFail();
        $customer = $order->customer;

        if ($order->balance_due <= 0) {
            return response()->json(['success' => false, 'message' => 'This order is already fully paid.']);
        }

        $podLink = $order->podPaymentLink;
        if ($podLink && $podLink->pod_status === PodPaymentLinkStatus::Expired) {
            return response()->json(['success' => false, 'message' => 'This payment link has expired. Please contact us for assistance.']);
        }

        $opaqueDataValue      = $request->input('opaqueDataValue');
        $opaqueDataDescriptor = $request->input('opaqueDataDescriptor');
        $firstName            = $request->input('firstName');
        $lastName             = $request->input('lastName');
        $idempotencyToken     = $request->input('idempotency_token');

        if (!$opaqueDataValue || !$opaqueDataDescriptor) {
            return response()->json(['success' => false, 'message' => 'Payment data missing or invalid.']);
        }

        // Idempotency — same lock pattern as the admin Receive Payment and
        // Refund flows, extended here since this is a customer-facing
        // gateway charge with no prior duplicate-submit protection: a
        // double-click or a reload after gateway latency could otherwise
        // charge the customer's card twice for the same balance.
        $lock = $idempotencyToken
            ? Cache::lock("pod-payment-idempotency:{$uniqueId}:{$idempotencyToken}", 30)
            : null;

        if ($lock && !$lock->get()) {
            return response()->json(['success' => false, 'message' => 'This payment is already being processed. Please wait a moment and refresh.']);
        }

        try {
            DB::beginTransaction();

            if ($idempotencyToken) {
                $alreadyProcessed = $order->payments()
                    ->where('idempotency_token', $idempotencyToken)
                    ->exists();

                if ($alreadyProcessed) {
                    DB::commit();

                    $redirectUrl = SignedUrlHelper::make('front.checkout.thank-you', ['order' => $order->unique_id], 5);

                    return response()->json(['success' => true, 'redirect_url' => $redirectUrl]);
                }
            }

            // Re-check the balance inside the lock/transaction, immediately
            // before charging — closes the race window between the initial
            // balance_due read above (before the lock) and an admin manual
            // payment or a concurrent checkout attempt landing in between.
            $amount = $order->fresh()->balance_due;

            if ($amount <= 0) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'This order is already fully paid.']);
            }

            $authorizeNetService = new AuthorizeNetService();

            if (!$authorizeNetService->validateOpaqueData(['dataValue' => $opaqueDataValue, 'dataDescriptor' => $opaqueDataDescriptor])) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Payment token invalid.']);
            }

            $paymentResult = $authorizeNetService->createOpaqueDataTransaction($opaqueDataValue, $amount, [
                'order_number' => $order->order_number,
                'customer'     => $customer->toArray(),
            ]);

            if (($paymentResult['status'] ?? null) !== 'success') {
                Log::error('Order payment failed for Order: ' . $order->unique_id . ' - ' . ($paymentResult['message'] ?? 'Unknown'));
                DB::rollBack();
                return response()->json(['success' => false, 'message' => $paymentResult['message'] ?? 'Payment failed.']);
            }

            $order->payments()->create([
                'payment_datetime'    => now(),
                'payment_method'      => 'Card',
                'amount'              => $amount,
                'transaction_id'      => $paymentResult['transaction_id'] ?? null,
                'auth_code'           => $paymentResult['auth_code'] ?? null,
                'customer_profile_id' => $paymentResult['customer_profile_id'] ?? null,
                'payment_profile_id'  => $paymentResult['payment_profile_id'] ?? null,
                'card_number'         => $paymentResult['card_number'] ?? null,
                'card_first_name'     => $firstName,
                'card_last_name'      => $lastName,
                'status'              => $paymentResult['payment_status'] ?? 'Paid',
                'payment_response'    => $paymentResult['payment_response'] ?? null,
                'idempotency_token'   => $idempotencyToken,
                'created_by_id'       => $customer->id,
                'created_by_type'     => Customer::class,
            ]);

            if (empty($customer->authorize_profile_id) && !empty($paymentResult['customer_profile_id'])) {
                $customer->authorize_profile_id = $paymentResult['customer_profile_id'];
                $customer->saveQuietly();
            }

            if (!empty($paymentResult['payment_profile_id'])) {
                $customer->cards()->updateOrCreate(
                    ['payment_profile_id' => $paymentResult['payment_profile_id']],
                    [
                        'first_name'  => $firstName,
                        'last_name'   => $lastName,
                        'card_number' => $paymentResult['card_number'] ?? null,
                        'card_type'   => $paymentResult['card_type'] ?? null,
                    ],
                );
            }

            CreateReceiptJob::dispatch($order->id, 'card');

            DB::commit();

            $redirectUrl = SignedUrlHelper::make('front.checkout.thank-you', ['order' => $order->unique_id], 5);

            return response()->json([
                'success'      => true,
                'redirect_url' => $redirectUrl,
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            if ($idempotencyToken && str_contains(strtolower($e->getMessage()), 'idempotency_token')) {
                $redirectUrl = SignedUrlHelper::make('front.checkout.thank-you', ['order' => $order->unique_id], 5);

                return response()->json(['success' => true, 'redirect_url' => $redirectUrl]);
            }

            Log::error('Order payment error', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred while processing payment.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order payment error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => 'An error occurred while processing payment.']);
        } finally {
            if ($lock) {
                $lock->release();
            }
        }
    }
}
