<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Service Operations Board — manual priority order within each technician
 * lane. The board is a swimlane-per-technician (team leader) view where the
 * LEFT-TO-RIGHT position of a card is its work order for the day. That order
 * is employee-set (drag to reorder), so it needs a durable per-ticket ordinal;
 * a null position sorts after positioned cards (falls back to opened date).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->integer('board_position')->nullable()->after('priority');
        });
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropColumn('board_position');
        });
    }
};
