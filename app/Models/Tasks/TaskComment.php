<?php

namespace App\Models\Tasks;

use App\Enums\Tasks\TaskCommentType;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    protected $table = 'daily_task_comments';

    protected $fillable = [
        'task_id',
        'user_id',
        'comment',
        'comment_type',
        'source_task_id',
        'source_comment_id',
        'source_user_id',
    ];

    protected $casts = [
        'comment_type' => TaskCommentType::class,
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->hasMany(TaskMedia::class, 'task_comment_id');
    }

    // ── Help Needed conversation sync ──────────────────────────────────

    /** The child (Help Needed) task a synchronized projection came from. */
    public function sourceTask()
    {
        return $this->belongsTo(Task::class, 'source_task_id');
    }

    /** The exact child comment this projection mirrors. */
    public function sourceComment()
    {
        return $this->belongsTo(self::class, 'source_comment_id');
    }

    /** The original author (used for display on a synchronized projection). */
    public function sourceUser()
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    /** True when this comment is a projection mirrored up from a child task. */
    public function isSynchronized(): bool
    {
        return $this->source_comment_id !== null;
    }

    /** Author to display — the original child author on a projection. */
    public function displayAuthor(): ?User
    {
        return $this->isSynchronized() ? ($this->sourceUser ?? $this->user) : $this->user;
    }

    protected static function booted(): void
    {
        // A native comment on a Help Needed child task mirrors up to its parent
        // as a synchronized projection, so the original owner follows one
        // canonical conversation. Projections carry source_comment_id and are
        // therefore never re-synced — this cannot loop, and the UNIQUE column
        // blocks a second projection of the same child comment.
        static::created(function (self $comment): void {
            if ($comment->source_comment_id !== null) {
                return; // already a projection — never synchronize a projection
            }

            $childTask = $comment->task;
            if (!$childTask || $childTask->parent_task_id === null) {
                return; // only native comments on a child task flow upward
            }

            $projectedType = match ($comment->comment_type) {
                TaskCommentType::Waiting   => TaskCommentType::Waiting,
                TaskCommentType::Completed => TaskCommentType::Completed,
                default                    => TaskCommentType::HelpNeededUpdate,
            };

            self::create([
                'task_id'           => $childTask->parent_task_id,
                'user_id'           => $comment->user_id,
                'comment'           => $comment->comment,
                'comment_type'      => $projectedType,
                'source_task_id'    => $childTask->id,
                'source_comment_id' => $comment->id,
                'source_user_id'    => $comment->user_id,
            ]);
        });
    }
}
