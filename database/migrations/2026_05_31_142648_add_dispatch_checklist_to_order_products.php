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
        Schema::table('order_products', function (Blueprint $table) {
            // Stores driver dispatch checklist state as JSON:
            // {
            //   "customer_contact": "spoke"|"vm"|"text"|null,
            //   "options": { "prepaid_fuel": bool, "prepaid_cleaning": bool },
            //   "products": { "<op_unique_id>": bool, ... },
            //   "updated_at": "ISO datetime"
            // }
            $table->json('dispatch_checklist')->nullable()->after('pickup_notes');
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('dispatch_checklist');
        });
    }
};
