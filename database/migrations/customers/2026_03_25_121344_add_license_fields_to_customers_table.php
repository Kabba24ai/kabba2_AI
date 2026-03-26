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

            $table->unsignedBigInteger('license_front_media_id')->nullable();
            $table->foreign('license_front_media_id')
                ->references('id')->on('media')
                ->nullOnDelete();

            $table->unsignedBigInteger('license_back_media_id')->nullable();
            $table->foreign('license_back_media_id')
                ->references('id')->on('media')
                ->nullOnDelete();

            $table->date('license_expiry_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {

            $table->dropForeign(['license_front_media_id']);
            $table->dropForeign(['license_back_media_id']);

            $table->dropColumn([
                'license_front_media_id',
                'license_back_media_id',
                'license_expiry_date'
            ]);
        });
    }
};
