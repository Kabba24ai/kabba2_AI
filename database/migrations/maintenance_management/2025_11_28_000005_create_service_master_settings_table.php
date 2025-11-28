<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_master_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('pending_before_hours')->default(20);
            $table->integer('pending_after_hours')->default(15);
            $table->string('master_admin_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_master_settings');
    }
};
