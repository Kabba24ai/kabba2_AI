<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

/**
 * Reusable grouping for the symptom library (e.g. "Engine & Starting",
 * "Boom Lift"). Categories may be broadly reusable across many equipment
 * families or specific to one — symptom profiles decide which categories
 * apply to which equipment, not the category itself.
 */
class ServiceSymptomCategory extends Model
{
    protected $fillable = [
        'name',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function symptoms()
    {
        return $this->hasMany(ServiceSymptom::class)->orderBy('display_order');
    }
}
