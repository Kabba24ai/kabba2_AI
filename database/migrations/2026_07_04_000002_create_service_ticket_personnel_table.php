<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Links Service Tickets to HRM employees (users table).
        // No employee data is duplicated here — HRM stays the source of truth.
        Schema::create('service_ticket_personnel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained('service_tickets')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['service_ticket_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_ticket_personnel');
    }
};
