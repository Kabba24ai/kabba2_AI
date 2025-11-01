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
        Schema::table('suppliers', function (Blueprint $table) {
            // Rename secondary contact columns → inside sales
            if (Schema::hasColumn('suppliers', 'secondary_contact_name')) {
                $table->renameColumn('secondary_contact_name', 'inside_sales_name');
            }
            if (Schema::hasColumn('suppliers', 'secondary_contact_email')) {
                $table->renameColumn('secondary_contact_email', 'inside_sales_email');
            }
            if (Schema::hasColumn('suppliers', 'secondary_contact_phone')) {
                $table->renameColumn('secondary_contact_phone', 'inside_sales_phone');
            }

            // Add new columns for Technical Support
            $table->string('technical_support_name')->nullable()->after('inside_sales_phone');
            $table->string('technical_support_email')->nullable()->after('technical_support_name');
            $table->string('technical_support_phone')->nullable()->after('technical_support_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Rename back
            if (Schema::hasColumn('suppliers', 'inside_sales_name')) {
                $table->renameColumn('inside_sales_name', 'secondary_contact_name');
            }
            if (Schema::hasColumn('suppliers', 'inside_sales_email')) {
                $table->renameColumn('inside_sales_email', 'secondary_contact_email');
            }
            if (Schema::hasColumn('suppliers', 'inside_sales_phone')) {
                $table->renameColumn('inside_sales_phone', 'secondary_contact_phone');
            }

            // Drop technical support columns
            $table->dropColumn([
                'technical_support_name',
                'technical_support_email',
                'technical_support_phone'
            ]);
        });
    }
};
