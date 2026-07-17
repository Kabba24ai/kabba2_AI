<?php

namespace App\Jobs;

use App\Models\Tasks\Task;
use App\Models\Tasks\TaskMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Retention rule for Task Center attachments: 30 days after a task is
 * completed, its images/videos are flushed from the dedicated task_media
 * disk (files + daily_task_media rows). Task, comments, and history are
 * untouched — only the media goes.
 */
class PurgeCompletedTaskMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const RETENTION_DAYS = 30;

    public function handle(): void
    {
        $cutoff = now()->subDays(self::RETENTION_DAYS);

        $taskIds = Task::query()
            ->whereNotNull('completed_at')
            ->where('completed_at', '<=', $cutoff)
            ->whereHas('media')
            ->pluck('id');

        if ($taskIds->isEmpty()) {
            return;
        }

        $filesPurged = 0;

        foreach ($taskIds as $taskId) {
            // Delete through Eloquent so the TaskMedia hook removes each file
            TaskMedia::where('task_id', $taskId)->get()->each(function (TaskMedia $media) use (&$filesPurged) {
                $media->delete();
                $filesPurged++;
            });

            // Remove the now-empty per-task folder
            Storage::disk(TaskMedia::DISK)->deleteDirectory((string) $taskId);
        }

        Log::info('[Task Media Purge] Flushed attachments for completed tasks.', [
            'tasks'          => $taskIds->count(),
            'files_purged'   => $filesPurged,
            'retention_days' => self::RETENTION_DAYS,
            'cutoff'         => $cutoff->toDateTimeString(),
        ]);
    }
}
