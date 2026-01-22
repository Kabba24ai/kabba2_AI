<?php

namespace App\Http\Controllers\Admin\Configurations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\Configurations\Setting;
use Database\Seeders\Configurations\SettingSeeder;

class SettingsResetController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            // Log::info('Settings reset started.');

            // Truncate settings table
            // Log::info('Truncating settings table...');
            Setting::truncate();
            // Log::info('Settings table truncated.');

            // Rerun the SettingSeeder
            // Log::info('Running SettingSeeder via Artisan...');
            $exitCode = Artisan::call('db:seed', [
                '--class' => SettingSeeder::class,
            ]);
            // Log::info('Seeder executed with exit code: ' . $exitCode);
            // Log::info('Seeder output: ' . Artisan::output());

            return redirect()->back()->with('success', 'Settings have been reset to defaults.');
        } catch (\Exception $e) {
            Log::error('Settings reset failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to reset settings: ' . $e->getMessage());
        }
    }
}
