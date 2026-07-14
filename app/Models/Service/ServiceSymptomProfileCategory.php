<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

/** One "Included Category" on a symptom profile — bulk-draws the whole category's active symptoms. */
class ServiceSymptomProfileCategory extends Model
{
    protected $fillable = [
        'service_symptom_profile_id',
        'service_symptom_category_id',
    ];

    public function profile()
    {
        return $this->belongsTo(ServiceSymptomProfile::class, 'service_symptom_profile_id');
    }

    public function category()
    {
        return $this->belongsTo(ServiceSymptomCategory::class, 'service_symptom_category_id');
    }
}
