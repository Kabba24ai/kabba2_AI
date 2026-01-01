<?php

namespace App\Http\Resources\Api\TimeTracker\V1\TimeClock;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AchievementGoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'goal_name' => $this->goal_name,
            'icon' => $this->icon,
            'color' => $this->color,
            'description' => $this->description,
            'goal_type' => $this->goal_type,
            'days_missed_max' => (int) $this->days_missed_max,
            'days_late_max' => (int) $this->days_late_max,
            'is_active' => (bool) $this->is_active,
            'display_order' => (int) $this->display_order,
            
        ];
    }
}

