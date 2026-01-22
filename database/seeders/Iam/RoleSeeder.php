<?php

namespace Database\Seeders\Iam;

use Illuminate\Database\Seeder;
use App\Models\Iam\AccessControl\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Remove all other roles first
        Role::query()->delete();

        // Roles to insert
        $roles = [
            [
                'name' => 'Master Admin',
                'short_name' => 'MA',
                'color' => '#ef4444',
                'description' => 'Full access to all system features and settings.',
                'status' => 'Active',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Admin',
                'short_name' => 'AD',
                'color' => '#3b82f6',
                'description' => 'Manage core administrative tasks.',
                'status' => 'Active',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Sales',
                'short_name' => 'SA',
                'color' => '#10b981',
                'description' => 'Access to sales modules and client management.',
                'status' => 'Active',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Technician',
                'short_name' => 'TE',
                'color' => '#8b5cf6',
                'description' => 'Handle technical operations and support.',
                'status' => 'Active',
                'guard_name' => 'web',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
