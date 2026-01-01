<?php

namespace Database\Seeders\Iam;

use Illuminate\Database\Seeder;

use App\Models\Iam\Personnel\VacationHour;


class VacationHourSeeder extends Seeder
{
    public function run(): void
    {
        $hours = range(0, 160, 8);

        foreach ($hours as $hour) {
            VacationHour::updateOrCreate(
                ['hours' => $hour],
                ['name' => "{$hour} hours"]
            );
        }
    }
}