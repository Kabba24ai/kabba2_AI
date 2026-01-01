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
        Schema::create('achievement_goals', function (Blueprint $table) {
              $table->id();

            $table->string('goal_name');
            $table->string('icon')->nullable(); // emoji or icon name
            $table->string('color')->nullable(); // hex or tailwind color
            $table->text('description')->nullable();

            // logic
            $table->enum('goal_type', ['positive', 'negative'])->default('positive');
            $table->unsignedInteger('days_missed_max')->default(0);
            $table->unsignedInteger('days_late_max')->default(0);

            // UI & state
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievement_goals');
    }
};
