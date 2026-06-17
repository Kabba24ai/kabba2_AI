<?php

namespace App\Models\Dispatch;

use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use Illuminate\Database\Eloquent\Model;

class DispatchAiDraftAssignment extends Model
{
    protected $table = 'dispatch_ai_draft_assignments';

    protected $fillable = [
        'draft_id',
        'order_product_id',
        'slot',
        'recommended_driver_id',
        'recommended_truck_id',
        'recommended_trailer_id',
        'recommended_priority',
        'is_early_delivery',
        'suggested_delivery_date',
        'ai_reasoning',
        'was_applied',
    ];

    protected function casts(): array
    {
        return [
            'is_early_delivery'     => 'boolean',
            'was_applied'           => 'boolean',
            'suggested_delivery_date'=> 'date',
        ];
    }

    public function draft(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DispatchAiDraft::class, 'draft_id');
    }

    public function orderProduct(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }

    public function recommendedDriver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'recommended_driver_id');
    }

    public function recommendedTruck(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DispatchAiTruck::class, 'recommended_truck_id');
    }

    public function recommendedTrailer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DispatchAiTrailer::class, 'recommended_trailer_id');
    }
}
