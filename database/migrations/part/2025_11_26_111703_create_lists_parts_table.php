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
        Schema::create('lists_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parts_list_id')
                ->constrained('parts_lists')
                ->cascadeOnDelete();
            $table->foreignId('part_id')
                ->constrained('parts')
                ->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lists_parts');
    }
};
