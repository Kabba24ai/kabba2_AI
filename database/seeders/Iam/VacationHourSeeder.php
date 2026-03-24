<?php

namespace Database\Seeders\Iam;

use Illuminate\Database\Seeder;
use App\Models\Iam\Personnel\VacationHour;

class VacationHourSeeder extends Seeder
{
    public function run(): void
    {
        $updateExisting = config('app.seeders.existing_settings_update');

        $hours = range(0, 160, 8);

        foreach ($hours as $hour) {

            $data = [
                'hours' => $hour,
                'name'  => "{$hour} hours",
            ];

            // Create if not exists
            $item = VacationHour::firstOrCreate(
                ['hours' => $hour], // unique key
                $data
            );

            // Update only if allowed
            if ($updateExisting && !$item->wasRecentlyCreated) {
                $item->update([
                    'name' => "{$hour} hours",
                ]);
            }
        }
    }
}