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
        Schema::table('order_media', function (Blueprint $table) {
            $table->dropForeign(['media_id']);
            $table->unsignedBigInteger('media_id')->nullable()->change();
            $table->foreign('media_id')
                ->references('id')->on('media')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_media', function (Blueprint $table) {
            $table->dropForeign(['media_id']);
            $table->foreign('media_id')
                ->references('id')->on('media')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }
};
