<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rental-order intake: the store where the equipment will be repaired
        // (replaces the In Shop / Customer Site decision for this path).
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->foreignId('service_store_id')->nullable()->after('service_location')
                ->constrained('stores')->nullOnDelete();
        });

        // One assigned employee can lead the ticket crew; everyone else is a
        // team member. HRM stays the source of truth for employee data.
        Schema::table('service_ticket_personnel', function (Blueprint $table) {
            $table->boolean('is_team_leader')->default(false)->after('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_store_id');
        });

        Schema::table('service_ticket_personnel', function (Blueprint $table) {
            $table->dropColumn('is_team_leader');
        });
    }
};
