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
        Schema::create('sales_funnels', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('funnel_name');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('sales_funnel_category_id')->nullable();
            $table->foreign('sales_funnel_category_id')->references('id')->on('sales_funnel_categories')->onDelete('set null');
            $table->enum('trigger_event', ['Rental Start Date', 'New Lead Added'])->nullable();
            $table->enum('trigger_event_timing', ['Before Event', 'After Event'])->nullable();
            $table->enum('timing_unit', ['Days', 'Weeks', 'Months'])->nullable();
            $table->integer('timing_value')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_funnels');
    }
};
