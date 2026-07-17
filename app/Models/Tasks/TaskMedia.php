<?php

namespace App\Models\Tasks;

use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Image/video attachment on a task (description) or one of its comments.
 * Physical files live on the dedicated task_media disk under {task_id}/…
 * and are removed with the row (deleting hook), so the 30-day
 * post-completion purge and task deletion both clean the disk.
 */
class TaskMedia extends Model
{
    public const DISK = 'task_media';

    protected $table = 'daily_task_media';

    protected $fillable = [
        'task_id',
        'task_comment_id',
        'media_type',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $media) {
            if ($media->file_path) {
                Storage::disk(self::DISK)->delete($media->file_path);
            }
        });
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function comment()
    {
        return $this->belongsTo(TaskComment::class, 'task_comment_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk(self::DISK)->url($this->file_path) : null;
    }

    public function isImage(): bool
    {
        return $this->media_type === 'image';
    }
}
