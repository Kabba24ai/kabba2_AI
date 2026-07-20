<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Billing Engine UI refinement: preset notes become a managed, ordered
// list (admin CRUD + dropdown presentation) instead of static buttons.
// Additive only; default 0 keeps existing rows valid and alphabetical
// order as the tiebreaker preserves today's effective ordering.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuel_note_presets', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('label');
        });

        Schema::table('resolution_note_presets', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('fuel_note_presets', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('resolution_note_presets', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
