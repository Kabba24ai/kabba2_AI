<?php

namespace App\Models\Dispatch;

use App\Models\Stores\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DispatchAiTrailer extends Model
{
    use SoftDeletes;

    protected $table = 'dispatch_ai_trailers';

    protected $fillable = [
        'unique_id',
        'trailer_name',
        'trailer_number',
        'store_id',
        'gvwr',
        'payload_capacity',
        'deck_length',
        'deck_width',
        'hitch_type',
        'cdl_required',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cdl_required'    => 'boolean',
            'is_active'       => 'boolean',
            'gvwr'            => 'decimal:2',
            'payload_capacity'=> 'decimal:2',
            'deck_length'     => 'decimal:2',
            'deck_width'      => 'decimal:2',
        ];
    }

    public function store(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $trailer) {
            if (empty($trailer->unique_id)) {
                $trailer->unique_id = 'TRL-' . strtoupper(\Illuminate\Support\Str::random(8));
            }
        });
    }
}
