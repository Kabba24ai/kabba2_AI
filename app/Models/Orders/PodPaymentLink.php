<?php

namespace App\Models\Orders;

use App\Enums\Orders\PodPaymentLinkEvent;
use App\Enums\Orders\PodPaymentLinkStatus;
use Illuminate\Database\Eloquent\Model;

class PodPaymentLink extends Model
{
    protected $fillable = [
        'order_id',
        'payment_link_token',
        'pod_status',
        'payment_link_created_at',
        'payment_link_opened_at',
        'payment_link_open_count',
        'pod_reminder_1_sent_at',
        'pod_reminder_2_sent_at',
        'pod_reminder_3_sent_at',
        'pod_reminder_4_sent_at',
        'pod_expired_at',
        'pod_reactivated_at',
        'pod_payment_completed_at',
    ];

    protected $casts = [
        'pod_status'                 => PodPaymentLinkStatus::class,
        'payment_link_created_at'    => 'datetime',
        'payment_link_opened_at'     => 'datetime',
        'pod_reminder_1_sent_at'     => 'datetime',
        'pod_reminder_2_sent_at'     => 'datetime',
        'pod_reminder_3_sent_at'     => 'datetime',
        'pod_reminder_4_sent_at'     => 'datetime',
        'pod_expired_at'             => 'datetime',
        'pod_reactivated_at'         => 'datetime',
        'pod_payment_completed_at'   => 'datetime',
        'payment_link_open_count'    => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function activities()
    {
        return $this->hasMany(PodPaymentLinkActivity::class)->latest('id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Record an event and keep the flat timestamp / status columns in sync.
     */
    public function recordEvent(PodPaymentLinkEvent $event, array $metadata = []): PodPaymentLinkActivity
    {
        $now = now();

        match ($event) {
            PodPaymentLinkEvent::LinkCreated => $this->payment_link_created_at ??= $now,

            PodPaymentLinkEvent::LinkOpened => (function () use ($now) {
                $this->payment_link_opened_at ??= $now;
                $this->payment_link_open_count++;
                if ($this->pod_status === PodPaymentLinkStatus::Pending) {
                    $this->pod_status = PodPaymentLinkStatus::Opened;
                }
            })(),

            PodPaymentLinkEvent::Reminder1Sent    => $this->pod_reminder_1_sent_at ??= $now,
            PodPaymentLinkEvent::Reminder2Sent    => $this->pod_reminder_2_sent_at ??= $now,
            PodPaymentLinkEvent::Reminder3Sent    => $this->pod_reminder_3_sent_at ??= $now,
            PodPaymentLinkEvent::Reminder4Sent    => $this->pod_reminder_4_sent_at ??= $now,

            PodPaymentLinkEvent::PaymentCompleted => (function () use ($now) {
                $this->pod_payment_completed_at ??= $now;
                $this->pod_status = PodPaymentLinkStatus::Completed;
            })(),

            PodPaymentLinkEvent::OrderExpired => (function () use ($now) {
                $this->pod_expired_at ??= $now;
                $this->pod_status = PodPaymentLinkStatus::Expired;
            })(),

            PodPaymentLinkEvent::OrderReactivated => (function () use ($now) {
                $this->pod_reactivated_at = $now;
                $this->pod_status = PodPaymentLinkStatus::Reactivated;
            })(),
        };

        $this->save();

        return $this->activities()->create([
            'order_id' => $this->order_id,
            'event'    => $event->value,
            'metadata' => $metadata ?: null,
        ]);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereNotIn('pod_status', [
            PodPaymentLinkStatus::Completed->value,
            PodPaymentLinkStatus::Expired->value,
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('pod_status', PodPaymentLinkStatus::Completed->value);
    }
}
