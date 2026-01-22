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
            $table->after('pickup_by', function (Blueprint $table) {
                $table->unsignedBigInteger('equipment_id')->nullable();
                $table->foreign('equipment_id')->references('id')->on('equipment')->onDelete('set null')->onUpdate('cascade');
                $table->json('equipment_details')->nullable();
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
                $table->timestamp('assigned_at')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropForeign(['equipment_id']);
            $table->dropColumn('equipment_id');
            $table->dropColumn('equipment_details');
            $table->dropForeign(['assigned_by']);
            $table->dropColumn('assigned_by');
            $table->dropColumn('assigned_at');
        });
    }
};
