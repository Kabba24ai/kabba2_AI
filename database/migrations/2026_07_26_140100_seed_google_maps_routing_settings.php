<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

/**
 * Materializes the global "Google Maps & Routing" integration settings rows in
 * the shared `settings` key-value store. Seeded via a migration (not the
 * SettingSeeder) because deploys run migrations, not seeders — this guarantees
 * the rows exist in production so the Configurations tab and the shared routing
 * config provider have something to read.
 *
 * Idempotent: firstOrCreate keyed on (setting_type, setting_name). The API key
 * row is created WITHOUT a value (is_encrypted) so the integration reports
 * "not configured" until an operator enters a key — no fake/placeholder secret.
 */
return new class extends Migration
{
    private const GROUP = 'Google Maps Settings';

    public function up(): void
    {
        $rows = [
            [
                'setting_name'  => 'google_maps_enabled',
                'setting_title' => 'Enable Google Maps & Routing',
                'value_type'    => 'boolean',
                'setting_value' => '0',
                'placeholder'   => null,
                'secure'        => false,
            ],
            [
                'setting_name'  => 'google_maps_provider',
                'setting_title' => 'Routing Provider',
                'value_type'    => 'text',
                'setting_value' => 'google',
                'placeholder'   => 'google',
                'secure'        => false,
            ],
            [
                'setting_name'  => 'google_maps_api_key',
                'setting_title' => 'Google Maps API Key',
                'value_type'    => 'text',
                'setting_value' => null,               // never seed a secret
                'placeholder'   => 'Enter Google Maps API key',
                'secure'        => true,
            ],
            [
                'setting_name'  => 'google_maps_traffic_aware',
                'setting_title' => 'Traffic-Aware Routing',
                'value_type'    => 'boolean',
                'setting_value' => '1',
                'placeholder'   => null,
                'secure'        => false,
            ],
            [
                'setting_name'  => 'google_maps_timeout',
                'setting_title' => 'Request Timeout (seconds)',
                'value_type'    => 'text',
                'setting_value' => '15',
                'placeholder'   => '15',
                'secure'        => false,
            ],
            [
                'setting_name'  => 'google_maps_units',
                'setting_title' => 'Default Units',
                'value_type'    => 'text',
                'setting_value' => 'imperial',
                'placeholder'   => 'imperial | metric',
                'secure'        => false,
            ],
            // Read-only diagnostic rows written by Test Connection.
            [
                'setting_name'  => 'google_maps_connection_status',
                'setting_title' => 'Connection Status',
                'value_type'    => 'text',
                'setting_value' => 'not_tested',
                'placeholder'   => null,
                'secure'        => false,
            ],
            [
                'setting_name'  => 'google_maps_last_tested_at',
                'setting_title' => 'Last Tested',
                'value_type'    => 'text',
                'setting_value' => null,
                'placeholder'   => null,
                'secure'        => false,
            ],
            [
                'setting_name'  => 'google_maps_last_success_at',
                'setting_title' => 'Last Successful Connection',
                'value_type'    => 'text',
                'setting_value' => null,
                'placeholder'   => null,
                'secure'        => false,
            ],
        ];

        $sortOrder = 0;
        foreach ($rows as $row) {
            $create = [
                'unique_id'       => (string) Str::uuid(),
                'setting_title'   => $row['setting_title'],
                'value_type'      => $row['value_type'],
                'setting_options' => null,
                'placeholder'     => $row['placeholder'],
                'is_secure_field' => $row['secure'] ? 1 : 0,
                'is_eye_toggle'   => $row['secure'] ? 1 : 0,
                'is_required'     => $row['setting_name'] === 'google_maps_api_key' ? 1 : 0,
                'is_encrypted'    => $row['secure'] ? 1 : 0,
                'sort_order'      => $sortOrder++,
            ];

            // Only supply a value when there is one — routing the assignment
            // through the model so the encryption mutator fires for secret rows.
            if ($row['setting_value'] !== null) {
                $create['setting_value'] = $row['setting_value'];
            }

            Setting::firstOrCreate(
                ['setting_type' => self::GROUP, 'setting_name' => $row['setting_name']],
                $create,
            );
        }
    }

    public function down(): void
    {
        Setting::where('setting_type', self::GROUP)->delete();
    }
};
