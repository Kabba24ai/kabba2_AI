<?php

namespace App\Models\Orders;

use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use Database\Factories\Orders\PaymentShortLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentShortLink extends Model
{
    use HasFactory;

    protected static function newFactory(): PaymentShortLinkFactory
    {
        return PaymentShortLinkFactory::new();
    }

    protected $fillable = [
        'token',
        'order_id',
        'customer_id',
        'created_by',
        'original_url',
        'expires_at',
        'used_at',
        'clicks',
        'max_clicks',
        'last_clicked_at',
        'last_clicked_ip',
        'last_clicked_user_agent',
    ];

    protected $casts = [
        'expires_at'       => 'datetime',
        'used_at'          => 'datetime',
        'last_clicked_at'  => 'datetime',
        'clicks'           => 'integer',
        'max_clicks'       => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── State helpers ─────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasReachedMaxClicks(): bool
    {
        return $this->max_clicks !== null && $this->clicks >= $this->max_clicks;
    }

    public function isActive(): bool
    {
        return !$this->isExpired() && !$this->hasReachedMaxClicks();
    }

    // ── Click tracking ────────────────────────────────────────────────────────

    public function recordClick(string $ip, ?string $userAgent): void
    {
        $this->increment('clicks');

        $this->update([
            'used_at'               => $this->used_at ?? now(),
            'last_clicked_at'       => now(),
            'last_clicked_ip'       => $ip,
            'last_clicked_user_agent' => $userAgent,
        ]);
    }
}
