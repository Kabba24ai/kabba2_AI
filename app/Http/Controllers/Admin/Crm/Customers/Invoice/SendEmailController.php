<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\Customers\Invoice;
use App\Events\Admin\Invoices\InvoiceEmailEvent;
use App\Listeners\Activities\Admin\Invoices\SendInvoiceEmailListener ;
use Illuminate\Http\Request;

class SendEmailController extends Controller
{
    /**
     * Send invoice email.
     */
    // public function __invoke(string $unique_id)
    // {
    //     try {
    //         // Find the invoice
    //         $invoice = Invoice::where('unique_id', $unique_id)->firstOrFail();

    //         // Dispatch event — Laravel will automatically resolve the listener and inject MailService
    //         event(new InvoiceEmailEvent($invoice));



    //         // If no exception -> success
    //         flash('Invoice email sent successfully.')->success();
    //     } catch (\Throwable $e) {
    //         // If listener threw exception -> failure
    //         Log::error('SendEmailController: Failed to send invoice email.', [
    //             'message' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //             'invoice_unique_id' => $unique_id,
    //         ]);

    //         flash('Something went wrong while sending the invoice email.')->error();
    //     }


    //     // session()->flash('active_tab', 'invoices');
    //     session(['active_tab' => 'invoices']);



    //     // Redirect back to customer view (adjust route name if needed)
    //     return redirect()->back();
    // }


    public function __invoke(Request $request)
    {
        try {
            $invoice = Invoice::with(['customer.billingAddress'])
                ->where('unique_id', $request->invoice_id)
                ->firstOrFail();

            $emails = [];

            if ($request->send_customer && $invoice->customer?->email) {
                $emails[] = $invoice->customer->email;
            }

            if ($request->send_billing && $invoice->customer?->billingAddress?->email) {
                $emails[] = $invoice->customer->billingAddress->email;
            }

            if (empty($emails)) {
                flash('No valid email selected.')->error();
                return back();
            }

           // Normalize emails (trim + lowercase)
            $emails = array_map(function ($email) {
                return strtolower(trim($email));
            }, $emails);

            // Remove duplicates
            $emails = array_unique($emails);

            foreach ($emails as $email) {
                event(new InvoiceEmailEvent($invoice, $email));
            }



            $invoice->update([
                'is_email_send' => 1,
                'mail_send_at' => now(),
            ]);

            flash('Invoice email sent successfully.')->success();

        } catch (\Throwable $e) {

            Log::error('SendEmailController error', [
                'message' => $e->getMessage(),
            ]);

            flash('Something went wrong while sending invoice email.')->error();
        }

        session(['active_tab' => 'invoices']);

        return back();
    }

}
