<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interval_presets', function (Blueprint $table) {
            $table->enum('interval_type', ['hour', 'date'])->default('hour')->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('interval_presets', function (Blueprint $table) {
            $table->dropColumn('interval_type');
        });
    }
};
