<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('authorise_submissions', function (Blueprint $table) {
            $table->string('street_address')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('authorise_submissions')
            ->whereNull('street_address')
            ->update(['street_address' => '']);

        DB::table('authorise_submissions')
            ->whereNull('password')
            ->update(['password' => '']);

        Schema::table('authorise_submissions', function (Blueprint $table) {
            $table->string('street_address')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
