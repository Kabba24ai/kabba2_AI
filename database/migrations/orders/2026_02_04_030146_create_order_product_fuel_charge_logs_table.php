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
        Schema::create('order_product_fuel_charge_logs', function (Blueprint $table) {
            $table->id();


              $table->foreignId('order_product_id')->constrained()->cascadeOnDelete();
                $table->decimal('before_amount', 10, 2)->default(0);
                $table->decimal('change_amount', 10, 2);
                $table->decimal('after_amount', 10, 2);
                $table->string('action')->nullable(); // added, adjusted, waived, etc.
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('note')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_product_fuel_charge_logs');
    }
};
