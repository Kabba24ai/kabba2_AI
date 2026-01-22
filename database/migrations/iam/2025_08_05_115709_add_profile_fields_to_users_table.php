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
        Schema::table('users', function (Blueprint $table) {
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('mobile_phone')->nullable()->after('email');
            $table->string('phone_number')->nullable()->after('mobile_phone');
            $table->string('street_address')->nullable()->after('phone_number');
            $table->string('city')->nullable()->after('street_address');
            $table->string('state')->nullable()->after('city');
            $table->string('zip_code')->nullable()->after('state');
            $table->string('country')->nullable()->after('zip_code');

            $table->date('start_date')->nullable()->after('country');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('pay_type')->nullable()->after('end_date');
            $table->string('clock_code')->nullable()->after('pay_type');
             $table->boolean('limit_start_time')->default(0)->after('clock_code');
            $table->boolean('limit_end_time')->default(0)->after('limit_start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'middle_name',
                'mobile_phone',
                'phone_number',
                'street_address',
                'city',
                'state',
                'zip_code',
                'country',
                'start_date',
                'end_date',
                'pay_type',
                'clock_code',
                'limit_end_time',
            ]);
        });
    }
};
