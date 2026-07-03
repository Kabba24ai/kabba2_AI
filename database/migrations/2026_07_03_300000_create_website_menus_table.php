<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_menus', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 20)->unique();
            $table->string('name');
            $table->string('menu_key', 100)->unique()->comment('Code identifier e.g. primary_header');
            $table->text('description')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_menus');
    }
};
