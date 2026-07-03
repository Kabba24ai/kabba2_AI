<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('name');
            $table->string('slug');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('parent_id');
            $table->unique(['slug', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_folders');
    }
};
