<?php

namespace App\Models\Global;

use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Model;

class OpenAILog extends Model
{
    protected $table = 'openai_logs';

    protected $fillable = [
        'request_type',
        'status',
        'sent_data',
        'received_data',
        'error_message',
        'error_code',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'response_time_ms',
        'openai_request_id',
        'model',
        'endpoint',
        'ip_address',
        'notes',
    ];

    protected $casts = [
        'sent_data' => 'array',
        'received_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // =====================
    // Scopes
    // =====================

    /**
     * Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Filter successful requests
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Filter failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Filter by request type
     */
    public function scopeByRequestType($query, $type)
    {
        return $query->where('request_type', $type);
    }

    /**
     * Filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Filter by model
     */
    public function scopeByModel($query, $model)
    {
        return $query->where('model', $model);
    }

    // =====================
    // Methods
    // =====================

    /**
     * Calculate total cost (if applicable, can be extended based on OpenAI pricing)
     * Returns cost in dollars
     */
    public function calculateCost()
    {
        // Pricing structure (example for GPT-4)
        $pricing = [
            'gpt-4' => [
                'prompt' => 0.03,      // $0.03 per 1K prompt tokens
                'completion' => 0.06,  // $0.06 per 1K completion tokens
            ],
            'gpt-3.5-turbo' => [
                'prompt' => 0.0005,    // $0.0005 per 1K prompt tokens
                'completion' => 0.0015, // $0.0015 per 1K completion tokens
            ],
        ];

        if (!isset($pricing[$this->model]) || !$this->prompt_tokens || !$this->completion_tokens) {
            return null;
        }

        $prices = $pricing[$this->model];
        $promptCost = ($this->prompt_tokens / 1000) * $prices['prompt'];
        $completionCost = ($this->completion_tokens / 1000) * $prices['completion'];

        return round($promptCost + $completionCost, 4);
    }

    /**
     * Get human-readable status badge color
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'success' => 'success',
            'failed' => 'danger',
            'pending' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Check if request was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if request failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
