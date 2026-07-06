@extends('admin.layouts.app')

@section('title', 'Wait List Alerts')

@push('css')
<style> main { background-color: #f8fafc; flex: 1 1 auto; } </style>
@endpush

@section('content')

    @include('flash::message')

    @php use App\Enums\WaitList\WaitListAlertStatus; @endphp

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 flex items-center gap-2">
                    <x-heroicon-o-bell-alert class="w-6 h-6 text-red-600" />
                    Wait List Alerts
                </h1>
                <p class="text-sm text-gray-500 mt-1">Returned equipment matched active customer demand. Managers decide what happens next.</p>
            </div>
            <div class="flex items-center gap-2">
                @foreach (['open' => 'Needs Attention', 'all' => 'History'] as $key => $label)
                    <a href="{{ route('admin.wait-list.alerts', ['view' => $key]) }}"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition
                        {{ $view === $key ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                        {{ $label }}
                    </a>
                @endforeach
                <a href="{{ route('admin.wait-list.index') }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold border bg-white text-gray-600 border-gray-200 hover:bg-gray-50 transition">
                    All Wait Lists
                </a>
            </div>
        </div>
    </div>

    @if ($alerts->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center mb-8">
            <p class="text-sm text-gray-400">No {{ $view === 'open' ? 'open' : '' }} wait list alerts.</p>
        </div>
    @else
        <div class="space-y-4 mb-8">
            @foreach ($alerts as $alert)
                @php $waitList = $alert->waitList; @endphp
                <div class="bg-white rounded-xl border {{ $alert->status === WaitListAlertStatus::Unacknowledged ? 'border-red-300' : 'border-gray-200' }} shadow-sm p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $alert->status->color() }}">
                                    {{ $alert->status->label() }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                    {{ $alert->match_type->label() }} Match
                                </span>
                                <span class="text-xs text-gray-400">{{ $alert->created_at->format('M j, Y g:i A') }}</span>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 mt-2">
                                {{ $waitList->company_name ?: $waitList->customer_name }}
                                <span class="font-normal text-gray-500">is waiting for</span>
                                {{ $waitList->demandLabel() }}
                            </p>
                            <p class="text-sm text-gray-600 mt-1">
                                Returned: <span class="font-medium">{{ $alert->equipment?->equipment_name ?? "Equipment #{$alert->equipment_id}" }}</span>
                                @if ($alert->matchedCategory) · Category: {{ $alert->matchedCategory->title }} @endif
                                · Status at match: {{ ucfirst($alert->equipment_status_at_match ?? '—') }}
                                · Current status: {{ $alert->equipment?->current_status?->label() ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1.5">
                                {{ $waitList->customer_name }} · {{ $waitList->phone ?? 'no phone' }} · {{ $waitList->email ?? 'no email' }}
                                · {{ $waitList->store_preference->label() }}@if ($waitList->store) ({{ $waitList->store->store_name }})@endif
                                · Waiting {{ $waitList->age_days }} {{ Str::plural('day', $waitList->age_days) }}
                            </p>
                            @if ($waitList->reason)
                                <p class="text-xs text-gray-500 mt-1"><span class="font-semibold">Reason:</span> {{ $waitList->reason }}</p>
                            @endif
                            @if ($waitList->internal_notes)
                                <p class="text-xs text-gray-500 mt-0.5"><span class="font-semibold">Notes:</span> {{ $waitList->internal_notes }}</p>
                            @endif
                            @if ($alert->acknowledged_at)
                                <p class="text-xs text-green-600 mt-1.5">
                                    Acknowledged by {{ $alert->acknowledgedBy?->full_name ?? '—' }} · {{ $alert->acknowledged_at->format('M j, Y g:i A') }}
                                </p>
                            @endif
                        </div>

                        <div class="flex flex-col gap-2 shrink-0 w-44">
                            @if ($alert->status === WaitListAlertStatus::Unacknowledged)
                                <form method="POST" action="{{ route('admin.wait-list.alerts.acknowledge', $alert) }}">
                                    @csrf
                                    <button type="submit" class="w-full px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-600 text-white hover:bg-green-700 transition">
                                        Acknowledge
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('admin.wait-list.show', $waitList) }}"
                                class="w-full text-center px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 transition">
                                Open Wait List
                            </a>
                            @if ($waitList->customer)
                                <a href="{{ route('admin.crm.customers.view', $waitList->customer->unique_id) }}"
                                    class="w-full text-center px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 transition">
                                    Open CRM Customer
                                </a>
                            @endif
                            @if ($alert->equipment)
                                <a href="{{ route('admin.checklist-management.equipment-management.show', $alert->equipment) }}"
                                    class="w-full text-center px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 transition">
                                    Open Equipment
                                </a>
                            @endif
                            @if ($alert->status === WaitListAlertStatus::Unacknowledged)
                                <form method="POST" action="{{ route('admin.wait-list.alerts.dismiss', $alert) }}"
                                    onsubmit="return confirm('Dismiss this alert? It stays in history.');">
                                    @csrf
                                    <button type="submit" class="w-full px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-200 bg-white text-gray-400 hover:text-red-600 transition">
                                        Dismiss
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mb-8">{{ $alerts->links() }}</div>
    @endif

@endsection
