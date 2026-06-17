<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_ai_trailers', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('trailer_name');
            $table->string('trailer_number')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->decimal('gvwr', 10, 2)->nullable();
            $table->decimal('payload_capacity', 10, 2)->nullable();
            $table->decimal('deck_length', 8, 2)->nullable();
            $table->decimal('deck_width', 8, 2)->nullable();
            $table->string('hitch_type')->nullable();  // 'Gooseneck', '5th Wheel', 'Bumper Pull'
            $table->boolean('cdl_required')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_ai_trailers');
    }
};
