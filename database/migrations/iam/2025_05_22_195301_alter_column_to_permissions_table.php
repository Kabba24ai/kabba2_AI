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
        Schema::table('permissions', function (Blueprint $table) {
            $table->bigInteger('module_id')->unsigned()->nullable()->after('id');
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('SET NULL');

            $table->string('title')->nullable()->after('name');

            $table->enum('permission_to_all',['No','Yes'])->after('guard_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['module_id']);
            $table->dropColumn('module_id');

            $table->dropColumn('title');

            $table->dropColumn('permission_to_all');
        });
    }
};
