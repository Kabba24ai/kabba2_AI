<?php

namespace App\Http\Requests\Api\TimeTracker\V1\Attendance;

use App\Http\Requests\ApiBaseFormRequest;

class UpdateGoalRequest extends ApiBaseFormRequest
{
    /**
     * Authorization
     */
    public function authorize(): bool
    {
        return true; // Add policy later if needed
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'goal_name' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:10'],
            'color' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'goal_type' => ['required', 'in:positive,negative'],
            'days_missed_max' => ['required', 'integer', 'min:0'],
            'days_late_max' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'display_order' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Body parameters for API docs
     */
    public function bodyParameters(): array
    {
        return [
            'goal_name' => [
                'description' => 'Achievement goal name',
                'example' => 'Gold Trophy',
            ],
            'icon' => [
                'description' => 'Emoji icon',
                'example' => '🏆',
            ],
            'color' => [
                'description' => 'Hex color code',
                'example' => '#FFD700',
            ],
            'description' => [
                'description' => 'Goal description',
                'example' => 'Perfect attendance for the month',
            ],
            'goal_type' => [
                'description' => 'Goal type (positive or negative)',
                'example' => 'positive',
            ],
            'days_missed_max' => [
                'description' => 'Maximum missed days allowed',
                'example' => 0,
            ],
            'days_late_max' => [
                'description' => 'Maximum late days allowed',
                'example' => 1,
            ],
            'is_active' => [
                'description' => 'Whether goal is active',
                'example' => true,
            ],
            'display_order' => [
                'description' => 'Display order',
                'example' => 1,
            ],
        ];
    }
}
