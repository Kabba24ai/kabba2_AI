<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Custom 1–4 one-way delivery fee columns (Phase 1 — Delivery
     * Configuration Foundation). Mirrors standard_delivery_fee /
     * extended_delivery_fee: nullable copies of the global Product Settings
     * rate for the product's truck_fee_size_setting size. NULL = tier not
     * configured; 0.00 = intentionally free.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $after = 'extended_delivery_fee';

            foreach ([1, 2, 3, 4] as $n) {
                $column = "custom_{$n}_delivery_fee";

                if (!Schema::hasColumn('products', $column)) {
                    $table->decimal($column)->nullable()->after($after);
                }

                $after = $column;
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach ([1, 2, 3, 4] as $n) {
                $column = "custom_{$n}_delivery_fee";

                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
