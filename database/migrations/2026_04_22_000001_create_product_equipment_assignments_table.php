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
        Schema::dropIfExists('product_equipment_assignment_path_items');
        Schema::dropIfExists('product_equipment_assignment_paths');
        Schema::dropIfExists('product_equipment_assignments');

        Schema::create('product_equipment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('base_product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->text('assignment_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('product_id', 'pea_product_uq');
            $table->index('is_active', 'pea_active_idx');
        });

        Schema::create('product_equipment_assignment_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')
                ->constrained('product_equipment_assignments', 'id', 'pea_paths_assignment_fk')
                ->cascadeOnDelete();
            $table->string('path_type', 40);
            $table->foreignId('product_category_id')
                ->nullable()
                ->constrained('product_categories', 'id', 'pea_paths_category_fk')
                ->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['assignment_id', 'path_type'], 'pea_paths_assign_path_uq');
            $table->index(['path_type', 'product_category_id'], 'pea_paths_type_cat_idx');
        });

        Schema::create('product_equipment_assignment_path_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_path_id')
                ->constrained('product_equipment_assignment_paths', 'id', 'pea_items_path_fk')
                ->cascadeOnDelete();
            $table->foreignId('equipment_id')
                ->constrained('equipment', 'id', 'pea_items_equipment_fk')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['assignment_path_id', 'equipment_id'], 'pea_items_path_equipment_uq');
            $table->index('equipment_id', 'pea_items_equipment_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_equipment_assignment_path_items');
        Schema::dropIfExists('product_equipment_assignment_paths');
        Schema::dropIfExists('product_equipment_assignments');
    }
};
