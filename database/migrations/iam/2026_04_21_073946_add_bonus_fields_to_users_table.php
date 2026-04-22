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
        Schema::table('users', function (Blueprint $table) {
                $table->decimal('bonus_vacation_hours', 8, 2)->nullable();
                $table->date('bonus_vacation_hours_start_date')->nullable();
                $table->date('bonus_vacation_hours_end_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'bonus_vacation_hours',
                'bonus_vacation_hours_start_date',
                'bonus_vacation_hours_end_date',
            ]);
        });
    }
};
