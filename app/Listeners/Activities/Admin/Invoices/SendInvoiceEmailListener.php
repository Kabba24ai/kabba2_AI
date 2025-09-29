<?php

namespace App\Listeners\Activities\Admin\Invoices;

use App\Events\Admin\Invoices\InvoiceEmailEvent;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use App\Helpers\ConfigurationHelper;

class SendInvoiceEmailListener
{
    public function handle(InvoiceEmailEvent $event)
    {
        $invoice = $event->invoice;

        try {
            Log::info(": Handling invoice email.", [
                'invoice_id' => $invoice->id,
                'customer_email' => $invoice->customer->email ?? null,
            ]);

            $sales_tax = ConfigurationHelper::getSettings(null, 'sales_tax');
            Log::info("Sales tax fetched.", ['sales_tax' => $sales_tax]);

            // Generate PDF
            $pdf = Pdf::loadView('admin.crm.customers.print_invoice', [
                'invoice' => $invoice,
                'customer' => $invoice->customer,
                'sales_tax' => $sales_tax,
            ]);
            $pdfData = $pdf->output();
            Log::info("PDF generated successfully.", ['pdf_size_bytes' => strlen($pdfData)]);

            Mail::raw('Please Checked Your Invoice Attached.', function ($message) use ($invoice, $pdfData) {
                $message->from(config('mail.from.address'), config('mail.from.name'));
                $message->to($invoice->customer->email)
                    ->subject('Your Invoice #' . $invoice->unique_id)
                    ->attachData($pdfData, 'invoice-' . $invoice->unique_id . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
            });

            Log::info("Invoice email sent successfully.", ['invoice_id' => $invoice->id]);
        } catch (\Throwable $e) {
            Log::error('SendInvoiceEmailListener: Failed to generate PDF or send email.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'invoice_id' => $invoice->id,
            ]);
        }
    }
}
