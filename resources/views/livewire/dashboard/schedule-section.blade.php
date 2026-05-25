<div wire:poll.30s="refreshData">
    @include('admin.dashboard.partials._schedule_section', [
        'scheduleStats' => $scheduleStats,
        'equipmentStats' => $equipmentStats,
        'pendingCount' => $pendingCount,
        'overdueCount' => $overdueCount,
    ])
</div>
