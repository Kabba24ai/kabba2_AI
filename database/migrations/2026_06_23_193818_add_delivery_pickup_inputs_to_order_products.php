<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dateTime('delivery_inputs_date')->nullable()->after('dispatch_checklist');
            $table->string('delivery_tnc_status')->nullable()->after('delivery_inputs_date');
            $table->string('delivery_drivers_license_status')->nullable()->after('delivery_tnc_status');
            $table->string('delivery_video_status')->nullable()->after('delivery_drivers_license_status');
            $table->string('delivery_checklist_status')->nullable()->after('delivery_video_status');

            $table->dateTime('pickup_inputs_date')->nullable()->after('delivery_checklist_status');
            $table->string('pickup_tnc_status')->nullable()->after('pickup_inputs_date');
            $table->string('pickup_drivers_license_status')->nullable()->after('pickup_tnc_status');
            $table->string('pickup_video_status')->nullable()->after('pickup_drivers_license_status');
            $table->string('pickup_checklist_status')->nullable()->after('pickup_video_status');
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_inputs_date',
                'delivery_tnc_status',
                'delivery_drivers_license_status',
                'delivery_video_status',
                'delivery_checklist_status',
                'pickup_inputs_date',
                'pickup_tnc_status',
                'pickup_drivers_license_status',
                'pickup_video_status',
                'pickup_checklist_status',
            ]);
        });
    }
};
