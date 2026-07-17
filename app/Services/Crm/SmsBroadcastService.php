<?php

namespace App\Services\Crm;

use App\Enums\Communication\SmsBroadcastStatus;
use App\Jobs\SendSmsBroadcastEventJob;
use App\Models\Customers\SmsBroadcastEvent;
use Illuminate\Support\Facades\DB;

/**
 * Broadcast lifecycle: freezing the prepared package when an event is
 * queued, rebuilding it on explicit edit, and the final send/schedule
 * authorization. Sending NEVER resolves audiences — only the frozen
 * sms_broadcast_recipients package is transmitted.
 */
class SmsBroadcastService
{
    public function __construct(private SmsAudienceResolver $resolver)
    {
    }

    /**
     * Freeze the prepared package and move the event to Awaiting
     * Confirmation. Snapshots the message content as it exists right now
     * and resolves the audience rules against current CRM data. Returns
     * the resolution stats.
     */
    public function queueEvent(SmsBroadcastEvent $event): array
    {
        $resolution = $this->resolver->resolve($event->audienceSpec());
        $recipients = $resolution['recipients'];

        DB::transaction(function () use ($event, $recipients) {
            // Replace any previous package (re-queue after edit)
            $event->recipients()->delete();

            $now = now();
            $rows = $recipients->map(fn (array $recipient) => [
                'sms_broadcast_event_id' => $event->id,
                'customer_id'            => $recipient['customer']->id,
                'customer_name'          => trim($recipient['customer']->first_name . ' ' . $recipient['customer']->last_name),
                'phone'                  => $recipient['phone'],
                'status'                 => 'pending',
                'created_at'             => $now,
                'updated_at'             => $now,
            ])->all();

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('sms_broadcast_recipients')->insert($chunk);
            }

            // Freeze the message content exactly as it stands at queue time
            $message = $event->message;

            $event->update([
                'message_name'    => $message?->name ?? $event->message_name,
                'message_content' => $message?->description ?? $event->message_content,
                'category_name'   => $message?->category?->name ?? $event->category_name,
                'recipient_count' => $recipients->count(),
                'sent_count'      => 0,
                'failed_count'    => 0,
                'status'          => SmsBroadcastStatus::AwaitingConfirmation,
                'queued_at'       => now(),
                'scheduled_at'    => null,
                'completed_at'    => null,
            ]);
        });

        return $resolution['stats'];
    }

    /**
     * Explicit edit of message or audience: discard the prepared package
     * and return the event to Draft so review + final confirmation are
     * required again.
     */
    public function revertToDraft(SmsBroadcastEvent $event, int $wizardStep = 1): void
    {
        DB::transaction(function () use ($event, $wizardStep) {
            $event->recipients()->delete();
            $event->update([
                'status'          => SmsBroadcastStatus::Draft,
                'wizard_step'     => $wizardStep,
                'recipient_count' => 0,
                'sent_count'      => 0,
                'failed_count'    => 0,
                'queued_at'       => null,
                'scheduled_at'    => null,
            ]);
        });
    }

    /** Final authorization: transmit the frozen package now. */
    public function sendNow(SmsBroadcastEvent $event): void
    {
        $event->update([
            'status'             => SmsBroadcastStatus::Sending,
            'sending_started_at' => now(),
            'scheduled_at'       => null,
        ]);

        SendSmsBroadcastEventJob::dispatch($event->id);
    }

    /** Final authorization: transmit the frozen package at a set time. */
    public function schedule(SmsBroadcastEvent $event, \DateTimeInterface|string $when): void
    {
        $event->update([
            'status'       => SmsBroadcastStatus::Scheduled,
            'scheduled_at' => $when,
        ]);
    }

    public function cancel(SmsBroadcastEvent $event): void
    {
        $event->update([
            'status'       => SmsBroadcastStatus::Cancelled,
            'cancelled_at' => now(),
            'scheduled_at' => null,
        ]);
    }
}
