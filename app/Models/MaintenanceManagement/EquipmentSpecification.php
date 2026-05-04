<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Model;

class EquipmentSpecification extends Model
{
    protected $table = 'equipment_specifications';

    protected $fillable = [
        'equipment_id',
        'spec_key',
        'spec_label',
        'value',
        'unit',
        'source_url',
        'confidence_score',
        'last_verified_at',
        'approved_by',
        'approved_at',
        'is_approved',
        'is_manual_override',
        'ai_lookup_payload',
    ];

    protected $casts = [
        'last_verified_at' => 'datetime',
        'approved_at'      => 'datetime',
        'is_approved'      => 'boolean',
        'is_manual_override' => 'boolean',
        'ai_lookup_payload'  => 'array',
        'confidence_score'   => 'float',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(\App\Models\Iam\Personnel\User::class, 'approved_by');
    }
}
