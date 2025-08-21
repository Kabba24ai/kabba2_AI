<?php

namespace App\Models\ChecklistManagement\RentalReady;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalReadyChecklistTemplateQuestion extends Model
{
     use HasFactory;

    protected $fillable = [
        'template_id',
        'question_id',
        'index_number',
        'unique_id',
    ];

    public function template()
    {
        return $this->belongsTo(RentalReadyChecklistTemplate::class, 'template_id');
    }

    public function question()
    {
        return $this->belongsTo(RentalReadyChecklistQuestion::class, 'question_id');
    }
}
