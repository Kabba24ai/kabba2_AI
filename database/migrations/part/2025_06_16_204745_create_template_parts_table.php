<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplatePartsTable extends Migration
{
    public function up()
    {
        Schema::create('template_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->onDelete('cascade');
            $table->foreignId('part_id')->constrained()->onDelete('cascade');
            $table->integer('sort_order')->default(1);
            $table->timestamps();
            
            $table->unique(['template_id', 'part_id']);
            $table->index(['template_id', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('template_parts');
    }
}
