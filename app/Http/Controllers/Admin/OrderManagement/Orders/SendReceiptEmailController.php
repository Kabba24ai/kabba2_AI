<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\Customers\Receipt;
use App\Events\Admin\Receipts\ReceiptEmailEvent;

class SendReceiptEmailController extends Controller
{
    public function __invoke(string $unique_id)
    {
        try {

            // ✔ Get receipt by order ID, not unique_id
            $receipt = Receipt::with([
                'invoice',
                'items',
                'items.orderProduct',
                'customer.addresses.state',
                'customer.billingAddress',
                'customer.shippingAddress',
            ])
                ->where('order_id', $unique_id)
                ->latest()
                ->firstOrFail();

            event(new ReceiptEmailEvent($receipt));

            flash('Receipt email sent successfully.')->success();

        } catch (\Throwable $e) {

            Log::error('SendReceiptEmailController failed', [
                'receipt_unique_id' => $unique_id,
                'message'           => $e->getMessage(),
            ]);

            flash('Something went wrong while sending the receipt email.')->error();
        }

        return redirect()->back();
    }
}
