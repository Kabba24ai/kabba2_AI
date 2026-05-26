<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_funnels', function (Blueprint $table) {
            if (Schema::hasColumn('sales_funnels', 'trigger_event')) {
                $table->dropColumn('trigger_event');
            }

            if (Schema::hasColumn('sales_funnels', 'trigger_event_timing')) {
                $table->dropColumn('trigger_event_timing');
            }

            if (Schema::hasColumn('sales_funnels', 'date_value')) {
                $table->dropColumn('date_value');
            }

            if (Schema::hasColumn('sales_funnels', 'hour_value')) {
                $table->dropColumn('hour_value');
            }

            if (Schema::hasColumn('sales_funnels', 'minute_value')) {
                $table->dropColumn('minute_value');
            }

            $table->enum('trigger_type', ['new_order', 'rental_schedule', 'lead_added'])
                ->default('new_order')
                ->after('sales_funnel_category_id');

            $table->enum('trigger_reference', [
                'order_created_datetime',
                'order_paid_datetime',
                'delivery_datetime',
                'return_datetime',
                'lead_added_datetime',
            ])
                ->default('order_created_datetime')
                ->after('trigger_type');
        });

        Schema::table('sales_funnel_steps', function (Blueprint $table) {
            $table->enum('timing_reference_type', [
                'funnel_entry_time',
                'order_created_datetime',
                'order_paid_datetime',
                'rental_delivery_datetime',
                'rental_return_datetime',
                'lead_added_datetime',
            ])
                ->default('funnel_entry_time')
                ->after('sales_funnel_id');

            $table->enum('offset_direction', ['before', 'after'])
                ->default('after')
                ->after('timing_reference_type');

            $table->enum('offset_unit', ['Days', 'Hours', 'Minutes'])
                ->default('Days')
                ->after('offset_direction');

            $table->integer('offset_value')->default(0)->after('offset_unit');
            $table->integer('offset_minutes')->default(0)->after('offset_value');
            $table->unsignedBigInteger('message_category_id')->nullable()->after('step_type');
            $table->unsignedBigInteger('message_template_id')->nullable()->after('message_category_id');
            $table->boolean('is_active')->default(true)->after('message_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_funnel_steps', function (Blueprint $table) {
            if (Schema::hasColumn('sales_funnel_steps', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('sales_funnel_steps', 'message_template_id')) {
                $table->dropColumn('message_template_id');
            }

            if (Schema::hasColumn('sales_funnel_steps', 'message_category_id')) {
                $table->dropColumn('message_category_id');
            }

            if (Schema::hasColumn('sales_funnel_steps', 'offset_minutes')) {
                $table->dropColumn('offset_minutes');
            }

            if (Schema::hasColumn('sales_funnel_steps', 'offset_value')) {
                $table->dropColumn('offset_value');
            }

            if (Schema::hasColumn('sales_funnel_steps', 'offset_unit')) {
                $table->dropColumn('offset_unit');
            }

            if (Schema::hasColumn('sales_funnel_steps', 'offset_direction')) {
                $table->dropColumn('offset_direction');
            }

            if (Schema::hasColumn('sales_funnel_steps', 'timing_reference_type')) {
                $table->dropColumn('timing_reference_type');
            }
        });

        Schema::table('sales_funnels', function (Blueprint $table) {
            if (Schema::hasColumn('sales_funnels', 'trigger_reference')) {
                $table->dropColumn('trigger_reference');
            }

            if (Schema::hasColumn('sales_funnels', 'trigger_type')) {
                $table->dropColumn('trigger_type');
            }

            $table->enum('trigger_event', ['Rental Start Date', 'New Lead Added'])->nullable()->after('sales_funnel_category_id');
            $table->enum('trigger_event_timing', ['Before Event', 'After Event'])->nullable()->after('trigger_event');
            $table->string('date_value')->nullable()->after('trigger_event_timing');
            $table->string('hour_value')->nullable()->after('date_value');
            $table->string('minute_value')->nullable()->after('hour_value');
        });
    }
};
