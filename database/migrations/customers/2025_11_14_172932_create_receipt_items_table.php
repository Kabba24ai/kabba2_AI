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
        Schema::create('receipt_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('receipt_id')
                ->constrained('receipts')->cascadeOnDelete();

            $table->enum('type', ['charge', 'discount', 'refund', 'order'])->default('charge');

            $table->string('item_name');
            $table->decimal('unit', 12, 2)->default(0);
            $table->integer('qty')->default(1);

            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_items');
    }
};
