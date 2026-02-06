<?php

namespace App\Listeners\Activities\Front\Checkout;

use App\Events\Front\Checkout\OrderPlacedEmailEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use App\Services\MailService;
use App\Services\ReceiptService;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class SendOrderPlacedEmailListener
{
    protected MailService $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function handle(OrderPlacedEmailEvent $event)
    {
        $order    = $event->order;
        $customer = $order->customer;

        try {

            /**  SAME AS ADMIN RECEIPT FLOW */
            $receipt = ReceiptService::getOrCreateReceipt($order);

            $pdf = Pdf::setOption(['isRemoteEnabled' => true])
                ->loadView('admin.order_management.orders.print_receipt', [
                    'receipt'   => $receipt,
                    'order'     => $order,
                    'customer'  => $customer,
                    'sales_tax' => config('settings.sales_tax'),
                ]);

            $pdfData = $pdf->output();

            $settings = $this->mailService->getSettings();

            $email = (new Email())
                ->from(new Address(
                    $settings['from']['address'],
                    $settings['from']['name']
                ))
                ->to($customer->email)
                ->subject("Order Confirmation #{$order->order_number}")
                ->text("Thank you for your order. Please find your receipt attached.")
                ->attach(
                    $pdfData,
                    "receipt-{$receipt->unique_id}.pdf",
                    "application/pdf"
                );

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

        

        } catch (\Throwable $e) {

            Log::error('SendOrderPlacedEmailListener failed', [
                'order_id'       => $order->id,
                'customer_email' => $customer->email,
                'message'        => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
