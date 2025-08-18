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
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('terms_status', ['Accepted', 'Declined', 'Pending', 'Exempt'])->default('Pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            DB::table('orders')->where('terms_status', 'Exempt')->update(['terms_status' => 'Pending']);
            $table->enum('terms_status', ['Accepted', 'Declined', 'Pending'])->default('Pending')->change();
        });
    }
};
