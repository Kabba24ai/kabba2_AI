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
 * Scheduler tick: any Scheduled broadcast whose time has arrived moves to
 * Sending and its frozen package is dispatched for transmission. Editing
 * stops the moment this transition happens.
 */
class ProcessScheduledSmsBroadcastsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        SmsBroadcastEvent::query()
            ->where('status', SmsBroadcastStatus::Scheduled->value)
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->get()
            ->each(function (SmsBroadcastEvent $event) {
                $event->update([
                    'status'             => SmsBroadcastStatus::Sending,
                    'sending_started_at' => now(),
                ]);

                SendSmsBroadcastEventJob::dispatch($event->id);

                Log::info('[SMS Broadcast] Scheduled broadcast released for sending.', [
                    'event_id'     => $event->id,
                    'scheduled_at' => $event->scheduled_at?->toDateTimeString(),
                ]);
            });
    }
}
