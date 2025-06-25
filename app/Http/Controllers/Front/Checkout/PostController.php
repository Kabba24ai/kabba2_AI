<?php

namespace App\Http\Controllers\Front\Checkout;

use App\Http\Controllers\Controller;

// Request
use App\Http\Requests\Front\Checkout\PostRequest;

// Models
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAddress;
use App\Models\Locations\State;
use App\Models\ProductManagement\Product;
use App\Services\RentalCartService;
use DB;

class PostController extends Controller
{
    protected $cartService;

    public function __construct(RentalCartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(PostRequest $request)
    {

        DB::beginTransaction();
        try {
            $validated = $request->validated();

            $cart = $this->cartService->getCart();
            if (empty($cart)) {
                return redirect()->back()->withInput()->with('error', 'Your cart is empty. Please add products before placing an order.');
            }

            // 1. Find or create customer
            $customer = Customer::firstOrCreate(
                [
                    'email' => $validated['billingEmail'],
                ],
                [
                    'first_name' => $validated['billingFirstName'],
                    'last_name' => $validated['billingLastName'],
                    'company' => $validated['billingCompany'] ?? null,
                    'phone' => $validated['billingPhone'],
                    'is_guest' => false,
                    'Status' => 'Active',
                ],
            );

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

            $deliveryAddress = CustomerAddress::updateOrCreate(
                $deliveryAddressField,
                $deliveryData,
            );

            $billingState = State::where('id', $billingAddress->state_id)->first();
            $deliveryState = State::where('id', $deliveryAddress->state_id)->first();

            $subTotal = 0;
            $taxAmount = 0;
            $grandTotal = 0;

            foreach ($cart as $item) {
                $subTotal += $item['total_price'];
                $taxAmount += $item['tax_amount'];
                $grandTotal += $item['total_price_with_tax'];
            }

            // 4. Save Order
            $order = $customer->orders()->create([
                'customer_id' => $customer->id,
                'customer_name' => $customer->full_name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'subtotal' => $subTotal,
                'tax_amount' => $taxAmount,
                'coupon_code' => null,
                'discount_amount' => 0,
                'grand_total' => $grandTotal,
                'payment_type' => $validated['payment'],
                'order_note' => $validated['orderNotes'] ?? null,
                'status' => 'Pending',
                'platform' => 'Web',
            ]);

            // Create order billing address
            $order->addresses()->create([
                'type' => 'Billing',
                'first_name' => $billingAddress->first_name,
                'last_name' => $billingAddress->last_name,
                'email' => $billingAddress->email,
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
                'email' => $deliveryAddress->email,
                'phone' => $deliveryAddress->phone,
                'address' => $deliveryAddress->address,
                'city' => $deliveryAddress->city,
                'state' => $deliveryState->name ?? null,
                'state_id' => $deliveryAddress->state_id ?? null,
                'zip_code' => $deliveryAddress->zip_code,
            ]);

            foreach ($cart as $item) {
                if ($product = Product::where('id', $item['product_id'])->first()) {
                    // Optional: If you need store, tax, or other related info, join here

                    $price = $item['base_price']; // use DB price, not posted price
                    $taxRate = $product->tax_rate ?? 0; // use DB tax rate
                    $productName = $product->product_name; // always from DB

                    // Calculate
                    $quantity = (int) $item['qty'];
                    $tax = round($price * $quantity * $taxRate, 2);
                    $total = round($price * $quantity + $tax, 2);

                    // Save order product
                    $order->products()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $productName,
                        'price' => $price,
                        'quantity' => $quantity,
                        'tax' => $tax,
                        'total' => $total,
                        'schedule_start_date' => $item['schedule_date'],
                        'schedule_end_date' => $item['schedule_end_date'] ?? null,
                        'product_data' => json_encode($item),
                        'service_method' => $item['service_method'] ?? null,
                        'service_option' => $item['service_option'] ?? null,
                        'store_id' => $product->store_id ?? null,
                        'distance_type' => $item['distance_type'] ?? null,
                        'distance_range' => $item['distance_range'] ?? null,
                    ]);
                }
            }

            DB::commit();

            // Success: redirect back with success message
            return redirect()->route('front.home.index')->with('success', 'Order placed successfully!');
        } catch (\Exception $e) {
            DB::rollback();
            dd($e);
            // Log the error if needed: logger($e);
            return redirect()->back()->withInput()->with('error', 'Something went wrong. Please try again.');
        }
    }
}
