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
        Schema::table('customer_accounts', function (Blueprint $table) {
            // Drop old column
            $table->dropColumn('responsible_person');

            // Add new responsible_person_id and foreign key
            $table->unsignedBigInteger('responsible_person_id')->nullable()->after('payment_type');
            $table->foreign('responsible_person_id')->references('id')->on('users')->nullOnDelete();

            // Add responsible_person_name
            $table->string('responsible_person_name')->nullable()->after('responsible_person_id');

            // Add sales_tax_type enum
            $table->enum('sales_tax_type', ['add', 'free', 'reverse'])->nullable()->after('sales_tax');

            $table->string('sales_tax')->default(0.0000)->change();


        });
    }


    /**
     * Reverse the migrations.
     */
     public function down(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {
            // Rollback changes
            $table->dropForeign(['responsible_person_id']);
            $table->dropColumn(['responsible_person_id', 'responsible_person_name', 'sales_tax_type']);

            // Re-add original column
            $table->string('responsible_person')->nullable()->after('payment_type');
        });
    }
};
