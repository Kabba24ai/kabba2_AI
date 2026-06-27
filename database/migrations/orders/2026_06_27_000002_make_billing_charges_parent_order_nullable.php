<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Dashboard fuel/damage charge modal creates charges without an order context.
     * parent_order_id must be nullable to support these customer-level charges.
     */
    public function up(): void
    {
        Schema::table('billing_charges', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_order_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('billing_charges', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_order_id')->nullable(false)->change();
        });
    }
};
