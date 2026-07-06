<?php

namespace App\Models\WaitList;

use App\Enums\WaitList\WaitListCommunicationType;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Manually logged contact attempt or outcome. Staff-entered only — the
 * system never contacts customers automatically.
 */
class EquipmentWaitListCommunication extends Model
{
    protected $table = 'equipment_wait_list_communications';

    protected $fillable = ['equipment_wait_list_id', 'user_id', 'type', 'note'];

    protected $casts = [
        'type' => WaitListCommunicationType::class,
    ];

    public function waitList()
    {
        return $this->belongsTo(EquipmentWaitList::class, 'equipment_wait_list_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
