<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_ai_settings', function (Blueprint $table) {
            $table->id();
            // General
            $table->boolean('prefer_same_driver_for_returns')->default(true);
            // Automation
            $table->boolean('ai_enabled')->default(false);
            $table->unsignedTinyInteger('cron_hour')->default(2);
            $table->unsignedTinyInteger('cron_minute')->default(0);
            $table->unsignedTinyInteger('look_ahead_days')->default(3);
            $table->boolean('auto_build_draft')->default(true);
            $table->boolean('allow_driver_assignment')->default(true);
            $table->boolean('allow_truck_assignment')->default(true);
            $table->boolean('allow_trailer_assignment')->default(true);
            $table->boolean('allow_route_optimization')->default(true);
            $table->boolean('allow_early_delivery')->default(true);
            // Early delivery
            $table->boolean('early_delivery_enabled')->default(false);
            $table->unsignedTinyInteger('early_delivery_max_days')->default(2);
            $table->time('early_delivery_window_start')->nullable();
            $table->time('early_delivery_window_end')->nullable();
            $table->boolean('early_delivery_customer_must_approve')->default(false);
            $table->boolean('early_delivery_prioritize_weekend_specials')->default(true);
            $table->boolean('early_delivery_reduce_friday_load')->default(true);
            // Routing weights (must sum to 100)
            $table->unsignedTinyInteger('route_efficiency_weight')->default(40);
            $table->unsignedTinyInteger('delivery_priority_weight')->default(30);
            $table->unsignedTinyInteger('driver_utilization_weight')->default(30);
            // Routing preferences
            $table->boolean('route_minimize_miles')->default(true);
            $table->boolean('route_minimize_drive_time')->default(false);
            $table->boolean('route_batch_nearby_deliveries')->default(true);
            $table->boolean('route_batch_nearby_pickups')->default(true);
            $table->boolean('route_keep_driver_near_home')->default(true);
            $table->boolean('route_respect_delivery_windows')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_ai_settings');
    }
};
