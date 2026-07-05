<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->text('customer_complaint')->nullable()->after('description');
            $table->text('technician_diagnosis')->nullable()->after('customer_complaint');
            $table->text('root_cause')->nullable()->after('technician_diagnosis');
            $table->text('repair_summary')->nullable()->after('root_cause');
            $table->text('internal_notes')->nullable()->after('repair_summary');
        });
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'customer_complaint',
                'technician_diagnosis',
                'root_cause',
                'repair_summary',
                'internal_notes',
            ]);
        });
    }
};
