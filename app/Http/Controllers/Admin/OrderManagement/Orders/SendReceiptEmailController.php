<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\Customers\Receipt;
use App\Events\Admin\Receipts\ReceiptEmailEvent;

use App\Services\ReceiptService;
use App\Models\Orders\Order;


class SendReceiptEmailController extends Controller
{
    public function __invoke(string $unique_id)
    {
        try {

            $order = Order::with(
                'shippingAddress',
                'products.product.categories',
                'lastPayment',
                'products.deliverySignatureMedia',
                'products.returnSignatureMedia'
            )->where('unique_id', $unique_id)->firstOrFail();

        
            //  auto-create or get existing receipt
            $receipt = ReceiptService::getOrCreateReceipt($order);


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
