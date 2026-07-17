<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Image/video attachments for Task Center tasks — on the task itself
 * (description evidence) or on an individual comment. Files live on the
 * dedicated task_media disk so the 30-days-after-completion purge can
 * flush them without touching other modules' media.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_task_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')
                ->constrained('daily_tasks')->cascadeOnDelete();
            // Null = attached to the task description; set = attached to a comment
            $table->foreignId('task_comment_id')->nullable()
                ->constrained('daily_task_comments')->cascadeOnDelete();
            $table->string('media_type', 10); // image | video
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('uploaded_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_task_media');
    }
};
