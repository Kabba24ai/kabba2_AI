<?php

namespace App\Models\Stores;

use Illuminate\Database\Eloquent\Model;

class StoreServiceArea extends Model
{
    protected $table = 'store_service_areas';

    protected $fillable = [
        'store_id',
        'area_group',
        'area_type',
        'name',
        'city',
        'county',
        'state',
        'zip_code',
        'radius_miles',
        'delivery_allowed',
        'pickup_allowed',
        'notes',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'radius_miles'     => 'float',
        'delivery_allowed' => 'boolean',
        'pickup_allowed'   => 'boolean',
        'is_active'        => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRadius($query)
    {
        return $query->where('area_group', 'radius');
    }

    public function scopeIncluded($query)
    {
        return $query->where('area_group', 'included');
    }

    public function scopeExcluded($query)
    {
        return $query->where('area_group', 'excluded');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getDisplayLabelAttribute(): string
    {
        $parts = array_filter([$this->name, $this->city, $this->county, $this->state, $this->zip_code]);
        return implode(', ', $parts) ?: '—';
    }
}
