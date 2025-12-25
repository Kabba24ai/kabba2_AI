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
        Schema::create('authorise_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone_number', 32)->nullable();
            $table->string('email')->index();
            $table->string('password');
            $table->string('business_name')->nullable();
            $table->string('street_address');
            $table->string('city')->nullable();
            $table->string('state', 64)->nullable();
            $table->string('zip_code', 32)->nullable();
            $table->string('card_name')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_expiry', 7)->nullable();
            $table->string('card_brand')->nullable();
            $table->string('customer_profile_id')->nullable();
            $table->string('payment_profile_id')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('amount', 10, 2)->default(0);
            $table->dateTime('schedule_datetime')->nullable();
            $table->text('response_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authorise_submissions');
    }
};
