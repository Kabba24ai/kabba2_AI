<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            
            // Basic Information
            $table->string('name')->nullable();
            $table->string('account_number')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            
            // Address Information
            $table->string('street_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip_code', 10)->nullable();
            $table->string('tax_id')->nullable();
            
            // Business Contact
            $table->string('main_phone')->nullable();
            $table->string('main_email')->nullable();
            $table->string('website')->nullable();
            
            // Sales Contact
            $table->string('sales_name')->nullable();
            $table->string('sales_phone')->nullable();
            $table->string('sales_cell')->nullable();
            $table->string('sales_email')->nullable();
            
            // Inside Sales Contact
            $table->string('inside_sales_name')->nullable();
            $table->string('inside_sales_phone')->nullable();
            $table->string('inside_sales_cell')->nullable();
            $table->string('inside_sales_email')->nullable();
            
            // Technical Support Contact
            $table->string('technical_name')->nullable();
            $table->string('technical_phone')->nullable();
            $table->string('technical_cell')->nullable();
            $table->string('technical_email')->nullable();
            
            // Parts Contact
            $table->string('parts_name')->nullable();
            $table->string('parts_phone')->nullable();
            $table->string('parts_cell')->nullable();
            $table->string('parts_email')->nullable();
            
            // Business Terms
            $table->string('payment_terms')->default('Net 30');
            $table->string('shipping_terms')->default('FOB Origin');
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('unique_id');
            $table->index('name');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('suppliers');
    }
};
// This migration creates the suppliers table with all necessary fields and indexes.