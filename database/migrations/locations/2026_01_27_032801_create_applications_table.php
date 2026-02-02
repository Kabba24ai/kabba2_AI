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
            Schema::create('applications', function (Blueprint $table) {
                $table->id();

                $table->uuid('unique_id')->unique();
                $table->tinyInteger('status')->default(0);

                $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();

                $table->string('first_name');
                $table->string('last_name');
                $table->string('email');
                $table->string('phone')->nullable();

                $table->date('start_date')->nullable();

                // JSON groups
                $table->json('personal_details')->nullable();
                $table->json('job_preferences')->nullable();
                $table->json('experience_details')->nullable();
                $table->json('skills')->nullable();
                $table->json('driving_details')->nullable();

                // Resume
                $table->unsignedBigInteger('resume_media_id')->nullable();
                $table->foreign('resume_media_id')->references('id')->on('media')->nullOnDelete();

                $table->timestamps();
            });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
