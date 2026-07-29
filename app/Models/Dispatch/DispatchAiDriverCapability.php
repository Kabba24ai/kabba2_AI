<?php

namespace App\Models\Dispatch;

use App\Models\Iam\Personnel\User;
use App\Models\Stores\Store;
use Illuminate\Database\Eloquent\Model;

class DispatchAiDriverCapability extends Model
{
    protected $table = 'dispatch_ai_driver_capabilities';

    protected $fillable = [
        'user_id',
        'cdl_license',
        'max_gvwr',
        'max_trailer_weight',
        'can_tow_equipment_trailer',
        'can_tow_gooseneck',
        'can_operate_cdl_truck',
        'home_store_id',
        'skill_rating',
        'designation',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cdl_license'               => 'boolean',
            'can_tow_equipment_trailer' => 'boolean',
            'can_tow_gooseneck'         => 'boolean',
            'can_operate_cdl_truck'     => 'boolean',
            'is_active'                 => 'boolean',
            'max_gvwr'                  => 'decimal:2',
            'max_trailer_weight'        => 'decimal:2',
        ];
    }

    public function driver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function homeStore(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Store::class, 'home_store_id');
    }
}
