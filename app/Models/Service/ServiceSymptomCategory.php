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

    /**
     * Equipment symptom profiles this category is attached to (via the
     * profile↔category pivot). Used for the Problem Library card's distinct
     * equipment-profile count — one row per profile, never inflated by symptom.
     */
    public function profiles()
    {
        return $this->belongsToMany(
            ServiceSymptomProfile::class,
            'service_symptom_profile_categories',
            'service_symptom_category_id',
            'service_symptom_profile_id',
        );
    }
}
