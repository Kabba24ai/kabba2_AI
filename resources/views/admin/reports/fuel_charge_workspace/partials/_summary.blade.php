{{-- Dashboard V2 Phase 1A — compact operational mini-dashboard. Values come
     from ChargeAlertQueue::summarize() — the identical calculation behind
     the dashboard Fuel card, so these numbers always match it. --}}
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
    <div class="rounded-xl border border-orange-200 bg-orange-50/60 p-4">
        <div class="text-2xl font-bold text-orange-700" data-metric="outstanding">{{ $summary['outstanding'] }}</div>
        <div class="text-xs font-medium text-gray-600 mt-1">Outstanding</div>
    </div>
    <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-4">
        <div class="text-2xl font-bold text-blue-700">{{ $summary['new_today'] }}</div>
        <div class="text-xs font-medium text-gray-600 mt-1">New Today</div>
    </div>
    <div class="rounded-xl border border-green-200 bg-green-50/60 p-4">
        <div class="text-2xl font-bold text-green-700">{{ $summary['completed_today'] }}</div>
        <div class="text-xs font-medium text-gray-600 mt-1">Completed Today</div>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4">
        <div class="text-2xl font-bold text-emerald-700">{{ $summary['resolved_this_week'] }}</div>
        <div class="text-xs font-medium text-gray-600 mt-1">Resolved This Week</div>
    </div>
    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
        <div class="text-2xl font-bold text-gray-700">{{ $summary['avg_age_days'] }}<span class="text-sm font-medium text-gray-500 ml-1">days</span></div>
        <div class="text-xs font-medium text-gray-600 mt-1">Average Age</div>
    </div>
</div>
