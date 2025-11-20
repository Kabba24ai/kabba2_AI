<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Drop the FK only if it actually exists
        $constraint = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'suppliers'
              AND COLUMN_NAME = 'supplier_category_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if ($constraint) {
            $name = $constraint->CONSTRAINT_NAME;
            DB::statement("ALTER TABLE suppliers DROP FOREIGN KEY `$name`");
        }

        // Step 2: Clean invalid supplier_category_id values
        DB::statement("
            UPDATE suppliers
            SET supplier_category_id = NULL
            WHERE supplier_category_id IS NOT NULL
            AND supplier_category_id NOT IN (SELECT id FROM part_categories)
        ");

        // Step 3: Add new foreign key safely
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreign('supplier_category_id')
                ->references('id')
                ->on('part_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Step 1: Drop the current FK if it exists
        $constraint = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'suppliers'
              AND COLUMN_NAME = 'supplier_category_id'
              AND REFERENCED_TABLE_NAME = 'part_categories'
        ");

        if ($constraint) {
            $name = $constraint->CONSTRAINT_NAME;
            DB::statement("ALTER TABLE suppliers DROP FOREIGN KEY `$name`");
        }

        // Step 2: Re-add the old relationship
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreign('supplier_category_id')
                ->references('id')
                ->on('supplier_categories')
                ->nullOnDelete();
        });
    }
};
