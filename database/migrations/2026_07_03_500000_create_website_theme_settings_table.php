<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('group', 50)->index();
            $table->timestamps();
        });

        // Bridge: pull existing branding values from legacy settings table
        // Mapping: old setting_name → new theme key
        $brandingMap = [
            'site_logo'         => 'logo_primary',
            'site_favicon'      => 'favicon',
            'hp_builder_logo'   => 'logo_alternate',
            'hp_builder_favicon'=> 'apple_touch_icon',
            'home_page_image'   => 'og_image_default',
        ];

        foreach ($brandingMap as $oldKey => $newKey) {
            $row = DB::table('settings')
                     ->where('setting_name', $oldKey)
                     ->where('setting_type', 'Website Management Branding')
                     ->value('setting_value');
            if ($row !== null) {
                DB::table('website_theme_settings')->insertOrIgnore([
                    'key'        => $newKey,
                    'value'      => $row,
                    'group'      => 'branding',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('website_theme_settings');
    }
};
