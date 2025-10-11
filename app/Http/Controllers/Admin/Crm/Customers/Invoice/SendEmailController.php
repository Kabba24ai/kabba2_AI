<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\Customers\Invoice;
use App\Events\Admin\Invoices\InvoiceEmailEvent;
use App\Listeners\Activities\Admin\Invoices\SendInvoiceEmailListener ;

class SendEmailController extends Controller
{
    /**
     * Send invoice email.
     */
    public function __invoke(string $unique_id)
    {
        try {
            // Find the invoice
            $invoice = Invoice::where('unique_id', $unique_id)->firstOrFail();

            // Dispatch event — Laravel will automatically resolve the listener and inject MailService
            event(new InvoiceEmailEvent($invoice));


            // If no exception -> success
            flash('Invoice email sent successfully.')->success();
        } catch (\Throwable $e) {
            // If listener threw exception -> failure
            Log::error('SendEmailController: Failed to send invoice email.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'invoice_unique_id' => $unique_id,
            ]);

            flash('Something went wrong while sending the invoice email.')->error();
        }


        session()->flash('active_tab', 'invoices');


        // Redirect back to customer view (adjust route name if needed)
        return redirect()->back();
    }
}
