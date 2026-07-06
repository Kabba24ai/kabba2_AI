<?php

namespace App\Models\WaitList;

use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Database\Eloquent\Model;

/** A specific equipment unit on a wait list (max 3 per record, validated). */
class EquipmentWaitListItem extends Model
{
    protected $table = 'equipment_wait_list_items';

    protected $fillable = ['equipment_wait_list_id', 'equipment_id'];

    public function waitList()
    {
        return $this->belongsTo(EquipmentWaitList::class, 'equipment_wait_list_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
