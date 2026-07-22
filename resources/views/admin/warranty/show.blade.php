@extends('admin.layouts.app')

@section('title', $case->case_number . ' — Warranty Case')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    @php
        use App\Enums\Warranty\WarrantyQueue;

        $queue = $case->queue;
        $path  = WarrantyQueue::happyPath();
        $queueIndex = array_search($queue, $path, true);

        $stageState = function (WarrantyQueue $stage) use ($case, $queue, $queueIndex, $path) {
            if ($queue === WarrantyQueue::Closed) {
                $column = $stage->timestampColumn();
                return ($stage === WarrantyQueue::NewIntake || ($column && $case->{$column})) ? 'complete' : 'pending';
            }
            $stageIndex = array_search($stage, $path, true);
            if ($stageIndex < $queueIndex) return 'complete';
            if ($stageIndex === $queueIndex) return 'current';
            return 'pending';
        };
    @endphp

    {{-- ===== Header ===== --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold flex items-center gap-2">
                    <x-heroicon-o-shield-check class="w-6 h-6 text-purple-600" />
                    {{ $case->case_number }}
                </h1>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $queue->color() }}">{{ $queue->label() }}</span>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $case->path->color() }}">{{ $case->path->label() }}</span>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $case->feeStatusColor() }}">Fee: {{ $case->feeStatusLabel() }}</span>
            </div>
            <p class="text-sm text-gray-500 mt-1">
                {{ $case->manufacturer }} {{ $case->model }} · SN {{ $case->serial_number }}
                @if ($case->customer) · {{ $case->customer->full_name }} @elseif ($case->equipment) · Internal Unit {{ $case->equipment->equipment_id }} @endif
                @if ($case->serviceTicket)
                    · Linked ticket
                    <a href="{{ route('admin.service-management.tickets.show', $case->serviceTicket) }}" class="text-blue-600 font-medium hover:text-blue-700">{{ $case->serviceTicket->ticket_number }}</a>
                @endif
            </p>
        </div>
        <a href="{{ route('admin.warranty.claims.index') }}"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition">
            <x-heroicon-o-arrow-left class="w-4 h-4" />
            Back to Warranty Claims
        </a>
    </div>

    {{-- ===== Queue ribbon ===== --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-5 py-4 mb-6 overflow-x-auto">
        <ol class="flex items-start gap-0 min-w-max">
            @foreach ($path as $stage)
                @php $state = $stageState($stage); @endphp
                <li class="flex items-start">
                    @if (!$loop->first)
                        <div class="w-7 lg:w-10 h-px mt-3.5 {{ $state === 'pending' ? 'bg-gray-200' : 'bg-purple-300' }}"></div>
                    @endif
                    <div class="flex flex-col items-center w-[92px] text-center">
                        @if ($state === 'complete')
                            <span class="w-7 h-7 rounded-full bg-green-100 border border-green-300 text-green-700 flex items-center justify-center">
                                <x-heroicon-s-check class="w-4 h-4" />
                            </span>
                        @elseif ($state === 'current')
                            <span class="w-7 h-7 rounded-full bg-purple-600 text-white text-xs font-bold flex items-center justify-center ring-4 ring-purple-100">
                                {{ $loop->iteration }}
                            </span>
                        @else
                            <span class="w-7 h-7 rounded-full bg-gray-100 border border-gray-200 text-gray-400 text-xs font-semibold flex items-center justify-center">
                                {{ $loop->iteration }}
                            </span>
                        @endif
                        <span class="mt-1.5 text-[11px] font-medium leading-tight {{ $state === 'current' ? 'text-purple-700' : ($state === 'complete' ? 'text-gray-600' : 'text-gray-400') }}">
                            {{ $stage->label() }}
                        </span>
                        @if ($column = $stage->timestampColumn())
                            @if ($case->{$column})
                                <span class="text-[10px] text-gray-400">{{ $case->{$column}->format('M j g:i A') }}</span>
                            @endif
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">

        {{-- ═══════════ LEFT: case context ═══════════ --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3">Equipment Identity</h2>
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between gap-2"><dt class="text-xs text-gray-400">Manufacturer</dt><dd class="text-gray-700 font-medium">{{ $case->manufacturer }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-xs text-gray-400">Model</dt><dd class="text-gray-700 font-medium">{{ $case->model }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-xs text-gray-400">Serial</dt><dd class="text-gray-700 font-medium">{{ $case->serial_number }}</dd></div>
                    @if ($case->engine_serial_number)
                        <div class="flex justify-between gap-2"><dt class="text-xs text-gray-400">Engine serial</dt><dd class="text-gray-700 font-medium">{{ $case->engine_serial_number }}</dd></div>
                    @endif
                    @if ($case->has_hour_meter)
                        <div class="flex justify-between gap-2"><dt class="text-xs text-gray-400">Hours</dt><dd class="text-gray-700 font-medium">{{ number_format((int) $case->hours) }}</dd></div>
                    @endif
                </dl>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3">Warranty Intake</h2>
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between gap-2"><dt class="text-xs text-gray-400">Path</dt><dd class="text-gray-700 font-medium">{{ $case->path->longLabel() }}</dd></div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-xs text-gray-400">{{ $case->customer ? 'Customer' : 'Owner' }}</dt>
                        <dd class="text-gray-700 font-medium text-right">{{ $case->customer?->full_name ?? 'Internal Equipment' }}</dd>
                    </div>
                    @if ($case->purchase_date)
                        <div class="flex justify-between gap-2"><dt class="text-xs text-gray-400">Purchased</dt><dd class="text-gray-700 font-medium">{{ $case->purchase_date->format('M j, Y') }}{{ $case->selling_dealer ? ' · ' . $case->selling_dealer : '' }}</dd></div>
                    @endif
                    @if ($case->warranty_registration_number)
                        <div class="flex justify-between gap-2"><dt class="text-xs text-gray-400">Registration</dt><dd class="text-gray-700 font-medium">{{ $case->warranty_registration_number }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-2"><dt class="text-xs text-gray-400">Opened</dt><dd class="text-gray-700 font-medium">{{ $case->created_at->format('M j, Y g:i A') }}</dd></div>
                </dl>
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Complaint</p>
                    <p class="text-sm text-gray-800 whitespace-pre-line">{{ $case->complaint }}</p>
                    @if ($case->internal_notes)
                        <p class="text-xs font-semibold text-gray-500 uppercase mt-3 mb-1">Internal Notes</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $case->internal_notes }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══════════ CENTER: current task ═══════════ --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border-2 {{ $queue === WarrantyQueue::Closed ? 'border-gray-200' : 'border-purple-200' }} shadow-sm p-6">
                <div class="flex items-center justify-between gap-3 mb-1">
                    <h2 class="text-base font-semibold text-gray-900">Current Task</h2>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $queue->color() }}">{{ $queue->label() }}</span>
                </div>
                <div class="mt-3 rounded-lg bg-purple-50 border border-purple-100 px-4 py-2.5">
                    <p class="text-xs text-purple-800"><span class="font-semibold">Waiting on:</span> {{ $queue->waitingOn() }}</p>
                    <p class="text-xs text-purple-800 mt-0.5"><span class="font-semibold">Next required action:</span> {{ $case->nextRequiredAction() }}</p>
                </div>

                <div class="mt-5">
                    @if ($queue === WarrantyQueue::NewIntake)
                        <p class="text-sm text-gray-600 mb-4">
                            Review the intake details{{ $case->feeApplies() ? ' and settle the diagnostic fee' : '' }}, then complete
                            intake to hand the machine to the shop for diagnosis on
                            <span class="font-semibold">{{ $case->serviceTicket?->ticket_number }}</span>.
                        </p>

                        @if ($case->feeApplies())
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 mb-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold text-gray-500 uppercase">Diagnostic Fee</p>
                                        <p class="text-sm text-gray-800 mt-0.5">
                                            ${{ number_format((float) ($case->diagnostic_fee_amount ?? 0), 2) }}
                                            · {{ $case->diagnostic_fee_taxable ? 'Taxable' : 'Non-taxable' }}
                                            · <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold {{ $case->feeStatusColor() }}">{{ $case->feeStatusLabel() }}</span>
                                        </p>
                                    </div>
                                    @unless ($case->feeSettled())
                                        <span class="flex items-center gap-2">
                                            <form method="POST" action="{{ route('admin.warranty.claims.fee.update', $case) }}">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="action" value="collected">
                                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-green-600 text-white hover:bg-green-700 transition">Mark Collected</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.warranty.claims.fee.update', $case) }}">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="action" value="waived">
                                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100 transition">Manager Waiver</button>
                                            </form>
                                        </span>
                                    @endunless
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.warranty.claims.complete-intake', $case) }}">
                            @csrf
                            <button type="submit" @disabled(!$case->feeSettled())
                                class="w-full sm:w-auto px-6 py-3 rounded-lg font-medium text-sm shadow-sm transition
                                {{ $case->feeSettled() ? 'bg-purple-600 text-white hover:bg-purple-700' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}"
                                @unless ($case->feeSettled()) title="Collect or waive the diagnostic fee first" @endunless>
                                Complete Intake → Awaiting Diagnosis
                            </button>
                            @unless ($case->feeSettled())
                                <p class="text-xs text-amber-600 mt-2">Collect (or waive) the diagnostic fee to enable this step.</p>
                            @endunless
                        </form>

                    @elseif ($queue === WarrantyQueue::AwaitingDiagnosis)
                        <p class="text-sm text-gray-600">
                            The shop is diagnosing on
                            <a href="{{ route('admin.service-management.tickets.show', $case->serviceTicket) }}" class="text-blue-600 font-semibold hover:text-blue-700">{{ $case->serviceTicket?->ticket_number }}</a>.
                            When the diagnosis is complete, this case advances automatically to <span class="font-semibold">Ready to Submit</span>.
                        </p>

                    @elseif ($queue === WarrantyQueue::Closed)
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 flex items-center gap-3">
                            <x-heroicon-o-check-circle class="w-6 h-6 text-slate-500 shrink-0" />
                            <p class="text-sm text-slate-700">Case closed {{ $case->closed_at?->format('M j, Y g:i A') }}.</p>
                        </div>

                    @elseif ($queue === WarrantyQueue::ReadyToSubmit)
                        {{-- Submit to Manufacturer --}}
                        <form method="POST" action="{{ route('admin.warranty.claims.submit', $case) }}" class="space-y-4">
                            @csrf
                            <p class="text-sm text-gray-600">Submit this claim to <span class="font-semibold">{{ $case->manufacturer }}</span> and record the claim/submission reference.</p>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Claim / Submission Reference <span class="text-red-500">*</span></label>
                                <input type="text" name="oem_submission_reference" required maxlength="255"
                                       class="w-full max-w-md border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Manufacturer claim #">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Optional"></textarea>
                            </div>
                            <button type="submit" class="px-6 py-3 rounded-lg font-medium text-sm bg-purple-600 text-white hover:bg-purple-700 shadow-sm transition">
                                Submit to Manufacturer → Waiting on Manufacturer
                            </button>
                        </form>

                    @elseif ($queue === WarrantyQueue::WaitingOnManufacturer)
                        {{-- Record OEM Decision --}}
                        <form method="POST" action="{{ route('admin.warranty.claims.oem-decision', $case) }}" class="space-y-4" x-data="{ decision: '' }">
                            @csrf
                            <p class="text-sm text-gray-600">
                                Submitted{{ $case->oem_submission_reference ? ' as ' : '' }}<span class="font-semibold">{{ $case->oem_submission_reference }}</span>. Record the manufacturer's decision.
                            </p>
                            <div class="flex flex-wrap gap-3">
                                @foreach (\App\Enums\Warranty\WarrantyOemDecision::cases() as $d)
                                    <label class="flex items-center gap-2 cursor-pointer border border-gray-200 rounded-lg px-3 py-2">
                                        <input type="radio" name="oem_decision" value="{{ $d->value }}" x-model="decision" required class="text-purple-600">
                                        <span class="text-sm text-gray-700">{{ $d->label() }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div x-show="decision === 'approved' || decision === 'partial'" x-cloak>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Approved Amount <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" min="0" name="oem_approved_amount" x-bind:required="decision === 'approved' || decision === 'partial'"
                                       class="w-full sm:w-64 border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="0.00">
                                <p class="text-xs text-gray-400 mt-1">The amount the manufacturer will cover (becomes the expected reimbursement).</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Optional"></textarea>
                            </div>
                            <button type="submit" class="px-6 py-3 rounded-lg font-medium text-sm bg-purple-600 text-white hover:bg-purple-700 shadow-sm transition">
                                Record OEM Decision
                            </button>
                            <p class="text-xs text-gray-400">Approved goes straight to repair; Partial and Denied ask the customer how to proceed.</p>
                        </form>

                    @elseif ($queue === WarrantyQueue::AwaitingCustomerDecision)
                        {{-- Record Customer Decision --}}
                        <form method="POST" action="{{ route('admin.warranty.claims.customer-decision', $case) }}" class="space-y-4">
                            @csrf
                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
                                Manufacturer outcome:
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $case->oem_decision?->color() }}">{{ $case->oem_decision?->label() }}</span>
                                @if ($case->oem_approved_amount !== null)
                                    · covers <span class="font-semibold">${{ number_format((float) $case->oem_approved_amount, 2) }}</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600">Does the customer want to proceed with the repair (paying any balance), or decline?</p>
                            <div class="flex flex-wrap gap-3">
                                @foreach (\App\Enums\Warranty\WarrantyCustomerDecision::cases() as $d)
                                    <label class="flex items-center gap-2 cursor-pointer border border-gray-200 rounded-lg px-3 py-2">
                                        <input type="radio" name="customer_decision" value="{{ $d->value }}" required class="text-purple-600">
                                        <span class="text-sm text-gray-700">{{ $d->label() }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Optional"></textarea>
                            </div>
                            <button type="submit" class="px-6 py-3 rounded-lg font-medium text-sm bg-purple-600 text-white hover:bg-purple-700 shadow-sm transition">
                                Record Customer Decision
                            </button>
                        </form>

                    @elseif ($queue === WarrantyQueue::ApprovedForRepair)
                        {{-- Repair happens on the linked Service Ticket --}}
                        <form method="POST" action="{{ route('admin.warranty.claims.repair-complete', $case) }}" class="space-y-4">
                            @csrf
                            <p class="text-sm text-gray-600">
                                The repair is performed on
                                <a href="{{ route('admin.service-management.tickets.show', $case->serviceTicket) }}" class="text-blue-600 font-semibold hover:text-blue-700">{{ $case->serviceTicket?->ticket_number }}</a>.
                                Mark it complete when the shop finishes.
                            </p>
                            @if (($case->reimbursement_expected_amount ?? 0) > 0)
                                <p class="text-xs text-gray-500">Expected OEM reimbursement: <span class="font-semibold">${{ number_format((float) $case->reimbursement_expected_amount, 2) }}</span> — the case will move to Awaiting Reimbursement.</p>
                            @else
                                <p class="text-xs text-gray-500">No OEM reimbursement expected — completing the repair will close the case.</p>
                            @endif
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Optional"></textarea>
                            </div>
                            <button type="submit" class="px-6 py-3 rounded-lg font-medium text-sm bg-purple-600 text-white hover:bg-purple-700 shadow-sm transition">
                                Repair Complete
                            </button>
                        </form>

                    @elseif ($queue === WarrantyQueue::AwaitingReimbursement)
                        {{-- Track Reimbursement (state-only) --}}
                        <form method="POST" action="{{ route('admin.warranty.claims.reimbursement', $case) }}" class="space-y-4">
                            @csrf
                            <p class="text-sm text-gray-600">
                                Awaiting reimbursement from <span class="font-semibold">{{ $case->manufacturer }}</span>
                                @if ($case->reimbursement_expected_amount !== null)
                                    — expected <span class="font-semibold">${{ number_format((float) $case->reimbursement_expected_amount, 2) }}</span>
                                @endif. Record it when received to close the case.
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount Received <span class="text-red-500">*</span></label>
                                    <input type="number" step="0.01" min="0" name="reimbursement_received_amount" required
                                           value="{{ $case->reimbursement_expected_amount }}"
                                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Received <span class="text-red-500">*</span></label>
                                    <input type="date" name="reimbursement_received_at" required value="{{ now()->toDateString() }}"
                                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Optional"></textarea>
                            </div>
                            <button type="submit" class="px-6 py-3 rounded-lg font-medium text-sm bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm transition">
                                Record Reimbursement → Close Case
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══════════ RIGHT: linkage, money, history ═══════════ --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-1.5">
                    <x-heroicon-o-wrench-screwdriver class="w-4 h-4 text-gray-400" />
                    Linked Service Ticket
                </h2>
                @if ($case->serviceTicket)
                    <dl class="space-y-1.5 text-sm">
                        <div class="flex justify-between gap-2"><dt class="text-xs text-gray-400">Ticket</dt>
                            <dd><a href="{{ route('admin.service-management.tickets.show', $case->serviceTicket) }}" class="text-blue-600 font-semibold hover:text-blue-700">{{ $case->serviceTicket->ticket_number }}</a></dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-xs text-gray-400">Status</dt>
                            <dd><span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $case->serviceTicket->repair_status->color() }}">{{ $case->serviceTicket->repair_status->label() }}</span></dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-xs text-gray-400">Type</dt>
                            <dd class="text-gray-700 font-medium text-xs pt-0.5">{{ $case->serviceTicket->service_type->label() }}</dd></div>
                    </dl>
                    <p class="text-xs text-gray-400 mt-3 pt-2.5 border-t border-gray-100">
                        The Warranty Case authorizes work — this ticket performs it.
                    </p>
                @else
                    <p class="text-sm text-gray-400">No linked ticket.</p>
                @endif
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-1.5">
                    <x-heroicon-o-banknotes class="w-4 h-4 text-gray-400" />
                    Financial Snapshot
                </h2>
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between gap-2"><dt class="text-xs text-gray-400">Diagnostic fee</dt>
                        <dd><span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $case->feeStatusColor() }}">{{ $case->feeStatusLabel() }}</span></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-xs text-gray-400">Reimbursed</dt><dd class="text-gray-700 font-medium">$0.00</dd></div>
                </dl>
                <p class="text-xs text-gray-400 mt-3 pt-2.5 border-t border-gray-100">Claim value and reimbursement tracking arrive in later phases.</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-1.5">
                    <x-heroicon-o-clock class="w-4 h-4 text-gray-400" />
                    Activity Timeline
                </h2>
                <ol class="space-y-3">
                    @forelse ($case->events as $event)
                        <li class="flex gap-2.5">
                            <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $event->event_type->color() }}"></span>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-700">
                                    {{ $event->event_type->label() }}
                                    @if ($event->old_value && $event->new_value)
                                        <span class="text-gray-400">— {{ $event->old_value }} → {{ $event->new_value }}</span>
                                    @elseif ($event->new_value)
                                        <span class="text-gray-400">— {{ $event->new_value }}</span>
                                    @endif
                                </p>
                                @if ($event->notes)
                                    <p class="text-[11px] text-gray-500">{{ $event->notes }}</p>
                                @endif
                                <p class="text-[11px] text-gray-400">
                                    {{ $event->user ? $event->user->first_name . ' ' . $event->user->last_name : 'System' }}
                                    · {{ $event->created_at->format('M j, g:i A') }}
                                </p>
                            </div>
                        </li>
                    @empty
                        <li class="text-xs text-gray-400">No events recorded.</li>
                    @endforelse
                </ol>
            </div>
        </div>
    </div>

@endsection
