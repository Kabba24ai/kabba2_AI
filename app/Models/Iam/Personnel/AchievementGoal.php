<?php

namespace App\Models\Iam\Personnel;

use Illuminate\Database\Eloquent\Model;

class AchievementGoal extends Model
{
    protected $fillable = [
        'goal_name',
        'icon',
        'color',
        'description',
        'goal_type',
        'days_missed_max',
        'days_late_max',
        'is_active',
        'display_order',
    ];
}
