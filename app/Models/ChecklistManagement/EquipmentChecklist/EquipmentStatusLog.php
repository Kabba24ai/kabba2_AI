<?php

namespace App\Models\ChecklistManagement\EquipmentChecklist;

use Illuminate\Database\Eloquent\Model;
use App\Models\MaintenanceManagement\Equipment;

use App\Models\Iam\Personnel\User;

class EquipmentStatusLog extends Model
{
    protected $fillable = [
        'equipment_id',
        'from_status',
        'to_status',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
