<?php

namespace App\Models\Dispatch;

use App\Models\Stores\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DispatchAiTruck extends Model
{
    use SoftDeletes;

    protected $table = 'dispatch_ai_trucks';

    protected $fillable = [
        'unique_id',
        'truck_type',
        'truck_name',
        'truck_number',
        'store_id',
        'gvwr',
        'tow_rating',
        'hitch_types',
        'cdl_required',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'hitch_types'  => 'array',
            'cdl_required' => 'boolean',
            'is_active'    => 'boolean',
            'gvwr'         => 'decimal:2',
            'tow_rating'   => 'decimal:2',
        ];
    }

    public function store(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $truck) {
            if (empty($truck->unique_id)) {
                $truck->unique_id = 'TRK-' . strtoupper(\Illuminate\Support\Str::random(8));
            }
        });
    }
}
