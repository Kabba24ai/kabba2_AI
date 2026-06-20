<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logic_documents', function (Blueprint $table) {
            $table->id();
            $table->string('module_key', 64)->index();          // e.g., 'dispatch'
            $table->string('section_key', 128)->index();        // e.g., 'driver_assignment_logic'
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('logic_body')->nullable();
            $table->enum('status', ['active', 'under_review', 'deprecated'])->default('active')->index();
            $table->enum('visibility', ['internal_admin', 'customer_visible', 'developer_only'])->default('internal_admin');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logic_documents');
    }
};
