<?php

namespace App\Http\Controllers\Front\Customer\Dashboard\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\Customer;
use App\Helpers\CustomHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Customers\CustomerAccount;
use App\Models\Customers\Invoice;
use App\Helpers\ConfigurationHelper;
use App\Services\AuthorizeNetService; //  Make sure you import this service
use App\Events\Front\Checkout\OrderPlacedEvent;
use App\Events\Admin\Invoices\InvoicePaidEvent;

class PaymentStoreController extends Controller
{
    public function __invoke(Request $request)
    {

        //  Validation
        $validated = $request->validate([
            'customer_id'        => 'required|exists:customers,id',
            'amount'             => 'required|string',
            'payment_type'       => 'nullable|string',
            'opaqueDataValue'    => 'nullable|string',
            'opaqueDataDescriptor' => 'nullable|string',
            'existing_card_id'   => 'nullable|string',
            'payment_number_id'  => 'nullable|string',
            'sales_tax'          => 'nullable|numeric',
            'firstName'          => 'nullable|string',
            'lastName'           => 'nullable|string',
            'balance'            => 'nullable|numeric',
            'invoice_id' => 'required',
        ]);

    

        //  Normalize the amount (remove $ and commas)
        $amount = preg_replace('/[^\d.]/', '', $validated['amount']);

        DB::beginTransaction();

        try {
            Log::debug('Creating new invoice paid record...');

            $customer = Customer::findOrFail($validated['customer_id']);

            // CARD ON FILE BRANCH
            if (!empty($validated['existing_card_id'])) {

                $cardDetail = $customer->cards()->where('unique_id', $validated['existing_card_id'])->first();

                if (!$cardDetail) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Saved card not found.');
                }

                $paymentProfileId  = $cardDetail->payment_profile_id;
                $customerProfileId = $customer->authorize_profile_id;

                if (!$customerProfileId) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Customer profile not found for saved card.');
                }

                $authorizeNetService = new AuthorizeNetService();

                $paymentResult = $authorizeNetService->chargeCustomerProfile(
                    $customerProfileId,
                    $paymentProfileId,
                    $amount,
                    ['customer' => $customer->toArray()]
                );

                if (($paymentResult['status'] ?? null) !== 'success') {
                    // logger()->error('Profile payment failed for Customer Account ID: ' . $record->unique_id, $paymentResult);
                    DB::rollBack();
                    return back()->withInput()->with('error', $paymentResult['message'] ?? 'Payment failed.');
                }

                $invoice = Invoice::where('invoice_number', $validated['invoice_id'])->firstOrFail();

                $invoice->invoice_status = 'paid';

                $invoice->payment_number_id  = $paymentResult['transaction_id'] ?? null;
                $invoice->auth_code          = $paymentResult['auth_code'] ?? null;
                $invoice->customer_profile_id = $paymentResult['customer_profile_id'] ?? $customerProfileId;
                $invoice->payment_profile_id  = $paymentResult['payment_profile_id'] ?? $paymentProfileId;

                $invoice->payment_method =  'card';

                $invoice->save();

            }
            // NEW CARD BRANCH
            else {
                $opaqueDataValue      = $validated['opaqueDataValue'] ?? null;
                $opaqueDataDescriptor = $validated['opaqueDataDescriptor'] ?? null;

                if (!$opaqueDataValue || !$opaqueDataDescriptor) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Payment data missing or invalid.');
                }

                $authorizeNetService = new AuthorizeNetService();
                $isValidOpaque = $authorizeNetService->validateOpaqueData([
                    'dataValue'      => $opaqueDataValue,
                    'dataDescriptor' => $opaqueDataDescriptor
                ]);

                if (!$isValidOpaque) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Payment token invalid.');
                }
                $paymentResult = $authorizeNetService->createOpaqueDataTransaction(
                    $opaqueDataValue,
                    $amount,
                    ['customer' => $customer->toArray()]
                );

                if (($paymentResult['status'] ?? null) !== 'success') {
                    // logger()->error('Payment failed for Customer Account ID: ' . $record->unique_id, $paymentResult);
                    DB::rollBack();
                    return back()->withInput()->with('error', $paymentResult['message'] ?? 'Payment failed.');
                }

                $invoice = Invoice::where('invoice_number', $validated['invoice_id'])->first();

                $invoice->invoice_status = 'paid';


                $invoice->payment_number_id  = $paymentResult['transaction_id'] ?? null;
                $invoice->auth_code          = $paymentResult['auth_code'] ?? null;
                $invoice->customer_profile_id = $paymentResult['customer_profile_id'] ?? null;
                $invoice->payment_profile_id  = $paymentResult['payment_profile_id'] ?? null;
                $invoice->payment_method =  'card';
                $invoice->save();


                // Save customer Authorize.Net profile ID if missing
                if (empty($customer->authorize_profile_id) && !empty($paymentResult['customer_profile_id'])) {
                    $customer->authorize_profile_id = $paymentResult['customer_profile_id'];
                    $customer->saveQuietly();
                }

                // Save card details in DB
                if (!empty($paymentResult['payment_profile_id'])) {
                    $customer->cards()->updateOrCreate(
                        ['payment_profile_id' => $paymentResult['payment_profile_id']],
                        [
                            'first_name'  => $validated['firstName'] ?? null,
                            'last_name'   => $validated['lastName'] ?? null,
                            'card_number' => $paymentResult['card_number'] ?? null,
                            'card_type'   => $paymentResult['card_type'] ?? null,
                        ]
                    );
                }


            }

            // Loop through all invoice items of type 'order'
            $invoice->items()->where('type', 'order')->get()->each(function ($invoiceItem) use ($amount, $validated, $customer, $paymentResult) {
                $orderProduct = $invoiceItem->orderProduct;

                if ($orderProduct && $orderProduct->order) {
                    $order = $orderProduct->order;

                    // Update the invoice_id on the order
                    $order->invoice_id = $invoiceItem->invoice_id;
                    $order->save();

                    // Record payment against the order

                    $payment = $order->payments()->create([
                        'payment_datetime'     => now(),
                        'payment_method'       => 'Card',
                        'status'               => 'Invoice Card',
                        'amount'               => $amount,
                        'transaction_id'       => $paymentResult['transaction_id'] ?? null,
                        'auth_code'            => $paymentResult['auth_code'] ?? null,
                        'customer_profile_id'  => $paymentResult['customer_profile_id'] ?? null,
                        'payment_profile_id'   => $paymentResult['payment_profile_id'] ?? null ,
                        'card_number'          => $paymentResult['card_number'] ?? null,
                        'card_first_name'      => $paymentResult['first_name'] ?? $validated['firstName'] ?? null, // <-- fixed
                        'card_last_name'       => $paymentResult['last_name'] ?? $validated['lastName'] ?? null,   // <-- fixed
                        'created_by_id'        => $customer->id,
                        'created_by_type'      => Customer::class,
                    ]);

                    Log::info('Recorded Payment for Order', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'payment_id' => $payment->id,
                        'amount' => $payment->amount,
                        'transaction_id' => $payment->transaction_id,
                        'status' => $payment->status,
                    ]);



                    // $orderActionType = 'invoice_card_payment';
                    $employee = null;

                    // Fire OrderPlaced event
                    //event(new OrderPlacedEvent($order, $customer, $payment, $orderActionType, $employee));

                    event(new InvoicePaidEvent($order, $customer, $payment, $employee));



                }
            });

            Log::info('Linked Orders to Invoice', [
                'invoice_id' => $invoice->id,
                'orders' => $invoice->items()->where('type', 'order')->get()->map(function ($item) {
                    return $item->orderProduct?->order?->order_number;
                })
            ]);

            DB::commit();
            flash('Payment recorded successfully.')->success();
            // session()->flash('active_tab', 'invoices');
session(['active_tab' => 'invoices']);


            return redirect()->back();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Exception in PaymentStore:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            report($e);

            flash('Something went wrong while recording the payment.')->error();
            // session()->flash('active_tab', 'invoices');
session(['active_tab' => 'invoices']);

            return redirect()->back()->withInput()->withErrors([
                'error' => 'An error occurred while recording the payment.',
            ]);
        }
    }
}
