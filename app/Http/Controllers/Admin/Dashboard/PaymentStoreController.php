<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

// Request
use App\Http\Requests\Admin\Dashboard\PaymentStoreRequest;
use Illuminate\Support\Carbon;
use App\Helpers\CustomHelper;
use App\Models\Iam\Personnel\User ;
use App\Services\AuthorizeNetService;
use App\Models\Orders\OrderExtraCharges;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;

use App\Events\Admin\Orders\OrderExtraChargeEvent;

class PaymentStoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(PaymentStoreRequest $request)
    {
        
        //    dd($request->all());

         $validated = $request->validated();

        //  dd($validated);
   $order = Order::where('unique_id', $validated['order_id'])->firstOrFail();
   $orderProduct = OrderProduct::where('unique_id', $validated['order_product_id'])->firstOrFail();
           

        Log::debug('OrderExtraCharges validated data:', $validated);

        DB::beginTransaction();
       

        try {
            Log::debug('Creating new OrderExtraCharges record...');
            $record = new OrderExtraCharges();
            $record->customer_id = $validated['customer_id'];
            $record->order_id = $order->id ?? null;
             $record->amount = $validated['amount'];

            $record->order_product_id = $orderProduct->id ?? null;
            $record->type = $validated['type'];
            $record->payment_type = $validated['payment_type'];
            $record->notes = $validated['notes'] ?? null;
            
       
        
            $record->payment_number_id = $validated['cheque_number'] ?? null;
        

                $user = User::findOrFail($validated['responsible_person']);
              Log::debug('Responsible person found:', $user->toArray());
                $record->responsible_person_id = $user->id ?? '';
                $record->responsible_person_name = $user->full_name ?? '';

        
    
            $record->save();

// Mark damage as paid if this is a damage charge
if (
    ($validated['type'] ?? null) === 'damage'
    && $orderProduct
) {
    $orderProduct->damage_status = 'completed';
    $orderProduct->save();

    Log::debug('OrderProduct damage status updated to Completed', [
        'order_product_id' => $orderProduct->id,
    ]);
}



// Mark damage as paid if this is a damage charge
if (
    ($validated['type'] ?? null) === 'fuel'
    && $orderProduct
) {
    $orderProduct->fuel_charge_status = 'completed';
    $orderProduct->save();

    Log::debug('OrderProduct fuel status updated to Completed', [
        'order_product_id' => $orderProduct->id,
    ]);
}



            $employee = auth()->user();

            $action = 'collected' ;

    event(new OrderExtraChargeEvent(
    $order,
    $record,
    $employee,
    $action
));



        // 'notes',





            Log::debug('OrderExtraCharges saved:', $record->toArray());

      
                    $customer = Customer::findOrFail($validated['customer_id']);
                    Log::debug('Customer loaded:', $customer->toArray());

            if (strtolower($validated['payment_type']) === 'creditcard') {
                Log::debug('---- Starting CreditCard payment process ----');
                Log::debug('Validated data received for payment:', $validated);

                $amount = $validated['amount'];
                Log::debug('Payment amount:', ['amount' => $amount]);

                // CARD ON FILE BRANCH
                if (!empty($validated['existing_card_id'])) {
                    Log::debug('Using existing card ID:', ['existing_card_id' => $validated['existing_card_id']]);

                    $cardDetail = $customer->cards()->where('unique_id', $validated['existing_card_id'])->first();
                    Log::debug('Card detail fetched:', $cardDetail ? $cardDetail->toArray() : ['card' => 'not found']);

                    if (!$cardDetail) {
                        DB::rollBack();
                        return back()->withInput()->with('error', 'Saved card not found.');
                    }

                    $paymentProfileId  = $cardDetail->payment_profile_id;
                    $customerProfileId = $customer->authorize_profile_id;
                    Log::debug('Authorize.Net profile IDs:', [
                        'customerProfileId' => $customerProfileId,
                        'paymentProfileId'  => $paymentProfileId
                    ]);

                    if (!$customerProfileId) {
                        DB::rollBack();
                        return back()->withInput()->with('error', 'Customer profile not found for saved card.');
                    }

                    $authorizeNetService = new AuthorizeNetService();
                    Log::debug('Sending charge request to Authorize.Net with profile IDs.');

                    $paymentResult = $authorizeNetService->chargeCustomerProfile(
                        $customerProfileId,
                        $paymentProfileId,
                        $amount,
                        ['customer' => $customer->toArray()]
                    );

                    Log::debug('Authorize.Net chargeCustomerProfile result:', $paymentResult);

                    if (($paymentResult['status'] ?? null) !== 'success') {
                        logger()->error('Profile payment failed for Customer Account ID: ' . $record->unique_id, $paymentResult);
                        DB::rollBack();
                        return back()->withInput()->with('error', $paymentResult['message'] ?? 'Payment failed.');
                    }

                    $record->payment_number_id  = $paymentResult['transaction_id'] ?? null;
                    $record->auth_code          = $paymentResult['auth_code'] ?? null;
                    $record->customer_profile_id = $paymentResult['customer_profile_id'] ?? $customerProfileId;
                    $record->payment_profile_id  = $paymentResult['payment_profile_id'] ?? $paymentProfileId;
                    $record->save();

                    Log::debug('OrderExtraCharges updated (card on file):', $record->toArray());
                }
                // NEW CARD BRANCH
                else {
                    $opaqueDataValue      = $validated['opaqueDataValue'] ?? null;
                    $opaqueDataDescriptor = $validated['opaqueDataDescriptor'] ?? null;
                    Log::debug('Processing NEW card entry.');
                    Log::debug('Opaque data received:', compact('opaqueDataValue', 'opaqueDataDescriptor'));

                    if (!$opaqueDataValue || !$opaqueDataDescriptor) {
                        DB::rollBack();
                        return back()->withInput()->with('error', 'Payment data missing or invalid.');
                    }

                    $authorizeNetService = new AuthorizeNetService();
                    Log::debug('Validating opaque data...');
                    $isValidOpaque = $authorizeNetService->validateOpaqueData([
                        'dataValue'      => $opaqueDataValue,
                        'dataDescriptor' => $opaqueDataDescriptor
                    ]);
                    Log::debug('Opaque data validation result:', ['is_valid' => $isValidOpaque]);

                    if (!$isValidOpaque) {
                        DB::rollBack();
                        return back()->withInput()->with('error', 'Payment token invalid.');
                    }

                    Log::debug('Sending createOpaqueDataTransaction request to Authorize.Net...');
                    $paymentResult = $authorizeNetService->createOpaqueDataTransaction(
                        $opaqueDataValue,
                        $amount,
                        ['customer' => $customer->toArray()]
                    );
                    Log::debug('Authorize.Net createOpaqueDataTransaction result:', $paymentResult);

                    if (($paymentResult['status'] ?? null) !== 'success') {
                        logger()->error('Payment failed for Customer Account ID: ' . $record->unique_id, $paymentResult);
                        DB::rollBack();
                        return back()->withInput()->with('error', $paymentResult['message'] ?? 'Payment failed.');
                    }

                    $record->payment_number_id   = $paymentResult['transaction_id'] ?? null;
                    $record->auth_code           = $paymentResult['auth_code'] ?? null;
                    $record->customer_profile_id = $paymentResult['customer_profile_id'] ?? null;
                    $record->payment_profile_id  = $paymentResult['payment_profile_id'] ?? null;
                    $record->save();

                    Log::debug('CustomerAccount updated (new card):', $record->toArray());

                    if (empty($customer->authorize_profile_id) && !empty($paymentResult['customer_profile_id'])) {
                        $customer->authorize_profile_id = $paymentResult['customer_profile_id'];
                        $customer->saveQuietly();
                        Log::debug('Customer authorize_profile_id updated:', ['authorize_profile_id' => $customer->authorize_profile_id]);
                    }

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
                        Log::debug('Customer card record created/updated in DB.');
                    }
                }

                Log::debug('---- CreditCard payment process completed successfully ----');
            }



                flash('Payment recorded successfully.')->success();

            DB::commit();
            
            return redirect()->back();
        } catch (\Throwable $e) {
            DB::rollBack();
             Log::error('Exception in PaymentStore:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
            ]);
            report($e);

         
          
            Log::info($e);

            return redirect()->back()->withInput()->withErrors([
                'error' => 'An error occurred while recording the payment.',
            ]);
        }

    }

}
