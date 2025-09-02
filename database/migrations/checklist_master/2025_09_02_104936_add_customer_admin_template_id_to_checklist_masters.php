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
        Schema::table('checklist_masters', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_admin_template_id')
                ->nullable()
                ->after('rental_ready_template_id');

            $table->foreign('customer_admin_template_id')
                ->references('id')
                ->on('customer_admin_templates')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checklist_masters', function (Blueprint $table) {
            $table->dropForeign(['customer_admin_template_id']);
            $table->dropColumn('customer_admin_template_id');
        });
    }
};
