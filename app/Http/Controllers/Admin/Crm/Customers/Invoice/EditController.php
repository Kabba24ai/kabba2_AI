<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Iam\Personnel\User;
use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use App\Helpers\CustomHelper;

use App\Models\Customers\Invoice;
use App\Helpers\ConfigurationHelper;

class EditController extends Controller
{

    public function __invoke(Request $request, $unique_id)
    {
        $invoice = Invoice::with([
            'creator',
            'customer',
            'items.orderProduct',
        ])->where('unique_id', $unique_id)->first();

        $sales_tax = ConfigurationHelper::getSettings(null, 'sales_tax');

        $customer = Customer::with('orders.products', 'orders.payments', 'accountApprovedBy', 'taxStatusApprovedBy', 'addresses.state', 'billingAddress', 'shippingAddress', 'accounts.responsibleUser', 'media')
            ->where('unique_id', $invoice->customer->unique_id)
            ->firstOrFail();

        $order = Order::with('shippingAddress', 'products.product.categories', 'lastPayment', 'products.deliverySignatureMedia', 'products.returnSignatureMedia')->where('customer_id', $customer->id)->whereNull('invoice_id')->get();

        $users = User::where('status', 'Active')->get();

        $sales_tax = ConfigurationHelper::getSettings(null, 'sales_tax');

        //  Build invoice items JSON for front-end
        $invoiceItems = $invoice->items->map(function ($item) {
            return [
                'id'              => $item->item_id ,
                'name'            => $item->item_name,
                'sku'             => $item->sku,
                'qty'             => $item->qty,
                'unit'            => $item->unit,
                'tax'             => $item->tax,
                'total'           => $item->total,
                'extras' => $item->extras ?? [],
                'orderId'         => $item->orderProduct->order->unique_id ?? null,
                'responsible_id'  => $item->responsible_person_id,
                'reference'       => $item->reference,
                'notes'           => $item->notes,
                'type'            => $item->type,
            ];
        });

        // Separate orders and other types
        $orderItems = $invoiceItems->filter(fn($item) => $item['type'] === 'order')->values();
        $otherItems = $invoiceItems->filter(fn($item) => $item['type'] !== 'order')->values();

        $invoiceItems->values()->toJson();

        return view('admin.crm.customers.create_invoice', ['invoice'=> $invoice , 'customer' => $customer, 'orders' => $order, 'users' => $users, 'sales_tax' => $sales_tax, 'invoiceItems'=> $invoiceItems ,'orderItems'=> $orderItems, 'otherItems' => $otherItems ]);
    }
}
