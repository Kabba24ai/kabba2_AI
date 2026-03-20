<?php

namespace Database\Seeders\Iam;

use Illuminate\Database\Seeder;
use App\Models\Iam\Personnel\AchievementGoal;

class AchievementGoalSeeder extends Seeder
{
    public function run(): void
    {
        $updateExisting = config('app.seeders.existing_settings_update');

        $goals = [
            [
                'goal_name' => 'Gold Trophy',
                'goal_type' => 'positive',
                'display_order' => 1,
                'icon' => '🏆',
                'color' => '#FFD700',
                'days_missed_max' => 0,
                'days_late_max' => 0,
                'description' => 'Perfect attendance - Zero days missed, zero days late',
                'is_active' => true,
            ],
            [
                'goal_name' => 'Silver Trophy',
                'goal_type' => 'positive',
                'display_order' => 2,
                'icon' => '🥈',
                'color' => '#C0C0C0',
                'days_missed_max' => 0,
                'days_late_max' => 1,
                'description' => 'Excellent attendance - Zero days missed, 1 day late',
                'is_active' => true,
            ],
            [
                'goal_name' => 'Bronze Trophy',
                'goal_type' => 'positive',
                'display_order' => 3,
                'icon' => '🥉',
                'color' => '#CD7F32',
                'days_missed_max' => 0,
                'days_late_max' => 2,
                'description' => 'Great attendance - Zero days missed, 2 days late',
                'is_active' => true,
            ],
            [
                'goal_name' => 'Sad Face',
                'goal_type' => 'negative',
                'display_order' => 4,
                'icon' => '😞',
                'color' => '#FFA500',
                'days_missed_max' => 2,
                'days_late_max' => 5,
                'description' => 'Needs improvement - 2 days missed or 5 days late',
                'is_active' => true,
            ],
            [
                'goal_name' => 'Angry Face',
                'goal_type' => 'negative',
                'display_order' => 5,
                'icon' => '😠',
                'color' => '#FF0000',
                'days_missed_max' => 3,
                'days_late_max' => 8,
                'description' => 'Poor attendance - 3 days missed or 8 days late',
                'is_active' => true,
            ],
        ];

        foreach ($goals as $goal) {

            // Create if not exists
            $item = AchievementGoal::firstOrCreate(
                ['goal_name' => $goal['goal_name']],
                $goal
            );

            // Update only if allowed
            if ($updateExisting && !$item->wasRecentlyCreated) {
                $item->update($goal);
            }
        }
    }
}