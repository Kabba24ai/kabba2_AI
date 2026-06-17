<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_ai_general_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_type');       // e.g. 'deliveries_before_returns', 'vip_priority', etc.
            $table->string('rule_label');      // human-readable label shown in UI
            $table->string('rule_value')->nullable();  // optional value/parameter
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('prefer_same_driver_for_returns')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_ai_general_rules');
    }
};
