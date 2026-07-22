@extends('admin.layouts.app')

@section('title', 'Service Operations')

@push('css')
<style>
    /* Light slate canvas behind the cards — scoped to this page */
    main {
        background-color: #f8fafc;
        flex: 1 1 auto;
    }
</style>
@endpush

@section('content')

    {{-- ===== Header card ===== --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold flex items-center gap-2">
                    <x-heroicon-o-wrench class="w-6 h-6 text-blue-600" />
                    Service Operations
                </h1>
                <p class="text-sm text-gray-500 mt-1">{{ now()->format('l, F j, Y') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.service-management.tickets.create') }}"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg font-medium text-sm transition">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    New Service Ticket
                </a>
                {{-- ST-5: Field Service shipped — the "Coming in Phase 3"
                     stub is now a live link to the Field Service intake. --}}
                <a href="{{ route('admin.field-service.tickets.create') }}"
                    class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2.5 rounded-lg font-medium text-sm transition">
                    <x-heroicon-o-truck class="w-4 h-4" />
                    Field Service Call
                </a>
                <a href="{{ route('admin.service-management.tickets.index') }}"
                    class="inline-flex items-center gap-2 border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 px-4 py-2.5 rounded-lg font-medium text-sm transition">
                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                    Search Tickets
                </a>
            </div>
        </div>
    </div>

    {{-- ===== KPI cards ===== --}}
    @php
        // ST-5: each tile deep-links into the ticket list using filters the
        // index already supports, so the destination matches the count.
        $ticketsIndex = route('admin.service-management.tickets.index');
        $kpiCards = [
            ['label' => 'Open Tickets',  'count' => $kpis['open'],          'icon' => 'ticket',               'iconColor' => 'text-blue-600',  'iconBg' => 'bg-blue-100',  'hint' => 'All unfinished tickets',        'href' => $ticketsIndex],
            ['label' => 'Emergency',     'count' => $kpis['emergency'],     'icon' => 'exclamation-triangle', 'iconColor' => 'text-red-600',   'iconBg' => 'bg-red-100',   'hint' => 'Open emergency priority',       'href' => $ticketsIndex . '?priority=emergency'],
            ['label' => 'Blocked',       'count' => $kpis['blocked'],       'icon' => 'pause-circle',         'iconColor' => 'text-amber-600', 'iconBg' => 'bg-amber-100', 'hint' => 'Waiting on parts / approvals',  'href' => $ticketsIndex . '?state=blocked'],
            ['label' => 'Ready to Bill', 'count' => $kpis['ready_to_bill'], 'icon' => 'banknotes',            'iconColor' => 'text-green-600', 'iconBg' => 'bg-green-100', 'hint' => 'Completed, awaiting charges',    'href' => $ticketsIndex . '?financial_status=ready_to_bill'],
        ];
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        @foreach ($kpiCards as $card)
            <a href="{{ $card['href'] }}"
                class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-center gap-4 hover:border-blue-300 hover:shadow-md transition">
                <span class="w-12 h-12 rounded-full {{ $card['iconBg'] }} flex items-center justify-center shrink-0">
                    @svg('heroicon-o-' . $card['icon'], 'w-6 h-6 ' . $card['iconColor'])
                </span>
                <div class="min-w-0">
                    <div class="text-2xl font-bold text-gray-900 leading-tight">{{ $card['count'] }}</div>
                    <div class="text-sm font-medium text-gray-700">{{ $card['label'] }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">{{ $card['hint'] }}</div>
                </div>
            </a>
        @endforeach
    </div>

    {{-- ===== Summary cards ===== --}}
    @php
        $summaryCards = [
            ['title' => 'Shop Operations', 'icon' => 'wrench-screwdriver', 'iconColor' => 'text-blue-500',   'rows' => $shop],
            ['title' => 'Field Service',   'icon' => 'truck',              'iconColor' => 'text-purple-500', 'rows' => $field],
            ['title' => 'Financial',       'icon' => 'banknotes',          'iconColor' => 'text-green-600',  'rows' => $financial],
        ];
    @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
        @foreach ($summaryCards as $card)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-4">
                    @svg('heroicon-o-' . $card['icon'], 'w-5 h-5 ' . $card['iconColor'])
                    {{ $card['title'] }}
                </h2>
                <div class="space-y-2.5">
                    @foreach ($card['rows'] as $label => $value)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">{{ $label }}</span>
                            <span class="font-semibold text-gray-900">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== Active Work Queue ===== --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-8">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
            <x-heroicon-o-bolt class="w-5 h-5 text-green-600" />
            <h2 class="text-base font-semibold text-gray-800">Active Work Queue</h2>
            <span class="text-xs text-gray-400">(actionable now — priority first, oldest first)</span>
            <span class="ml-auto text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">{{ $activeQueue->count() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left whitespace-nowrap">
                <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
                    <tr>
                        <th class="py-3 px-5 text-left">Priority</th>
                        <th class="py-3 px-5 text-left">Ticket #</th>
                        <th class="py-3 px-5 text-left">Type / Equipment</th>
                        <th class="py-3 px-5 text-left">Assigned Personnel</th>
                        <th class="py-3 px-5 text-left">Status</th>
                        <th class="py-3 px-5 text-center">Age</th>
                        <th class="py-3 px-5 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($activeQueue as $ticket)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $ticket->priority->color() }}">
                                    {{ $ticket->priority->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-5 font-semibold text-gray-900">{{ $ticket->ticket_number }}</td>
                            <td class="py-3 px-5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $ticket->service_type->color() }}">
                                    {{ $ticket->service_type->label() }}
                                </span>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $ticket->equipment?->equipment_name ?? '—' }}
                                    @if($ticket->customer)
                                        · {{ trim($ticket->customer->first_name . ' ' . $ticket->customer->last_name) }}
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-5">
                                @include('admin.service_management.partials._personnel_avatars', ['people' => $ticket->personnel])
                            </td>
                            <td class="py-3 px-5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $ticket->repair_status->color() }}">
                                    {{ $ticket->repair_status->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-5 text-center">
                                <span class="{{ $ticket->age_days >= 7 ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                    {{ $ticket->age_days }}d
                                </span>
                            </td>
                            <td class="py-3 px-5">
                                <div class="flex gap-1.5 items-center justify-center">
                                    <a href="{{ route('admin.service-management.tickets.show', $ticket) }}" title="View ticket"
                                        class="p-1 text-sky-600 hover:text-sky-800"><x-heroicon-o-eye class="w-4 h-4" /></a>
                                    <a href="{{ route('admin.service-management.tickets.edit', $ticket) }}" title="Edit ticket"
                                        class="p-1 text-gray-500 hover:text-gray-700"><x-heroicon-o-pencil-square class="w-4 h-4" /></a>
                                    <a href="{{ route('admin.service-management.tickets.index') }}" title="All tickets"
                                        class="p-1 text-gray-400 hover:text-gray-600"><x-heroicon-o-ellipsis-horizontal class="w-4 h-4" /></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 px-5 text-center text-sm text-gray-400 italic">No actionable tickets — the shop floor is clear.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Blocked Work Queue ===== --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-8">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
            <x-heroicon-o-pause-circle class="w-5 h-5 text-amber-500" />
            <h2 class="text-base font-semibold text-gray-800">Blocked Work Queue</h2>
            <span class="text-xs text-gray-400">(waiting on parts / approvals — sorted by expected action date)</span>
            <span class="ml-auto text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">{{ $blockedQueue->count() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left whitespace-nowrap">
                <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
                    <tr>
                        <th class="py-3 px-5 text-left">Priority</th>
                        <th class="py-3 px-5 text-left">Ticket #</th>
                        <th class="py-3 px-5 text-left">Waiting On</th>
                        <th class="py-3 px-5 text-left">Expected Action</th>
                        <th class="py-3 px-5 text-left">Assigned Personnel</th>
                        <th class="py-3 px-5 text-left">Status</th>
                        <th class="py-3 px-5 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($blockedQueue as $ticket)
                        @php $overdue = $ticket->expected_action_date && $ticket->expected_action_date->isPast(); @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $ticket->priority->color() }}">
                                    {{ $ticket->priority->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-5 font-semibold text-gray-900">{{ $ticket->ticket_number }}</td>
                            <td class="py-3 px-5">
                                <div class="font-medium text-gray-800">{{ $ticket->repair_status->waitingOnLabel() }}</div>
                                @if($ticket->blocked_reason)
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $ticket->blocked_reason }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-5">
                                @if ($ticket->expected_action_date)
                                    <span class="{{ $overdue ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                                        {{ $ticket->expected_action_date->format('M j, Y') }}
                                    </span>
                                    @if($overdue)
                                        <div class="text-xs text-red-400 mt-0.5">Past due</div>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-5">
                                @include('admin.service_management.partials._personnel_avatars', ['people' => $ticket->personnel])
                            </td>
                            <td class="py-3 px-5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $ticket->repair_status->color() }}">
                                    {{ $ticket->repair_status->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-5">
                                <div class="flex gap-1.5 items-center justify-center">
                                    <a href="{{ route('admin.service-management.tickets.show', $ticket) }}" title="View ticket"
                                        class="p-1 text-sky-600 hover:text-sky-800"><x-heroicon-o-eye class="w-4 h-4" /></a>
                                    <a href="{{ route('admin.service-management.tickets.edit', $ticket) }}" title="Edit ticket"
                                        class="p-1 text-gray-500 hover:text-gray-700"><x-heroicon-o-pencil-square class="w-4 h-4" /></a>
                                    <a href="{{ route('admin.service-management.tickets.index') }}" title="All tickets"
                                        class="p-1 text-gray-400 hover:text-gray-600"><x-heroicon-o-ellipsis-horizontal class="w-4 h-4" /></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 px-5 text-center text-sm text-gray-400 italic">Nothing is blocked right now.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Service Alerts ===== --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-8">
        <h2 class="text-base font-semibold text-gray-800 flex items-center gap-2 mb-4">
            <x-heroicon-o-bell-alert class="w-5 h-5 text-red-500" />
            Service Alerts
            <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">{{ count($alerts) }}</span>
        </h2>
        @if (count($alerts) === 0)
            <p class="text-sm text-gray-400 italic">No alerts — everything is on track.</p>
        @else
            <div class="space-y-2">
                @foreach ($alerts as $alert)
                    @php
                        $alertStyles = [
                            'red'   => ['bg-red-50 border-red-200',     'text-red-500',   'heroicon-o-exclamation-circle'],
                            'amber' => ['bg-amber-50 border-amber-200', 'text-amber-500', 'heroicon-o-clock'],
                            'green' => ['bg-green-50 border-green-200', 'text-green-600', 'heroicon-o-check-circle'],
                        ][$alert['level']] ?? ['bg-gray-50 border-gray-200', 'text-gray-400', 'heroicon-o-information-circle'];
                    @endphp
                    <div class="flex items-start gap-2.5 rounded-lg border {{ $alertStyles[0] }} px-3.5 py-2.5">
                        @svg($alertStyles[2], 'w-4 h-4 mt-0.5 shrink-0 ' . $alertStyles[1])
                        <span class="text-sm text-gray-700">{{ $alert['text'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

@endsection
