<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentServiceTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'equipment_service_tasks';

    protected $fillable = [
        'equipment_id',
        'service_template_id',
        'service_task_id',
        'interval_value',
        'interval_type',
        'performed_by',
        'checked_by',
        'performed_date',
        'checked_date',
        'actual_hours',
        'notes',
    ];

    protected $casts = [
        'performed_date' => 'date',
        'checked_date' => 'date',
        'actual_hours' => 'decimal:2',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

}
