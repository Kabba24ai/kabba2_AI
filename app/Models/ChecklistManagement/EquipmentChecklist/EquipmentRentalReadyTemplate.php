<?php

namespace App\Models\ChecklistManagement\EquipmentChecklist;

use App\Enums\ChecklistManagement\RentalReadyLifecycleStatus;
use App\Enums\ChecklistManagement\RentalReadyResult;
use App\Helpers\ModelHelper;

use App\Models\MaintenanceManagement\Equipment;

use App\Models\Orders\OrderProduct;


use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EquipmentRentalReadyTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unique_id',
        'equipment_id',
        'employee_id',
        'employee_name',
        'order_id',
        'order_product_id',
        'inspection_date',
        'inspection_time',
        'equipment_hours',
        'general_notes',
        'status',
        // Phase 2A — lifecycle (editability/authority) separate from result (outcome).
        'lifecycle_status',
        'result',
        'completed_at',
        'voided_at',
        'voided_by',
        'void_reason',
        'superseded_by_template_id',
        'completion_idempotency_key',
        'is_complete',
        'total_questions',
        'required_questions',
        'optional_questions',
        'required_items_completed',
        'items_requiring_maintenance',
        'damaged_items',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'lifecycle_status' => RentalReadyLifecycleStatus::class,
        'result' => RentalReadyResult::class,
        'completed_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    /** Completed and not voided — the only authoritative inspections. */
    public function scopeCompleted($query)
    {
        return $query->where('lifecycle_status', RentalReadyLifecycleStatus::Completed->value);
    }

    /** In-progress inspections — surfaced separately, never authoritative. */
    public function scopeDraft($query)
    {
        return $query->where('lifecycle_status', RentalReadyLifecycleStatus::Draft->value);
    }

    /** A terminal (frozen) inspection may never be reused for a later inspection. */
    public function isTerminal(): bool
    {
        return $this->lifecycle_status instanceof RentalReadyLifecycleStatus
            ? $this->lifecycle_status->isTerminal()
            : ($this->lifecycle_status !== RentalReadyLifecycleStatus::Draft->value);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
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

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }

    /** Order-scoped inspections may carry order_id directly (no order product). */
    public function order()
    {
        return $this->belongsTo(\App\Models\Orders\Order::class, 'order_id');
    }

}
