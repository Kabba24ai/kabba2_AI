<?php

namespace Database\Seeders\Iam;

use Illuminate\Database\Seeder;
use Hash;

// Models
use App\Models\Iam\Personnel\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'first_name' => 'Raj',
                'last_name' => 'Chotaliya',
                'email' => 'rajkc.webdev@gmail.com',
                'password' => Hash::make('Raj#1234'),
                'status' => 'Active',
            ],
            [
                'first_name' => 'Jigar',
                'last_name' => 'Khatri',
                'email' => 'jigar.khatri@kabba.ai',
                'password' => Hash::make('Jigar#1234'),
                'status' => 'Active',
            ],
            [
                'first_name' => 'Nipa',
                'last_name' => 'Soni',
                'email' => 'nipa.soni@kabba.ai',
                'password' => Hash::make('Nipa#1234'),
                'status' => 'Active',
            ],
            [
                'first_name' => 'Gary',
                'last_name' => 'Jezorski',
                'email' => 'gary.jezorski@kabba.ai',
                'password' => Hash::make('Gary#1234'),
                'status' => 'Active',
            ],
            [
                'first_name' => 'Akshay',
                'last_name' => 'Vyas',
                'email' => 'akshay@kabba.ai',
                'password' => Hash::make('Akshay#1234'),
                'status' => 'Active',
            ],
            [
                'first_name' => 'kabba',
                'last_name' => 'admin',
                'email' => 'admin@kabba.ai',
                'password' => Hash::make('K@bba!9XrT#L2pQ'),
                'status' => 'Active',
            ]
        ];



        $role_item = \Spatie\Permission\Models\Role::orderBy('id', 'ASC')->first();

        if (is_array($users) && count($users) > 0) {
            foreach ($users as $item) {
                $objUser = User::where('email', $item['email'])->first();
                if (is_null($objUser)) {
                    $objUser = User::create($item);
                }
                $objUser->syncRoles([$role_item->name]);
            }
        }
    }
}
