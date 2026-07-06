<?php

namespace App\Services\WaitList;

use App\Enums\WaitList\WaitListAlertStatus;
use App\Models\WaitList\EquipmentWaitListAlert;
use App\Services\FirebaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Internal (staff-only) mobile pushes for wait list alerts — customers never
 * receive automatic messages. Delivery respects business hours: after-hours
 * alerts keep the alert immediately but defer the push to the next opening.
 * A second push fires if nobody acknowledges within the configured delay.
 */
class WaitListPushService
{
    public function __construct(private FirebaseService $firebase)
    {
    }

    /** @param Collection<EquipmentWaitListAlert> $alerts */
    public function sendFirstPush(Collection $alerts): void
    {
        foreach ($alerts as $alert) {
            if (BusinessHours::isOpen()) {
                $this->push($alert, first: true);
            } else {
                $alert->update(['push_deferred_until' => BusinessHours::nextOpening()]);
            }
        }
    }

    /**
     * Scheduler entry point: deliver deferred first pushes once business
     * hours begin, and second pushes for unacknowledged alerts.
     */
    public function processPending(): void
    {
        if (!BusinessHours::isOpen()) {
            return;
        }

        // Deferred first pushes whose window has arrived
        EquipmentWaitListAlert::open()
            ->whereNull('first_push_sent_at')
            ->whereNotNull('push_deferred_until')
            ->where('push_deferred_until', '<=', now())
            ->get()
            ->each(fn ($alert) => $this->push($alert, first: true));

        // Second push: still unacknowledged N minutes after the first
        $delay = (int) config('waitlist.second_push_delay_minutes', 30);

        EquipmentWaitListAlert::open()
            ->whereNotNull('first_push_sent_at')
            ->whereNull('second_push_sent_at')
            ->where('first_push_sent_at', '<=', now()->subMinutes($delay))
            ->get()
            ->each(fn ($alert) => $this->push($alert, first: false));
    }

    private function push(EquipmentWaitListAlert $alert, bool $first): void
    {
        if ($alert->status !== WaitListAlertStatus::Unacknowledged) {
            return;
        }

        $waitList  = $alert->waitList;
        $equipment = $alert->equipment;

        $title = $first ? 'Wait List Match' : 'Wait List Match — Still Unacknowledged';
        $body  = sprintf(
            '%s is waiting for %s. Returned: %s.',
            $waitList->company_name ?: $waitList->customer_name,
            $waitList->demandLabel(),
            $equipment?->equipment_name ?? "equipment #{$alert->equipment_id}",
        );

        try {
            $this->firebase->sendToAllDevices($title, $body, [
                'type'          => 'wait_list_alert',
                'alert_id'      => (string) $alert->id,
                'wait_list_id'  => (string) $alert->equipment_wait_list_id,
                'equipment_id'  => (string) $alert->equipment_id,
            ]);
        } catch (\Throwable $e) {
            // Push delivery must never break returns or the scheduler.
            Log::warning('Wait list push failed', ['alert_id' => $alert->id, 'error' => $e->getMessage()]);
        }

        $alert->update($first
            ? ['first_push_sent_at' => now(), 'push_deferred_until' => null]
            : ['second_push_sent_at' => now()]);
    }
}
