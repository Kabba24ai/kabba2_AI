<?php

namespace Database\Seeders\Stores;

use App\Models\Stores\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $storeData = [
            [
                'store_name' => 'Bon Aqua',
                'email'      => 'sales@RentnKing.com',
                'phone'      => '(615) 815-6734',
                'address'    => '10296 High 46',
                'state_id'      => 43,
                'city'       => 'Bon Aqua',
                'is_primary' => 'Yes',
                'country'    => 'USA',
            ],
            [
                'store_name' => 'Charlotte',
                'email'      => 'Sales@RentnKing.com',
                'phone'      => '(615) 815-6734',
                'address'    => '4385 SR-48',
                'state_id'      => 43,
                'city'       => 'Charlotte',
                'is_primary' => 'No',
                'country'    => 'USA',
            ],
        ];

        $updateFlag = false;

        foreach ($storeData as $store) {
            $model = Store::firstOrCreate(
                ['store_name' => $store['store_name']],
                $store
            );

            // If it already existed, update it with the latest data
            if ($updateFlag) {
                $model->update($store);
            }
        }
    }
}
