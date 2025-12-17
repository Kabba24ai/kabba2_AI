<div wire:poll.5s="refreshData">
    @include('admin.dashboard.partials._schedule_section', [
        'scheduleStats' => $scheduleStats,
        'equipmentStats' => $equipmentStats,
    ])
</div>
