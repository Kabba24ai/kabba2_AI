<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('unique_id')->unique();
            $table->string('name');
            $table->string('category');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('created_by');
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
            $table->index('name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('templates');
    }
}
