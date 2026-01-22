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
        Schema::create('checklist_masters', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('checklist_system_name');

            // Make FK columns nullable if using set null
            $table->unsignedBigInteger('equipment_category_id')->nullable();
            $table->unsignedBigInteger('rental_ready_template_id')->nullable();
            // $table->unsignedBigInteger('customer_admin_template_id')->nullable();

            $table->timestamps();

            // Foreign keys (make sure the referenced tables exist with same type IDs)
            $table->foreign('equipment_category_id')
                ->references('id')->on('product_categories')
                ->nullOnDelete();

            $table->foreign('rental_ready_template_id')
                ->references('id')->on('rental_ready_checklist_templates')
                ->nullOnDelete();

            // $table->foreign('customer_admin_template_id')
            //       ->references('id')->on('templates')
            //       ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_masters');
    }
};
