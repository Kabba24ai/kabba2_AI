<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_assignment_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('product_name')->nullable()->index();
            $table->unsignedBigInteger('equipment_id')->nullable()->index();
            $table->string('equipment_name')->nullable()->index();
            $table->string('relationship_type', 50); // primary / upgrade / downgrade
            $table->json('actions_required')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_assignment_rules');
    }
};
