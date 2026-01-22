<?php

namespace App\Listeners\Activities\Front\Auth;

use App\Events\Front\Auth\SendResetPasswordEvent;
use App\Services\MailService;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Illuminate\Support\Facades\Log;

class SendResetPasswordEmailListener
{
    protected MailService $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function handle(SendResetPasswordEvent $event)
    {



        $settings = $this->mailService->getSettings();

        try {
            $fromAddress = new Address($settings['from']['address'], $settings['from']['name']);
            $resetUrl = route('front.auth.forgot-password.reset-password.form', ['token' => $event->token]);

            $email = (new Email())
                ->from($fromAddress)
                ->to($event->email)
                ->subject("Reset Your Password")
                ->text("Click the following link to reset your password: {$resetUrl}");




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
          
        } catch (\Throwable $e) {
            Log::error("Reset password email sending failed", [
                'email' => $event->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
