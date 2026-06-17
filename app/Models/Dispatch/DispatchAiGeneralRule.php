<?php

namespace App\Models\Dispatch;

use Illuminate\Database\Eloquent\Model;

class DispatchAiGeneralRule extends Model
{
    protected $table = 'dispatch_ai_general_rules';

    protected $fillable = [
        'rule_type',
        'rule_label',
        'rule_value',
        'sort_order',
        'is_active',
        'prefer_same_driver_for_returns',
    ];

    protected function casts(): array
    {
        return [
            'is_active'                     => 'boolean',
            'prefer_same_driver_for_returns' => 'boolean',
        ];
    }
}
