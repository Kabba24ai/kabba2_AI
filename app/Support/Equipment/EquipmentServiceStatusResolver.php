<?php

namespace App\Support\Equipment;

use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Support\Collection;

class EquipmentServiceStatusResolver
{
    /**
     * Resolve the service status (overdue / pending / completed / not_due / empty)
     * for a single equipment item. Shared by the web rental-ready screen and the
     * admin app equipment API so both surfaces agree on the same status.
     *
     * @param  Equipment  $item
     * @param  Collection  $serviceRecords  keyed by "{equipment_id}_{service_task_id}"
     * @param  int  $pendingBeforeHours
     * @param  int  $pendingAfterHours
     */
    public static function resolve($item, Collection $serviceRecords, int $pendingBeforeHours, int $pendingAfterHours): string
    {
        if (!$item->serviceTemplate || !$item->serviceTemplate->preset || $item->serviceTemplate->templateTasks->isEmpty()) {
            return 'empty';
        }

        $intervalType = $item->serviceTemplate->preset->interval_type ?? 'hour';
        $isDateBased = $intervalType !== 'hour';

        $currentValue = $isDateBased && $item->date_acquired
            ? ceil((time() - strtotime($item->date_acquired)) / (60 * 60 * 24))
            : ($item->equipment_hours ?? 0);

        $hasOverdue = false;
        $hasPending = false;
        $hasNotDue = false;
        $totalTasks = 0;
        $completedTasks = 0;

        foreach ($item->serviceTemplate->templateTasks as $templateTask) {
            $taskId = $templateTask->task?->id;
            if (!$taskId) {
                continue;
            }

            $ints = $templateTask->intervals ?? ($templateTask->intervals_json ?? ($templateTask->interval ?? []));
            $arr = is_array($ints) ? $ints : (is_string($ints) ? json_decode($ints, true) ?? [] : [$ints]);

            foreach ($arr as $interval) {
                $totalTasks++;

                $recordKey = $item->id . '_' . $taskId;
                $records = $serviceRecords[$recordKey] ?? collect();

                if ($records->contains(fn($record) => $record->interval_value == $interval)) {
                    $completedTasks++;
                    continue;
                }

                $greyThreshold = $interval - $pendingBeforeHours;
                $yellowMax = $interval + $pendingAfterHours;

                if ($currentValue < $greyThreshold) {
                    $hasNotDue = true;
                } elseif ($currentValue <= $yellowMax) {
                    $hasPending = true;
                } else {
                    $hasOverdue = true;
                }
            }
        }

        if ($hasOverdue) {
            return 'overdue';
        }

        if ($hasPending) {
            return 'pending';
        }

        if ($totalTasks > 0 && $completedTasks === $totalTasks) {
            return 'completed';
        }

        if ($hasNotDue) {
            return 'not_due';
        }

        return 'empty';
    }
}
