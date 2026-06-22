<?php

namespace App\Models\Orders;

use App\Enums\Orders\PodPaymentLinkEvent;
use Illuminate\Database\Eloquent\Model;

class PodPaymentLinkActivity extends Model
{
    protected $fillable = [
        'pod_payment_link_id',
        'order_id',
        'event',
        'metadata',
    ];

    protected $casts = [
        'event'    => PodPaymentLinkEvent::class,
        'metadata' => 'array',
    ];

    public function podPaymentLink()
    {
        return $this->belongsTo(PodPaymentLink::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
