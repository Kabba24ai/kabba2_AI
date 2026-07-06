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
        use App\Enums\Service\ApprovalStatus;
        use App\Enums\Service\DiagnosticStatus;
        use App\Enums\Service\FinancialResponsibility;
        use App\Enums\Service\RepairStatus;
        use App\Enums\Service\ResponsibilityDecision;
        $isBlocked  = $ticket->is_blocked;
        $isFinished = !in_array($ticket->repair_status->value, RepairStatus::notFinished(), true);
        [$workbenchLabel, $workbenchBadge] = $ticket->workbenchState();
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
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $workbenchBadge }}">
                        {{ $workbenchLabel }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-2">
                    @if ($ticket->completed_at) Completed {{ $ticket->completed_at->format('M j, Y') }} @endif
                    @if ($ticket->closed_at) · Closed {{ $ticket->closed_at->format('M j, Y') }} @endif
                    @if ($ticket->createdBy) {{ $ticket->completed_at || $ticket->closed_at ? '·' : '' }} Created by {{ $ticket->createdBy->full_name }} @endif
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.service-management.tickets.index') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    Back to Tickets
                </a>
                <a href="{{ route('admin.service-management.tickets.edit', $ticket) }}"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition">
                    <x-heroicon-o-pencil-square class="w-4 h-4" />
                    Edit Ticket
                </a>
            </div>
        </div>
    </div>

    {{-- ===== Blocked status card — prominent only when blocked ===== --}}
    @if ($isBlocked)
        @php
            $expected  = $ticket->expected_action_date;
            $deltaDays = $expected ? (int) now()->startOfDay()->diffInDays($expected->startOfDay(), false) : null;
        @endphp
        <div class="rounded-xl border border-amber-300 bg-amber-50 shadow-sm p-5 mb-6">
            <div class="flex items-start gap-3">
                <x-heroicon-o-pause-circle class="w-6 h-6 text-amber-500 mt-0.5 shrink-0" />
                <div class="flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-amber-800">
                            Blocked — Waiting on {{ $ticket->repair_status->waitingOnLabel() }}
                        </p>
                        @if (!is_null($deltaDays))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                {{ $deltaDays < 0 ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-amber-100 text-amber-700 border border-amber-200' }}">
                                @if ($deltaDays < 0)
                                    {{ abs($deltaDays) }} {{ Str::plural('day', abs($deltaDays)) }} overdue
                                @elseif ($deltaDays === 0)
                                    Action due today
                                @else
                                    {{ $deltaDays }} {{ Str::plural('day', $deltaDays) }} remaining
                                @endif
                            </span>
                        @endif
                    </div>
                    @if ($ticket->blocked_reason)
                        <p class="text-sm text-amber-700 mt-1">{{ $ticket->blocked_reason }}</p>
                    @endif
                    @if ($expected)
                        <p class="text-sm mt-1 {{ $deltaDays < 0 ? 'text-red-600 font-semibold' : 'text-amber-700' }}">
                            Expected action date: {{ $expected->format('M j, Y') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-start">

        {{-- ===== Left column ===== --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Ticket summary --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
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
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Opened</dt>
                        <dd class="font-medium text-gray-900">{{ $ticket->opened_at->format('M j, Y') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Age</dt>
                        <dd class="font-medium text-gray-900">{{ $ticket->age_days }} {{ Str::plural('day', $ticket->age_days) }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Diagnostic (Phase 2D — diagnostic-first lifecycle) --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-1">
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-sm font-semibold text-gray-800">Diagnostic</h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->diagnostic_status->color() }}">
                            {{ $ticket->diagnostic_status->label() }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->responsibility_decision->color() }}">
                            {{ $ticket->responsibility_decision === ResponsibilityDecision::Pending ? 'Responsibility Pending' : $ticket->responsibility_decision->label() }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($ticket->diagnostic_status === DiagnosticStatus::NotStarted)
                            <form method="POST" action="{{ route('admin.service-management.tickets.diagnostic.start', $ticket) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-sky-600 text-white hover:bg-sky-700 transition">
                                    Start Diagnostic
                                </button>
                            </form>
                        @elseif ($ticket->diagnostic_status === DiagnosticStatus::InProgress)
                            <form method="POST" action="{{ route('admin.service-management.tickets.diagnostic.complete', $ticket) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-600 text-white hover:bg-green-700 transition">
                                    Complete Diagnostic
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <p class="text-xs text-gray-400 mb-4">
                    @if ($ticket->diagnostic_started_at) Started {{ $ticket->diagnostic_started_at->format('M j, Y g:i A') }} @endif
                    @if ($ticket->diagnostic_completed_at) · Completed {{ $ticket->diagnostic_completed_at->format('M j, Y g:i A') }} @endif
                    @if ($ticket->responsibility_decided_at)
                        · Responsibility decided {{ $ticket->responsibility_decided_at->format('M j, Y') }}
                        @if ($ticket->responsibilityDecidedBy) by {{ $ticket->responsibilityDecidedBy->full_name }} @endif
                    @endif
                </p>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Technician Diagnosis</p>
                        <p class="text-sm {{ $ticket->technician_diagnosis ? 'text-gray-700 whitespace-pre-line' : 'text-gray-300 italic' }}">
                            {{ $ticket->technician_diagnosis ?: 'Not documented yet.' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Root Cause</p>
                        <p class="text-sm {{ $ticket->root_cause ? 'text-gray-700 whitespace-pre-line' : 'text-gray-300 italic' }}">
                            {{ $ticket->root_cause ?: 'Not documented yet.' }}
                        </p>
                    </div>
                    <div class="lg:col-span-2">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Recommended Repair</p>
                        <p class="text-sm {{ $ticket->recommended_repair ? 'text-gray-700 whitespace-pre-line' : 'text-gray-300 italic' }}">
                            {{ $ticket->recommended_repair ?: 'Not documented yet.' }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3 text-sm mt-4 pt-4 border-t border-gray-100">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Est. Labor Hours</dt>
                        <dd class="font-medium text-gray-900">{{ $ticket->estimated_labor_hours !== null ? rtrim(rtrim(number_format($ticket->estimated_labor_hours, 2), '0'), '.') : '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Est. Parts Total</dt>
                        <dd class="font-medium text-gray-900">{{ $ticket->estimated_parts_total !== null ? '$' . number_format($ticket->estimated_parts_total, 2) : '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Est. Repair Total</dt>
                        <dd class="font-medium text-gray-900">{{ $ticket->estimated_repair_total !== null ? '$' . number_format($ticket->estimated_repair_total, 2) : '—' }}</dd>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 mt-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->warranty_possible ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-400' }}">
                        Warranty {{ $ticket->warranty_possible ? 'Possible' : 'Not Indicated' }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->customer_damage_possible ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-400' }}">
                        Customer Damage {{ $ticket->customer_damage_possible ? 'Possible' : 'Not Indicated' }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->diagnostic_fee_required ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-400' }}">
                        Diagnostic Fee {{ $ticket->diagnostic_fee_required ? 'Required' : 'Not Required' }}
                    </span>
                    @if ($ticket->diagnostic_fee_required)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                            {{ $ticket->diagnostic_fee_amount !== null ? '$' . number_format($ticket->diagnostic_fee_amount, 2) : 'Amount TBD' }}
                            · {{ $ticket->diagnostic_fee_paid ? 'Paid' : 'Unpaid' }}
                            @if ($ticket->diagnostic_fee_creditable) · Creditable @endif
                            @if ($ticket->diagnostic_fee_credited) · Credited @endif
                        </span>
                    @endif
                </div>

                {{-- Edit diagnostic details --}}
                <details class="mt-4 pt-4 border-t border-gray-100">
                    <summary class="text-sm font-medium text-blue-600 hover:text-blue-700 cursor-pointer select-none">Edit Diagnostic Details</summary>
                    <form method="POST" action="{{ route('admin.service-management.tickets.diagnostic.update', $ticket) }}"
                        class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                        @csrf
                        @method('PUT')
                        <div class="col-span-2 sm:col-span-4">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Recommended Repair</label>
                            <textarea name="recommended_repair" rows="2"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">{{ old('recommended_repair', $ticket->recommended_repair) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Est. Labor Hours</label>
                            <input type="number" name="estimated_labor_hours" step="0.25" min="0"
                                value="{{ old('estimated_labor_hours', $ticket->estimated_labor_hours) }}"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Est. Parts Total ($)</label>
                            <input type="number" name="estimated_parts_total" step="0.01" min="0"
                                value="{{ old('estimated_parts_total', $ticket->estimated_parts_total) }}"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Est. Repair Total ($)</label>
                            <input type="number" name="estimated_repair_total" step="0.01" min="0"
                                value="{{ old('estimated_repair_total', $ticket->estimated_repair_total) }}"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Diagnostic Fee ($)</label>
                            <input type="number" name="diagnostic_fee_amount" step="0.01" min="0"
                                value="{{ old('diagnostic_fee_amount', $ticket->diagnostic_fee_amount) }}"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div class="col-span-2 sm:col-span-4 flex flex-wrap gap-x-5 gap-y-2 pt-1">
                            @foreach ([
                                'warranty_possible'         => 'Warranty Possible',
                                'customer_damage_possible'  => 'Customer Damage Possible',
                                'diagnostic_fee_required'   => 'Fee Required',
                                'diagnostic_fee_paid'       => 'Fee Paid',
                                'diagnostic_fee_creditable' => 'Fee Creditable',
                                'diagnostic_fee_credited'   => 'Fee Credited',
                            ] as $field => $label)
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="hidden" name="{{ $field }}" value="0">
                                    <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $ticket->{$field})) class="rounded border-gray-300">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <div class="col-span-2 sm:col-span-4">
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition">
                                Save Diagnostic Details
                            </button>
                            <span class="text-xs text-gray-400 ml-2">Fee state is tracked only — no payment is processed.</span>
                        </div>
                    </form>
                </details>

                {{-- Responsibility decision — unlocked once diagnosis is complete --}}
                <div class="mt-4 pt-4 border-t border-gray-100">
                    @if ($ticket->diagnostic_status->allowsResponsibilityDecision())
                        <form method="POST" action="{{ route('admin.service-management.tickets.responsibility.decide', $ticket) }}"
                            class="flex flex-wrap items-end gap-3">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Responsibility Decision</label>
                                <select name="responsibility_decision" required class="border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                                    @foreach (ResponsibilityDecision::cases() as $decision)
                                        @continue($decision === ResponsibilityDecision::Pending)
                                        <option value="{{ $decision->value }}" @selected($ticket->responsibility_decision === $decision)>{{ $decision->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-800 text-white hover:bg-gray-900 transition">
                                Set Responsibility
                            </button>
                            <span class="text-xs text-gray-400">Sets the ticket's financial responsibility path.</span>
                        </form>
                    @else
                        <p class="text-xs text-gray-400">Complete the diagnostic to set the responsibility decision.</p>
                    @endif
                </div>
            </div>

            {{-- Repair documentation --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
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

            {{-- Labor entries (Phase 2B — billing preparation) --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">Labor</h2>
                    <span class="text-sm font-semibold text-gray-700">${{ number_format($ticket->labor_total, 2) }}</span>
                </div>

                @if ($ticket->laborEntries->isEmpty())
                    <p class="text-sm text-gray-400 italic">No labor recorded yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">
                                    <th class="pb-2 pr-3">Employee</th>
                                    <th class="pb-2 pr-3">Date</th>
                                    <th class="pb-2 pr-3 text-right">Hours</th>
                                    <th class="pb-2 pr-3 text-right">Rate</th>
                                    <th class="pb-2 pr-3 text-right">Total</th>
                                    <th class="pb-2 pr-3">Billable</th>
                                    <th class="pb-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($ticket->laborEntries as $entry)
                                    <tr>
                                        <td class="py-2 pr-3 font-medium text-gray-800">
                                            {{ $entry->employee?->full_name ?? 'Unassigned' }}
                                            @if ($entry->labor_description)
                                                <p class="text-xs text-gray-500 font-normal">{{ $entry->labor_description }}</p>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-3 text-gray-600 whitespace-nowrap">{{ $entry->labor_date->format('M j, Y') }}</td>
                                        <td class="py-2 pr-3 text-right text-gray-700">{{ rtrim(rtrim(number_format($entry->hours, 2), '0'), '.') }}</td>
                                        <td class="py-2 pr-3 text-right text-gray-700">{{ $entry->labor_rate !== null ? '$' . number_format($entry->labor_rate, 2) : '—' }}</td>
                                        <td class="py-2 pr-3 text-right font-medium text-gray-900">{{ $entry->labor_total !== null ? '$' . number_format($entry->labor_total, 2) : '—' }}</td>
                                        <td class="py-2 pr-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $entry->billable ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                                {{ $entry->billable ? 'Billable' : 'Non-billable' }}
                                            </span>
                                        </td>
                                        <td class="py-2 text-right">
                                            <form method="POST" action="{{ route('admin.service-management.tickets.labor.destroy', [$ticket, $entry]) }}"
                                                onsubmit="return confirm('Remove this labor entry?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 text-gray-300 hover:text-red-500 transition" title="Remove">
                                                    <x-heroicon-o-trash class="w-4 h-4" />
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <details class="mt-4 pt-4 border-t border-gray-100">
                    <summary class="text-sm font-medium text-blue-600 hover:text-blue-700 cursor-pointer select-none">+ Add Labor Entry</summary>
                    <form method="POST" action="{{ route('admin.service-management.tickets.labor.store', $ticket) }}" class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                        @csrf
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Employee</label>
                            <select name="employee_id" class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                                <option value="">— Unassigned —</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1 required">Date</label>
                            <input type="date" name="labor_date" required value="{{ now()->toDateString() }}"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Hours</label>
                            <input type="number" name="hours" step="0.25" min="0.01" placeholder="e.g. 1.5"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Start Time</label>
                            <input type="time" name="start_time" class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">End Time</label>
                            <input type="time" name="end_time" class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Rate ($/hr)</label>
                            <input type="number" name="labor_rate" step="0.01" min="0" placeholder="0.00"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div class="flex items-end pb-2">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="hidden" name="billable" value="0">
                                <input type="checkbox" name="billable" value="1" @checked($ticket->defaultBillable()) class="rounded border-gray-300">
                                Billable
                            </label>
                        </div>
                        <div class="col-span-2 sm:col-span-4">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Description</label>
                            <input type="text" name="labor_description" placeholder="What was done"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div class="col-span-2 sm:col-span-4">
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition">
                                Add Labor Entry
                            </button>
                            <span class="text-xs text-gray-400 ml-2">Hours are calculated from the time range when left blank.</span>
                        </div>
                    </form>
                </details>
            </div>

            {{-- Charge lines (Phase 2B — proposed charges, not payment processing) --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">Charge Lines</h2>
                    <span class="text-sm font-semibold text-gray-700">${{ number_format($ticket->charge_line_total, 2) }}</span>
                </div>

                @if ($ticket->chargeLines->isEmpty())
                    <p class="text-sm text-gray-400 italic">No charge lines prepared yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">
                                    <th class="pb-2 pr-3">Type</th>
                                    <th class="pb-2 pr-3">Description</th>
                                    <th class="pb-2 pr-3 text-right">Qty</th>
                                    <th class="pb-2 pr-3 text-right">Unit</th>
                                    <th class="pb-2 pr-3 text-right">Total</th>
                                    <th class="pb-2 pr-3">Flags</th>
                                    <th class="pb-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($ticket->chargeLines as $line)
                                    <tr>
                                        <td class="py-2 pr-3 font-medium text-gray-800 whitespace-nowrap">{{ $line->charge_type->label() }}</td>
                                        <td class="py-2 pr-3 text-gray-600">{{ $line->description }}</td>
                                        <td class="py-2 pr-3 text-right text-gray-700">{{ rtrim(rtrim(number_format($line->quantity, 2), '0'), '.') }}</td>
                                        <td class="py-2 pr-3 text-right text-gray-700">${{ number_format($line->unit_amount, 2) }}</td>
                                        <td class="py-2 pr-3 text-right font-medium text-gray-900">${{ number_format($line->line_total, 2) }}</td>
                                        <td class="py-2 pr-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $line->billable ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                                {{ $line->billable ? 'Billable' : 'Non-billable' }}
                                            </span>
                                            @if ($line->taxable)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-sky-100 text-sky-700">Taxable</span>
                                            @endif
                                        </td>
                                        <td class="py-2 text-right">
                                            <form method="POST" action="{{ route('admin.service-management.tickets.charges.destroy', [$ticket, $line]) }}"
                                                onsubmit="return confirm('Remove this charge line?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 text-gray-300 hover:text-red-500 transition" title="Remove">
                                                    <x-heroicon-o-trash class="w-4 h-4" />
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <details class="mt-4 pt-4 border-t border-gray-100">
                    <summary class="text-sm font-medium text-blue-600 hover:text-blue-700 cursor-pointer select-none">+ Add Charge Line</summary>
                    <form method="POST" action="{{ route('admin.service-management.tickets.charges.store', $ticket) }}" class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1 required">Type</label>
                            <select name="charge_type" required class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                                @foreach (\App\Enums\Service\ServiceChargeType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Qty</label>
                            <input type="number" name="quantity" step="0.01" min="0.01" value="1"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1 required">Unit Amount ($)</label>
                            <input type="number" name="unit_amount" step="0.01" min="0" required placeholder="0.00"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div class="flex items-end gap-4 pb-2">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="hidden" name="billable" value="0">
                                <input type="checkbox" name="billable" value="1" @checked($ticket->defaultBillable()) class="rounded border-gray-300">
                                Billable
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="hidden" name="taxable" value="0">
                                <input type="checkbox" name="taxable" value="1" class="rounded border-gray-300">
                                Taxable
                            </label>
                        </div>
                        <div class="col-span-2 sm:col-span-4">
                            <label class="block text-xs font-medium text-gray-500 mb-1 required">Description</label>
                            <input type="text" name="description" required placeholder="e.g. Final drive assembly"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div class="col-span-2 sm:col-span-4">
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition">
                                Add Charge Line
                            </button>
                            <span class="text-xs text-gray-400 ml-2">Prepared charges only — nothing is billed to the customer yet.</span>
                        </div>
                    </form>
                </details>
            </div>

            {{-- Parts used (Phase 2C — repair record, no inventory deduction) --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">Parts Used</h2>
                    <div class="text-xs text-gray-500">
                        Cost <span class="font-semibold text-gray-800">${{ number_format($ticket->parts_cost_total, 2) }}</span>
                        <span class="mx-1 text-gray-300">·</span>
                        Customer <span class="font-semibold text-gray-800">${{ number_format($ticket->parts_customer_total, 2) }}</span>
                    </div>
                </div>

                @if ($ticket->partsUsed->isEmpty())
                    <p class="text-sm text-gray-400 italic">No parts recorded yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">
                                    <th class="pb-2 pr-3">Part #</th>
                                    <th class="pb-2 pr-3">Description</th>
                                    <th class="pb-2 pr-3 text-right">Qty</th>
                                    <th class="pb-2 pr-3 text-right">Unit Cost</th>
                                    <th class="pb-2 pr-3 text-right">Customer Price</th>
                                    <th class="pb-2 pr-3">Warranty</th>
                                    <th class="pb-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($ticket->partsUsed as $part)
                                    <tr>
                                        <td class="py-2 pr-3 font-mono text-xs text-gray-500 whitespace-nowrap">{{ $part->part_number ?? '—' }}</td>
                                        <td class="py-2 pr-3 font-medium text-gray-800">{{ $part->description }}</td>
                                        <td class="py-2 pr-3 text-right text-gray-700">{{ $part->quantityLabel() }}</td>
                                        <td class="py-2 pr-3 text-right text-gray-700">
                                            {{ $part->unit_cost !== null ? '$' . number_format($part->unit_cost, 2) : '—' }}
                                            @if ($part->cost_total !== null && (float) $part->quantity !== 1.0)
                                                <span class="block text-[11px] text-gray-400">${{ number_format($part->cost_total, 2) }} total</span>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-3 text-right text-gray-700">
                                            {{ $part->customer_price !== null ? '$' . number_format($part->customer_price, 2) : '—' }}
                                            @if ($part->customer_total !== null && (float) $part->quantity !== 1.0)
                                                <span class="block text-[11px] text-gray-400">${{ number_format($part->customer_total, 2) }} total</span>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-3">
                                            @if ($part->warranty_eligible)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-100 text-indigo-700">Warranty</span>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>
                                        <td class="py-2 text-right">
                                            <form method="POST" action="{{ route('admin.service-management.tickets.parts.destroy', [$ticket, $part]) }}"
                                                onsubmit="return confirm('Remove this part?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 text-gray-300 hover:text-red-500 transition" title="Remove">
                                                    <x-heroicon-o-trash class="w-4 h-4" />
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <details class="mt-4 pt-4 border-t border-gray-100">
                    <summary class="text-sm font-medium text-blue-600 hover:text-blue-700 cursor-pointer select-none">+ Add Part</summary>
                    <form method="POST" action="{{ route('admin.service-management.tickets.parts.store', $ticket) }}" class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Part Number</label>
                            <input type="text" name="part_number" placeholder="Optional"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Qty</label>
                            <input type="number" name="quantity" step="0.01" min="0.01" value="1"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Unit Cost ($)</label>
                            <input type="number" name="unit_cost" step="0.01" min="0" placeholder="0.00"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Customer Price ($)</label>
                            <input type="number" name="customer_price" step="0.01" min="0" placeholder="0.00"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div class="col-span-2 sm:col-span-3">
                            <label class="block text-xs font-medium text-gray-500 mb-1 required">Description</label>
                            <input type="text" name="description" required placeholder="e.g. Hydraulic hose 3/8&quot; x 48&quot;"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div class="flex items-end pb-2">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="hidden" name="warranty_eligible" value="0">
                                <input type="checkbox" name="warranty_eligible" value="1" class="rounded border-gray-300">
                                Warranty Eligible
                            </label>
                        </div>
                        <div class="col-span-2 sm:col-span-4">
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition">
                                Add Part
                            </button>
                            <span class="text-xs text-gray-400 ml-2">Record only — inventory is not deducted.</span>
                        </div>
                    </form>
                </details>
            </div>

            {{-- Media / attachments (Phase 2C) --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Media &amp; Attachments</h2>

                @if ($ticket->media->isEmpty())
                    <p class="text-sm text-gray-400 italic">No files uploaded yet.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($ticket->media->groupBy(fn ($m) => $m->category->value) as $group)
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">{{ $group->first()->category->label() }}</p>
                                <div class="flex flex-wrap gap-3">
                                    @foreach ($group as $item)
                                        <div class="group relative border border-gray-200 rounded-lg p-2 w-40">
                                            <a href="{{ $item->url }}" target="_blank" rel="noopener" class="block">
                                                @if ($item->isImage())
                                                    <img src="{{ $item->url }}" alt="{{ $item->original_filename }}"
                                                        class="h-20 w-full object-cover rounded-md bg-gray-100">
                                                @else
                                                    <span class="h-20 w-full flex items-center justify-center rounded-md bg-gray-50">
                                                        <x-heroicon-o-document class="w-8 h-8 text-gray-300" />
                                                    </span>
                                                @endif
                                                <p class="text-xs font-medium text-gray-700 truncate mt-1.5" title="{{ $item->original_filename }}">
                                                    {{ $item->original_filename ?? basename($item->file_path) }}
                                                </p>
                                            </a>
                                            <p class="text-[11px] text-gray-400 truncate">
                                                {{ $item->uploadedBy?->full_name ?? '—' }} · {{ $item->created_at->format('M j') }}
                                            </p>
                                            <form method="POST" action="{{ route('admin.service-management.tickets.media.destroy', [$ticket, $item]) }}"
                                                onsubmit="return confirm('Remove this file?');"
                                                class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 rounded-md bg-white/90 border border-gray-200 text-gray-400 hover:text-red-500" title="Remove">
                                                    <x-heroicon-o-trash class="w-3.5 h-3.5" />
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <details class="mt-4 pt-4 border-t border-gray-100">
                    <summary class="text-sm font-medium text-blue-600 hover:text-blue-700 cursor-pointer select-none">+ Upload File</summary>
                    <form method="POST" action="{{ route('admin.service-management.tickets.media.store', $ticket) }}"
                        enctype="multipart/form-data" class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1 required">File</label>
                            <input type="file" name="file" required
                                accept=".jpg,.jpeg,.png,.gif,.webp,.heic,.mp4,.mov,.avi,.webm,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                                class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm bg-white file:mr-2 file:border-0 file:bg-gray-100 file:rounded file:px-2 file:py-1 file:text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1 required">Category</label>
                            <select name="category" required class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                                @foreach (\App\Enums\Service\ServiceMediaCategory::cases() as $category)
                                    <option value="{{ $category->value }}">{{ $category->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Notes</label>
                            <input type="text" name="notes" placeholder="Optional"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        </div>
                        <div class="sm:col-span-3">
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition">
                                Upload
                            </button>
                            <span class="text-xs text-gray-400 ml-2">Images, video, PDF, and documents up to 50 MB.</span>
                        </div>
                    </form>
                </details>
            </div>

            {{-- Future sections — visible placeholders only, no functionality yet --}}
            @php
                $futureSections = [
                    ['label' => 'Customer Billing',       'icon' => 'banknotes',    'phase' => 'Phase 3', 'blurb' => 'Charges generated from this ticket and their payment status.'],
                    ['label' => 'Warranty Claim',         'icon' => 'shield-check', 'phase' => 'Phase 3', 'blurb' => 'OEM claim submission, approval tracking, and reimbursement.'],
                    ['label' => 'Field Dispatch Timeline','icon' => 'truck',        'phase' => 'Phase 3', 'blurb' => 'On-site visit scheduling and dispatch history for field service calls.'],
                ];
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                @foreach ($futureSections as $section)
                    <div @if($loop->last) class="sm:col-span-2" @endif>
                        <div class="bg-white rounded-xl border border-dashed border-gray-300 shadow-sm p-5 h-full">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
                                        @svg('heroicon-o-' . $section['icon'], 'w-4 h-4 text-gray-400')
                                    </span>
                                    <h2 class="text-sm font-semibold text-gray-400">{{ $section['label'] }}</h2>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-400 border border-gray-200">
                                    {{ $section['phase'] }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-300">{{ $section['blurb'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Timeline (Phase 2C — automatic event log, newest first) --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Timeline</h2>
                @if ($ticket->events->isEmpty())
                    <p class="text-sm text-gray-400 italic">No activity recorded yet.</p>
                @else
                    <ol class="relative border-s border-gray-200 ml-1.5 space-y-5">
                        @foreach ($ticket->events as $event)
                            <li class="ms-5">
                                <span class="absolute -start-[5px] mt-1.5 w-2.5 h-2.5 rounded-full ring-4 ring-white {{ $event->event_type->color() }}"></span>
                                <p class="text-sm font-medium text-gray-800">
                                    {{ $event->event_type->label() }}
                                    @if ($event->old_value && $event->new_value)
                                        <span class="font-normal text-gray-500">— {{ $event->old_value }} <span class="text-gray-300">→</span> {{ $event->new_value }}</span>
                                    @elseif ($event->new_value)
                                        <span class="font-normal text-gray-500">— {{ $event->new_value }}</span>
                                    @endif
                                </p>
                                @if ($event->notes)
                                    <p class="text-sm text-gray-500">{{ $event->notes }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ $event->user?->full_name ?? 'System' }} · {{ $event->created_at->format('M j, Y g:i A') }}
                                </p>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>

        {{-- ===== Right column ===== --}}
        <div class="space-y-6">

            {{-- Repair authorization gate (Phase 2E) --}}
            <div class="bg-white rounded-xl border {{ $ticket->repair_authorized ? 'border-green-300' : 'border-gray-200' }} shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h2 class="text-sm font-semibold text-gray-800">Repair Authorization</h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $workbenchBadge }}">
                        {{ $workbenchLabel }}
                    </span>
                </div>

                @php
                    $gates = [
                        'Diagnostic completed'     => $ticket->diagnostic_status->allowsResponsibilityDecision(),
                        'Responsibility decided'   => $ticket->responsibility_decision !== ResponsibilityDecision::Pending,
                        'Approval'                 => $ticket->approval_status->satisfied(),
                        'Parts deposit'            => $ticket->depositSatisfied(),
                    ];
                @endphp
                <ul class="space-y-1.5 text-sm">
                    @foreach ($gates as $gate => $satisfied)
                        <li class="flex items-center gap-2">
                            @if ($satisfied)
                                <x-heroicon-o-check-circle class="w-4 h-4 text-green-500 shrink-0" />
                                <span class="text-gray-600">{{ $gate }}</span>
                            @else
                                <x-heroicon-o-x-circle class="w-4 h-4 text-gray-300 shrink-0" />
                                <span class="text-gray-400">{{ $gate }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4 pt-3 border-t border-gray-100">
                    @if ($ticket->repair_authorized)
                        <p class="text-xs text-gray-500 mb-2">
                            Authorized {{ $ticket->repair_authorized_at?->format('M j, Y g:i A') }}
                            @if ($ticket->repairAuthorizedBy) by {{ $ticket->repairAuthorizedBy->full_name }} @endif
                        </p>
                        @if ($ticket->repair_authorization_notes)
                            <p class="text-xs text-gray-500 mb-2">{{ $ticket->repair_authorization_notes }}</p>
                        @endif
                        <form method="POST" action="{{ route('admin.service-management.tickets.authorization.revoke', $ticket) }}"
                            onsubmit="return confirm('Revoke repair authorization?');">
                            @csrf
                            <button type="submit" class="w-full px-3 py-2 rounded-lg text-sm font-medium border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition">
                                Revoke Authorization
                            </button>
                        </form>
                    @elseif ($ticket->canAuthorizeRepair())
                        <form method="POST" action="{{ route('admin.service-management.tickets.authorization.store', $ticket) }}" class="space-y-2">
                            @csrf
                            <input type="text" name="repair_authorization_notes" placeholder="Authorization notes (optional)"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                            <button type="submit" class="w-full px-3 py-2 rounded-lg text-sm font-semibold bg-green-600 text-white hover:bg-green-700 transition">
                                Authorize Repair
                            </button>
                        </form>
                    @else
                        <p class="text-xs text-gray-400 mb-2">
                            Blocked: {{ $ticket->authorizationBlockers()->implode(' · ') }}
                        </p>
                        {{-- Management always retains the ability to override the gate --}}
                        <details>
                            <summary class="text-sm font-medium text-purple-600 hover:text-purple-700 cursor-pointer select-none">Manager Override</summary>
                            <form method="POST" action="{{ route('admin.service-management.tickets.authorization.override', $ticket) }}" class="mt-2 space-y-2">
                                @csrf
                                <input type="text" name="authorization_override_reason" required
                                    placeholder="Reason for overriding authorization"
                                    class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                                <button type="submit" class="w-full px-3 py-2 rounded-lg text-sm font-medium border border-purple-200 bg-purple-50 text-purple-700 hover:bg-purple-100 transition">
                                    Override &amp; Authorize Repair
                                </button>
                            </form>
                        </details>
                    @endif
                    @if ($ticket->authorization_override && $ticket->repair_authorized)
                        <p class="text-xs text-purple-700 mt-2">
                            Manager override by {{ $ticket->authorizationOverrideBy?->full_name ?? '—' }}
                            {{ $ticket->authorization_override_at?->format('M j, Y g:i A') }}: {{ $ticket->authorization_override_reason }}
                        </p>
                    @endif
                </div>
            </div>

            {{-- Approval status (Phase 2E) --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h2 class="text-sm font-semibold text-gray-800">Approval</h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->approval_status->color() }}">
                        {{ $ticket->approval_status->label() }}
                    </span>
                </div>

                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Type</dt>
                        <dd class="font-medium text-gray-900">{{ $ticket->approval_type?->label() ?? '—' }}</dd>
                    </div>
                    @if ($ticket->estimate_sent_at)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Estimate Sent</dt>
                            <dd class="text-gray-700">{{ $ticket->estimate_sent_at->format('M j, Y') }}</dd>
                        </div>
                    @endif
                    @if ($ticket->estimate_approved_at)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Approved</dt>
                            <dd class="text-gray-700">{{ $ticket->estimate_approved_at->format('M j, Y') }}</dd>
                        </div>
                    @endif
                    @if ($ticket->estimate_declined_at)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Declined</dt>
                            <dd class="text-gray-700">{{ $ticket->estimate_declined_at->format('M j, Y') }}</dd>
                        </div>
                    @endif
                    @if ($ticket->approved_by_customer_name)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Approved By (Customer)</dt>
                            <dd class="text-gray-700">{{ $ticket->approved_by_customer_name }}</dd>
                        </div>
                    @endif
                    @if ($ticket->approvedByUser)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Recorded By</dt>
                            <dd class="text-gray-700">{{ $ticket->approvedByUser->full_name }}</dd>
                        </div>
                    @endif
                </dl>
                @if ($ticket->approval_notes)
                    <p class="text-xs text-gray-500 mt-2">{{ $ticket->approval_notes }}</p>
                @endif

                @if ($ticket->approval_type)
                    <div class="mt-4 pt-3 border-t border-gray-100 space-y-2">
                        @if (!in_array($ticket->approval_status, [ApprovalStatus::Approved], true))
                            @if ($ticket->approval_status !== ApprovalStatus::EstimateSent)
                                <form method="POST" action="{{ route('admin.service-management.tickets.approval.estimate-sent', $ticket) }}">
                                    @csrf
                                    <button type="submit" class="w-full px-3 py-2 rounded-lg text-sm font-medium border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 transition">
                                        Mark Estimate Sent
                                    </button>
                                </form>
                            @endif
                            <details>
                                <summary class="text-sm font-medium text-green-600 hover:text-green-700 cursor-pointer select-none">Record Approval</summary>
                                <form method="POST" action="{{ route('admin.service-management.tickets.approval.approve', $ticket) }}" class="mt-2 space-y-2">
                                    @csrf
                                    @if ($ticket->approval_type === \App\Enums\Service\ApprovalType::CustomerApproval)
                                        <input type="text" name="approved_by_customer_name" placeholder="Customer name (who approved)"
                                            class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                                    @endif
                                    <input type="text" name="approval_notes" placeholder="Notes (optional)"
                                        class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                                    <button type="submit" class="w-full px-3 py-2 rounded-lg text-sm font-semibold bg-green-600 text-white hover:bg-green-700 transition">
                                        Record Approval
                                    </button>
                                </form>
                            </details>
                            <details>
                                <summary class="text-sm font-medium text-red-500 hover:text-red-600 cursor-pointer select-none">Record Decline</summary>
                                <form method="POST" action="{{ route('admin.service-management.tickets.approval.decline', $ticket) }}" class="mt-2 space-y-2">
                                    @csrf
                                    <input type="text" name="approval_notes" placeholder="Reason (optional)"
                                        class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                                    <button type="submit" class="w-full px-3 py-2 rounded-lg text-sm font-medium border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition">
                                        Record Decline
                                    </button>
                                </form>
                            </details>
                        @else
                            <form method="POST" action="{{ route('admin.service-management.tickets.approval.revoke', $ticket) }}"
                                onsubmit="return confirm('Revoke this approval? Repair authorization will be withdrawn too.');">
                                @csrf
                                <button type="submit" class="w-full px-3 py-2 rounded-lg text-sm font-medium border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition">
                                    Revoke Approval
                                </button>
                            </form>
                        @endif
                    </div>
                @else
                    <p class="text-xs text-gray-400 mt-3">Approval path is set when the responsibility decision is made.</p>
                @endif
            </div>

            {{-- Parts deposit / repair funding (Phase 2E — state only, no payments) --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h2 class="text-sm font-semibold text-gray-800">Parts Deposit</h2>
                    @if (!$ticket->parts_deposit_required)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-400">Not Required</span>
                    @elseif ($ticket->parts_deposit_paid)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Paid</span>
                    @elseif ($ticket->deposit_override)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">Overridden</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Awaiting Payment</span>
                    @endif
                </div>

                @if ($ticket->parts_deposit_required)
                    <dl class="space-y-1.5 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Amount</dt>
                            <dd class="font-medium text-gray-900">{{ $ticket->parts_deposit_amount !== null ? '$' . number_format($ticket->parts_deposit_amount, 2) : 'TBD' }}</dd>
                        </div>
                        @if ($ticket->parts_deposit_paid_at)
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">Paid</dt>
                                <dd class="text-gray-700">{{ $ticket->parts_deposit_paid_at->format('M j, Y') }}</dd>
                            </div>
                        @endif
                        @if ($ticket->parts_deposit_payment_reference)
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">Reference</dt>
                                <dd class="text-gray-700 font-mono text-xs">{{ $ticket->parts_deposit_payment_reference }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Creditable to Invoice</dt>
                            <dd class="text-gray-700">{{ $ticket->parts_deposit_creditable ? 'Yes' : 'No' }}{{ $ticket->parts_deposit_applied_to_final_invoice ? ' · Applied' : '' }}</dd>
                        </div>
                        @if ($ticket->deposit_override)
                            <div class="pt-1">
                                <p class="text-xs text-purple-700">
                                    Override by {{ $ticket->depositOverrideBy?->full_name ?? '—' }}
                                    {{ $ticket->deposit_override_at?->format('M j, Y') }}: {{ $ticket->deposit_override_reason }}
                                </p>
                            </div>
                        @endif
                    </dl>
                @else
                    <p class="text-sm text-gray-400 italic">No deposit required. Mark it required for non-returnable or special-order parts.</p>
                @endif

                <details class="mt-4 pt-3 border-t border-gray-100">
                    <summary class="text-sm font-medium text-blue-600 hover:text-blue-700 cursor-pointer select-none">Edit Deposit</summary>
                    <form method="POST" action="{{ route('admin.service-management.tickets.deposit.update', $ticket) }}" class="mt-2 space-y-2 text-sm">
                        @csrf
                        @method('PUT')
                        <input type="number" name="parts_deposit_amount" step="0.01" min="0" placeholder="Deposit amount ($)"
                            value="{{ old('parts_deposit_amount', $ticket->parts_deposit_amount) }}"
                            class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        <input type="text" name="parts_deposit_payment_reference" placeholder="Payment reference (optional)"
                            value="{{ old('parts_deposit_payment_reference', $ticket->parts_deposit_payment_reference) }}"
                            class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                        <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                            @foreach ([
                                'parts_deposit_required'   => 'Required',
                                'parts_deposit_paid'       => 'Paid',
                                'parts_deposit_creditable' => 'Creditable',
                                'parts_deposit_applied_to_final_invoice' => 'Applied to Invoice',
                            ] as $field => $label)
                                <label class="inline-flex items-center gap-1.5 text-sm text-gray-700">
                                    <input type="hidden" name="{{ $field }}" value="0">
                                    <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $ticket->{$field})) class="rounded border-gray-300">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="w-full px-3 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition">
                            Save Deposit
                        </button>
                        <p class="text-xs text-gray-400">State tracking only — no payment is processed here.</p>
                    </form>
                </details>

                @if ($ticket->parts_deposit_required && !$ticket->parts_deposit_paid && !$ticket->deposit_override)
                    <details class="mt-2">
                        <summary class="text-sm font-medium text-purple-600 hover:text-purple-700 cursor-pointer select-none">Manager Override</summary>
                        <form method="POST" action="{{ route('admin.service-management.tickets.deposit.override', $ticket) }}" class="mt-2 space-y-2">
                            @csrf
                            <input type="text" name="deposit_override_reason" required placeholder="Reason for skipping the deposit"
                                class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm bg-white">
                            <button type="submit" class="w-full px-3 py-2 rounded-lg text-sm font-medium border border-purple-200 bg-purple-50 text-purple-700 hover:bg-purple-100 transition">
                                Record Override
                            </button>
                        </form>
                    </details>
                @endif
            </div>

            {{-- Operational actions --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Operational Actions</h2>
                @php
                    $actions = [
                        RepairStatus::InProgress,
                        RepairStatus::WaitingOnParts,
                        RepairStatus::WaitingOnCustomerApproval,
                        RepairStatus::WaitingOnWarrantyApproval,
                        RepairStatus::ReadyForPickup,
                        RepairStatus::Completed,
                        RepairStatus::Closed,
                    ];
                @endphp
                <div class="space-y-2">
                    @foreach ($actions as $status)
                        @php
                            $isCurrent = $status === $ticket->repair_status;
                            // Phase 2E.5: execution statuses are locked until the repair is authorized to proceed.
                            $authBlocked = $status->requiresAuthorization() && !$ticket->repairExecutionAllowed();
                            // Closed is only reachable from Completed; finished tickets get Reopen instead of new work states.
                            $disabled = $isCurrent
                                || $authBlocked
                                || ($status === RepairStatus::Closed && $ticket->repair_status !== RepairStatus::Completed)
                                || ($isFinished && $status !== RepairStatus::Closed);
                            $needsModal = in_array($status->value, RepairStatus::blocked(), true);
                        @endphp
                        @if ($isCurrent)
                            <div class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold border {{ $status->color() }} border-transparent">
                                <span>{{ $status->label() }}</span>
                                <span class="text-[11px] font-semibold uppercase tracking-wide opacity-70">Current</span>
                            </div>
                        @elseif ($disabled)
                            <button type="button" disabled
                                title="{{ $authBlocked
                                    ? 'Repair not authorized — ' . $ticket->authorizationBlockers()->implode('; ')
                                    : ($status === RepairStatus::Closed ? 'Ticket must be Completed before it can be Closed.' : 'Reopen the ticket to change its work status.') }}"
                                class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium border border-gray-200 bg-gray-50 text-gray-300 cursor-not-allowed {{ $authBlocked ? 'flex items-center justify-between' : '' }}">
                                {{ $status->label() }}
                                @if ($authBlocked)
                                    <x-heroicon-o-lock-closed class="w-3.5 h-3.5 shrink-0" />
                                @endif
                            </button>
                        @elseif ($needsModal)
                            <button type="button"
                                class="sc-blocked-transition w-full text-left px-3 py-2 rounded-lg text-sm font-medium border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 transition"
                                data-status="{{ $status->value }}" data-label="{{ $status->label() }}">
                                {{ $status->label() }}
                            </button>
                        @else
                            <form method="POST" action="{{ route('admin.service-management.tickets.status', $ticket) }}">
                                @csrf
                                <input type="hidden" name="repair_status" value="{{ $status->value }}">
                                <button type="submit"
                                    class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium border transition
                                    {{ $status === RepairStatus::Closed
                                        ? 'bg-gray-800 border-gray-800 text-white hover:bg-gray-900'
                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}">
                                    Mark {{ $status->label() }}
                                </button>
                            </form>
                        @endif
                    @endforeach

                    @if ($isFinished)
                        <form method="POST" action="{{ route('admin.service-management.tickets.status', $ticket) }}" class="pt-1">
                            @csrf
                            <input type="hidden" name="repair_status" value="{{ RepairStatus::Open->value }}">
                            <button type="submit"
                                class="w-full px-3 py-2 rounded-lg text-sm font-semibold border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 transition">
                                Reopen Ticket
                            </button>
                        </form>
                    @endif
                </div>
                <p class="text-xs text-gray-400 mt-4">Waiting statuses require a blocked reason and expected action date.</p>
            </div>

            {{-- Financial settlement (Phase 3A) — handoff record; payment status lives on the order --}}
            @php $activeSettlement = $ticket->activeSettlement(); @endphp
            @if ($activeSettlement)
                <div class="bg-white rounded-xl border border-emerald-300 shadow-sm p-5">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <h2 class="text-sm font-semibold text-gray-800">Financial Settlement</h2>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                            <x-heroicon-o-check class="w-3.5 h-3.5" /> Customer Charge Created
                        </span>
                    </div>
                    <dl class="space-y-1.5 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Amount</dt>
                            <dd class="font-semibold text-gray-900">${{ number_format($activeSettlement->final_amount, 2) }}</dd>
                        </div>
                        @if ($ticket->order)
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">Order</dt>
                                <dd class="font-medium text-gray-900">
                                    {!! $ticket->order->view_link ?? $ticket->order->order_number !!}
                                </dd>
                            </div>
                        @endif
                        @if ($activeSettlement->extraCharge)
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">Charge Reference</dt>
                                <dd class="font-mono text-xs text-gray-700">{{ $activeSettlement->extraCharge->unique_id }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Created</dt>
                            <dd class="text-gray-700">{{ $activeSettlement->created_at->format('M j, Y g:i A') }}</dd>
                        </div>
                        @if ($activeSettlement->createdBy)
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">Created By</dt>
                                <dd class="text-gray-700">{{ $activeSettlement->createdBy->full_name }}</dd>
                            </div>
                        @endif
                    </dl>
                    <p class="text-xs text-gray-400 mt-3">Billing and payment are handled on the order — open the order for payment status.</p>
                </div>
            @endif

            {{-- Billing preparation totals — calculations live on the model --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Billing Preparation</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Labor Total</dt>
                        <dd class="font-medium text-gray-900">${{ number_format($ticket->labor_total, 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Charge Lines Total</dt>
                        <dd class="font-medium text-gray-900">${{ number_format($ticket->charge_line_total, 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Parts Cost</dt>
                        <dd class="font-medium text-gray-900">${{ number_format($ticket->parts_cost_total, 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Parts Customer Price</dt>
                        <dd class="font-medium text-gray-900">${{ number_format($ticket->parts_customer_total, 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 pt-2 border-t border-gray-100">
                        <dt class="text-gray-500">Billable Total</dt>
                        <dd class="font-semibold text-green-700">${{ number_format($ticket->billable_total, 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Non-Billable Total</dt>
                        <dd class="font-medium text-gray-600">${{ number_format($ticket->non_billable_total, 2) }}</dd>
                    </div>
                    @if ($ticket->financial_responsibility === FinancialResponsibility::OemWarranty)
                        <div class="flex justify-between gap-4 pt-2 border-t border-gray-100">
                            <dt class="text-gray-500">Warranty Claim Total</dt>
                            <dd class="font-semibold text-indigo-700">${{ number_format($ticket->warranty_claim_total, 2) }}</dd>
                        </div>
                    @endif
                    @if ($ticket->internal_cost_total > 0)
                        <div class="flex justify-between gap-4 pt-2 border-t border-gray-100">
                            <dt class="text-gray-500">Internal Cost</dt>
                            <dd class="font-medium text-gray-700">${{ number_format($ticket->internal_cost_total, 2) }}</dd>
                        </div>
                    @endif
                </dl>
                <p class="text-xs text-gray-400 mt-4">Preparation only — no customer charges are created from this page.</p>
                @if ($ticket->responsibility_decision === \App\Enums\Service\ResponsibilityDecision::CustomerPay && !$activeSettlement)
                    <a href="{{ route('admin.service-management.tickets.settlement.preview', $ticket) }}"
                        class="mt-3 block w-full text-center px-3 py-2 rounded-lg text-sm font-semibold border border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition">
                        Open Settlement Preview
                    </a>
                @endif
            </div>

            {{-- Assigned personnel --}}
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
