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
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->bigInteger('module_category_id')->unsigned()->nullable();
            $table->foreign('module_category_id')->references('id')->on('module_categories')->onDelete('SET NULL');
            $table->string('title')->nullable();
            $table->string('name')->nullable();
            $table->string('model_name')->nullable();
            $table->text('permissions')->nullable();
            $table->text('permission_options')->nullable();
            $table->enum('need_set_permissions',['Yes','No']);
            $table->dateTime('permission_updated_at')->nullable();
            $table->bigInteger('permission_updated_user_id')->unsigned()->nullable();
            $table->foreign('permission_updated_user_id')->references('id')->on('users')->onDelete('SET NULL');
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
