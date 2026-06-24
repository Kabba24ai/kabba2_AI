<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Corrective migration: prior migrations (2026_06_23_100/101/102) were marked
// as Ran on existing servers before the tables existed. This creates the tables
// that should have been created. HasTable guards make it safe on fresh installs.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('daily_tasks')) {
            Schema::create('daily_tasks', function (Blueprint $table) {
                $table->id();
                $table->string('category');
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('priority')->default('normal');
                $table->string('status')->default('open');
                $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->dateTime('due_date')->nullable();
                $table->unsignedBigInteger('related_order_id')->nullable();
                $table->unsignedBigInteger('related_customer_id')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('daily_task_comments')) {
            Schema::create('daily_task_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('daily_tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('comment');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('daily_task_activity_logs')) {
            Schema::create('daily_task_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('daily_tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action');
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('daily_task_activity_logs');
        Schema::dropIfExists('daily_task_comments');
        Schema::dropIfExists('daily_tasks');
        Schema::enableForeignKeyConstraints();
    }
};
