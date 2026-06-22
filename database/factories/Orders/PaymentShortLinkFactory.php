<?php

namespace Database\Factories\Orders;

use App\Models\Orders\PaymentShortLink;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentShortLinkFactory extends Factory
{
    protected $model = PaymentShortLink::class;

    public function definition(): array
    {
        return [
            'token'                   => Str::random(10),
            'order_id'                => null,
            'customer_id'             => null,
            'created_by'              => null,
            'original_url'            => 'https://rentnking.com/checkout/order-payment-form/' . Str::random(20),
            'expires_at'              => now()->addDays(14),
            'used_at'                 => null,
            'clicks'                  => 0,
            'max_clicks'              => null,
            'last_clicked_at'         => null,
            'last_clicked_ip'         => null,
            'last_clicked_user_agent' => null,
        ];
    }
}
