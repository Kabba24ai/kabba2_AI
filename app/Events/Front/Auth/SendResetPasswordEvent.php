<?php

namespace App\Events\Front\Auth;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendResetPasswordEvent
{
    use Dispatchable, SerializesModels;

    public string $email;
    public string $token;

    public function __construct(string $email, string $token)
    {
        $this->email = $email;
        $this->token = $token;
    }
}
