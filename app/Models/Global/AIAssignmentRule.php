<?php

namespace App\Models\Global;

use Illuminate\Database\Eloquent\Model;

class AIAssignmentRule extends Model
{
    protected $table = 'ai_assignment_rules';

    protected $fillable = [
        'product_id',
        'product_name',
        'equipment_id',
        'equipment_name',
        'relationship_type',
        'actions_required',
        'notes',
        'active',
    ];

    protected $casts = [
        'actions_required' => 'array',
        'active' => 'boolean',
    ];
}

