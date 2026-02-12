<?php

namespace App\Http\Controllers\Front\Checkout;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;

// Enums
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
use App\Jobs\ProcessPaymentJob;
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
        $perfStart = microtime(true);
        $perfMarks = [];
        $perfMark = function (string $label) use (&$perfMarks, $perfStart) {
            $perfMarks[$label] = round((microtime(true) - $perfStart) * 1000, 2);
        };

        $validated = $request->validated();
        // return redirect()->back()->withInput()->with('error', 'Debug stop before processing.');
        $cart = json_decode($validated['cart'], true);
        $cartSummary = CartHelper::buildCartSummary(['cart_items' => $cart]);
        $perfMark('cart_summary');
        $employeeCode = $validated['employee_code'] ?? null;

        if ($employeeCode) {
            // Validate employee code if provided
            $employee = User::where('employee_code', $employeeCode)->active()->first();
            if (!$employee) {
                return redirect()->back()->withInput()->with('error', 'Invalid employee code.');
            }
        } else {
            $employee = null;
        }
        $perfMark('employee_check');

        try {
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
            $perfMark('customer_ready');

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

            $perfMark('addresses_ready');

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
                'cart_data' => $cart,
                'is_tax_exempt' => $cartSummary['tax_exempt'] ? 'Yes' : 'No',
                'platform' => 'Web',
            ]);

            if (!empty($validated['orderNotes'])) {
                // Create order note if provided
                $order->notes()->create([
                    'note' => $validated['orderNotes'],
                    'created_by_type' => Customer::class,
                    'created_by_id' => $customer->id,
                ]);
            }
            $perfMark('order_created');

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

            $primaryStoreId = Store::primary()->value('id');
            $perfMark('order_addresses');

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
            $perfMark('order_products');

            // Eager load products and their terms for terms generation
            $order->load(['products.product.terms']);

            $termsContentData = TermsContentHelper::generateTermsContent($order);

            $order->terms_collection = $termsContentData['merged_terms'] ?? null;
            $order->pending_terms_content = $termsContentData['terms_content'] ?? null;
            $order->terms_status = OrderTermsStatus::Pending;
            $order->saveQuietly(); // saveQuietly() saves the model to the database without firing any Eloquent events (like "saved", "updated", etc.)
            $perfMark('terms_ready');


            // If payment type is card, process payment asynchronously
            if (strtolower($validated['payment']) === 'card') {
                $amount = $order->grand_total;
                $payment = null;

                // Validate payment data upfront
                if (session()->has('impersonated_by_admin') && !empty($validated['customer_card'])) {
                    $cardDetail = $customer->cards()->where('unique_id', $validated['customer_card'])->first();

                    if (!$customer->authorize_profile_id) {
                        DB::rollBack();
                        return back()->withInput()->with('error', 'Customer profile not found for saved card.');
                    }

                    // Dispatch payment job (will run asynchronously)
                    ProcessPaymentJob::dispatch(
                        $order,
                        $customer,
                        $amount,
                        'card',
                        $customer->authorize_profile_id,
                        $cardDetail->payment_profile_id,
                        null,
                        null,
                        [
                            'first_name' => $cardDetail->first_name ?? null,
                            'last_name' => $cardDetail->last_name ?? null,
                        ]
                    );

                    // Create a pending payment record (will be updated by the job)
                    $payment = $order->payments()->create([
                        'payment_datetime' => now(),
                        'payment_method' => 'Card',
                        'amount' => $amount,
                        'status' => 'Pending',
                        'created_by_id' => $customer->id,
                        'created_by_type' => Customer::class,
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

                    // Dispatch payment job (will run asynchronously)
                    ProcessPaymentJob::dispatch(
                        $order,
                        $customer,
                        $amount,
                        'Card',
                        null,
                        null,
                        $opaqueDataValue,
                        $opaqueDataDescriptor,
                        [
                            'first_name' => $validated['firstName'] ?? null,
                            'last_name' => $validated['lastName'] ?? null,
                        ]
                    );

                    // Create a pending payment record (will be updated by the job)
                    $payment = $order->payments()->create([
                        'payment_datetime' => now(),
                        'payment_method' => 'Card',
                        'amount' => $amount,
                        'status' => 'Pending',
                        'created_by_id' => $customer->id,
                        'created_by_type' => Customer::class,
                    ]);
                }
            } else {
                // If payment type is not card, just create a pending payment record
                $payment = $order->payments()->create([
                    'payment_datetime' => now(),
                    'payment_method' => $validated['payment'],
                    'amount' => $order->grand_total,
                    'status' => $validated['payment'] === 'Account' ? 'Account' : 'Pending',
                    'created_by_id' => $customer->id,
                    'created_by_type' => Customer::class,
                ]);
            }
            $perfMark('payment_and_receipt');

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
            $perfMark('account_records');

            if (session()->has('tax_exempt')) {
                session()->forget('tax_exempt'); // Clear tax exempt session if already set
            }

            DB::commit();
            $perfMark('db_commit');

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

            // Fire OrderPlaced event (queued, non-blocking)
            event(new OrderPlacedEvent($order, $customer, $payment, $orderActionType, $employee));
            // Send order confirmation email (queued, non-blocking)
            event(new OrderPlacedEmailEvent($order));

            $perfMark('events_deferred');

            // Generate a signed URL for the thank you page with order unique id
            // $signedUrl = \URL::temporarySignedRoute('front.checkout.thank-you', now()->addMinutes(5), ['order' => $order->unique_id]);

            if (!empty($termsContentData['terms_content'])) {
                $redirectUrl = route('front.terms-and-conditions.index', ['orderUniqueId' => $order->unique_id]);
            } else {
                $redirectUrl = SignedUrlHelper::make('front.checkout.thank-you', ['order' => $order->unique_id], 5);
            }

            // Success: redirect to signed thank you page with order id

            Log::info('Checkout timing (ms)', $perfMarks + [
                'total' => round((microtime(true) - $perfStart) * 1000, 2),
                'order_id' => $order->id,
                'order_unique_id' => $order->unique_id,
            ]);

            return response()->json([
                'success' => true,
                'redirect_url' => $redirectUrl,
                'order_id' => $order->unique_id ?? ''
            ]);
        } catch (\Exception $e) {
            // Log the error if needed: logger($e);
            logger($e);
            Log::info('Checkout timing (ms)', $perfMarks + [
                'total' => round((microtime(true) - $perfStart) * 1000, 2),
                'error' => true,
            ]);
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.'
            ]);
        }
    }
}
