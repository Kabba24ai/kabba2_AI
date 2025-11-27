<?php

namespace App\Models\MaintenanceManagement;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;


class ListsPart extends Pivot
{
     protected $table = 'lists_parts';

    protected $fillable = [
        'parts_list_id',
        'part_id',
        'sort_order'
    ];

    protected $casts = [
        'sort_order' => 'integer'
    ];
}
