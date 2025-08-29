<?php

namespace App\Models\ChecklistManagement\EquipmentChecklist;

use App\Helpers\ModelHelper;

use App\Models\MaintenanceManagement\Equipment;
use App\Models\Iam\Personnel\User;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EquipmentRentalReadyTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'equipment_id',
        'employee_id',
        'employee_name',
        'order_id',
        'inspection_date',
        'inspection_time',
        'equipment_hours',
        'general_notes',
        'status',
        'total_questions',
        'required_questions',
        'optional_questions',
        'required_items_completed',
        'items_requiring_maintenance',
        'damaged_items',
        'created_by',
        'updated_by',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                // Generate a unique ID with prefix 'ERTL'
                $model->unique_id = ModelHelper::generateUniqueID($model, 'ERTL');
            }
        });
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }


    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function checklistQuestions()
    {
        return $this->hasMany(EquipmentRentalReadyChecklistQuestion::class, 'equipment_rental_ready_template_id');
    }

    public function logs()
    {
        return $this->hasMany(EquipmentRentalReadyChecklistQuestionLog::class, 'equipment_rental_ready_template_id');
    }

}
