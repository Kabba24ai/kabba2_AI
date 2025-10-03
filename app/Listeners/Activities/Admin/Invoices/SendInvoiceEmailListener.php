<?php

namespace App\Listeners\Activities\Admin\Invoices;

use App\Events\Admin\Invoices\InvoiceEmailEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use App\Helpers\ConfigurationHelper;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmailListener
{
    public function handle(InvoiceEmailEvent $event)
    {
        $invoice = $event->invoice;

        try {
            $sales_tax = ConfigurationHelper::getSettings(null, 'sales_tax');

            // Log current mail settings
            // Log::info('Current Mail Configuration', [
            //     'mailer'     => config('mail.default'),
            //     'host'       => config('mail.mailers.smtp.host'),
            //     'port'       => config('mail.mailers.smtp.port'),
            //     'username'   => config('mail.mailers.smtp.username') ? '***hidden***' : null,
            //     'encryption' => config('mail.mailers.smtp.encryption'),
            //     'from'       => config('mail.from.address'),
            // ]);

            // Generate PDF
            $pdf = Pdf::loadView('admin.crm.customers.print_invoice', [
                'invoice'   => $invoice,
                'customer'  => $invoice->customer,
                'sales_tax' => $sales_tax,
            ]);
            $pdfData = $pdf->output();

            // Build email in a variable
            $email = (new Email())
                ->from(config('mail.from.address'))
                ->to($invoice->customer->email)
                ->subject('Your Invoice #' . $invoice->unique_id)
                ->text('Please check your attached invoice.')
                ->attach($pdfData, 'invoice-' . $invoice->unique_id . '.pdf', 'application/pdf');

            // Send email via Symfony Mailer transport
            Mail::mailer()->getSymfonyTransport()->send($email);

            // Update invoice only if sent successfully
            $invoice->is_email_send = 'send';
            $invoice->save();

            // Log::info("Invoice email sent successfully.", [
            //     'invoice_id' => $invoice->id,
            //     'to'         => $invoice->customer->email,
            //     'subject'    => $email->getSubject(),
            // ]);
        } catch (\Throwable $e) {

            Log::error("SendInvoiceEmailListener: Failed to generate PDF or send email.", [
                'invoice_id' => $invoice->id,
                'message'    => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            //  Rethrow the exception so controller knows it failed
            throw $e;
        }
    }
}
