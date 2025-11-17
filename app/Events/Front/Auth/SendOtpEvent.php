<?php

namespace App\Events\Front\Auth;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendOtpEvent
{
    use Dispatchable, SerializesModels;

    public string $email;
    public string $otp;

    public function __construct(string $email, string $otp)
    {
        $this->email = $email;
        $this->otp = $otp;
    }
}
