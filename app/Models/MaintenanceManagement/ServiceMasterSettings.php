<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceMasterSettings extends Model
{
    use HasFactory;

    protected $table = 'service_master_settings';

    protected $fillable = [
        'pending_before_hours',
        'pending_after_hours',
        'pending_before_dates',
        'pending_after_dates',
        'master_admin_code'
    ];

    protected $casts = [
        'pending_before_hours' => 'integer',
        'pending_after_hours' => 'integer',
        'pending_before_dates' => 'integer',
        'pending_after_dates' => 'integer'
    ];
}