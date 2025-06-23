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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable(); // nullable if guest
            $table->unsignedBigInteger('media_id')->nullable();
            $table->string('phone')->nullable();
            $table->date('dob')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Archived'])->default('Active');
            $table->boolean('is_guest')->default(false);
            $table->enum('tax_status', ['Taxable', 'Exempt'])->default('Taxable');
            $table->unsignedBigInteger('tax_document_media_id')->nullable();
            $table->date('tax_document_upload_date')->nullable();
            $table->date('tax_document_valid_until')->nullable();
            $table->boolean('is_credit_account')->default(false);
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->timestamps();

            // If media_id relates to a media table:
            $table->foreign('media_id')->references('id')->on('media')->nullOnDelete();
            $table->foreign('tax_document_media_id')->references('id')->on('media')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
