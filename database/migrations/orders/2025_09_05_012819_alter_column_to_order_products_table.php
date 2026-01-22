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
        Schema::table('order_products', function (Blueprint $table) {
            $table->after('delivery_by', function (Blueprint $table) {
                $table->unsignedBigInteger('delivery_signature_media_id')->nullable();
                $table->text('delivery_notes')->nullable();
            });

            $table->after('pickup_by', function (Blueprint $table) {
                $table->unsignedBigInteger('pickup_signature_media_id')->nullable();
                $table->text('pickup_notes')->nullable();
            });

            $table->foreign('delivery_signature_media_id')->references('id')->on('media')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('pickup_signature_media_id')->references('id')->on('media')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropForeign(['delivery_signature_media_id']);
            $table->dropForeign(['pickup_signature_media_id']);
            $table->dropColumn(['delivery_signature_media_id', 'delivery_notes', 'pickup_signature_media_id', 'pickup_notes']);
        });
    }
};
