<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_call_neededs', function (Blueprint $table) {
            $table->string('priority')->default('normal')->after('is_urgent');
        });

        // Backfill existing records from the is_urgent flag
        DB::table('customer_call_neededs')->where('is_urgent', 1)->update(['priority' => 'urgent']);
    }

    public function down(): void
    {
        Schema::table('customer_call_neededs', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};
