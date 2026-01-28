<?php

namespace Database\Seeders\Locations;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Stores\EmploymentPosition;


class EmploymentPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
            EmploymentPosition::create($position);
        }
    }
}
