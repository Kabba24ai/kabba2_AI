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
        Schema::table('order_product_funnel_logs', function (Blueprint $table) {

            $table->after('product_id', function (Blueprint $table) {
                $table->unsignedBigInteger('sales_funnel_step_id')->nullable();
                $table->string('step_type')->nullable();
                $table->string('step_name')->nullable();
            });

            $table->foreign('sales_funnel_step_id')
                    ->references('id')
                    ->on('sales_funnel_steps')
                    ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_product_funnel_logs', function (Blueprint $table) {
            $table->dropForeign(['sales_funnel_step_id']);
            $table->dropColumn('sales_funnel_step_id');
            $table->dropColumn('step_type');
            $table->dropColumn('step_name');
        });
    }
};
