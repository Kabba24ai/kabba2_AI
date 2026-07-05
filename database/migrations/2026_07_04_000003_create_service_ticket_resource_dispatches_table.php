<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Field service timing foundation: each technician / vehicle / trailer /
        // equipment dispatched on a ticket gets its own timing row so resources
        // can depart, arrive, and return independently.
        Schema::create('service_ticket_resource_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained('service_tickets')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            // Vehicle/trailer catalogs are not finalized as core tables yet, so
            // these stay unconstrained references for Phase 1.
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('trailer_id')->nullable();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();

            $table->timestamp('departed_shop_at')->nullable();
            $table->timestamp('arrived_on_site_at')->nullable();
            $table->timestamp('work_started_at')->nullable();
            $table->timestamp('work_completed_at')->nullable();
            $table->timestamp('departed_site_at')->nullable();
            $table->timestamp('returned_to_shop_at')->nullable();

            $table->timestamps();

            $table->index('service_ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_ticket_resource_dispatches');
    }
};
