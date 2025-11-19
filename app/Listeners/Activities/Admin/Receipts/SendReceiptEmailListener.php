<?php

namespace App\Listeners\Activities\Admin\Receipts;

use App\Events\Admin\Receipts\ReceiptEmailEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use App\Services\MailService;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class SendReceiptEmailListener
{
    protected MailService $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function handle(ReceiptEmailEvent $event)
    {
        $receipt  = $event->receipt;
        $customer = $receipt->customer;

        try {

            $paid_stamp = base64_encode(file_get_contents(public_path('storage/admin/images/icons/paid.png')));


            $pdf = Pdf::setOption(['isRemoteEnabled' => true])->loadView('admin.order_management.orders.print_receipt', [
                'receipt'  => $receipt,
                'order'    => $receipt->order,
                'customer' => $customer,
                'sales_tax' => config('settings.sales_tax'),
                'paid_stamp' => $paid_stamp,
            ]);

            $pdfData = $pdf->output();

            $settings = $this->mailService->getSettings();

            $email = (new Email())
                ->from(new Address($settings['from']['address'], $settings['from']['name']))
                ->to($customer->email)
                ->subject("Your Receipt #{$receipt->unique_id}")
                ->text("Please find your receipt attached.")
                ->attach($pdfData, "receipt-{$receipt->unique_id}.pdf", "application/pdf");

            $dsn = sprintf(
                '%s://%s:%s@%s:%s',
                $settings['mailer'],
                urlencode($settings['username']),
                urlencode($settings['password']),
                $settings['host'],
                $settings['port']
            );

            $mailer = new Mailer(Transport::fromDsn($dsn));

            $mailer->send($email);

            // Mark as sent
            $receipt->update([
                'is_email_status' => 'send',
                'mail_send_at'    => now(),
            ]);
        } catch (\Throwable $e) {

            Log::error('SendReceiptEmailListener failed', [
                'receipt_id'     => $receipt->id,
                'customer_email' => $customer->email,
                'message'        => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
