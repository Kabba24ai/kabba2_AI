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
            $table->string('company_website')->nullable()->after('company_name'); // or after another column
            $table->string('company_phone')->nullable()->after('company_website'); // or after another column
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            //
            $table->dropColumn('company_website','company_phone');

        });
    }
};
