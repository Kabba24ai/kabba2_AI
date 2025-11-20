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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->enum('type', ['charge', 'discount', 'refund', 'order']);
            $table->string('item_name');
            $table->text('item_id')->nullable();

            $table->integer('qty')->default(1);
            $table->string('sku')->nullable();

            $table->decimal('unit', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->json('extras')->nullable();

            $table->text('notes')->nullable();
            $table->string('reference')->nullable();

            $table->foreignId('responsible_person_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
