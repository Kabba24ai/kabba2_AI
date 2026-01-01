<?php

namespace App\Models\Configurations;

use Illuminate\Database\Eloquent\Model;

class TimeTrackerSetting extends Model
{
    protected $fillable = ['settings'];

    protected $casts = [
        'settings' => 'array',
    ];
}
