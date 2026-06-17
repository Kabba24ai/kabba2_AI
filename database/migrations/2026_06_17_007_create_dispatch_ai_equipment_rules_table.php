<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_ai_equipment_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_category_id')->unique();
            $table->decimal('min_trailer_capacity', 10, 2)->nullable();
            $table->json('allowed_trailer_types')->nullable();   // array of hitch_type strings
            $table->json('allowed_truck_types')->nullable();     // array of hitch_type strings
            $table->boolean('cdl_required')->default(false);
            $table->boolean('can_share_trailer')->default(false);
            $table->boolean('must_haul_alone')->default(false);
            $table->text('special_notes')->nullable();
            $table->timestamps();

            $table->foreign('product_category_id')->references('id')->on('product_categories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_ai_equipment_rules');
    }
};
