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
            ],
        ];

        $demoUsers =[
            [
                'first_name' => 'Sue',
                'last_name' => 'Perb',
                'email' => 'sue.perb@kabba.ai',
                'password' => Hash::make('Sue#1234'),
                'status' => 'Active',
            ],
            [
                'first_name' => 'Paige',
                'last_name' => 'Turner',
                'email' => 'paige.turner@kabba.ai',
                'password' => Hash::make('Paige#1234'),
                'status' => 'Active',
            ],
            [
                'first_name' => 'Hank',
                'last_name' => 'Greaser',
                'email' => 'hank.greaser@kabba.ai',
                'password' => Hash::make('Hank#1234'),
                'status' => 'Active',
            ],
            [
                'first_name' => 'Frank',
                'last_name' => 'Leeorganized',
                'email' => 'frank.leeorganized@kabba.ai',
                'password' => Hash::make('Frank#1234'),
                'status' => 'Active',
            ],
            [
                'first_name' => 'Gus',
                'last_name' => 'Wrencher',
                'email' => 'gus.wrencher@kabba.ai',
                'password' => Hash::make('Gus#1234'),
                'status' => 'Active',
            ],
            [
                'first_name' => 'Doug',
                'last_name' => 'Cashman',
                'email' => 'doug.cashman@kabba.ai',
                'password' => Hash::make('Doug#1234'),
                'status' => 'Active',
            ],
            [
                'first_name' => 'Mike',
                'last_name' => 'Hammerly',
                'email' => 'mike.hammerly@kabba.ai',
                'password' => Hash::make('Mike#1234'),
                'status' => 'Active',
            ],
            [
                'first_name' => 'Will',
                'last_name' => 'Booker',
                'email' => 'will.booker@kabba.ai',
                'password' => Hash::make('Will#1234'),
                'status' => 'Active',
            ],
            [
                'first_name' => 'Neil',
                'last_name' => 'Stockman',
                'email' => 'neil.stockman@kabba.ai',
                'password' => Hash::make('Neil#1234'),
                'status' => 'Active',
            ],
            [
                'first_name' => 'Drew',
                'last_name' => 'Ledger',
                'email' => 'drew.ledger@kabba.ai',
                'password' => Hash::make('Drew#1234'),
                'status' => 'Active',
            ],
            [
                'first_name' => 'Cal',
                'last_name' => 'Torkman',
                'email' => 'cal.torkman@kabba.ai',
                'password' => Hash::make('Cal#1234'),
                'status' => 'Active',
            ],
            [
                'first_name' => 'Bill',
                'last_name' => 'Payton',
                'email' => 'bill.payton@kabba.ai',
                'password' => Hash::make('Bill#1234'),
                'status' => 'Active',
            ],
        ];

        if (config('app.demo_enabled') === true) {
            $users = array_merge($users, $demoUsers);
        }

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
