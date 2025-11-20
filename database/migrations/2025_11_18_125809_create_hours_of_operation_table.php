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
        Schema::create('hours_of_operation', function (Blueprint $table) {
            $table->id();

            // Unique ID for internal reference
            $table->uuid('unique_id')->unique();

            // Store relationship (optional)
            $table->unsignedBigInteger('store_id')->nullable();

            // Day (Monday, Tuesday, …)
            $table->string('day_name');

            // Is the store closed this day?
            $table->boolean('is_closed')->default(false);

            // Times (nullable because closed days have no times)
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hours_of_operation');
    }
};
