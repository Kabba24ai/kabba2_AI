<?php

namespace Database\Seeders\Iam;

use Illuminate\Database\Seeder;

use App\Models\Iam\Personnel\VacationDay;

class VacationDaySeeder extends Seeder
{
    public function run(): void
    {
        $days = [
            ['name' => 'Immediate', 'day_number' => 0],
            ['name' => '30 days',   'day_number' => 30],
            ['name' => '60 days',   'day_number' => 60],
            ['name' => '90 days',   'day_number' => 90],
            ['name' => '120 days',  'day_number' => 120],
            ['name' => '180 days',  'day_number' => 180],
            ['name' => '1 year',    'day_number' => 365],
        ];

        foreach ($days as $day) {
            VacationDay::updateOrCreate(
                ['day_number' => $day['day_number']],
                ['name' => $day['name']]
            );
        }
    }
}