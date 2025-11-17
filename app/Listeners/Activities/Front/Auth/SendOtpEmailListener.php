<?php

namespace App\Listeners\Auth;

use App\Events\Front\Auth\SendOtpEvent;
use App\Services\MailService;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class SendOtpEmailListener
{
    protected MailService $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function handle(SendOtpEvent $event)
    {
        $settings = $this->mailService->getSettings();

        try {

            $fromAddress = new Address(
                $settings['from']['address'],
                $settings['from']['name']
            );

            $email = (new Email())
                ->from($fromAddress)
                ->to($event->email)
                ->subject("Your OTP Code")
                ->text("Your OTP code is: {$event->otp}");

            $dsn = sprintf(
                '%s://%s:%s@%s:%s',
                $settings['mailer'],
                urlencode($settings['username']),
                urlencode($settings['password']),
                $settings['host'],
                $settings['port']
            );

            $transport = Transport::fromDsn($dsn);
            $mailer = new Mailer($transport);

            $mailer->send($email);

            Log::info("OTP email successfully sent to {$event->email}");
        } catch (\Throwable $e) {

            Log::error("OTP sending failed", [
                'email' => $event->email,
                'error' => $e->getMessage(),
            ]);

            throw $e; // let controller catch it
        }
    }
}
