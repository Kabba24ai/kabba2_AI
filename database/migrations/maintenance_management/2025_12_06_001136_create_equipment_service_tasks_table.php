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
        Schema::create('equipment_service_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id');
            $table->unsignedBigInteger('service_template_id');
            $table->unsignedBigInteger('service_task_id');
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->unsignedBigInteger('checked_by')->nullable();
            $table->decimal('actual_hours', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('equipment_id')
                  ->references('id')
                  ->on('equipment')
                  ->onDelete('cascade');
                  
            $table->foreign('service_template_id')
                  ->references('id')
                  ->on('service_templates')
                  ->onDelete('cascade');
                  
            $table->foreign('service_task_id')
                  ->references('id')
                  ->on('service_tasks')
                  ->onDelete('cascade');
                  
            $table->foreign('performed_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
                  
            $table->foreign('checked_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_service_tasks');
    }
};
