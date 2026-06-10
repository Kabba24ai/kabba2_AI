<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->decimal('freight_shipping', 12, 2)->nullable()->after('purchase_cost');
            $table->decimal('taxes_fees', 12, 2)->nullable()->after('freight_shipping');
        });
    }

    public function down()
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn(['freight_shipping', 'taxes_fees']);
        });
    }
};
