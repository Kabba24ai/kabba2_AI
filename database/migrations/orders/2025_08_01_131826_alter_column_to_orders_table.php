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
            $table->after('cart_data', function (Blueprint $table) {
                $table->longText('terms_collection')->nullable();
                $table->longText('pending_terms_content')->nullable();
                $table->longText('accepted_terms_content')->nullable();
                $table->dateTime('terms_accepted_at')->nullable();
                $table->enum('terms_status', ['Accepted', 'Declined', 'Pending'])->default('Pending');
                $table->longText('signature_image')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'terms_collection',
                'pending_terms_content',
                'accepted_terms_content',
                'terms_accepted_at',
                'terms_status',
                'signature_image',
            ]);
        });
    }
};
