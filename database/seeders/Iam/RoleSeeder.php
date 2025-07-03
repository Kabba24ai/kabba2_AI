<?php

namespace Database\Seeders\Iam;

use Illuminate\Database\Seeder;

// Models
use App\Models\Iam\AccessControl\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $objRole = Role::firstOrCreate([
            'name' => 'System Architecture',
            'guard_name' => 'web',
        ]);

        $objRole->update([
            'short_name' => 'SA',
            'status' => 'Active',
        ]);
    }
}
