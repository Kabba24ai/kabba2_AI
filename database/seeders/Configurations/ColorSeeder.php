<?php

namespace Database\Seeders\Configurations;

use Illuminate\Database\Seeder;


use App\Models\Configurations\Color;


class ColorSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['title' => 'Blue', 'hash_code' => '#3b82f6'],
            ['title' => 'Green', 'hash_code' => '#10b981'],
            ['title' => 'Amber', 'hash_code' => '#f59e0b'],
            ['title' => 'Red', 'hash_code' => '#ef4444'],
            ['title' => 'Purple', 'hash_code' => '#8b5cf6'],
            ['title' => 'Gray', 'hash_code' => '#6b7280'],
        ];

       foreach ($colors as $color) {
            Color::firstOrCreate(['title' => $color['title']], $color);
        }

    }
}
