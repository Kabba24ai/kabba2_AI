@extends('admin.layouts.app')

@section('title', $ticket->ticket_number . ' — Field Operations Workbench')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    @php
        use App\Enums\FieldService\FieldMissionStatus;
        use App\Enums\FieldService\FieldOperationalExpectation;
        use App\Enums\FieldService\FieldRecoveryRisk;
        use App\Enums\FieldService\FieldSafetyConcern;
        use App\Enums\FieldService\FieldSiteAccess;
        use App\Enums\FieldService\FieldYesNoUnknown;

        $inputClass = 'w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm bg-white text-gray-900 focus:ring focus:border-blue-400 outline-none disabled:bg-gray-50 disabled:text-gray-400';
        $labelClass = 'block text-sm font-medium text-gray-700 mb-1';

        $status    = $ticket->mission_status;
        $flow      = FieldMissionStatus::missionFlow();
        $next      = $ticket->nextMissionStatus();
        $flowIndex = array_search($status, $flow, true);
        $isDone    = $status === FieldMissionStatus::Completed;
        $isCancelled = $status === FieldMissionStatus::Cancelled;

        $stageState = function (FieldMissionStatus $stage) use ($ticket, $flowIndex, $isDone, $isCancelled, $flow) {
            if ($isDone) return 'complete';
            if ($isCancelled) {
                $column = $stage->timestampColumn();
                return ($stage === FieldMissionStatus::Draft || ($column && $ticket->{$column})) ? 'complete' : 'pending';
            }
            $stageIndex = array_search($stage, $flow, true);
            if ($flowIndex === false) return 'pending';
            if ($stageIndex < $flowIndex) return 'complete';
            if ($stageIndex === $flowIndex) return 'current';
            return 'pending';
        };
    @endphp

    {{-- ===== Ticket header ===== --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold flex items-center gap-2">
                    <x-heroicon-o-truck class="w-6 h-6 text-blue-600" />
                    {{ $ticket->ticket_number }}
                </h1>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $status->color() }}">{{ $status->label() }}</span>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $ticket->priority->color() }}">{{ $ticket->priority->label() }}</span>
            </div>
            <p class="text-sm text-gray-500 mt-1">
                Field Operations Workbench ·
                Created {{ $ticket->created_at->format('M j, Y g:i A') }}
                @if ($ticket->createdBy) by {{ $ticket->createdBy->first_name }} {{ $ticket->createdBy->last_name }} @endif
            </p>
        </div>
        <a href="{{ route('admin.field-service.tickets.index') }}"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition">
            <x-heroicon-o-arrow-left class="w-4 h-4" />
            Back to Field Service
        </a>
    </div>

    {{-- ===== Mission progress ribbon ===== --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-5 py-4 mb-6 overflow-x-auto">
        <ol class="flex items-start gap-0 min-w-max">
            @foreach ($flow as $stage)
                @php $state = $stageState($stage); @endphp
                <li class="flex items-start">
                    @if (!$loop->first)
                        <div class="w-8 lg:w-12 h-px mt-3.5 {{ $state === 'pending' ? 'bg-gray-200' : 'bg-blue-300' }}"></div>
                    @endif
                    <div class="flex flex-col items-center w-24 text-center">
                        @if ($state === 'complete')
                            <span class="w-7 h-7 rounded-full bg-green-100 border border-green-300 text-green-700 flex items-center justify-center">
                                <x-heroicon-s-check class="w-4 h-4" />
                            </span>
                        @elseif ($state === 'current')
                            <span class="w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center ring-4 ring-blue-100">
                                {{ $loop->iteration }}
                            </span>
                        @else
                            <span class="w-7 h-7 rounded-full bg-gray-100 border border-gray-200 text-gray-400 text-xs font-semibold flex items-center justify-center">
                                {{ $loop->iteration }}
                            </span>
                        @endif
                        <span class="mt-1.5 text-[11px] font-medium leading-tight {{ $state === 'current' ? 'text-blue-700' : ($state === 'complete' ? 'text-gray-600' : 'text-gray-400') }}">
                            {{ $stage->label() }}
                        </span>
                        @if ($column = $stage->timestampColumn())
                            @if ($ticket->{$column})
                                <span class="text-[10px] text-gray-400">{{ $ticket->{$column}->format('M j g:i A') }}</span>
                            @endif
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>

    @if ($isCancelled)
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 flex items-center gap-3">
            <x-heroicon-o-x-circle class="w-6 h-6 text-red-500 shrink-0" />
            <div>
                <p class="text-sm font-semibold text-red-700">Mission cancelled {{ $ticket->cancelled_at?->format('M j, Y g:i A') }}</p>
                <p class="text-xs text-red-600">This field service ticket is closed.</p>
            </div>
        </div>
    @elseif ($isDone)
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-5 py-4 flex items-center gap-3">
            <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 shrink-0" />
            <div>
                <p class="text-sm font-semibold text-green-700">Mission completed {{ $ticket->completed_at?->format('M j, Y g:i A') }}</p>
                <p class="text-xs text-green-600">Assessment recorded — downstream field workflows (repair, recovery, dealer service) arrive in a later phase.</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">

        {{-- ═══════════ LEFT: mission context ═══════════ --}}
        <div class="space-y-6">
            {{-- Customer & Order --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3">Customer &amp; Order</h2>
                <dl class="space-y-2 text-sm">
                    <div>
                        <dt class="text-xs text-gray-400">Customer</dt>
                        <dd class="text-gray-800 font-medium">
                            {{ $ticket->customer?->full_name ?? $ticket->order?->customer_name ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">Rental Order</dt>
                        <dd class="text-gray-800">{{ $ticket->order?->order_number ?? 'No related order' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Equipment --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3">Equipment</h2>
                @if ($ticket->equipment)
                    <p class="text-sm font-medium text-gray-800">{{ $ticket->equipment->equipment_name }}</p>
                    <dl class="mt-2 space-y-1 text-sm">
                        @if ($ticket->equipment->equipment_id)
                            <div class="flex justify-between"><dt class="text-gray-400 text-xs">Unit #</dt><dd class="text-gray-700">{{ $ticket->equipment->equipment_id }}</dd></div>
                        @endif
                        @if ($ticket->serial_number)
                            <div class="flex justify-between"><dt class="text-gray-400 text-xs">Serial</dt><dd class="text-gray-700">{{ $ticket->serial_number }}</dd></div>
                        @endif
                        @if ($ticket->equipment->brand)
                            <div class="flex justify-between"><dt class="text-gray-400 text-xs">Brand</dt><dd class="text-gray-700">{{ $ticket->equipment->brand }}</dd></div>
                        @endif
                    </dl>
                @else
                    <p class="text-sm text-gray-400">No equipment linked.</p>
                    @if ($ticket->serial_number)
                        <p class="text-sm text-gray-600 mt-1">Serial: {{ $ticket->serial_number }}</p>
                    @endif
                @endif
            </div>

            {{-- Job site --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3">Job Site</h2>
                <p class="text-sm text-gray-800">{{ $ticket->job_site_address }}</p>
                <dl class="mt-3 space-y-1 text-sm">
                    @if ($ticket->contact_name)
                        <div class="flex justify-between"><dt class="text-gray-400 text-xs">Contact</dt><dd class="text-gray-700">{{ $ticket->contact_name }}</dd></div>
                    @endif
                    @if ($ticket->contact_phone)
                        <div class="flex justify-between"><dt class="text-gray-400 text-xs">Phone</dt><dd class="text-gray-700">{{ $ticket->contact_phone }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-gray-400 text-xs">Reported</dt><dd class="text-gray-700">{{ $ticket->reported_at->format('M j, Y g:i A') }}</dd></div>
                </dl>
            </div>

            {{-- Safety & site alerts --}}
            <div class="rounded-xl border shadow-sm p-5 {{ $ticket->hasSafetyOrSiteAlert() ? 'bg-red-50 border-red-200' : 'bg-white border-gray-200' }}">
                <h2 class="text-sm font-semibold {{ $ticket->hasSafetyOrSiteAlert() ? 'text-red-700' : 'text-gray-800' }} mb-3 flex items-center gap-1.5">
                    <x-heroicon-o-shield-exclamation class="w-4 h-4" />
                    Safety &amp; Site
                </h2>
                <dl class="space-y-1.5 text-sm">
                    @foreach ([
                        'Safety Concern' => [$ticket->safety_concern, $ticket->safety_concern === FieldSafetyConcern::Significant],
                        'Machine Status' => [$ticket->machine_status, false],
                        'Machine Stuck'  => [$ticket->machine_stuck, $ticket->machine_stuck === FieldYesNoUnknown::Yes],
                        'Recovery Risk'  => [$ticket->recovery_risk, $ticket->recovery_risk === FieldRecoveryRisk::Likely],
                        'Site Access'    => [$ticket->site_access, $ticket->site_access === FieldSiteAccess::Difficult],
                    ] as $label => [$value, $alert])
                        <div class="flex justify-between items-center">
                            <dt class="text-xs text-gray-500">{{ $label }}</dt>
                            <dd class="text-xs font-semibold {{ $alert ? 'text-red-700' : 'text-gray-700' }}">
                                {{ $value->label() }}@if ($alert) ⚠️ @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
                @if ($ticket->site_notes)
                    <p class="mt-3 text-xs {{ $ticket->hasSafetyOrSiteAlert() ? 'text-red-700' : 'text-gray-500' }} border-t {{ $ticket->hasSafetyOrSiteAlert() ? 'border-red-200' : 'border-gray-100' }} pt-2">
                        {{ $ticket->site_notes }}
                    </p>
                @endif
            </div>
        </div>

        {{-- ═══════════ CENTER: current mission step ═══════════ --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border-2 {{ $status->isTerminal() ? 'border-gray-200' : 'border-blue-200' }} shadow-sm p-6">
                <div class="flex items-center justify-between gap-3 mb-1">
                    <h2 class="text-base font-semibold text-gray-900">Current Mission Step</h2>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $status->color() }}">{{ $status->label() }}</span>
                </div>

                {{-- Problem summary is always in view — it is the mission --}}
                <div class="mt-4 rounded-lg bg-gray-50 border border-gray-200 p-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Reported Problem</p>
                    <p class="text-sm text-gray-800 whitespace-pre-line">{{ $ticket->problem_summary }}</p>
                    @if ($ticket->diagnostic_summary)
                        <p class="text-xs font-semibold text-gray-500 uppercase mt-3 mb-1">Diagnostic Handoff</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $ticket->diagnostic_summary }}</p>
                    @endif
                    @if ($ticket->ai_session_reference)
                        <p class="text-xs text-gray-400 mt-2">AI Technician session: {{ $ticket->ai_session_reference }}</p>
                    @endif
                </div>

                <div class="mt-5">
                    @if ($status === FieldMissionStatus::Draft)
                        <p class="text-sm text-gray-600 mb-4">
                            Review the incident details, media checklist, and dispatch assessment. When the ticket is
                            solid, mark it ready so it enters the dispatch queue.
                        </p>
                        <form method="POST" action="{{ route('admin.field-service.tickets.status', $ticket) }}">
                            @csrf
                            <input type="hidden" name="mission_status" value="{{ FieldMissionStatus::ReadyForDispatch->value }}">
                            <button type="submit" class="w-full sm:w-auto px-6 py-3 rounded-lg font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                                Mark Ready for Dispatch
                            </button>
                        </form>

                    @elseif ($status === FieldMissionStatus::ReadyForDispatch)
                        <p class="text-sm text-gray-600 mb-4">
                            Assign the technician and truck, set expected times, and note tools and parts to bring.
                            Then confirm the assignment.
                        </p>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 mb-4">
                            @include('admin.field_service.partials._dispatch_form')
                        </div>
                        <form method="POST" action="{{ route('admin.field-service.tickets.status', $ticket) }}">
                            @csrf
                            <input type="hidden" name="mission_status" value="{{ FieldMissionStatus::Assigned->value }}">
                            <button type="submit" @disabled(!$ticket->technician_id)
                                class="w-full sm:w-auto px-6 py-3 rounded-lg font-medium text-sm shadow-sm transition
                                {{ $ticket->technician_id ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}"
                                @if (!$ticket->technician_id) title="Assign a technician first" @endif>
                                Confirm Assignment
                            </button>
                            @unless ($ticket->technician_id)
                                <p class="text-xs text-amber-600 mt-2">Save a technician assignment above to enable this step.</p>
                            @endunless
                        </form>

                    @elseif ($status === FieldMissionStatus::Assigned)
                        <p class="text-sm text-gray-600 mb-4">
                            {{ $ticket->technician?->first_name }} {{ $ticket->technician?->last_name }} is assigned.
                            When the truck rolls, mark the technician en route.
                        </p>
                        <form method="POST" action="{{ route('admin.field-service.tickets.status', $ticket) }}">
                            @csrf
                            <input type="hidden" name="mission_status" value="{{ FieldMissionStatus::EnRoute->value }}">
                            <button type="submit" class="w-full sm:w-auto px-6 py-3 rounded-lg font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                                Technician En Route
                            </button>
                        </form>

                    @elseif ($status === FieldMissionStatus::EnRoute)
                        <p class="text-sm text-gray-600 mb-4">
                            En route since {{ $ticket->en_route_at?->format('g:i A') }}.
                            @if ($ticket->estimated_arrival_at) Estimated arrival {{ $ticket->estimated_arrival_at->format('M j, g:i A') }}. @endif
                            Mark arrival when the technician reaches the job site.
                        </p>
                        <form method="POST" action="{{ route('admin.field-service.tickets.status', $ticket) }}">
                            @csrf
                            <input type="hidden" name="mission_status" value="{{ FieldMissionStatus::OnSite->value }}">
                            <button type="submit" class="w-full sm:w-auto px-6 py-3 rounded-lg font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                                Arrived On Site
                            </button>
                        </form>

                    @elseif ($status === FieldMissionStatus::OnSite)
                        <p class="text-sm text-gray-600 mb-4">
                            On site since {{ $ticket->on_site_at?->format('g:i A') }}. Begin the field assessment —
                            walk the machine, verify the reported problem, and evaluate site conditions.
                        </p>
                        <form method="POST" action="{{ route('admin.field-service.tickets.status', $ticket) }}">
                            @csrf
                            <input type="hidden" name="mission_status" value="{{ FieldMissionStatus::FieldAssessment->value }}">
                            <button type="submit" class="w-full sm:w-auto px-6 py-3 rounded-lg font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                                Begin Field Assessment
                            </button>
                        </form>

                    @elseif ($status === FieldMissionStatus::FieldAssessment)
                        <p class="text-sm text-gray-600 mb-4">
                            Record what the technician found and confirm (or correct) the expected outcome.
                            The initial expectation was
                            <span class="font-semibold">{{ $ticket->operational_expectation->label() }}</span>.
                        </p>
                        <form method="POST" action="{{ route('admin.field-service.tickets.status', $ticket) }}">
                            @csrf
                            <input type="hidden" name="mission_status" value="{{ FieldMissionStatus::AssessmentComplete->value }}">
                            <div class="mb-3">
                                <label class="{{ $labelClass }} required">Field Assessment Summary</label>
                                <textarea name="assessment_summary" rows="4" required class="{{ $inputClass }}"
                                    placeholder="What was found on site — condition, cause if known, what it will take to resolve…">{{ old('assessment_summary', $ticket->assessment_summary) }}</textarea>
                                @error('assessment_summary')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div class="mb-4 sm:max-w-xs">
                                <label class="{{ $labelClass }}">Confirmed Outcome Direction</label>
                                <select name="operational_expectation" class="{{ $inputClass }}">
                                    @foreach (FieldOperationalExpectation::cases() as $case)
                                        <option value="{{ $case->value }}" @selected(old('operational_expectation', $ticket->operational_expectation->value) === $case->value)>{{ $case->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="w-full sm:w-auto px-6 py-3 rounded-lg font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                                Complete Assessment
                            </button>
                        </form>

                    @elseif ($status === FieldMissionStatus::AssessmentComplete)
                        {{-- Transient state — assessment normally flows straight into the decision --}}
                        <p class="text-sm text-gray-600 mb-4">
                            The assessment is recorded. The mission now requires an Operational Decision before anything else can happen.
                        </p>
                        <form method="POST" action="{{ route('admin.field-service.tickets.status', $ticket) }}">
                            @csrf
                            <input type="hidden" name="mission_status" value="{{ FieldMissionStatus::OperationalDecision->value }}">
                            <button type="submit" class="w-full sm:w-auto px-6 py-3 rounded-lg font-medium text-sm bg-purple-600 text-white hover:bg-purple-700 shadow-sm transition">
                                Proceed to Operational Decision
                            </button>
                        </form>

                    @elseif ($status === FieldMissionStatus::OperationalDecision && !$ticket->operational_outcome)
                        <div class="rounded-lg border border-teal-200 bg-teal-50 p-4 mb-4">
                            <p class="text-xs font-semibold text-teal-700 uppercase mb-1">Field Assessment</p>
                            <p class="text-sm text-teal-900 whitespace-pre-line">{{ $ticket->assessment_summary }}</p>
                        </div>

                        <div class="rounded-lg border-2 border-purple-300 bg-purple-50 px-4 py-3 mb-4 flex items-center gap-3">
                            <x-heroicon-o-arrows-pointing-out class="w-6 h-6 text-purple-600 shrink-0" />
                            <div>
                                <p class="text-sm font-semibold text-purple-800">Operational Decision Required</p>
                                <p class="text-xs text-purple-700">
                                    Not how to fix the machine — what operational response the company takes.
                                    Every field incident resolves to exactly one outcome, and it drives everything that follows.
                                </p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.field-service.tickets.decision.store', $ticket) }}">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach (\App\Enums\FieldService\FieldOperationalOutcome::cases() as $outcome)
                                    <label class="cursor-pointer {{ $loop->last ? 'sm:col-span-2' : '' }}">
                                        <input type="radio" name="operational_outcome" value="{{ $outcome->value }}" class="peer sr-only" required>
                                        <div class="h-full rounded-xl border-2 border-gray-200 bg-white p-4 transition hover:border-purple-300 hover:shadow-sm
                                            peer-checked:border-purple-500 peer-checked:bg-purple-50 peer-checked:ring-2 peer-checked:ring-purple-200">
                                            <div class="flex items-center gap-2.5 mb-1.5">
                                                <x-dynamic-component :component="$outcome->icon()" class="w-6 h-6 {{ $outcome->color() }}" />
                                                <span class="text-sm font-semibold text-gray-900">{{ $outcome->label() }}</span>
                                            </div>
                                            <p class="text-xs text-gray-600">{{ $outcome->description() }}</p>
                                            <p class="text-[11px] text-gray-400 mt-1.5"><span class="font-semibold">Typical:</span> {{ $outcome->examples() }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('operational_outcome')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                            <div class="flex items-center justify-between gap-3 mt-4">
                                <p class="text-xs text-gray-400">This decision is permanent and becomes part of the incident history.</p>
                                <button type="submit"
                                    class="px-6 py-3 rounded-lg font-medium text-sm bg-purple-600 text-white hover:bg-purple-700 shadow-sm transition shrink-0">
                                    Record Operational Decision
                                </button>
                            </div>
                        </form>

                    @elseif ($status === FieldMissionStatus::OperationalDecision)
                        <div class="rounded-lg border border-teal-200 bg-teal-50 p-4 mb-4">
                            <p class="text-xs font-semibold text-teal-700 uppercase mb-1">Field Assessment</p>
                            <p class="text-sm text-teal-900 whitespace-pre-line">{{ $ticket->assessment_summary }}</p>
                        </div>
                        <div class="rounded-xl border-2 border-purple-300 bg-purple-50 p-4 mb-4 flex items-center gap-3">
                            <x-dynamic-component :component="$ticket->operational_outcome->icon()" class="w-8 h-8 {{ $ticket->operational_outcome->color() }} shrink-0" />
                            <div>
                                <p class="text-xs font-semibold text-purple-700 uppercase">Operational Decision</p>
                                <p class="text-base font-semibold text-purple-900">{{ $ticket->operational_outcome->label() }}</p>
                                <p class="text-xs text-purple-700">{{ $ticket->operational_outcome->description() }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">
                            The guided {{ $ticket->operational_outcome->label() }} workflow arrives in a later phase.
                            For now, complete the mission to close this ticket.
                        </p>
                        <form method="POST" action="{{ route('admin.field-service.tickets.status', $ticket) }}">
                            @csrf
                            <input type="hidden" name="mission_status" value="{{ FieldMissionStatus::Completed->value }}">
                            <button type="submit" class="w-full sm:w-auto px-6 py-3 rounded-lg font-medium text-sm bg-green-600 text-white hover:bg-green-700 shadow-sm transition">
                                Complete Mission
                            </button>
                        </form>

                    @else
                        @if ($ticket->operational_outcome)
                            <div class="rounded-xl border-2 border-purple-200 bg-purple-50 p-4 mb-4 flex items-center gap-3">
                                <x-dynamic-component :component="$ticket->operational_outcome->icon()" class="w-8 h-8 {{ $ticket->operational_outcome->color() }} shrink-0" />
                                <div>
                                    <p class="text-xs font-semibold text-purple-700 uppercase">Operational Decision</p>
                                    <p class="text-base font-semibold text-purple-900">{{ $ticket->operational_outcome->label() }}</p>
                                </div>
                            </div>
                        @endif
                        @if ($ticket->assessment_summary)
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Field Assessment</p>
                                <p class="text-sm text-gray-800 whitespace-pre-line">{{ $ticket->assessment_summary }}</p>
                            </div>
                        @elseif (!$ticket->operational_outcome)
                            <p class="text-sm text-gray-500">This mission is closed.</p>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Cancel mission (kept out of the primary action area) --}}
            @unless ($status->isTerminal())
                <details class="bg-white rounded-xl border border-gray-200 shadow-sm">
                    <summary class="px-5 py-3 text-sm text-gray-500 cursor-pointer select-none hover:text-red-600 transition">
                        Cancel this mission…
                    </summary>
                    <form method="POST" action="{{ route('admin.field-service.tickets.status', $ticket) }}" class="px-5 pb-5">
                        @csrf
                        <input type="hidden" name="mission_status" value="{{ FieldMissionStatus::Cancelled->value }}">
                        <label class="{{ $labelClass }} required">Why is this mission being cancelled?</label>
                        <textarea name="note" rows="2" required class="{{ $inputClass }}"
                            placeholder="Customer resolved it, duplicate ticket, handled another way…">{{ old('note') }}</textarea>
                        @error('note')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        <div class="flex justify-end mt-3">
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-red-600 text-white hover:bg-red-700 transition">
                                Cancel Mission
                            </button>
                        </div>
                    </form>
                </details>
            @endunless
        </div>

        {{-- ═══════════ RIGHT: media, dispatch, notes, timeline ═══════════ --}}
        <div class="space-y-6">
            {{-- Media checklist --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-1.5">
                    <x-heroicon-o-photo class="w-4 h-4 text-gray-400" />
                    Media Review
                </h2>
                <form method="POST" action="{{ route('admin.field-service.tickets.media.update', $ticket) }}">
                    @csrf
                    @method('PUT')
                    <div class="space-y-2">
                        @foreach ([
                            'photos_received'           => 'Photos received',
                            'video_received'            => 'Video received',
                            'media_reviewed'            => 'Media reviewed',
                            'additional_media_required' => 'More media required',
                            'media_bypassed'            => 'Media intentionally bypassed',
                        ] as $flag => $label)
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="{{ $flag }}" value="1" @checked($ticket->{$flag})
                                    class="text-blue-600 rounded focus:ring-blue-500" @disabled($status->isTerminal())>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @unless ($status->isTerminal())
                        <div class="flex justify-end mt-3">
                            <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 transition">
                                Save Checklist
                            </button>
                        </div>
                    @endunless
                </form>
            </div>

            {{-- Dispatch details --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-1.5">
                    <x-heroicon-o-map class="w-4 h-4 text-gray-400" />
                    Dispatch Details
                </h2>
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><dt class="text-xs text-gray-400">Technician</dt>
                        <dd class="text-gray-700">{{ $ticket->technician ? $ticket->technician->first_name . ' ' . $ticket->technician->last_name : 'Not assigned' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-gray-400">Truck</dt>
                        <dd class="text-gray-700">{{ $ticket->truck?->truck_name ?? 'Not assigned' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-gray-400">Est. Departure</dt>
                        <dd class="text-gray-700">{{ $ticket->estimated_departure_at?->format('M j, g:i A') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-gray-400">Est. Arrival</dt>
                        <dd class="text-gray-700">{{ $ticket->estimated_arrival_at?->format('M j, g:i A') ?? '—' }}</dd></div>
                </dl>
                @if ($ticket->suggested_tools || $ticket->suggested_parts || $ticket->special_instructions)
                    <div class="mt-3 pt-3 border-t border-gray-100 space-y-2 text-xs text-gray-600">
                        @if ($ticket->suggested_tools)<p><span class="font-semibold text-gray-500">Tools:</span> {{ $ticket->suggested_tools }}</p>@endif
                        @if ($ticket->suggested_parts)<p><span class="font-semibold text-gray-500">Parts:</span> {{ $ticket->suggested_parts }}</p>@endif
                        @if ($ticket->special_instructions)<p><span class="font-semibold text-gray-500">Instructions:</span> {{ $ticket->special_instructions }}</p>@endif
                    </div>
                @endif
                @if (!$status->isTerminal() && $status !== FieldMissionStatus::ReadyForDispatch)
                    <details class="mt-3 pt-3 border-t border-gray-100">
                        <summary class="text-xs text-blue-600 cursor-pointer select-none hover:text-blue-700">Edit dispatch details…</summary>
                        <div class="mt-3">
                            @include('admin.field_service.partials._dispatch_form')
                        </div>
                    </details>
                @endif
            </div>

            {{-- Notes --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-1.5">
                    <x-heroicon-o-chat-bubble-left-ellipsis class="w-4 h-4 text-gray-400" />
                    Notes
                </h2>
                <form method="POST" action="{{ route('admin.field-service.tickets.notes.store', $ticket) }}" class="mb-4">
                    @csrf
                    <textarea name="note" rows="2" required class="{{ $inputClass }}" placeholder="Add a mission note…">{{ old('note') }}</textarea>
                    @error('note')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    <div class="flex justify-end mt-2">
                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-600 text-white hover:bg-blue-700 transition">
                            Add Note
                        </button>
                    </div>
                </form>
                <div class="space-y-3">
                    @forelse ($ticket->notes as $note)
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <p class="text-sm text-gray-800 whitespace-pre-line">{{ $note->note }}</p>
                            <p class="text-[11px] text-gray-400 mt-1.5">
                                {{ $note->createdBy ? $note->createdBy->first_name . ' ' . $note->createdBy->last_name : 'System' }}
                                · {{ $note->created_at->format('M j, g:i A') }}
                            </p>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400">No notes yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Timeline --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-1.5">
                    <x-heroicon-o-clock class="w-4 h-4 text-gray-400" />
                    Timeline
                </h2>
                <ol class="space-y-3">
                    @forelse ($ticket->events as $event)
                        @php $isDecision = $event->event_type === \App\Enums\FieldService\FieldTicketEventType::OperationalDecision; @endphp
                        <li class="flex gap-2.5 {{ $isDecision ? 'rounded-lg border border-purple-200 bg-purple-50 p-2 -mx-2' : '' }}">
                            <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $event->event_type->color() }}"></span>
                            <div class="min-w-0">
                                <p class="text-xs font-medium {{ $isDecision ? 'text-purple-800' : 'text-gray-700' }}">
                                    @if ($isDecision)
                                        <span class="font-semibold">Operational Decision: {{ $event->new_value }}</span>
                                    @else
                                        {{ $event->event_type->label() }}
                                        @if ($event->old_value && $event->new_value)
                                            <span class="text-gray-400">— {{ $event->old_value }} → {{ $event->new_value }}</span>
                                        @elseif ($event->new_value)
                                            <span class="text-gray-400">— {{ $event->new_value }}</span>
                                        @endif
                                    @endif
                                </p>
                                @if ($event->notes)
                                    <p class="text-[11px] text-gray-500 truncate">{{ $event->notes }}</p>
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
