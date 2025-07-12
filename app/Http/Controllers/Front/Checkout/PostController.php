<?php

namespace App\Http\Controllers\Front\Checkout;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;

// Helpers
use App\Helpers\CartHelper;

// Request
use App\Http\Requests\Front\Checkout\PostRequest;

// Models
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAddress;
use App\Models\Locations\State;
use App\Models\ProductManagement\Product;
use App\Services\AuthorizeNetService;

class PostController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(PostRequest $request)
    {
        DB::beginTransaction();
        $validated = $request->validated();

        $cart = json_decode($validated['cart'], true);
        $cartSummary = CartHelper::buildCartSummary(['cart_items' => $cart]);

        try {
            // 1. Find or create customer
            $customer = Customer::firstOrCreate(
                [
                    'email' => $validated['billingEmail'],
                ],
                [
                    'first_name' => $validated['billingFirstName'],
                    'last_name' => $validated['billingLastName'],
                    'company_name' => $validated['billingCompany'] ?? null,
                    'phone' => $validated['billingPhone'],
                    'is_guest' => false,
                    'Status' => 'Active',
                ],
            );

            // Update company and phone if not already set
            $updated = false;
            if (empty($customer->company) && !empty($validated['billingCompany'])) {
                $customer->company_name = $validated['billingCompany'];
                $updated = true;
            }
            if (empty($customer->phone) && !empty($validated['billingPhone'])) {
                $customer->phone = $validated['billingPhone'];
                $updated = true;
            }
            if ($updated) {
                $customer->save();
                $customer->refresh();
            }

            if (!empty($validated['showPassword']) && $validated['showPassword'] === 'Yes' && !empty($validated['password'])) {
                $customer->password = bcrypt($validated['password']);
                $customer->save();
            }

            // 2. Add addresses (Billing & Delivery)
            $billingAddress = CustomerAddress::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'type' => 'Billing',
                    'address' => $validated['billingAddress'],
                ],
                [
                    'first_name' => $validated['billingFirstName'],
                    'last_name' => $validated['billingLastName'],
                    'email' => $validated['billingEmail'],
                    'phone' => $validated['billingPhone'],
                    'address' => $validated['billingAddress'],
                    'city' => $validated['billingCity'],
                    'state_id' => $validated['billingState'],
                    'zip_code' => $validated['billingZip'],
                ],
            );

            // Set primary billing address if not already set
            if (!$billingAddress->is_primary) {
                CustomerAddress::setPrimaryBillingAddress($billingAddress);
            }

            // 3. Add Delivery Address (Check if same as billing)
            if (!empty($validated['sameAsBilling']) && $validated['sameAsBilling'] === 'Yes') {
                $deliveryData = [
                    'first_name' => $validated['billingFirstName'],
                    'last_name' => $validated['billingLastName'],
                    'email' => $validated['billingEmail'],
                    'phone' => $validated['billingPhone'],
                    'address' => $validated['billingAddress'],
                    'city' => $validated['billingCity'],
                    'state_id' => $validated['billingState'],
                    'zip_code' => $validated['billingZip'],
                ];
                $deliveryAddressField = [
                    'customer_id' => $customer->id,
                    'type' => 'Shipping',
                    'address' => $validated['billingAddress'],
                ];
            } else {
                $deliveryData = [
                    'first_name' => $validated['deliveryFirstName'],
                    'last_name' => $validated['deliveryLastName'],
                    'email' => $validated['deliveryEmail'],
                    'phone' => $validated['deliveryPhone'],
                    'address' => $validated['deliveryAddress'],
                    'city' => $validated['deliveryCity'],
                    'state_id' => $validated['deliveryState'],
                    'zip_code' => $validated['deliveryZip'],
                ];
                $deliveryAddressField = [
                    'customer_id' => $customer->id,
                    'type' => 'Shipping',
                    'address' => $validated['deliveryAddress'],
                ];
            }

            $deliveryAddress = CustomerAddress::updateOrCreate($deliveryAddressField, $deliveryData);

            $billingState = State::where('id', $billingAddress->state_id)->first();
            $deliveryState = State::where('id', $deliveryAddress->state_id)->first();

            // Save Order
            $order = $customer->orders()->create([
                'customer_id' => $customer->id,
                'customer_name' => $customer->full_name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'subtotal' => $cartSummary['sub_total'],
                'tax_amount' => $cartSummary['tax_total'],
                'coupon_code' => null,
                'discount_amount' => $cartSummary['discount'],
                'grand_total' => $cartSummary['grand_total'],
                'payment_type' => $validated['payment'],
                'order_note' => $validated['orderNotes'] ?? null,
                'status' => 'Pending',
                'cart_data' => $cart,
                'platform' => 'Web',
            ]);

            // Create order billing address
            $order->addresses()->create([
                'type' => 'Billing',
                'first_name' => $billingAddress->first_name,
                'last_name' => $billingAddress->last_name,
                'email' => $customer->email,
                'phone' => $billingAddress->phone,
                'address' => $billingAddress->address,
                'city' => $billingAddress->city,
                'state' => $billingState->name ?? null,
                'state_id' => $billingAddress->state_id ?? null,
                'zip_code' => $billingAddress->zip_code,
            ]);

            // Create order delivery address
            $order->addresses()->create([
                'type' => 'Shipping',
                'first_name' => $deliveryAddress->first_name,
                'last_name' => $deliveryAddress->last_name,
                'email' => $customer->email,
                'phone' => $deliveryAddress->phone,
                'address' => $deliveryAddress->address,
                'city' => $deliveryAddress->city,
                'state' => $deliveryState->name ?? null,
                'state_id' => $deliveryAddress->state_id ?? null,
                'zip_code' => $deliveryAddress->zip_code,
            ]);

            foreach ($cartSummary['cart_items'] as $item) {
                if ($product = Product::where('unique_id', $item['product_unique_id'])->first()) {
                    $order->products()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $item['product_name'],
                        'price' => $item['product_price'],
                        'quantity' => $item['quantity'],
                        'sub_total' => $item['sub_total'],
                        'tax' => $item['tax'],
                        'total' => $item['total'],
                        'schedule_start_date' => !empty($item['schedule_start_date']) ? Carbon::parse($item['schedule_start_date'])->format(config('app.date.db_date_format')) : null,
                        'schedule_end_date' => !empty($item['schedule_end_date']) ? Carbon::parse($item['schedule_end_date'])->format(config('app.date.db_date_format')) : null,
                        'product_data' => $item,
                        'service_method' => $item['service_method'] ?? null,
                        'service_option' => $item['service_option'] ?? null,
                        'store_id' => $item['store_id'] ?? null,
                        'distance_type' => $item['distance_type'] ?? null,
                        'distance_range' => $item['distance_range'] ?? null,
                    ]);
                }
            }

            // If payment type is card, process payment using AuthorizeNetService
            if (strtolower($validated['payment']) === 'card') {
                $opaqueDataValue = $validated['opaqueDataValue'] ?? null;
                $opaqueDataDescriptor = $validated['opaqueDataDescriptor'] ?? null;
                $amount = $order->grand_total;
                if (!$opaqueDataValue || !$opaqueDataDescriptor) {
                    DB::rollback();
                    return redirect()->back()->withInput()->with('error', 'Payment data missing or invalid.');
                }
                $authorizeNetService = new AuthorizeNetService();
                if (!$authorizeNetService->validateOpaqueData(['dataValue' => $opaqueDataValue, 'dataDescriptor' => $opaqueDataDescriptor])) {
                    DB::rollback();
                    return redirect()->back()->withInput()->with('error', 'Payment token invalid.');
                }
                $paymentResult = $authorizeNetService->createOpaqueDataTransaction(
                    $opaqueDataValue,
                    $amount,
                    ['order_number' => $order->order_number, 'customer'=>$customer->toArray()]
                );
                if ($paymentResult['status'] !== 'success') {
                    DB::rollback();
                    return redirect()->back()->withInput()->with('error', $paymentResult['message'] ?? 'Payment failed.');
                }
                $order->payments()->create([
                    'payment_datetime' => now(),
                    'payment_method' => $validated['payment'],
                    'amount' => $amount,
                    'transaction_id' => $paymentResult['transaction_id'] ?? null,
                    'auth_code' => $paymentResult['auth_code'] ?? null,
                    'customer_profile_id' => $paymentResult['customer_profile_id'] ?? null,
                    'payment_profile_id' => $paymentResult['payment_profile_id'] ?? null,
                    'card_number' => $paymentResult['card_number'] ?? null,
                    'card_first_name' => $validated['firstName'] ?? null,
                    'card_last_name' => $validated['lastName'] ?? null,
                    'status' => $paymentResult['payment_status'] ?? 'Pending',
                    'created_by_id' => $customer->id,
                    'created_by_type' => Customer::class,
                ]);
            }else{
                // If payment type is not card, just create a pending payment record
                $order->payments()->create([
                    'payment_datetime' => now(),
                    'payment_method' => $validated['payment'],
                    'amount' => $order->grand_total,
                    'status' => 'Pending',
                    'created_by_id' => $customer->id,
                    'created_by_type' => Customer::class,
                ]);
            }

            DB::commit();

            // Generate a signed URL for the thank you page with order unique id
            $signedUrl = \URL::temporarySignedRoute('front.checkout.thank-you',now()->addMinutes(5),['order' => $order->unique_id]);

            // Success: redirect to signed thank you page with order id
            return redirect($signedUrl);
        } catch (\Exception $e) {
            DB::rollback();
            //dd($e);
            // Log the error if needed: logger($e);
            return redirect()->back()->withInput()->with('error', 'Something went wrong. Please try again.');
        }
    }
}
