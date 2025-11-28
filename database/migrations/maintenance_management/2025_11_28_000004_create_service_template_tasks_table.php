<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_template_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('service_templates')->onDelete('cascade');
            $table->foreignId('task_id')->constrained('service_tasks')->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['template_id', 'task_id']);
            $table->index(['template_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_template_tasks');
    }
};