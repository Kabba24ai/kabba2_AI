<?php
namespace Database\Seeders\Iam;

use Illuminate\Database\Seeder;
use App\Models\Iam\Personnel\VacationRequestHour;

class VacationRequestHoursSeeder extends Seeder
{
    public function run(): void
    {
        
      $hours = [
            ['name' => '1 Hour', 'hours' => 1],
            ['name' => '2 Hours', 'hours' => 2],
            ['name' => '3 Hours', 'hours' => 3],
            ['name' => '4 Hours (Half day)', 'hours' => 4],
            ['name' => '5 Hours', 'hours' => 5],
            ['name' => '6 Hours', 'hours' => 6],
            ['name' => '7 Hours', 'hours' => 7],
            ['name' => '8 Hours (Full day)', 'hours' => 8],

            ['name' => '2 Days', 'hours' => 16],
            ['name' => '3 Days', 'hours' => 24],
            ['name' => '4 Days', 'hours' => 32],
            ['name' => '5 Days', 'hours' => 40],
            ['name' => '6 Days', 'hours' => 48],
            ['name' => '7 Days', 'hours' => 56],
            ['name' => '8 Days', 'hours' => 64],
            ['name' => '9 Days', 'hours' => 72],
            ['name' => '10 Days', 'hours' => 80],
        ];


        foreach ($hours as $hour) {
            VacationRequestHour::updateOrCreate(
                ['hours' => $hour['hours']],
                $hour
            );
        }
    }
}
