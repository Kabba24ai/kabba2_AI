@extends('admin.layouts.app')

@section('title', 'Wait List Matches')

@push('css')
<style> main { background-color: #f8fafc; flex: 1 1 auto; } </style>
@endpush

@section('content')

    @include('flash::message')

    @php
        use App\Enums\Equipments\EquipmentCurrentStatus;
        use App\Enums\WaitList\WaitListAlertDisposition;
        use App\Enums\WaitList\WaitListAlertStatus;
    @endphp

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 flex items-center gap-2">
                    <x-heroicon-o-bell-alert class="w-6 h-6 text-red-600" />
                    Wait List Matches
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    A returned unit may suit an active request — evaluate, contact the customer, and record the outcome.
                    Nothing is reserved or promised automatically.
                </p>
            </div>
            <div class="flex items-center gap-2">
                @foreach (['open' => 'Contact Needed', 'all' => 'History'] as $key => $label)
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
            <p class="text-sm text-gray-400">No {{ $view === 'open' ? 'open' : '' }} wait list matches.</p>
        </div>
    @else
        <div class="space-y-4 mb-8">
            @foreach ($alerts as $alert)
                @php
                    $waitList = $alert->waitList;
                    $currentStatus = $alert->equipment?->current_status;
                    $statusTone = match ($currentStatus) {
                        EquipmentCurrentStatus::Available => 'bg-green-100 text-green-700',
                        EquipmentCurrentStatus::Maintenance => 'bg-amber-100 text-amber-800',
                        EquipmentCurrentStatus::Damaged => 'bg-red-100 text-red-700',
                        default => 'bg-gray-100 text-gray-600',
                    };
                    $isOpen = $alert->status !== WaitListAlertStatus::Dismissed;
                @endphp
                <div class="bg-white rounded-xl border {{ $alert->status === WaitListAlertStatus::Unacknowledged ? 'border-red-300' : 'border-gray-200' }} shadow-sm p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $alert->status->color() }}">
                                    {{ $alert->status->label() }}
                                </span>
                                @if ($alert->disposition)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $alert->disposition->color() }}">
                                        {{ $alert->disposition->label() }}
                                    </span>
                                @endif
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                    {{ $alert->match_type->label() }} Match
                                </span>
                                <span class="text-xs text-gray-400">Matched {{ $alert->created_at->format('M j, Y g:i A') }}</span>
                            </div>

                            <p class="text-sm font-semibold text-gray-900 mt-2">
                                {{ $waitList->company_name ?: $waitList->customer_name }}
                                <span class="font-normal text-gray-500">is waiting in</span>
                                {{ $waitList->category?->title ?? $waitList->demandLabel() }}
                            </p>

                            {{-- The returned unit and its REAL current status --}}
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-700">
                                <span>Returned unit:</span>
                                <span class="font-semibold">{{ $alert->equipment?->equipment_name ?? "Equipment #{$alert->equipment_id}" }}</span>
                                @if ($alert->matchedProduct)
                                    <span class="text-gray-400">·</span>
                                    <span>Product: <span class="font-medium">{{ $alert->matchedProduct->product_name }}</span></span>
                                @endif
                                <span class="text-gray-400">·</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $statusTone }}">
                                    {{ $currentStatus?->label() ?? 'Unknown' }}
                                </span>
                                @if (in_array($currentStatus, [EquipmentCurrentStatus::Maintenance, EquipmentCurrentStatus::Damaged], true))
                                    <span class="text-[11px] font-medium text-amber-700">Returned — not immediately available; evaluate before promising.</span>
                                @endif
                                @if ($alert->equipment?->store)
                                    <span class="text-gray-400">·</span>
                                    <span>At {{ $alert->equipment->store->store_name }}</span>
                                @endif
                            </div>

                            {{-- The record's selected acceptable products --}}
                            @if ($waitList->selectedProducts->isNotEmpty())
                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                    <span class="text-xs text-gray-500">Acceptable products:</span>
                                    @foreach ($waitList->selectedProducts as $product)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium
                                            {{ $product->id === $alert->matched_product_id ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $product->product_name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            <p class="text-xs text-gray-500 mt-2">
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
                                    Worked by {{ $alert->acknowledgedBy?->full_name ?? '—' }} · {{ $alert->acknowledged_at->format('M j, Y g:i A') }}
                                </p>
                            @endif
                            @if ($alert->dismissed_at)
                                <p class="text-xs text-gray-400 mt-0.5">Resolved {{ $alert->dismissed_at->format('M j, Y g:i A') }}</p>
                            @endif
                        </div>

                        <div class="flex flex-col gap-2 shrink-0 w-56">
                            @if ($isOpen)
                                {{-- Record the outcome of this match opportunity --}}
                                <form method="POST" action="{{ route('admin.wait-list.alerts.disposition', $alert) }}" class="space-y-2">
                                    @csrf
                                    <select name="disposition" required class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs bg-white">
                                        <option value="">— Record outcome —</option>
                                        @foreach (WaitListAlertDisposition::options() as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="w-full px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 text-white hover:bg-blue-700 transition">
                                        Save Outcome
                                    </button>
                                </form>
                                @if ($alert->status === WaitListAlertStatus::Unacknowledged)
                                    <form method="POST" action="{{ route('admin.wait-list.alerts.acknowledge', $alert) }}">
                                        @csrf
                                        <button type="submit" class="w-full px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-600 text-white hover:bg-green-700 transition">
                                            Acknowledge
                                        </button>
                                    </form>
                                @endif
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
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mb-8">{{ $alerts->links() }}</div>
    @endif

@endsection
