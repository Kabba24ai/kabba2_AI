<?php

namespace Database\Seeders\Locations;

use Illuminate\Database\Seeder;
use App\Models\Stores\EmploymentPosition;

class EmploymentPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Read flag from .env
        $updateExisting = config('app.seeders.existing_settings_update');

        $positions = [
            [
                'title' => 'Store Manager',
                'description' => 'Oversee store operations',
                'is_active' => true,
            ],
            [
                'title' => 'Diesel Mechanic',
                'description' => 'Maintain and repair diesel equipment',
                'is_active' => true,
            ],
            [
                'title' => 'Delivery Driver',
                'description' => 'Deliver equipment to customers',
                'is_active' => true,
            ],
        ];

        foreach ($positions as $position) {

            // Create if not exists
            $item = EmploymentPosition::firstOrCreate(
                ['title' => $position['title']], // unique key
                $position
            );

            // Update only if allowed AND record already exists
            if ($updateExisting && !$item->wasRecentlyCreated) {
                $item->update([
                    'description' => $position['description'],
                    'is_active'   => $position['is_active'],
                ]);
            }
        }
    }
}