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
        Schema::create('equipment_rental_ready_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('unique_id')->unique();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_name');
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->date('inspection_date');
            $table->time('inspection_time');
            $table->integer('equipment_hours')->default(0);
            $table->text('general_notes')->nullable();
            $table->enum('status', ['Draft', 'Rental Ready', 'Damaged'])->default('Draft');

            $table->integer('total_questions')->default(0);
            $table->integer('required_questions')->default(0);
            $table->integer('optional_questions')->default(0);
            $table->integer('required_items_completed')->default(0);
            $table->integer('items_requiring_maintenance')->default(0);
            $table->integer('damaged_items')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_rental_ready_templates');
    }
};
