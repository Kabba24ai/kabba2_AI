<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_funnels', function (Blueprint $table) {
            $table->enum('funnel_order_type', ['all', 'cod', 'paid'])
                  ->default('all')
                  ->after('status')
                  ->comment('all=any order, cod=COD/POD orders only, paid=paid orders only');
        });
    }

    public function down(): void
    {
        Schema::table('sales_funnels', function (Blueprint $table) {
            $table->dropColumn('funnel_order_type');
        });
    }
};
