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
        Schema::table('parts', function (Blueprint $table) {
             if (Schema::hasColumn('parts', 'primary_part_number')) {
                $table->string('primary_part_number')->nullable()->change();
            }

            if (Schema::hasColumn('parts', 'primary_part_cost')) {
                $table->decimal('primary_part_cost', 15, 2)->nullable()->change();
            }

           if (Schema::hasColumn('parts', 'primary_part_supplier_id')) {
    $table->string('primary_part_supplier_id')->nullable()->change();
}

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            if (Schema::hasColumn('parts', 'primary_part_number')) {
                $table->string('primary_part_number')->nullable(false)->change();
            }

            if (Schema::hasColumn('parts', 'primary_part_cost')) {
                $table->decimal('primary_part_cost', 15, 2)->nullable(false)->change();
            }

            if (Schema::hasColumn('parts', 'primary_part_supplier_id')) {
                $table->unsignedBigInteger('primary_part_supplier_id')->nullable(false)->change();
            }
        });
    }
};
