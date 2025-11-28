<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('service_categories')->onDelete('cascade');
            $table->boolean('auto_apply')->default(false);
            $table->longText('instructions')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['name', 'category_id']);
            $table->index(['auto_apply']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_tasks');
    }
};