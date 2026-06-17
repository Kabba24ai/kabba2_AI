<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_service_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();

            // radius | included | excluded
            $table->enum('area_group', ['radius', 'included', 'excluded'])->index();

            // radius | city | county | zip | custom_area
            $table->enum('area_type', ['radius', 'city', 'county', 'zip', 'custom_area']);

            $table->string('name', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('county', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('zip_code', 20)->nullable();

            // Only used when area_group = radius
            $table->decimal('radius_miles', 8, 2)->nullable();

            $table->boolean('delivery_allowed')->default(true);
            $table->boolean('pickup_allowed')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['store_id', 'area_group', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_service_areas');
    }
};
