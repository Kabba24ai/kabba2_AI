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
        Schema::create('rental_ready_checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_name');
            $table->text('description')->nullable();
            $table->string('equipment_category_id')->nullable();
            $table->boolean('active_template')->default(0);
            $table->string('unique_id')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_ready_checklist_templates');
    }
};
