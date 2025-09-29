<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\Customers\Invoice;
use App\Events\Admin\Invoices\InvoiceEmailEvent;

class SendEmailController extends Controller
{
    /**
     * Send invoice email.
     */
    public function __invoke(string $unique_id)
    {
        try {
            Log::info("SendEmailController: Start sending email for invoice.", ['invoice_unique_id' => $unique_id]);

            // Find the invoice
            $invoice = Invoice::where('unique_id', $unique_id)->firstOrFail();

            Log::info("Invoice found.", ['invoice_id' => $invoice->id, 'customer_id' => $invoice->customer_id]);


            // Fire event (listener will handle PDF + email)
            event(new InvoiceEmailEvent($invoice));

            Log::info("InvoiceEmailEvent dispatched.", ['invoice_id' => $invoice->id]);


            flash('Invoice email sent successfully.')->success();
        } catch (\Throwable $e) {
            Log::error('SendEmailController: Failed to send invoice email.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'invoice_unique_id' => $unique_id,
            ]);

            flash('Something went wrong while sending the invoice email.')->error();
        }

        // Redirect back to customer view (adjust route name if needed)
        return redirect()->back();
    }
}
