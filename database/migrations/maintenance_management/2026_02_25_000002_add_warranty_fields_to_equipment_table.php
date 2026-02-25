<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->integer('warranty_duration_months')->nullable()->after('imei');
            $table->integer('warranty_duration_hours')->nullable()->after('warranty_duration_months');
        });
    }

    public function down()
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn(['warranty_duration_months', 'warranty_duration_hours']);
        });
    }
};
