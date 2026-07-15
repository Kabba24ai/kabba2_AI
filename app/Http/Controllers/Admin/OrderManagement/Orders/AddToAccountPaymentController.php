<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Enums\Orders\OrderPaymentStatus;
use App\Enums\Orders\OrderPaymentMethod;
use App\Http\Controllers\Controller;
use App\Events\Admin\Orders\PaymentAddedToAccountEvent;
use App\Models\Orders\Order;
use App\Models\Configurations\Setting;
use App\Models\Customers\CustomerAccount;
use App\Services\LedgerBalanceService;


class AddToAccountPaymentController extends Controller
{
    public function __invoke($uniqueId)
    {
        $user = auth()->user();

        $order = Order::with(['products.product'])->where('unique_id', $uniqueId)->first();
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        try {
            $lastPayment = $order->payments()->pending()->cod()->latest('id')->first();
            if (!$lastPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No payments found for this order.'
                ], 404);
            }

            $lastPayment->status = OrderPaymentStatus::Account;
            $lastPayment->payment_method = OrderPaymentMethod::Account;
            $lastPayment->save();

            // Handle CustomerAccount logic
            $customer = $order->customer;
            $salesTaxSetting = Setting::where('setting_name', 'sales_tax')->first();

            // $record = new CustomerAccount();
            // $record->customer_id = $customer->id;
            // $record->order_id = $order->id;
            // $record->balance = $customer->available_credit_balance ?? 0;
            // $record->amount = $order->subtotal;
            // $record->sales_tax = $order->tax_amount > 0 ? $salesTaxSetting?->setting_value : 0.0;
            // $record->date = now();
            // $record->type = 'order';
            // $record->save();

            $products = $order->products;

            // Phase 3A fix: this used to push every product's full
            // sub_total to the customer's Account balance unconditionally,
            // independent of anything already collected on this order via
            // another channel. Cap the total amount converted at the
            // order's actual remaining collection balance (grand_total −
            // settled payments) instead of re-deriving it from product
            // totals — in the common case (nothing paid yet, the only
            // scenario this flow has historically been used for) the
            // remaining balance equals the full product-subtotal sum and
            // every row is created exactly as before; only a
            // partially-paid order changes behavior, capping the total so
            // the already-collected portion is never double-counted.
            $remainingToConvert = max(0.0, (float) $order->balance_due);

            foreach ($products as $product) {
                if ($remainingToConvert <= 0) {
                    break;
                }

                $productAmount = min((float) $product->sub_total, $remainingToConvert);
                $remainingToConvert -= $productAmount;

                $record = new CustomerAccount();
                $record->customer_id = $customer->id;
                $record->order_id = $order->id;

                $record->amount = $productAmount;
                // $record->sales_tax = $product->tax ?? 0;
                $record->sales_tax = $product->tax > 0 ? $salesTaxSetting?->setting_value : 0.0;

                $record->date = now();
                $record->type = 'order';
                $record->reason = $product->product_name;

                $record->balance = $customer->available_credit_balance ?? 0; // Optional: adjust this if you need per-product logic

                $record->save();

                // Update credit balance per product (optional, depends on logic)
                LedgerBalanceService::applyTransaction($record, $product->tax ?? 0);
            }

            // Update credit balance
            // CustomHelper::updateCreditBalance($record, $order->tax_amount);

            // Fire event
            event(new PaymentAddedToAccountEvent($order, $user, $lastPayment));

            return response()->json([
                'success' => true,
                'message' => 'Payment added to account successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not add payment to account.'
            ], 500);
        }
    }
}
