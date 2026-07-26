<?php

namespace App\Http\Controllers\Front\Customer\Dashboard\Order;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Customers\Receipt;
use App\Events\Admin\Receipts\ReceiptEmailEvent;

use App\Services\ReceiptService;


class SendReceiptEmailController extends Controller
{
    /**
     * Email the receipt for one of the AUTHENTICATED customer's own orders.
     *
     * Security: the order is resolved through the session customer's orders()
     * morph relationship (created_by), so a customer cannot trigger a receipt
     * email (and Receipt row creation) for another customer's order.
     */
    public function __invoke(string $unique_id)
    {
        try {

            $order = Auth::guard('customer')->user()
                ->orders()
                ->with(
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
