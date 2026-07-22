<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Help Needed conversation sync: a native comment on a child (Help Needed)
 * task is mirrored up to its parent as a synchronized projection so the
 * original owner follows one canonical conversation. These columns identify
 * a projection's origin and, via the UNIQUE source_comment_id, guarantee a
 * given child comment mirrors to at most one parent comment (no loops, no
 * duplicates). A null source_comment_id marks a native, non-projected comment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_task_comments', function (Blueprint $table) {
            $table->foreignId('source_task_id')->nullable()->after('comment_type')
                ->constrained('daily_tasks')->cascadeOnDelete();
            $table->foreignId('source_comment_id')->nullable()->after('source_task_id')
                ->unique()->constrained('daily_task_comments')->cascadeOnDelete();
            $table->foreignId('source_user_id')->nullable()->after('source_comment_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_task_comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_comment_id');
            $table->dropConstrainedForeignId('source_task_id');
            $table->dropConstrainedForeignId('source_user_id');
        });
    }
};
