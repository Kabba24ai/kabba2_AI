<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 20)->unique();
            $table->foreignId('website_menu_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('title');
            $table->enum('type', ['internal_page', 'external_url', 'anchor', 'email', 'phone'])
                  ->default('external_url');
            $table->unsignedBigInteger('page_id')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('icon', 150)->nullable();
            $table->unsignedBigInteger('icon_media_id')->nullable();
            $table->string('css_class', 200)->nullable();
            $table->enum('target', ['_self', '_blank'])->default('_self');
            $table->string('rel', 100)->nullable();
            $table->enum('visibility', ['both', 'desktop_only', 'mobile_only'])->default('both');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->json('content')->nullable()->comment('Future: mega menu, badges, columns, etc.');
            $table->timestamps();

            $table->foreign('parent_id')
                  ->references('id')->on('website_menu_items')
                  ->nullOnDelete();

            $table->foreign('page_id')
                  ->references('id')->on('website_pages')
                  ->nullOnDelete();

            $table->index(['website_menu_id', 'parent_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_menu_items');
    }
};
