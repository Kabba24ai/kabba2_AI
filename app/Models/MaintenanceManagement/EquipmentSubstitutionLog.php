<?php

namespace App\Models\MaintenanceManagement;

use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use Illuminate\Database\Eloquent\Model;

class EquipmentSubstitutionLog extends Model
{
    protected $fillable = [
        'order_product_id',
        'original_equipment_id',
        'substitute_equipment_id',
        'overall_score',
        'spec_score',
        'rule_score',
        'compatibility_score',
        'requires_manager_approval',
        'approval_reason',
        'rule_violations',
        'rule_supports',
        'spec_comparison',
        'evaluation_context',
        'evaluated_by',
    ];

    protected $casts = [
        'rule_violations'          => 'array',
        'rule_supports'            => 'array',
        'spec_comparison'          => 'array',
        'evaluation_context'       => 'array',
        'requires_manager_approval' => 'boolean',
        'overall_score'            => 'float',
        'spec_score'               => 'float',
        'rule_score'               => 'float',
        'compatibility_score'      => 'float',
    ];

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class);
    }

    public function originalEquipment()
    {
        return $this->belongsTo(\App\Models\MaintenanceManagement\Equipment::class, 'original_equipment_id');
    }

    public function substituteEquipment()
    {
        return $this->belongsTo(\App\Models\MaintenanceManagement\Equipment::class, 'substitute_equipment_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    public function approvalLevel(): string
    {
        if ($this->overall_score >= 80) return 'none';
        if ($this->overall_score >= 60) return 'recommended';
        return 'required';
    }
}
