<?php

namespace Database\Seeders\Configurations;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Configurations\Setting;

class EncryptedSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'value_type'      => 'password',
                'setting_name'    => 'payment_api_key',
            ],
            [
                'value_type'      => 'password',
                'setting_name'    => 'master_passcode',
              ],
        ];

        foreach ($settings as $data) {
            Setting::updateOrCreate(
                ['setting_name' => $data['setting_name']], // search by unique name
                $data
            );
        }
    }
}
