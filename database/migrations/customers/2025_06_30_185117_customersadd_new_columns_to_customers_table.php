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
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('account_approved_by')->default(0)->after('credit_limit');
            $table->date('account_application_completed')->nullable()->after('account_approved_by');
            $table->integer('tax_status_approved_by')->default(0)->after('tax_document_valid_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('website');
            $table->dropColumn('account_application_completed');
            $table->dropColumn('tax_status_approved_by');
        });
    }
};
