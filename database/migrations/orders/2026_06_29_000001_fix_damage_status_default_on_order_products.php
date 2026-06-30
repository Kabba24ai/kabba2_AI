<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make the column nullable first, then clear false-positive defaults.
        Schema::table('order_products', function (Blueprint $table) {
            $table->string('damage_status')->nullable()->default(null)->change();
        });

        // Every order_product got damage_status = 'pending' as a column default when
        // the column was added, regardless of whether damage was ever reported.
        // Records with damage_charge = 0 have never had an actual damage event —
        // set them to NULL so the damage alert report only shows genuine alerts.
        // Records with damage_charge > 0 are legitimate and keep their status.
        DB::statement("
            UPDATE order_products
            SET damage_status = NULL
            WHERE damage_status = 'pending'
            AND (damage_charge IS NULL OR damage_charge = 0)
        ");
    }

    public function down(): void
    {
        // Restore records that were nulled out (best-effort — cannot distinguish
        // from records that were legitimately null before the migration ran).
        DB::statement("
            UPDATE order_products
            SET damage_status = 'pending'
            WHERE damage_status IS NULL
        ");

        Schema::table('order_products', function (Blueprint $table) {
            $table->string('damage_status')->nullable(false)->default('pending')->change();
        });
    }
};
