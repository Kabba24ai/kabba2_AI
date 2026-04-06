<?php

namespace App\Http\Controllers\Front\Checkout;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;

// Enums
use App\Enums\Orders\OrderMediaType;
use App\Enums\Orders\OrderTermsStatus;

// Services
use App\Services\AuthorizeNetService;

// Events
use App\Events\Front\Checkout\OrderPlacedEvent;
use App\Events\Front\Checkout\OrderPlacedEmailEvent;


// Helpers
use App\Helpers\CartHelper;
use App\Helpers\CustomHelper;
use App\Helpers\SignedUrlHelper;
use App\Helpers\TermsContentHelper;
use App\Helpers\ModelHelper;

// Request
use App\Http\Requests\Front\Checkout\PostRequest;
use App\Jobs\CreateReceiptJob;
// Models
use App\Models\Customers\Customer;
use App\Models\Customers\Receipt;

use App\Models\Customers\CustomerAddress;
use App\Models\Customers\CustomerAccount;
use App\Models\ProductManagement\Product;
use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;
use App\Models\Locations\State;
use App\Models\Orders\Order;
use App\Models\Stores\Store;
use App\Models\Orders\OrderProduct;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(PostRequest $request)
    {
        $checkoutStart = microtime(true);
        Log::info('Checkout process started at ' . now());
        $validated = $request->validated();
        // return redirect()->back()->withInput()->with('error', 'Debug stop before processing.');
        $cart = json_decode($validated['cart'], true);
        $cartSummary = CartHelper::buildCartSummary(['cart_items' => $cart]);

        $employeeCode = $validated['employee_code'] ?? null;

        Log::info('cart summary generated at ' . now());

        if ($employeeCode) {
            // Validate employee code if provided
            $employee = User::where('employee_code', $employeeCode)->active()->first();
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid employee code.',
                ]);
            }
        } else {
            $employee = null;
        }


        try {
            Log::info('Starting database transaction at ' . now());
            DB::beginTransaction();
            if (auth()->guard('customer')->check()) {
                $customer = auth()->guard('customer')->user();
            } else {
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
                        'is_guest' => true,
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
                }

                if (!empty($validated['showPassword']) && $validated['showPassword'] === 'Yes' && !empty($validated['password'])) {
                    $customer->password = bcrypt($validated['password']);
                    $customer->save();
                }
            }

            // 2. Add addresses (Billing & Delivery)
            // Customer has only one address, update or create as 'Billing'
            $billingAddress = CustomerAddress::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'type' => 'Billing',
                    //'address' => $validated['billingAddress'],
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
                CustomerAddress::setPrimaryAddress($billingAddress);
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
                    //'address' => $validated['billingAddress'],
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
                    //'address' => $validated['deliveryAddress'],
                ];
            }

            $deliveryAddress = CustomerAddress::updateOrCreate($deliveryAddressField, $deliveryData);
            if (!$deliveryAddress->is_primary) {
                CustomerAddress::setPrimaryAddress($deliveryAddress);
            }

            $states = State::whereIn('id', [$billingAddress->state_id, $deliveryAddress->state_id])->pluck('name', 'id');

            $billingStateName = $states[$billingAddress->state_id] ?? null;
            $deliveryStateName = $states[$deliveryAddress->state_id] ?? null;


            // Save Order
            $order = $customer->orders()->create([
                'customer_id' => $customer->id,
                'reference_order_number' => session('order.type') === 'existing_order'
                    ? session('order.number')
                    : null,
                'customer_name' => $customer->full_name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'company_name' => $customer->company_name,
                'company_website' => $customer->company_website,
                'subtotal' => $cartSummary['sub_total'],
                'tax_amount' => $cartSummary['tax_total'],
                'coupon_code' => null,
                'discount_amount' => $cartSummary['discount'],
                'grand_total' => $cartSummary['grand_total'],
                'po_id' => $validated['po_id'] ?? null,
                'cart_data' => $cart,
                'is_tax_exempt' => $cartSummary['tax_exempt'] ? 'Yes' : 'No',
                'platform' => 'Web',
                'auto_inject' => $validated['auto_inject'] ?? false,
            ]);

            if (!empty($validated['orderNotes'])) {
                // Create order note if provided
                $order->notes()->create([
                    'note' => $validated['orderNotes'],
                    'created_by_type' => Customer::class,
                    'created_by_id' => $customer->id,
                ]);
            }

            $now = now();

            $order->addresses()->insert([
                [
                    'order_id' => $order->id,
                    'type' => 'Billing',
                    'first_name' => $billingAddress->first_name,
                    'last_name' => $billingAddress->last_name,
                    'email' => $customer->email,
                    'phone' => $billingAddress->phone,
                    'address' => $billingAddress->address,
                    'city' => $billingAddress->city,
                    'state' => $billingStateName,
                    'state_id' => $billingAddress->state_id,
                    'zip_code' => $billingAddress->zip_code,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'order_id' => $order->id,
                    'type' => 'Shipping',
                    'first_name' => $deliveryAddress->first_name,
                    'last_name' => $deliveryAddress->last_name,
                    'email' => $customer->email,
                    'phone' => $deliveryAddress->phone,
                    'address' => $deliveryAddress->address,
                    'city' => $deliveryAddress->city,
                    'state' => $deliveryStateName,
                    'state_id' => $deliveryAddress->state_id,
                    'zip_code' => $deliveryAddress->zip_code,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            $primaryStoreId = Store::primary()->value('id') ?? Store::orderBy('id', 'asc')->first()?->id ?? null;

            $dateFormat = config('app.date.db_date_format');
            $timeFormat = config('app.date.db_time_format');

            $orderProductRows = [];

            foreach ($cartSummary['cart_items'] as $item) {

                if ($item) {
                    $orderProductRows[] = [
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'price' => $item['product_price'],
                        'quantity' => $item['quantity'],
                        'hour_tracking' => $item['hour_tracking'] ?? 'No',
                        'hour_rate' => $item['hour_rate'] ?? 0,
                        'allocated_hours' => $item['allocated_hours'] ?? 0.0,
                        'sub_total' => $item['sub_total'],
                        'tax' => $item['tax'],
                        'total' => $item['total'],
                        'product_data' => json_encode($item),
                        'unique_id' => ModelHelper::generateUniqueID(new OrderProduct(), 'ORD-SCH'),
                        'service_method' => $item['service_method'] ?? null,
                        'service_option' => $item['service_option'] ?? null,
                        'distance_type' => $item['distance_type'] ?? null,
                        'distance_range' => $item['distance_range'] ?? null,

                        'delivery_transport_mode' => $item['delivery_transport_mode'] ?? null,
                        'delivery_store_id' => $item['delivery_store_id'] ?? $primaryStoreId,
                        'delivery_date' => !empty($item['delivery_date']) ? Carbon::parse($item['delivery_date'])->format($dateFormat) : null,
                        'delivery_time' => !empty($item['delivery_time']) ? Carbon::parse($item['delivery_time'])->format($timeFormat) : null,

                        'pickup_transport_mode' => $item['pickup_transport_mode'] ?? null,
                        'pickup_store_id' => $item['pickup_store_id'] ?? $primaryStoreId,
                        'pickup_date' => !empty($item['pickup_date']) ? Carbon::parse($item['pickup_date'])->format($dateFormat) : null,
                        'pickup_time' => !empty($item['pickup_time']) ? Carbon::parse($item['pickup_time'])->format($timeFormat) : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (!empty($orderProductRows)) {
                $order->products()->insert($orderProductRows);
            }

            if($validated['auto_inject'] ?? false){
                $licenseMediaRows = [];

                if (!empty($customer->license_front_media_id)) {
                    $licenseMediaRows[] = [
                        'type' => OrderMediaType::LICENSE->value,
                        'side' => 'front',
                        'media_id' => $customer->license_front_media_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($customer->license_back_media_id)) {
                    $licenseMediaRows[] = [
                        'type' => OrderMediaType::LICENSE->value,
                        'side' => 'back',
                        'media_id' => $customer->license_back_media_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($licenseMediaRows)) {
                    $order->media()->createMany($licenseMediaRows);
                }

            }


            // Eager load products and their terms for terms generation
            $order->load(['products.product.terms']);

            $termsContentData = TermsContentHelper::generateTermsContent($order);

            $order->terms_collection = $termsContentData['merged_terms'] ?? null;
            $order->pending_terms_content = $termsContentData['terms_content'] ?? null;
            $order->terms_status = OrderTermsStatus::Pending;
            $order->saveQuietly(); // saveQuietly() saves the model to the database without firing any Eloquent events (like "saved", "updated", etc.)

            // If payment type is card, process payment using AuthorizeNetService
            if (strtolower($validated['payment']) === 'card') {
                Log::info('Processing card payment at ' . now());
                $amount = $order->grand_total;
                $paymentResult = null;

                if (session()->has('impersonated_by_admin') && !empty($validated['customer_card'])) {
                    $cardDetail = $customer->cards()->where('unique_id', $validated['customer_card'])->first();

                    $paymentProfileId = $cardDetail->payment_profile_id;
                    $customerProfileId = $customer->authorize_profile_id;

                    if (!$customerProfileId) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Customer profile not found for saved card.',
                        ]);
                    }

                    $authorizeNetService = new AuthorizeNetService();

                    // Your service should wrap Authorize.Net CIM createTransactionRequest
                    // e.g. createProfileTransaction / chargeCustomerProfile

                    $paymentResult = $authorizeNetService->chargeCustomerProfile($customerProfileId, $paymentProfileId, $amount, [
                        'order_number' => $order->order_number,
                        'customer' => $customer->toArray(),
                    ]);

                    if (($paymentResult['status'] ?? null) !== 'success') {
                        logger()->error('Profile payment failed for Order ID: ' . $order->unique_id . ' - ' . ($paymentResult['message'] ?? 'Unknown error'));
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => $paymentResult['message'] ?? 'Payment failed.',
                        ]);
                    }
                } else {
                    $opaqueDataValue = $validated['opaqueDataValue'] ?? null;
                    $opaqueDataDescriptor = $validated['opaqueDataDescriptor'] ?? null;

                    if (!$opaqueDataValue || !$opaqueDataDescriptor) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Payment data missing or invalid.',
                        ]);
                    }
                    $authorizeNetService = new AuthorizeNetService();
                    if (!$authorizeNetService->validateOpaqueData(['dataValue' => $opaqueDataValue, 'dataDescriptor' => $opaqueDataDescriptor])) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Payment token invalid.',
                        ]);
                    }
                    Log::info('Opaque data validated at ' . now());
                    $paymentResult = $authorizeNetService->createOpaqueDataTransaction($opaqueDataValue, $amount, ['order_number' => $order->order_number, 'customer' => $customer->toArray()]);
                    if ($paymentResult['status'] !== 'success') {
                        logger()->error('Payment failed for Order ID: ' . $order->unique_id . ' - ' . $paymentResult['message']);
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => $paymentResult['message'] ?? 'Payment failed.',
                        ]);
                    }
                    Log::info('Card payment processed at ' . now() . ' with status: ' . $paymentResult['status']);
                }

                // Create payment record
                if ($paymentResult) {
                    $payment = $order->payments()->create([
                        'payment_datetime' => now(),
                        'payment_method' => $validated['payment'],
                        'amount' => $amount,
                        'transaction_id' => $paymentResult['transaction_id'] ?? null,
                        'auth_code' => $paymentResult['auth_code'] ?? null,
                        'customer_profile_id' => $paymentResult['customer_profile_id'] ?? null,
                        'payment_profile_id' => $paymentResult['payment_profile_id'] ?? null,
                        'card_number' => $paymentResult['card_number'] ?? null,
                        'card_first_name' => $validated['firstName'] ?? $cardDetail->first_name ?? null,
                        'card_last_name' => $validated['lastName'] ?? $cardDetail->last_name ?? null,
                        'status' => $paymentResult['payment_status'] ?? 'Pending',
                        'payment_response' => $paymentResult['payment_response'] ?? null,
                        'created_by_id' => $customer->id,
                        'created_by_type' => Customer::class,
                    ]);

                    if (empty($customer->authorize_profile_id) && !empty($paymentResult['customer_profile_id'])) {
                        $customer->authorize_profile_id = $paymentResult['customer_profile_id'];
                        $customer->saveQuietly();
                    }

                    if (!empty($paymentResult['payment_profile_id'])) {
                        $customer->cards()->updateOrCreate(
                            ['payment_profile_id' => $paymentResult['payment_profile_id']],
                            [
                                'first_name' => $validated['firstName'] ?? $cardDetail->first_name ?? null,
                                'last_name' => $validated['lastName'] ?? $cardDetail->last_name ?? null,
                                'card_number' => $paymentResult['card_number'] ?? null,
                                'card_type' => $paymentResult['card_type'] ?? null,
                            ],
                        );
                    }

                    Log::info('Payment record created at ' . now() . ' with ID: ' . $payment->id);

                    // Queue receipt creation instead of doing it synchronously
                    if ($paymentResult['status'] == 'success') {
                        CreateReceiptJob::dispatch($order->id, 'card');
                    }
                    Log::info('CreateReceiptJob dispatched completed at ' . now());
                }
            } else {
                // If payment type is not card, just create a pending payment record
                $payment = $order->payments()->create([
                    'payment_datetime' => now(),
                    'payment_method' => $validated['payment'],
                    'amount' => $order->grand_total,
                    'status' => $validated['payment'] === 'Account' ? 'Account' : 'Pending',
                    'payment_response' => null,
                    'created_by_id' => $customer->id,
                    'created_by_type' => Customer::class,
                ]);
            }

            if ($validated['payment'] === 'Account') {
                $salesTaxSetting = Setting::where('setting_name', 'sales_tax')->first();
                $products = $order->products;

                foreach ($products as $product) {
                    $record = new CustomerAccount();
                    $record->customer_id = $customer->id;
                    $record->order_id = $order->id;

                    $record->amount = $product->sub_total;
                    // $record->sales_tax = $product->tax ?? 0;
                    $record->sales_tax = $product->tax > 0 ? $salesTaxSetting?->setting_value : 0.0;

                    $record->date = now();
                    $record->type = 'order';
                    $record->reason = $product->product_name;

                    $record->balance = $customer->available_credit_balance ?? 0; // Optional: adjust this if you need per-product logic

                    $record->save();

                    // Update credit balance per product (optional, depends on logic)
                    CustomHelper::updateCreditBalance($record, $product->tax ?? 0);
                }
            }

            if (session()->has('tax_exempt')) {
                session()->forget('tax_exempt'); // Clear tax exempt session if already set
            }

            DB::commit();
            Log::info('Database transaction committed at ' . now());

            Log::info('Determining order action type at ' . now());

            $orderActionType = null;

            if (session()->has('order.number')) {
                // Reorder performed while impersonating
                $orderActionType = 'reorder';
            } elseif (session()->has('master_passcode')) {
                // Master passcode flow
                $orderActionType = 'master_passcode';
            } elseif (!empty($validated['employee_code']) && auth()->guard('customer')->check()) {
                // Customer is logged in (with an employee code)
                $orderActionType = 'website_login';
            } elseif (auth()->guard('customer')->check()) {
                // Customer is logged in (without an employee code)
                $employee = null;
                $orderActionType = 'customer_account_login';
            } elseif (!empty($validated['employee_code'])) {
                // New account created with an employee code (no customer login)
                $orderActionType = 'new_account';
            } else {
                // Guest checkout, no employee code
                $employee = null;
                $orderActionType = 'customer_no_account';
            }

            // Update the reference_order_number if exists
            if (session()->has('order')) {
                session()->forget('order');
            }

            Log::info('event dispatching started at ' . now());

            // Fire OrderPlaced event
            event(new OrderPlacedEvent($order, $customer, $payment, $orderActionType, $employee));
            // after order is successfully placed
            event(new OrderPlacedEmailEvent($order));

            Log::info('event dispatching completed at ' . now());
            // Generate a signed URL for the thank you page with order unique id
            // $signedUrl = \URL::temporarySignedRoute('front.checkout.thank-you', now()->addMinutes(5), ['order' => $order->unique_id]);

            Log::info('Generating redirect URL based on terms content at ' . now());
            if (!empty($termsContentData['terms_content'])) {
                $redirectUrl = route('front.terms-and-conditions.index', ['orderUniqueId' => $order->unique_id]);
            } else {
                $redirectUrl = SignedUrlHelper::make('front.checkout.thank-you', ['order' => $order->unique_id], 5);
            }


            // Success: redirect to signed thank you page with order id
            Log::info('Redirecting to thank you page at ' . now() . ' with URL: ' . $redirectUrl);

            $totalDuration = round((microtime(true) - $checkoutStart) * 1000, 2);
            Log::info("Total checkout duration: {$totalDuration} ms, seconds: " . round($totalDuration / 1000, 2) . "s");

            return response()->json([
                'success' => true,
                'redirect_url' => $redirectUrl,
                'order_id' => $order->unique_id ?? ''
            ]);
        } catch (\Exception $e) {
            // Log the error if needed: logger($e);
            Log::error('Checkout failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            DB::rollback();

            $totalDuration = round((microtime(true) - $checkoutStart) * 1000, 2);
            Log::info("Total checkout duration: {$totalDuration} ms, seconds: " . round($totalDuration / 1000, 2) . "s");

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your order. Please try again. Error: ' . $e->getMessage(),
            ]);
        }
    }
}
