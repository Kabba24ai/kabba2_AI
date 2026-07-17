<?php

namespace App\Jobs;

use App\Enums\Communication\SmsBroadcastStatus;
use App\Models\Customers\SmsBroadcastEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Completed and cancelled broadcasts stay in the active queue view for
 * seven days, then move to the Archive automatically. Archived records
 * keep their frozen content and per-recipient results — nothing is
 * deleted here.
 */
class ArchiveCompletedSmsBroadcastsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const ACTIVE_DAYS = 7;

    public function handle(): void
    {
        $cutoff = now()->subDays(self::ACTIVE_DAYS);

        $count = SmsBroadcastEvent::query()
            ->whereNull('archived_at')
            ->whereIn('status', [
                SmsBroadcastStatus::Sent->value,
                SmsBroadcastStatus::PartiallySent->value,
                SmsBroadcastStatus::Failed->value,
                SmsBroadcastStatus::Cancelled->value,
            ])
            ->where(function ($query) use ($cutoff) {
                $query->where('completed_at', '<=', $cutoff)
                    ->orWhere(function ($q) use ($cutoff) {
                        $q->whereNull('completed_at')->where('cancelled_at', '<=', $cutoff);
                    });
            })
            ->update(['archived_at' => now()]);

        if ($count > 0) {
            Log::info('[SMS Broadcast] Auto-archived completed broadcasts.', ['count' => $count]);
        }
    }
}
