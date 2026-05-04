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
        Schema::table('hours_of_operation', function (Blueprint $table) {
             $table->boolean('is_lunch_required')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hours_of_operation', function (Blueprint $table) {
             $table->dropColumn([
            'is_lunch_required',
           
        ]);
        });
    }
};
