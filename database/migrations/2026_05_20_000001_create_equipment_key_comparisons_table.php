<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_key_comparisons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id');
            $table->foreign('equipment_id')->references('id')->on('equipment')->cascadeOnDelete();
            $table->string('spec_label'); // Cloned specification label
            $table->string('spec_value'); // Cloned specification value
            $table->string('spec_unit')->nullable(); // Cloned specification unit
            $table->boolean('is_manual_override')->default(false);
            $table->integer('sort_order')->default(0)->comment('Display order');
            $table->timestamps();

            $table->index(['equipment_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_key_comparisons');
    }
};
