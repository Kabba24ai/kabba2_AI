@extends('admin.layouts.app')

@section('title', 'Inspection Detail')

@php
    use App\Enums\ChecklistManagement\RentalReadyLifecycleStatus;
    use App\Enums\ChecklistManagement\RentalReadyResult;

    $lc = $inspection->lifecycle_status instanceof RentalReadyLifecycleStatus ? $inspection->lifecycle_status->value : (string) $inspection->lifecycle_status;
    $rs = $inspection->result instanceof RentalReadyResult ? $inspection->result : null;
    $isDraft = $lc === 'draft';

    $lifecycleTone = [
        'draft' => 'bg-gray-100 text-gray-700 border-gray-200',
        'completed' => 'bg-green-50 text-green-700 border-green-200',
        'voided' => 'bg-red-50 text-red-700 border-red-200',
        'superseded' => 'bg-amber-50 text-amber-800 border-amber-200',
        'abandoned' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
    $resultTone = [
        'rental_ready' => 'bg-green-50 text-green-700 border-green-200',
        'maintenance_hold' => 'bg-amber-50 text-amber-800 border-amber-200',
        'damaged' => 'bg-red-50 text-red-700 border-red-200',
    ];
    $answerTone = [
        'Rental Ready' => 'bg-green-50 text-green-700 border-green-200',
        'Maint. Hold' => 'bg-amber-50 text-amber-800 border-amber-200',
        'Damaged' => 'bg-red-50 text-red-700 border-red-200',
    ];
@endphp

@section('content')
    @include('flash::message')

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Rental Ready Inspection</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $equipment->equipment_name }} <span class="text-gray-400">· #{{ $equipment->equipment_id }}</span>
            </p>
        </div>
        <a href="{{ route('admin.checklist-management.equipment-management.rental-ready-history', $equipment->unique_id) }}"
            class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
            <x-heroicon-o-arrow-left class="w-4 h-4" /> Back to History
        </a>
    </div>

    @if ($isDraft)
        <div class="mb-4 rounded-lg border border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-700 flex items-center gap-2">
            <x-heroicon-o-pencil-square class="w-5 h-5 text-gray-400" />
            <span><span class="font-semibold">Incomplete draft</span> — this inspection was never finalized. It has no result and did not set the equipment's status.</span>
        </div>
    @endif

    {{-- Header: lifecycle + result shown SEPARATELY, plus full attribution. --}}
    <div class="bg-white rounded-md shadow-sm border border-gray-200 mb-6">
        <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center gap-3">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Lifecycle</span>
            <span class="inline-flex items-center px-2.5 py-1 rounded border text-xs font-semibold {{ $lifecycleTone[$lc] ?? 'bg-gray-100 text-gray-700 border-gray-200' }}">{{ ucfirst($lc) }}</span>
            <span class="h-5 w-px bg-gray-200"></span>
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Result</span>
            @if ($rs)
                <span class="inline-flex items-center px-2.5 py-1 rounded border text-xs font-semibold {{ $resultTone[$rs->value] ?? '' }}">{{ $rs->label() }}</span>
            @else
                <span class="text-xs text-gray-400">None (draft)</span>
            @endif
        </div>
        <dl class="px-5 py-4 grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-3 text-sm">
            <div><dt class="text-gray-400 text-xs uppercase tracking-wide">Inspector</dt><dd class="text-gray-900 mt-0.5">{{ $inspection->employee_name ?: optional($inspection->employee)->full_name ?: '—' }}</dd></div>
            <div><dt class="text-gray-400 text-xs uppercase tracking-wide">Equipment Hours</dt><dd class="text-gray-900 mt-0.5">{{ $inspection->equipment_hours !== null ? rtrim(rtrim(number_format((float) $inspection->equipment_hours, 1), '0'), '.') : '—' }}</dd></div>
            <div><dt class="text-gray-400 text-xs uppercase tracking-wide">Order</dt><dd class="text-gray-900 mt-0.5">{{ $orderNumber ? '#' . $orderNumber : '— (no order context)' }}</dd></div>
            <div><dt class="text-gray-400 text-xs uppercase tracking-wide">Inspection Date</dt><dd class="text-gray-900 mt-0.5">{{ \Illuminate\Support\Carbon::parse($inspection->inspection_date)->format('M j, Y') }}{{ $inspection->inspection_time ? ' · ' . \Illuminate\Support\Carbon::parse($inspection->inspection_time)->format('g:i A') : '' }}</dd></div>
            <div><dt class="text-gray-400 text-xs uppercase tracking-wide">Created</dt><dd class="text-gray-900 mt-0.5">{{ optional($inspection->created_at)->format('M j, Y g:i A') ?? '—' }}</dd></div>
            <div><dt class="text-gray-400 text-xs uppercase tracking-wide">Completed</dt><dd class="text-gray-900 mt-0.5">{{ $inspection->completed_at ? $inspection->completed_at->format('M j, Y g:i A') : '—' }}</dd></div>
            <div class="col-span-2 md:col-span-3"><dt class="text-gray-400 text-xs uppercase tracking-wide">Inspection ID</dt><dd class="text-gray-500 mt-0.5 font-mono text-xs">{{ $inspection->unique_id }}</dd></div>
        </dl>
        <div class="px-5 py-3 border-t border-gray-100 flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500">
            <span>Questions answered: <span class="font-semibold text-gray-700">{{ (int) $inspection->required_items_completed }}/{{ (int) $inspection->total_questions }}</span></span>
            <span>Maintenance findings: <span class="font-semibold {{ (int) $inspection->items_requiring_maintenance > 0 ? 'text-amber-700' : 'text-gray-700' }}">{{ (int) $inspection->items_requiring_maintenance }}</span></span>
            <span>Damage findings: <span class="font-semibold {{ (int) $inspection->damaged_items > 0 ? 'text-red-700' : 'text-gray-700' }}">{{ (int) $inspection->damaged_items }}</span></span>
        </div>
    </div>

    @if ($inspection->general_notes)
        <div class="bg-white rounded-md shadow-sm border border-gray-200 mb-6 px-5 py-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">General Notes</div>
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $inspection->general_notes }}</p>
        </div>
    @endif

    {{-- Answers — reconstructed from the inspection's OWN snapshot, never the
         live checklist master, so this stays correct after later edits. --}}
    <div class="mb-2 flex items-center gap-2 text-xs text-gray-400">
        <x-heroicon-o-lock-closed class="w-3.5 h-3.5" />
        Reconstructed from the inspection's stored snapshot ({{ $questionCount }} question{{ $questionCount === 1 ? '' : 's' }}) — reflects what the inspector saw at the time.
    </div>

    @forelse ($sections as $sectionName => $questions)
        <div class="bg-white rounded-md shadow-sm border border-gray-200 mb-4 overflow-hidden">
            <div class="px-5 py-2.5 bg-gray-50 border-b border-gray-100 text-sm font-semibold text-gray-700">{{ $sectionName }}</div>
            <ul class="divide-y divide-gray-100">
                @foreach ($questions as $q)
                    <li class="px-5 py-3">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $q['question_name'] }}
                                    @if ($q['required_question'])
                                        <span class="ml-1 text-[10px] font-semibold uppercase text-gray-400">Required</span>
                                    @endif
                                </div>
                                @if (!empty($q['note']))
                                    <div class="text-xs text-gray-500 mt-1">Note: {{ $q['note'] }}</div>
                                @endif
                            </div>
                            <div class="shrink-0 text-right">
                                @php $sel = $q['selected_answer']; @endphp
                                @if ($sel && isset($sel['answer_name']))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded border text-xs font-semibold {{ $answerTone[$sel['type'] ?? ''] ?? 'bg-gray-100 text-gray-700 border-gray-200' }}">
                                        {{ $sel['answer_name'] }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 italic">Not answered</span>
                                @endif
                            </div>
                        </div>
                        {{-- The full option set as it existed at inspection time. --}}
                        @if (!empty($q['answers']))
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($q['answers'] as $opt)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] border {{ ($opt['is_selected'] ?? false) ? ($answerTone[$opt['type'] ?? ''] ?? 'bg-gray-100 text-gray-700 border-gray-200') : 'bg-white text-gray-400 border-gray-200' }}">
                                        {{ $opt['answer_name'] ?? '—' }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <div class="bg-white rounded-md shadow-sm border border-gray-200 px-5 py-10 text-center text-gray-400 text-sm">
            No answer snapshot was recorded for this inspection.
        </div>
    @endforelse
@endsection
