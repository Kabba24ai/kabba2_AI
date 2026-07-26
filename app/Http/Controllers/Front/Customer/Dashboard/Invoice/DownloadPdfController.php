<?php

namespace App\Http\Controllers\Front\Customer\Dashboard\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Helpers\CustomHelper;

use App\Helpers\ConfigurationHelper;

class DownloadPdfController extends Controller
{
    /**
     * Download one of the AUTHENTICATED customer's own invoice PDFs.
     *
     * Security: the invoice is resolved through the session customer's
     * invoices() relationship (constrained to customer_id = auth id), so
     * another customer's invoice 404s instead of being exported.
     */
    public function __invoke(Request $request, $unique_id)
    {
        $customer = Auth::guard('customer')->user();

        $invoice = $customer->invoices()->with([
            'creator',
            'customer',
            'items.orderProduct',
        ])->where('unique_id', $unique_id)->firstOrFail();

        $customer->load(
            'orders.products',
            'orders.payments',
            'accountApprovedBy',
            'taxStatusApprovedBy',
            'addresses.state',
            'billingAddress',
            'shippingAddress',
            'accounts.responsibleUser',
            'media'
        );

        $order = Order::with(
            'shippingAddress',
            'products.product.categories',
            'lastPayment',
            'products.deliverySignatureMedia',
            'products.returnSignatureMedia'
        )->where('customer_id', $customer->id)->get();

        $users = User::where('status', 'Active')->get();
        $sales_tax = ConfigurationHelper::getSettings(null, 'sales_tax');

        $invoiceItems = $invoice->items->map(function ($item) {
            return [
                'id' => $item->item_id,
                'name' => $item->item_name,
                'sku' => $item->sku,
                'qty' => $item->qty,
                'unit' => $item->unit,
                'tax' => $item->tax,
                'total' => $item->total,
                'extras' => $item->extras ?? [],
                'orderId' => $item->orderProduct->order->unique_id ?? null,
                'responsible_id' => $item->responsible_person_id,
                'reference' => $item->reference,
                'notes' => $item->notes,
                'type' => $item->type,
            ];
        });

        $orderItems = $invoiceItems->filter(fn($item) => $item['type'] === 'order')->values();
        $otherItems = $invoiceItems->filter(fn($item) => $item['type'] !== 'order')->values();

        // Load Blade into PDF
        $pdf = Pdf::loadView('admin.crm.customers.print_invoice', [
            'invoice'      => $invoice,
            'customer'     => $customer,
            'orders'       => $order,
            'users'        => $users,
            'sales_tax'    => $sales_tax,
            'invoiceItems' => $invoiceItems,
            'orderItems'   => $orderItems,
            'otherItems'   => $otherItems,
        ]);

        // Stream in browser OR download

        return $pdf->download('invoice-' . $invoice->unique_id . '.pdf');

    }
}
