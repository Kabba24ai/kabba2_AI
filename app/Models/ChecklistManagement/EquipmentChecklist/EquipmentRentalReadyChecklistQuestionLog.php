<?php

namespace App\Models\ChecklistManagement\EquipmentChecklist;

use App\Helpers\ModelHelper;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Iam\Personnel\User;

class EquipmentRentalReadyChecklistQuestionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'equipment_checklist_question_id',
        'equipment_rental_ready_template_id',
        'rental_ready_all_qa_json',
        'action_by',
        'action_user_name',
    ];

 
    public function checklistQuestion()
    {
        return $this->belongsTo(EquipmentRentalReadyChecklistQuestion::class, 'equipment_checklist_question_id');
    }

    public function template()
    {
        return $this->belongsTo(EquipmentRentalReadyTemplate::class, 'equipment_rental_ready_template_id');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                // Generate a unique ID with prefix 'ECQL'
                $model->unique_id = ModelHelper::generateUniqueID($model, 'ECQL');
            }
        });
    }

    public function actionUser()
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}