<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartsTable extends Migration
{
    public function up()
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->uuid('unique_id')->unique();
            $table->string('part_name');
            $table->string('category');
            $table->string('equipment_name');
            $table->string('equipment_id');
            $table->string('part_number');
            $table->string('supplier');
            $table->decimal('unit_cost', 10, 2);
            $table->integer('stock_level')->default(0);
            $table->integer('min_stock')->default(0);
            $table->boolean('dni')->default(false);
            $table->text('description')->nullable();
            
            // Alternative suppliers
            $table->string('part_number_alt_1')->nullable();
            $table->decimal('cost_alt_1', 10, 2)->nullable();
            $table->string('supplier_alt_1')->nullable();
            $table->string('part_number_alt_2')->nullable();
            $table->decimal('cost_alt_2', 10, 2)->nullable();
            $table->string('supplier_alt_2')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['category', 'equipment_id']);
            $table->index('part_name');
            $table->index('stock_level');
        });
    }

    public function down()
    {
        Schema::dropIfExists('parts');
    }
}
