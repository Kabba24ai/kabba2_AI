<?php

namespace App\Http\Controllers\Front\Checkout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Iam\Personnel\User;
use App\Models\Customers\Customer;
use App\Models\Customers\Receipt;
use App\Services\ReceiptService;



use App\Models\Orders\Order;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Helpers\CustomHelper;

use App\Models\Customers\Invoice;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Helpers\ConfigurationHelper;

class ReceiptDownload extends Controller
{

    public function __invoke(Request $request, $unique_id)
    {
      


     try {
            $uniqueId = decrypt($unique_id);   // <-- decrypt the route param
        } catch (DecryptException $e) {
            abort(403, 'Invalid or tampered order parameter.');
        }
    

        $order = Order::with(
            'shippingAddress',
            'products.product.categories',
            'lastPayment',
            'products.deliverySignatureMedia',
            'products.returnSignatureMedia'
        )->where('unique_id', $uniqueId)->firstOrFail();

        $customer = Customer::with(
            'orders.products',
            'orders.payments',
            'accountApprovedBy',
            'taxStatusApprovedBy',
            'addresses.state',
            'billingAddress',
            'shippingAddress',
            'accounts.responsibleUser',
            'media'
        )->where('id', $order->customer_id)->firstOrFail();

        $users = User::where('status', 'Active')->get();
        $sales_tax = ConfigurationHelper::getSettings(null, 'sales_tax');


        //  auto-create or get existing receipt
        $receipt = ReceiptService::getOrCreateReceipt($order);

     
        $pdf = Pdf::setOption(['isRemoteEnabled' => true])->loadView('admin.order_management.orders.print_receipt', [
            'customer' => $customer,
            'order'    => $order,
            'users'    => $users,
            'sales_tax' => $sales_tax,
            'receipt'  => $receipt,
        ]);

        return $pdf->stream("receipt-{$receipt->unique_id}.pdf");
    }
}
