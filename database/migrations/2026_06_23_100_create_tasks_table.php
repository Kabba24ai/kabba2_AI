<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('category');             // sales, yard, shop, admin
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('normal');  // low, normal, high, urgent
            $table->string('status')->default('open');      // open, in_progress, waiting, completed, cancelled
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('due_date')->nullable();
            $table->unsignedBigInteger('related_order_id')->nullable();
            $table->unsignedBigInteger('related_customer_id')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_tasks');
    }
};
