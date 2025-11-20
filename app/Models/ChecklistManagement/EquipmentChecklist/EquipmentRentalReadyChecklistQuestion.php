<?php

namespace App\Models\ChecklistManagement\EquipmentChecklist;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\ModelHelper;

use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion ;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;

use Illuminate\Database\Eloquent\SoftDeletes;


class EquipmentRentalReadyChecklistQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unique_id',
        'equipment_rental_ready_template_id',
        'rental_ready_checklist_questions_id',
        'selected_answer_id',
        'rental_ready_qa_json',
        'general_notes',
    ];

    public function template()
    {
        return $this->belongsTo(EquipmentRentalReadyTemplate::class, 'equipment_rental_ready_template_id');
    }

    public function question()
    {
        return $this->belongsTo(RentalReadyChecklistQuestion::class, 'rental_ready_checklist_questions_id');
    }

    public function selectedAnswer()
    {
        return $this->belongsTo(RentalReadyChecklistQuestionAnswer::class, 'selected_answer_id');
    }

   

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                // Generate a unique ID with prefix 'ERCQ'
                $model->unique_id = ModelHelper::generateUniqueID($model, 'ERCQ');
            }
        });
    }

}
