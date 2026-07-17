<?php

namespace App\Jobs;

use App\Enums\Communication\SmsBroadcastStatus;
use App\Enums\Communication\SmsType;
use App\Models\Customers\SmsBroadcastEvent;
use App\Services\TwilioService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Transmits ONE broadcast event's frozen recipient package.
 *
 * Operational rules:
 * - Processes only sms_broadcast_recipients rows — never re-resolves
 *   tags, never rebuilds the audience, never reloads the library message.
 * - Each recipient row records its own outcome, so an interruption
 *   resumes from the remaining 'pending' rows without changing who
 *   receives the broadcast.
 * - One recipient failure never terminates the run; the final status
 *   (Sent / Partially Sent / Failed) reflects actual outcomes.
 */
class SendSmsBroadcastEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $eventId)
    {
    }

    public function handle(): void
    {
        $event = SmsBroadcastEvent::find($this->eventId);

        if (!$event || $event->status !== SmsBroadcastStatus::Sending) {
            Log::warning('[SMS Broadcast] Event missing or not authorized for sending — skipping.', [
                'event_id' => $this->eventId,
                'status'   => $event?->status?->value,
            ]);
            return;
        }

        try {
            // Container-resolved so tests swap in a fake transport
            $twilio = app(TwilioService::class);
        } catch (\Throwable $e) {
            Log::error('[SMS Broadcast] Twilio is not configured — broadcast failed.', [
                'event_id' => $event->id,
                'error'    => $e->getMessage(),
            ]);

            $event->recipients()->where('status', 'pending')->update([
                'status'        => 'failed',
                'error_message' => 'Twilio is not configured: ' . $e->getMessage(),
            ]);
            $this->finalize($event);
            return;
        }

        // Fresh query (not a loaded relation): resume-safe after interruption
        $event->recipients()->where('status', 'pending')->orderBy('id')
            ->each(function ($recipient) use ($twilio, $event) {
                try {
                    $result = $twilio->sendSms($recipient->phone, (string) $event->message_content, [], [
                        'customer_id' => $recipient->customer_id,
                        'sms_type'    => SmsType::CRM_BROADCAST,
                    ]);
                } catch (\Throwable $e) {
                    $result = ['success' => false, 'message' => $e->getMessage()];
                }

                if (($result['success'] ?? false) === true) {
                    $recipient->update([
                        'status'        => 'sent',
                        'twilio_sid'    => $result['sid'] ?? null,
                        'error_message' => null,
                        'sent_at'       => now(),
                    ]);
                } else {
                    $recipient->update([
                        'status'        => 'failed',
                        'error_message' => $result['message'] ?? 'Unknown error',
                    ]);
                }
            });

        $this->finalize($event);
    }

    private function finalize(SmsBroadcastEvent $event): void
    {
        $sent   = $event->recipients()->where('status', 'sent')->count();
        $failed = $event->recipients()->where('status', 'failed')->count();

        $status = $failed === 0
            ? SmsBroadcastStatus::Sent
            : ($sent > 0 ? SmsBroadcastStatus::PartiallySent : SmsBroadcastStatus::Failed);

        $event->update([
            'sent_count'   => $sent,
            'failed_count' => $failed,
            'status'       => $status,
            'completed_at' => now(),
        ]);

        Log::info('[SMS Broadcast] Broadcast finished.', [
            'event_id' => $event->id,
            'sent'     => $sent,
            'failed'   => $failed,
            'status'   => $status->value,
        ]);
    }
}
