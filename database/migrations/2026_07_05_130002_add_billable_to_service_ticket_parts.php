<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_ticket_parts', function (Blueprint $table) {
            // Settlement preview: parts can be marked non-billable so they
            // are excluded from the customer charge (cost still tracked).
            $table->boolean('billable')->default(true)->after('warranty_eligible');
        });
    }

    public function down(): void
    {
        Schema::table('service_ticket_parts', function (Blueprint $table) {
            $table->dropColumn('billable');
        });
    }
};
