<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// Seeders
use Database\Seeders\Configurations\SettingSeeder;
use Database\Seeders\Configurations\ColorSeeder;
use Database\Seeders\Configurations\EncryptedSettingSeeder;


// Seeders
use Database\Seeders\Iam\ModuleSeeder;
use Database\Seeders\Iam\RoleSeeder;
use Database\Seeders\Iam\UserSeeder;
use Database\Seeders\Locations\StateSeeder;
use Database\Seeders\MaintenanceManagement\SupplierSeeder;
use Database\Seeders\Stores\StoreSeeder;
use Database\Seeders\TermsAndConditions\TermsSeeder;

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
        $this->call(SettingSeeder::class); // fourth to create settings
        $this->call(EncryptedSettingSeeder::class); // fourth to create settings

        $this->call(StateSeeder::class); // fourth to create settings
        $this->call(StoreSeeder::class); // fourth to create settings
        $this->call(ColorSeeder::class); // fourth to create settings
        $this->call(TermsSeeder::class); // fourth to create settings
        $this->call(SupplierSeeder::class); // fourth to create settings


    }
}
