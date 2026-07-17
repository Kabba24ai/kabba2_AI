<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One frozen recipient in a broadcast event's prepared package. Written
 * when the event is queued; the transmission worker only ever reads and
 * updates these rows — it never re-resolves the audience.
 */
class SmsBroadcastRecipient extends Model
{
    protected $fillable = [
        'sms_broadcast_event_id',
        'customer_id',
        'customer_name',
        'phone',
        'status',
        'twilio_sid',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(SmsBroadcastEvent::class, 'sms_broadcast_event_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
