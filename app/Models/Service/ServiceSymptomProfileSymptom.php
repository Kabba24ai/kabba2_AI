<?php

namespace App\Models\Service;

use App\Enums\Service\ServiceSymptomProfileSymptomMode;
use Illuminate\Database\Eloquent\Model;

/** A single per-symptom override (addition or exclusion) on a symptom profile. */
class ServiceSymptomProfileSymptom extends Model
{
    protected $fillable = [
        'service_symptom_profile_id',
        'service_symptom_id',
        'mode',
    ];

    protected $casts = [
        'mode' => ServiceSymptomProfileSymptomMode::class,
    ];

    public function profile()
    {
        return $this->belongsTo(ServiceSymptomProfile::class, 'service_symptom_profile_id');
    }

    public function symptom()
    {
        return $this->belongsTo(ServiceSymptom::class, 'service_symptom_id');
    }
}
