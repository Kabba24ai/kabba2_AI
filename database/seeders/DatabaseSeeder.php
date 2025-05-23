<?php

namespace Database\Seeders;


// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Database\Seeders\Iam\ModuleSeeder;
use Database\Seeders\Iam\RoleSeeder;
use Illuminate\Database\Seeder;

// Seeders
use Database\Seeders\Iam\UserSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class); // first to create roles
        $this->call(ModuleSeeder::class); // third to create modules
        $this->call(UserSeeder::class); // second to create users
    }
}
