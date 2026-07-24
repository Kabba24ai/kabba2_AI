<?php

namespace App\Models\Global;

use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $table = 'api_logs';

    protected $fillable = [
        'service_name',
        'name',
        'order_id',
        'order_product_id',
        'method',
        'endpoint',
        'request_params',
        'response_code',
        'response_json',
        'status',
        'error_message',
        'requested_at',
        'responded_at',
        'duration_ms',
    ];

    protected $casts = [
        'request_params' => 'array',
        'response_json' => 'array',
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    // =====================
    // Relations
    // =====================

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class);
    }

    // =====================
    // Scopes
    // =====================

    public function scopeByService($query, $serviceName)
    {
        return $query->where('service_name', $serviceName);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // =====================
    // Methods
    // =====================

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'success' => 'success',
            'failed' => 'danger',
            'pending' => 'warning',
            default => 'secondary',
        };
    }
}
