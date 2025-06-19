<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->uuid('unique_id')->unique();
            
            // Basic Information
            $table->string('equipment_name');
            $table->string('category');
            $table->string('equipment_id')->unique();
            
            // Equipment Details
            $table->string('brand');
            $table->string('model')->nullable();
            $table->year('model_year')->nullable();
            $table->date('date_acquired')->nullable();
            
            // Financial & Legal
            $table->decimal('cost', 12, 2)->nullable();
            $table->enum('ownership_type', ['owned', 'financed', 'leased'])->nullable();
            $table->string('finance_company')->nullable();
            $table->integer('term')->nullable(); // months
            $table->decimal('rate', 5, 2)->nullable(); // percentage
            $table->decimal('monthly_payment', 10, 2)->nullable();
            
            // Identification Numbers
            $table->string('vin')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('plate')->nullable();
            $table->string('imei')->nullable();
            
            // Module Sections
            $table->string('rental_ready_checklist')->nullable();
            $table->string('equipment_service_list')->nullable();
            
            // Equipment Notes
            $table->text('equipment_notes')->nullable();
            
            // Status & Management
            $table->enum('status', ['available', 'rented', 'maintenance', 'damaged'])->default('available');
            $table->string('tech_manager')->nullable();
            $table->string('location')->nullable();
            
            // Service Information
            $table->integer('service_interval')->nullable(); // hours
            $table->integer('current_hours')->nullable();
            $table->date('last_service')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['category', 'status']);
            $table->index('equipment_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('equipment');
    }
};
