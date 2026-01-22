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
            
            $table->string('billing_contact_name')->nullable()->after('technical_support_phone');
            $table->string('billing_contact_email')->nullable()->after('billing_contact_name');
            $table->string('billing_contact_phone')->nullable()->after('billing_contact_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
             $table->dropColumn([
                'billing_contact_name',
                'billing_contact_email',
                'billing_contact_phone',
            ]);
        });
    }
};
