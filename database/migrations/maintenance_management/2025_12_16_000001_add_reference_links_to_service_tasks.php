<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('service_tasks')) {
            Schema::table('service_tasks', function (Blueprint $table) {
                // Use JSON if supported by the DB; otherwise it will be created as TEXT by some drivers
                $table->json('reference_links')->nullable()->after('instructions');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('service_tasks')) {
            Schema::table('service_tasks', function (Blueprint $table) {
                $table->dropColumn('reference_links');
            });
        }
    }
};
