<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacation_requests', function (Blueprint $table) {

            //  remove old hours column
            if (Schema::hasColumn('vacation_requests', 'hours')) {
                $table->dropColumn('hours');
            }

            // add foreign key
            $table->foreignId('vacation_request_hour_id')
                ->after('end_date')
                ->constrained('vacation_request_hours')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vacation_requests', function (Blueprint $table) {

            $table->dropForeign(['vacation_request_hour_id']);
            $table->dropColumn('vacation_request_hour_id');

            // restore hours column if rolled back
            $table->decimal('hours', 5, 2)->after('end_date');
        });
    }
};
