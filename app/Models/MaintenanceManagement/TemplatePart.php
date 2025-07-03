<?php

namespace App\Models\MaintenanceManagement;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TemplatePart extends Pivot
{
    protected $table = 'template_parts';

    protected $fillable = [
        'template_id',
        'part_id',
        'sort_order'
    ];

    protected $casts = [
        'sort_order' => 'integer'
    ];
}
