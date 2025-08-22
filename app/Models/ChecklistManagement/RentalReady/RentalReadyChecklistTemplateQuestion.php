<?php

namespace App\Models\ChecklistManagement\RentalReady;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\ModelHelper;

class RentalReadyChecklistTemplateQuestion extends Model
{
     use HasFactory;

    protected $fillable = [
        'template_id',
        'question_id',
        'index_number',
        'unique_id',
    ];

     protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                // Generate a unique ID with prefix 'TQST'
                $model->unique_id = ModelHelper::generateUniqueID($model, 'TQST');
            }
        });
    }

    public function template()
    {
        return $this->belongsTo(RentalReadyChecklistTemplate::class, 'template_id');
    }

    public function question()
    {
        return $this->belongsTo(RentalReadyChecklistQuestion::class, 'question_id');
    }

    
}
