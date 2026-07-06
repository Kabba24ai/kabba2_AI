<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_ticket_labor_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained('service_tickets')->cascadeOnDelete();
            // HRM users table is the source of truth for employees — reference only.
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('labor_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('hours', 6, 2);
            $table->text('labor_description')->nullable();
            $table->text('internal_notes')->nullable();

            $table->boolean('billable')->default(true);
            $table->decimal('labor_rate', 10, 2)->nullable();
            $table->decimal('labor_total', 10, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['service_ticket_id', 'billable']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_ticket_labor_entries');
    }
};
