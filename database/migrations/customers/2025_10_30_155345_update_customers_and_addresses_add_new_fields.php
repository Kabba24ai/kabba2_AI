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
        //  Add country column to customer_addresses
        Schema::table('customer_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_addresses', 'country')) {
                $table->string('country', 100)->nullable()->after('state_id');
            }
        });

        //  Add same_as_billing, tags, and notes_json to customers
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'same_as_billing')) {
                $table->boolean('same_as_billing')->default(false)->after('is_credit_account');
            }

            if (!Schema::hasColumn('customers', 'tags')) {
                $table->json('tags')->nullable()->after('same_as_billing');
            }

          
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
