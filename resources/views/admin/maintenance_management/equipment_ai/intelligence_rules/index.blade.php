@extends('admin.layouts.app')

@section('title', 'Equipment Intelligence Rules — ' . $category->title)

@section('content')
<div class="space-y-6">

    {{-- ── Page Header ──────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                <a href="{{ route('admin.maintenance-management.equipment-ai.index', ['category_id' => $category->id]) }}"
                   class="hover:text-brand-600 dark:hover:text-brand-400">Equipment Management AI</a>
                <span>/</span>
                <span class="text-gray-700 dark:text-gray-300">{{ $category->title }}</span>
                <span>/</span>
                <span>Intelligence Rules</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Equipment Intelligence Rules</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Business judgment and rental experience rules — separate from hard specifications.
                AI-generated rules start as <span class="font-medium text-amber-600">Pending</span> and must be approved before use.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            {{-- AI Generate button --}}
            <button type="button" id="ai-generate-btn"
                data-category-id="{{ $category->id }}"
                class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-1 transition">
                <x-heroicon-o-sparkles class="h-4 w-4" />
                Research & Create Rules via AI
            </button>
            {{-- Add Rule button --}}
            <button type="button" id="add-rule-btn"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1 transition">
                <x-heroicon-o-plus class="h-4 w-4" />
                Add Rule
            </button>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700/50 dark:bg-green-900/20 dark:text-green-400">
            <x-heroicon-o-check-circle class="h-5 w-5 shrink-0" />
            {{ session('success') }}
        </div>
    @endif

    {{-- ── AI Generate Status Bar (hidden until request) ──────────────── --}}
    <div id="ai-status-bar" class="hidden rounded-lg border border-purple-200 bg-purple-50 px-4 py-3 text-sm text-purple-800 dark:border-purple-700/50 dark:bg-purple-900/20 dark:text-purple-300">
        <div class="flex items-center gap-2">
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <span id="ai-status-text">Generating intelligence rules…</span>
        </div>
    </div>

    {{-- ── Filters ──────────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('admin.maintenance-management.equipment-ai.intelligence-rules.index') }}"
          class="rounded-xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900 px-5 py-4">
        <input type="hidden" name="category_id" value="{{ $category->id }}">
        <div class="flex flex-wrap items-end gap-3">

            {{-- Rule Type --}}
            <div class="flex flex-col gap-1 min-w-[180px]">
                <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Rule Type</label>
                <select name="rule_type" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">All Types</option>
                    @foreach($ruleTypes as $type)
                        <option value="{{ $type->value }}" @selected($ruleType === $type->value)>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Profile --}}
            <div class="flex flex-col gap-1 min-w-[200px]">
                <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Make / Model</label>
                <select name="profile_id" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">Category-wide + All Models</option>
                    <option value="category" @selected($profileId === 'category')>Category-wide only</option>
                    @foreach($profiles as $p)
                        <option value="{{ $p->id }}" @selected($profileId == $p->id)>
                            {{ $p->make }} {{ $p->model }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div class="flex flex-col gap-1 min-w-[150px]">
                <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Status</label>
                <select name="status" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="" @selected($status === '')>All</option>
                    <option value="approved" @selected($status === 'approved')>Approved</option>
                    <option value="pending"  @selected($status === 'pending')>Pending Review</option>
                </select>
            </div>

            <a href="{{ route('admin.maintenance-management.equipment-ai.intelligence-rules.index', ['category_id' => $category->id]) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-800 transition">
                <x-heroicon-o-x-mark class="h-4 w-4" /> Clear
            </a>
        </div>
    </form>

    {{-- ── Rules Stats Bar ──────────────────────────────────────────── --}}
    @php
        $totalCount    = $rules->count();
        $pendingCount  = $rules->where('approved_by_admin', false)->count();
        $approvedCount = $rules->where('approved_by_admin', true)->count();
        $aiCount       = $rules->where('source_type.value', 'ai_generated')->count();
    @endphp
    <div class="flex flex-wrap gap-3 text-sm">
        <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
            <span class="font-semibold">{{ $totalCount }}</span> total rules
        </span>
        @if($pendingCount > 0)
        <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
            <x-heroicon-o-clock class="h-3.5 w-3.5" />
            <span class="font-semibold">{{ $pendingCount }}</span> pending approval
        </span>
        @endif
        <span class="inline-flex items-center gap-1.5 rounded-full border border-green-200 bg-green-50 px-3 py-1 text-green-800 dark:border-green-700 dark:bg-green-900/20 dark:text-green-400">
            <x-heroicon-o-check-circle class="h-3.5 w-3.5" />
            <span class="font-semibold">{{ $approvedCount }}</span> approved
        </span>
    </div>

    {{-- ── Rules List ───────────────────────────────────────────────── --}}
    @if($rules->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700 py-16 text-center">
            <x-heroicon-o-light-bulb class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-3" />
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No intelligence rules yet for this category.</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Click "Research & Create Rules via AI" to generate a first set, or add one manually.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($rules as $rule)
                @include('admin.maintenance_management.equipment_ai.intelligence_rules._rule_card', ['rule' => $rule])
            @endforeach
        </div>
    @endif

</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- Add Rule Modal                                                      --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div id="add-rule-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl dark:bg-gray-900 overflow-y-auto max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Add Intelligence Rule</h3>
            <button type="button" class="close-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <x-heroicon-o-x-mark class="h-5 w-5" />
            </button>
        </div>

        <form method="POST" action="{{ route('admin.maintenance-management.equipment-ai.intelligence-rules.store') }}" class="px-6 py-5 space-y-4">
            @csrf
            <input type="hidden" name="equipment_category_id" value="{{ $category->id }}">
            <input type="hidden" name="source_type" value="admin">

            <div class="grid grid-cols-2 gap-4">
                {{-- Rule Type --}}
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Rule Type <span class="text-red-500">*</span></label>
                    <select name="rule_type" required
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                        <option value="">— Select —</option>
                        @foreach($ruleTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Applies To --}}
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Applies To</label>
                    <select name="equipment_profile_id"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                        <option value="">Entire Category</option>
                        @foreach($profiles as $p)
                            <option value="{{ $p->id }}">{{ $p->make }} {{ $p->model }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Rule Name --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Rule Name <span class="text-red-500">*</span></label>
                <input type="text" name="rule_name" required maxlength="255"
                    placeholder="Short descriptive name…"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
            </div>

            {{-- Condition --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Condition <span class="text-red-500">*</span>
                    <span class="text-gray-400 font-normal">(when does this rule apply?)</span>
                </label>
                <textarea name="condition" rows="2" required
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                    placeholder="Customer needs elevated access around a home, trees, gutters…"></textarea>
            </div>

            {{-- Recommendation --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Recommendation <span class="text-red-500">*</span>
                    <span class="text-gray-400 font-normal">(what should happen?)</span>
                </label>
                <textarea name="recommendation" rows="2" required
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                    placeholder="Recommend towable boom lifts when setup space is available…"></textarea>
            </div>

            {{-- Reason --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="2" required
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                    placeholder="Towable boom lifts are lighter and less damaging to residential yards…"></textarea>
            </div>

            {{-- Priority + Confidence --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Priority (1–100)</label>
                    <input type="number" name="priority" value="50" min="1" max="100"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Confidence (0–1)</label>
                    <input type="number" name="confidence_score" value="0.70" min="0" max="1" step="0.05"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                </div>
            </div>

            {{-- Tags --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Tags
                    <span class="text-gray-400 font-normal">(comma-separated)</span>
                </label>
                <input type="text" id="tags-input-add" name="_tags_raw"
                    placeholder="residential, soft_ground, tight_access"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                <div id="tags-hidden-add"></div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" class="close-modal px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400">Cancel</button>
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-600 transition">
                    Save Rule
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- Edit Rule Modal (populated via JS)                                  --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div id="edit-rule-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl dark:bg-gray-900 overflow-y-auto max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Edit Intelligence Rule</h3>
            <button type="button" class="close-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <x-heroicon-o-x-mark class="h-5 w-5" />
            </button>
        </div>
        <form id="edit-rule-form" method="POST" class="px-6 py-5 space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Rule Type <span class="text-red-500">*</span></label>
                    <select name="rule_type" required
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                        @foreach($ruleTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Applies To</label>
                    <select name="equipment_profile_id"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                        <option value="">Entire Category</option>
                        @foreach($profiles as $p)
                            <option value="{{ $p->id }}">{{ $p->make }} {{ $p->model }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Rule Name <span class="text-red-500">*</span></label>
                <input type="text" name="rule_name" required maxlength="255"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Condition <span class="text-red-500">*</span></label>
                <textarea name="condition" rows="2" required
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"></textarea>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Recommendation <span class="text-red-500">*</span></label>
                <textarea name="recommendation" rows="2" required
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"></textarea>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="2" required
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Priority</label>
                    <input type="number" name="priority" min="1" max="100"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Confidence</label>
                    <input type="number" name="confidence_score" min="0" max="1" step="0.05"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Tags <span class="text-gray-400 font-normal">(comma-separated)</span></label>
                <input type="text" id="tags-input-edit" name="_tags_raw"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                <div id="tags-hidden-edit"></div>
            </div>
            <input type="hidden" name="source_type" value="admin">
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" class="close-modal px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400">Cancel</button>
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-600 transition">
                    Update Rule
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@php
    $ruleData = $rules->keyBy('id')->map(function ($r) {
        return [
            'id'                   => $r->id,
            'rule_type'            => $r->rule_type?->value,
            'equipment_profile_id' => $r->equipment_profile_id,
            'rule_name'            => $r->rule_name,
            'condition'            => $r->condition,
            'recommendation'       => $r->recommendation,
            'reason'               => $r->reason,
            'priority'             => $r->priority,
            'confidence_score'     => $r->confidence_score,
            'tags'                 => $r->tags ?? [],
            'source_type'          => $r->source_type?->value,
        ];
    });
@endphp

@push('js')
<script>
// ── Rule data for edit modal ──────────────────────────────────────────
const ruleData = @json($ruleData);

// ── Modal helpers ─────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.replace('hidden', 'flex'); }
function closeModal(id) { document.getElementById(id).classList.replace('flex', 'hidden'); }

document.getElementById('add-rule-btn').addEventListener('click', () => openModal('add-rule-modal'));

document.querySelectorAll('.close-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        ['add-rule-modal', 'edit-rule-modal'].forEach(closeModal);
    });
});

// Close on backdrop click
['add-rule-modal', 'edit-rule-modal'].forEach(id => {
    document.getElementById(id).addEventListener('click', e => {
        if (e.target === e.currentTarget) closeModal(id);
    });
});

// ── Edit rule ─────────────────────────────────────────────────────────
document.querySelectorAll('.edit-rule-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const id   = btn.dataset.ruleId;
        const rule = ruleData[id];
        if (!rule) return;

        const form = document.getElementById('edit-rule-form');
        form.action = `{{ route('admin.maintenance-management.equipment-ai.intelligence-rules.update', '__ID__') }}`.replace('__ID__', id);

        form.querySelector('[name="rule_type"]').value             = rule.rule_type;
        form.querySelector('[name="equipment_profile_id"]').value  = rule.equipment_profile_id ?? '';
        form.querySelector('[name="rule_name"]').value             = rule.rule_name;
        form.querySelector('[name="condition"]').value             = rule.condition;
        form.querySelector('[name="recommendation"]').value        = rule.recommendation;
        form.querySelector('[name="reason"]').value                = rule.reason;
        form.querySelector('[name="priority"]').value              = rule.priority;
        form.querySelector('[name="confidence_score"]').value      = rule.confidence_score;
        form.querySelector('[name="source_type"]').value           = rule.source_type;
        document.getElementById('tags-input-edit').value           = (rule.tags || []).join(', ');
        buildHiddenTags('edit');

        openModal('edit-rule-modal');
    });
});

// ── Tag helpers (comma input → hidden array inputs) ───────────────────
function buildHiddenTags(suffix) {
    const raw      = document.getElementById(`tags-input-${suffix}`).value;
    const container = document.getElementById(`tags-hidden-${suffix}`);
    container.innerHTML = '';
    raw.split(',').map(t => t.trim()).filter(Boolean).forEach(tag => {
        const inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = 'tags[]';
        inp.value = tag;
        container.appendChild(inp);
    });
}

['add', 'edit'].forEach(s => {
    const input = document.getElementById(`tags-input-${s}`);
    if (input) input.addEventListener('input', () => buildHiddenTags(s));
});
// Build on page load for edit (in case form is pre-filled)
buildHiddenTags('add');
buildHiddenTags('edit');

// ── Approve rule (AJAX) ──────────────────────────────────────────────
document.querySelectorAll('.approve-rule-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id  = btn.dataset.ruleId;
        const url = `{{ route('admin.maintenance-management.equipment-ai.intelligence-rules.approve', '__ID__') }}`.replace('__ID__', id);

        btn.disabled = true;
        btn.textContent = 'Approving…';

        const res  = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success) {
            location.reload();
        } else {
            btn.disabled = false;
            btn.textContent = 'Approve';
            alert('Approval failed.');
        }
    });
});

// ── Delete rule ───────────────────────────────────────────────────────
document.querySelectorAll('.delete-rule-form').forEach(form => {
    form.addEventListener('submit', e => {
        if (!confirm('Delete this rule? It can be recovered from trash if needed.')) {
            e.preventDefault();
        }
    });
});

// ── AI Generate ───────────────────────────────────────────────────────
document.getElementById('ai-generate-btn').addEventListener('click', async () => {
    const btn     = document.getElementById('ai-generate-btn');
    const bar     = document.getElementById('ai-status-bar');
    const barText = document.getElementById('ai-status-text');

    const profileSel = document.querySelector('[name="profile_id"]');
    const profileId  = profileSel ? profileSel.value : '';
    const isProfile  = profileId && profileId !== 'category';

    btn.disabled = true;
    bar.classList.remove('hidden');
    barText.textContent = isProfile
        ? 'Generating model-specific intelligence rules via AI…'
        : 'Generating category intelligence rules via AI — this may take 15–30 seconds…';

    const body = new FormData();
    body.append('_token', '{{ csrf_token() }}');
    body.append('category_id', '{{ $category->id }}');
    if (isProfile) body.append('profile_id', profileId);

    try {
        const res  = await fetch('{{ route('admin.maintenance-management.equipment-ai.intelligence-rules.ai-generate') }}', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body,
        });
        const data = await res.json();

        if (data.success) {
            barText.textContent = data.message + ' Reloading…';
            setTimeout(() => location.reload(), 1500);
        } else {
            barText.textContent = 'Error: ' + (data.message || 'AI generation failed.');
            btn.disabled = false;
        }
    } catch (err) {
        barText.textContent = 'Network error. Please try again.';
        btn.disabled = false;
    }
});
</script>
@endpush
