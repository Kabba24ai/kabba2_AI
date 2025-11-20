<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('placeholder')->nullable()->after('setting_value'); // default null
            $table->boolean('is_secure_field')->default(0)->after('placeholder'); // 0/1, default 0
            $table->boolean('is_eye_toggle')->default(0)->after('is_secure_field'); // 0/1, default 0
            $table->boolean('is_required')->default(0)->after('is_eye_toggle'); // 0/1, default 0
            $table->boolean('is_encrypted')->default(0)->after('is_required'); // 0/1, default 0
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['placeholder', 'is_secure_field', 'is_eye_toggle', 'is_required', 'is_encrypted']);
        });
    }
};
