@extends('admin.layouts.app')

@section('title', $ticket->ticket_number)

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    @php
        use App\Enums\Service\RepairStatus;
        $isBlocked = $ticket->is_blocked;
        $isFinished = !in_array($ticket->repair_status->value, RepairStatus::notFinished(), true);
    @endphp

    {{-- ===== Header card ===== --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-semibold text-gray-900">{{ $ticket->ticket_number }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $ticket->priority->color() }}">
                        {{ $ticket->priority->label() }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->repair_status->color() }}">
                        {{ $ticket->repair_status->label() }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                        {{ $ticket->financial_responsibility->label() }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                        {{ $ticket->financial_status->label() }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-2">
                    Opened {{ $ticket->opened_at->format('M j, Y') }}
                    · {{ $ticket->age_days }} {{ Str::plural('day', $ticket->age_days) }} old
                    @if ($ticket->completed_at) · Completed {{ $ticket->completed_at->format('M j, Y') }} @endif
                    @if ($ticket->closed_at) · Closed {{ $ticket->closed_at->format('M j, Y') }} @endif
                    @if ($ticket->createdBy) · Created by {{ $ticket->createdBy->full_name }} @endif
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.service-management.tickets.index') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    All Tickets
                </a>
                <a href="{{ route('admin.service-management.tickets.edit', $ticket) }}"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition">
                    <x-heroicon-o-pencil-square class="w-4 h-4" />
                    Edit Ticket
                </a>
            </div>
        </div>

        {{-- Status actions — only sensible transitions for the current state --}}
        @php
            $transitions = collect([
                RepairStatus::InProgress,
                RepairStatus::WaitingOnParts,
                RepairStatus::WaitingOnCustomerApproval,
                RepairStatus::WaitingOnWarrantyApproval,
                RepairStatus::ReadyForPickup,
                RepairStatus::Completed,
            ])->reject(fn ($s) => $s === $ticket->repair_status);
            if ($isFinished) {
                $transitions = collect([RepairStatus::Open])->reject(fn ($s) => $s === $ticket->repair_status);
            }
        @endphp
        <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide mr-1">Mark:</span>
            @foreach ($transitions as $status)
                @if (in_array($status->value, RepairStatus::blocked(), true))
                    <button type="button"
                        class="sc-blocked-transition px-3 py-1.5 rounded-lg text-xs font-semibold border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 transition"
                        data-status="{{ $status->value }}" data-label="{{ $status->label() }}">
                        {{ $status->label() }}
                    </button>
                @else
                    <form method="POST" action="{{ route('admin.service-management.tickets.status', $ticket) }}" class="inline">
                        @csrf
                        <input type="hidden" name="repair_status" value="{{ $status->value }}">
                        <button type="submit"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 transition">
                            {{ $isFinished && $status === RepairStatus::Open ? 'Reopen Ticket' : $status->label() }}
                        </button>
                    </form>
                @endif
            @endforeach
            @if ($ticket->repair_status === RepairStatus::Completed)
                <form method="POST" action="{{ route('admin.service-management.tickets.status', $ticket) }}" class="inline">
                    @csrf
                    <input type="hidden" name="repair_status" value="closed">
                    <button type="submit"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-800 text-white hover:bg-gray-900 transition">
                        Close Ticket
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- ===== Blocked banner ===== --}}
    @if ($isBlocked)
        @php
            $expected = $ticket->expected_action_date;
            $deltaDays = $expected ? (int) now()->startOfDay()->diffInDays($expected->startOfDay(), false) : null;
        @endphp
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 mb-6">
            <div class="flex items-start gap-3">
                <x-heroicon-o-pause-circle class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" />
                <div>
                    <p class="text-sm font-semibold text-amber-800">
                        Blocked — {{ $ticket->repair_status->waitingOnLabel() }}
                    </p>
                    @if ($ticket->blocked_reason)
                        <p class="text-sm text-amber-700 mt-1">{{ $ticket->blocked_reason }}</p>
                    @endif
                    @if ($expected)
                        <p class="text-sm mt-1 {{ $deltaDays < 0 ? 'text-red-600 font-semibold' : 'text-amber-700' }}">
                            Expected action: {{ $expected->format('M j, Y') }}
                            @if ($deltaDays < 0)
                                ({{ abs($deltaDays) }} {{ Str::plural('day', abs($deltaDays)) }} past due)
                            @elseif ($deltaDays === 0)
                                (today)
                            @else
                                (in {{ $deltaDays }} {{ Str::plural('day', $deltaDays) }})
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- ===== Ticket summary ===== --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 lg:col-span-2">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Ticket Summary</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Service Type</dt>
                    <dd class="font-medium text-gray-900">{{ $ticket->service_type->label() }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Service Location</dt>
                    <dd class="font-medium text-gray-900">{{ $ticket->service_location->label() }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Equipment</dt>
                    <dd class="font-medium text-gray-900 text-right">
                        {{ $ticket->equipment?->equipment_name ?? '—' }}
                        @if ($ticket->equipment?->equipment_id)
                            <span class="text-xs font-mono text-gray-500">({{ $ticket->equipment->equipment_id }})</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Customer</dt>
                    <dd class="font-medium text-gray-900">
                        {{ $ticket->customer ? trim($ticket->customer->first_name . ' ' . $ticket->customer->last_name) : '—' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Order</dt>
                    <dd class="font-medium text-gray-900">
                        @if ($ticket->order)
                            {!! $ticket->order->view_link ?? $ticket->order->order_number !!}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Rental Date</dt>
                    <dd class="font-medium text-gray-900">{{ $ticket->rental_date?->format('M j, Y') ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        {{-- ===== Personnel ===== --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Assigned Personnel</h2>
            @if ($ticket->personnel->isEmpty())
                <p class="text-sm text-gray-400 italic">No personnel assigned.</p>
            @else
                <div class="space-y-2.5">
                    @foreach ($ticket->personnel as $person)
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 text-[11px] font-bold flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($person->first_name, 0, 1) . substr($person->last_name, 0, 1)) }}
                            </span>
                            <span class="text-sm font-medium text-gray-800">{{ $person->full_name }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
            <p class="text-xs text-gray-400 mt-4">Add or remove personnel from the edit form.</p>
        </div>
    </div>

    {{-- ===== Repair documentation ===== --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-8">
        <h2 class="text-sm font-semibold text-gray-800 mb-4">Repair Documentation</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            @foreach ([
                'customer_complaint'   => 'Customer Complaint',
                'technician_diagnosis' => 'Technician Diagnosis',
                'root_cause'           => 'Root Cause',
                'repair_summary'       => 'Repair Summary',
                'internal_notes'       => 'Internal Notes',
            ] as $field => $label)
                <div @if($field === 'internal_notes') class="lg:col-span-2" @endif>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ $label }}</p>
                    @if ($ticket->{$field})
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $ticket->{$field} }}</p>
                    @else
                        <p class="text-sm text-gray-300 italic">Not documented yet.</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- ===== Blocked-transition modal (reason + expected date required) ===== --}}
    <div id="blockedTransitionModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 flex justify-center items-center px-4">
        <div class="bg-white rounded-lg w-full max-w-md shadow-lg">
            <div class="relative px-6 pt-6 pb-4 border-b">
                <h2 id="blocked-modal-title" class="text-lg font-semibold text-gray-900">Mark Waiting</h2>
                <button type="button" id="close-blocked-modal"
                    class="text-2xl text-gray-400 hover:text-gray-700 leading-none absolute right-6 top-6">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.service-management.tickets.status', $ticket) }}">
                @csrf
                <input type="hidden" name="repair_status" id="blocked-modal-status" value="">
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 required">Blocked Reason</label>
                        <input type="text" name="blocked_reason" required
                            placeholder="e.g. Final drive on backorder from OEM"
                            class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm bg-white focus:ring focus:border-blue-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 required">Expected Action Date</label>
                        <input type="date" name="expected_action_date" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm bg-white focus:ring focus:border-blue-400 outline-none">
                    </div>
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                    <button type="button" id="cancel-blocked-modal"
                        class="px-5 py-2.5 rounded-lg text-sm border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 rounded-lg text-sm bg-amber-500 text-white hover:bg-amber-600 transition">
                        Save Status
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('blockedTransitionModal');
    document.querySelectorAll('.sc-blocked-transition').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('blocked-modal-status').value = this.dataset.status;
            document.getElementById('blocked-modal-title').textContent = 'Mark ' + this.dataset.label;
            modal.classList.remove('hidden');
        });
    });
    ['close-blocked-modal', 'cancel-blocked-modal'].forEach(id =>
        document.getElementById(id)?.addEventListener('click', () => modal.classList.add('hidden')));
    modal?.addEventListener('click', e => { if (e.target === modal) modal.classList.add('hidden'); });
});
</script>
@endpush
