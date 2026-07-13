<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

/**
 * One reusable symptom, belonging to exactly one category, selectable by
 * any number of equipment symptom profiles. Reported at intake — this is
 * never itself a diagnosis. ServiceTicketComplaint snapshots the name and
 * category label at selection time, so renaming or retiring a symptom here
 * never rewrites history.
 */
class ServiceSymptom extends Model
{
    protected $fillable = [
        'service_symptom_category_id',
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

    public function category()
    {
        return $this->belongsTo(ServiceSymptomCategory::class, 'service_symptom_category_id');
    }
}
