@php
    $statusColors = [
        'open' => 'bg-gray-100 text-gray-700',
        'in_progress' => 'bg-blue-100 text-blue-700',
        'waiting' => 'bg-yellow-100 text-yellow-700',
        'escalated' => 'bg-red-100 text-red-700',
        'completed' => 'bg-green-100 text-green-700',
    ];
    $priorityColors = [
        'low' => 'bg-gray-100 text-gray-600',
        'normal' => 'bg-blue-50 text-blue-600',
        'high' => 'bg-orange-100 text-orange-700',
        'urgent' => 'bg-red-100 text-red-700',
    ];
    $resolutionLabels = \App\Services\ResolutionCenter\ResolutionPolicy::LABELS + \App\Services\ResolutionCenter\ManualResolutionScenario::RESOLUTION_LABELS;
@endphp

<div class="overflow-x-auto bg-white rounded-xl border border-gray-200 shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr class="text-left text-[11px] uppercase tracking-wide text-gray-400">
                <th class="px-4 py-3">Case #</th>
                <th class="px-4 py-3">Customer</th>
                <th class="px-4 py-3">Order</th>
                <th class="px-4 py-3">Scenario</th>
                <th class="px-4 py-3">Issue Category</th>
                <th class="px-4 py-3">Assigned</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Recommended</th>
                <th class="px-4 py-3">Priority</th>
                <th class="px-4 py-3">Created</th>
                <th class="px-4 py-3">Last Activity</th>
                <th class="px-4 py-3">Age</th>
                <th class="px-4 py-3">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($cases as $case)
                @php
                    $recommendedKeys = $case->recommended_resolution ? explode(',', $case->recommended_resolution) : [];
                @endphp
                <tr>
                    <td class="px-4 py-3">
                        <a class="text-blue-600 hover:underline font-medium" href="{{ route('admin.resolution-center.show', $case->unique_id) }}">{{ $case->unique_id }}</a>
                    </td>
                    <td class="px-4 py-3">{{ optional($case->customer)->first_name }} {{ optional($case->customer)->last_name }}</td>
                    <td class="px-4 py-3">#{{ optional($case->order)->order_number }}</td>
                    <td class="px-4 py-3">{{ $case->scenario()->label() }}</td>
                    <td class="px-4 py-3">{{ \App\Services\ResolutionCenter\ManualResolutionScenario::CATEGORY_LABELS[$case->issue_category] ?? ($case->issue_category ?? '—') }}</td>
                    <td class="px-4 py-3">{{ optional($case->assignedTo)->full_name ?? 'Unassigned' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs {{ $statusColors[$case->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ str_replace('_', ' ', ucfirst($case->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @foreach($recommendedKeys as $key)
                            <span class="inline-block text-xs text-gray-600">{{ $resolutionLabels[$key] ?? $key }}</span>
                        @endforeach
                        @if(empty($recommendedKeys))—@endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs {{ $priorityColors[$case->priority] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ ucfirst($case->priority) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $case->created_at->format('M j, Y') }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $case->updated_at->diffForHumans() }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $case->created_at->diffInDays(now()) }}d</td>
                    <td class="px-4 py-3">
                        @can('resolution_center.override')
                        <div class="flex flex-wrap gap-1">
                            <button type="button" data-action="open-assign" data-unique-id="{{ $case->unique_id }}" data-current-assignee="{{ $case->assigned_to_user_id }}" class="px-2 py-1 rounded border border-gray-300 text-xs">Assign</button>
                            @if($case->status !== 'escalated' && $case->status !== 'completed')
                                <button type="button" data-action="quick-status" data-endpoint="escalate" data-unique-id="{{ $case->unique_id }}" class="px-2 py-1 rounded border border-red-300 text-red-700 text-xs">Escalate</button>
                            @endif
                            @if($case->status !== 'completed')
                                <button type="button" data-action="quick-status" data-endpoint="close" data-unique-id="{{ $case->unique_id }}" class="px-2 py-1 rounded border border-green-300 text-green-700 text-xs">Close</button>
                            @else
                                <button type="button" data-action="quick-status" data-endpoint="reopen" data-unique-id="{{ $case->unique_id }}" class="px-2 py-1 rounded border border-gray-300 text-xs">Reopen</button>
                            @endif
                        </div>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="px-4 py-6 text-center text-gray-500">No Resolution Cases match these filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(!is_array($cases) && $cases->hasPages())
    <div class="mt-4">{{ $cases->links() }}</div>
@endif
