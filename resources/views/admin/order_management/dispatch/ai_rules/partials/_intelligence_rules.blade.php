@php
    $totalRules    = $rules->count();
    $pendingRules  = $rules->where('approved_by_admin', false)->count();
    $approvedRules = $rules->where('approved_by_admin', true)->count();

    // Encode for JS edit modal
    $ruleData = $rules->keyBy('id')->map(fn($r) => [
        'id'               => $r->id,
        'rule_type'        => $r->rule_type->value,
        'rule_name'        => $r->rule_name,
        'condition'        => $r->condition,
        'recommendation'   => $r->recommendation,
        'reason'           => $r->reason,
        'priority'         => $r->priority,
        'confidence_score' => $r->confidence_score,
        'source_type'      => $r->source_type->value,
        'tags'             => implode(', ', $r->tags ?? []),
    ])->toArray();
@endphp

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-semibold text-gray-900">Dispatch Intelligence Rules</h2>
        <p class="text-sm text-gray-500 mt-0.5">Business and operational rules that make the AI smarter over time. Approved rules are sent to the AI on every draft.</p>
    </div>
    <div class="flex gap-2">
        <button type="button" id="ai-generate-btn"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold bg-purple-600 text-white hover:bg-purple-700 transition shadow-sm">
            <x-heroicon-o-sparkles class="w-4 h-4" />
            Research & Generate via AI
        </button>
        <button type="button" id="add-rule-btn"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold bg-brand text-white hover:bg-brand/90 transition shadow-sm">
            <x-heroicon-o-plus class="w-4 h-4" />
            Add Rule
        </button>
    </div>
</div>

{{-- AI Generation Status Bar --}}
<div id="ai-status-bar" class="hidden mb-4 flex items-center gap-3 bg-purple-50 border border-purple-200 rounded-lg px-4 py-3">
    <svg class="animate-spin w-4 h-4 text-purple-600 shrink-0" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
    </svg>
    <span id="ai-status-text" class="text-sm text-purple-800 font-medium">Analyzing fleet configuration and generating rules…</span>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.order-management.dispatch.ai-rules.index', ['tab' => 'intelligence_rules']) }}" class="flex flex-wrap gap-3 mb-5">
    <select name="rule_type" onchange="this.form.submit()"
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white text-gray-700 min-w-[180px]">
        <option value="">All Rule Types</option>
        @foreach($ruleTypes as $type)
            <option value="{{ $type->value }}" @selected($filterType === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </select>
    <select name="status" onchange="this.form.submit()"
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white text-gray-700 min-w-[160px]">
        <option value="">All Statuses</option>
        <option value="approved" @selected($filterStatus === 'approved')>Approved</option>
        <option value="pending"  @selected($filterStatus === 'pending')>Pending Review</option>
    </select>
    @if($filterType || $filterStatus)
        <a href="{{ route('admin.order-management.dispatch.ai-rules.index', ['tab' => 'intelligence_rules']) }}"
            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm text-gray-600 hover:text-gray-900 border border-gray-200 rounded-lg bg-white">
            <x-heroicon-o-x-mark class="w-3.5 h-3.5" /> Clear
        </a>
    @endif
</form>

{{-- Stats Bar --}}
<div class="flex gap-4 mb-5">
    <div class="bg-white border border-gray-200 rounded-lg px-4 py-2.5 flex items-center gap-2 text-sm">
        <span class="font-semibold text-gray-900">{{ $totalRules }}</span>
        <span class="text-gray-500">Total Rules</span>
    </div>
    <div class="bg-white border border-amber-200 rounded-lg px-4 py-2.5 flex items-center gap-2 text-sm">
        <span class="font-semibold text-amber-700">{{ $pendingRules }}</span>
        <span class="text-amber-600">Pending Review</span>
    </div>
    <div class="bg-white border border-green-200 rounded-lg px-4 py-2.5 flex items-center gap-2 text-sm">
        <span class="font-semibold text-green-700">{{ $approvedRules }}</span>
        <span class="text-green-600">Active in AI</span>
    </div>
</div>

{{-- Rules List --}}
@if($rules->isEmpty())
    <div class="text-center py-16 bg-white border border-dashed border-gray-300 rounded-xl">
        <x-heroicon-o-light-bulb class="w-10 h-10 text-gray-300 mx-auto mb-3" />
        <p class="text-gray-500 font-medium">No intelligence rules yet</p>
        <p class="text-sm text-gray-400 mt-1">Click "Research & Generate via AI" to have the AI analyze your fleet and generate rules, or add rules manually.</p>
    </div>
@else
    <div class="space-y-3">
        @foreach($rules as $rule)
            @php
                $isPending = $rule->isPending();
                $cardBorder = $isPending ? 'border-amber-300 bg-amber-50/30' : 'border-gray-200 bg-white';
            @endphp
            <div class="border rounded-xl p-4 {{ $cardBorder }}">
                <div class="flex items-start justify-between gap-4">
                    {{-- Left: badges + name --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            {{-- Rule type --}}
                            <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full {{ $rule->rule_type->badgeColor() }}">
                                {{ $rule->rule_type->label() }}
                            </span>
                            {{-- Source --}}
                            <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full {{ $rule->source_type->badgeColor() }}">
                                @if($rule->source_type->value === 'ai_generated')
                                    <x-heroicon-o-sparkles class="w-3 h-3" />
                                @endif
                                {{ $rule->source_type->label() }}
                            </span>
                            {{-- Approval --}}
                            @if($isPending)
                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">
                                    <x-heroicon-o-clock class="w-3 h-3" /> Pending Review
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-800">
                                    <x-heroicon-o-check-circle class="w-3 h-3" /> Active in AI
                                </span>
                            @endif
                            {{-- Priority --}}
                            <span class="text-xs text-gray-500">Priority: <strong>{{ $rule->priority }}</strong></span>
                            {{-- Confidence --}}
                            <span class="text-xs text-gray-500">Confidence: <strong>{{ $rule->confidencePercent() }}%</strong></span>
                        </div>

                        <h4 class="font-semibold text-gray-900 text-sm mb-2">{{ $rule->rule_name }}</h4>

                        <div class="space-y-1.5 text-sm">
                            <div>
                                <span class="font-medium text-gray-600 uppercase text-xs tracking-wide">When:</span>
                                <p class="text-gray-700 mt-0.5">{{ $rule->condition }}</p>
                            </div>
                            <div>
                                <span class="font-medium text-gray-600 uppercase text-xs tracking-wide">Do:</span>
                                <p class="text-gray-700 mt-0.5">{{ $rule->recommendation }}</p>
                            </div>
                            <div>
                                <span class="font-medium text-gray-600 uppercase text-xs tracking-wide">Why:</span>
                                <p class="text-gray-500 mt-0.5">{{ $rule->reason }}</p>
                            </div>
                        </div>

                        {{-- Tags --}}
                        @if(!empty($rule->tags))
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach($rule->tags as $tag)
                                    <span class="text-xs bg-gray-100 text-gray-600 rounded px-1.5 py-0.5">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif

                        {{-- Meta --}}
                        <div class="mt-2 text-xs text-gray-400 flex flex-wrap gap-3">
                            <span>Added {{ $rule->created_at->diffForHumans() }} by {{ $rule->creator?->full_name ?? 'System' }}</span>
                            @if($rule->approved_at)
                                <span>Approved {{ $rule->approved_at->diffForHumans() }} by {{ $rule->approver?->full_name }}</span>
                            @endif
                            @if($rule->last_reviewed_at)
                                <span>Last reviewed {{ $rule->last_reviewed_at->diffForHumans() }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Right: actions --}}
                    <div class="flex items-center gap-2 shrink-0">
                        @if($isPending)
                            <button type="button"
                                data-approve-url="{{ route('admin.order-management.dispatch.ai-rules.intelligence-rules.approve', $rule) }}"
                                class="approve-btn inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-green-600 text-white hover:bg-green-700 transition">
                                <x-heroicon-o-check class="w-3.5 h-3.5" /> Approve
                            </button>
                        @endif
                        <button type="button"
                            data-rule-id="{{ $rule->id }}"
                            class="edit-rule-btn inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                            <x-heroicon-o-pencil class="w-3.5 h-3.5" /> Edit
                        </button>
                        <form method="POST"
                            action="{{ route('admin.order-management.dispatch.ai-rules.intelligence-rules.destroy', $rule) }}"
                            onsubmit="return confirm('Delete this rule?')" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition">
                                <x-heroicon-o-trash class="w-3.5 h-3.5" />
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- ── Add Rule Modal ─────────────────────────────────────────────── --}}
<div id="add-rule-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/40 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl" id="add-rule-modal-box">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">Add Intelligence Rule</h3>
            <button type="button" class="close-modal text-gray-400 hover:text-gray-600"><x-heroicon-o-x-mark class="w-5 h-5" /></button>
        </div>
        <form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.intelligence-rules.store') }}" class="p-6 space-y-4">
            @csrf
            @include('admin.order_management.dispatch.ai_rules.partials._intelligence_rule_form')
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="close-modal px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-brand rounded-lg hover:bg-brand/90">Save Rule</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Edit Rule Modal ─────────────────────────────────────────────── --}}
<div id="edit-rule-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/40 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl" id="edit-rule-modal-box">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">Edit Intelligence Rule</h3>
            <button type="button" class="close-modal text-gray-400 hover:text-gray-600"><x-heroicon-o-x-mark class="w-5 h-5" /></button>
        </div>
        <form id="edit-rule-form" method="POST" action="" class="p-6 space-y-4">
            @csrf @method('PUT')
            @include('admin.order_management.dispatch.ai_rules.partials._intelligence_rule_form', ['editing' => true])
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="close-modal px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-brand rounded-lg hover:bg-brand/90">Update Rule</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const ruleData = @json($ruleData);

// ── Modal helpers ─────────────────────────────────────────────────
function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeAllModals() {
    ['add-rule-modal','edit-rule-modal'].forEach(id => document.getElementById(id).classList.add('hidden'));
}

document.getElementById('add-rule-btn').addEventListener('click', () => openModal('add-rule-modal'));

document.querySelectorAll('.close-modal').forEach(btn => btn.addEventListener('click', closeAllModals));
['add-rule-modal','edit-rule-modal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) closeAllModals();
    });
});

// ── Edit Rule ─────────────────────────────────────────────────────
document.querySelectorAll('.edit-rule-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id   = this.dataset.ruleId;
        const rule = ruleData[id];
        if (!rule) return;

        const form = document.getElementById('edit-rule-form');
        form.action = `{{ url('admin/order-management/dispatch/ai-rules/intelligence-rules') }}/${id}`;

        form.querySelector('[name="rule_type"]').value        = rule.rule_type;
        form.querySelector('[name="rule_name"]').value        = rule.rule_name;
        form.querySelector('[name="condition"]').value        = rule.condition;
        form.querySelector('[name="recommendation"]').value   = rule.recommendation;
        form.querySelector('[name="reason"]').value           = rule.reason;
        form.querySelector('[name="priority"]').value         = rule.priority;
        form.querySelector('[name="confidence_score"]').value = rule.confidence_score;
        form.querySelector('[name="source_type"]').value      = rule.source_type;
        form.querySelector('#edit-tags-display').value        = rule.tags;
        buildHiddenTags('edit-tags-display', 'edit-tags-container');

        openModal('edit-rule-modal');
    });
});

// ── Approve (AJAX) ────────────────────────────────────────────────
document.querySelectorAll('.approve-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const url = this.dataset.approveUrl;
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) window.location.reload();
        });
    });
});

// ── AI Generate ───────────────────────────────────────────────────
document.getElementById('ai-generate-btn').addEventListener('click', function() {
    const statusBar  = document.getElementById('ai-status-bar');
    const statusText = document.getElementById('ai-status-text');
    statusBar.classList.remove('hidden');
    statusText.textContent = 'Analyzing your fleet configuration and generating dispatch intelligence rules…';
    this.disabled = true;

    fetch('{{ route('admin.order-management.dispatch.ai-rules.intelligence-rules.ai-generate') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            statusText.textContent = `✓ ${data.message}`;
            setTimeout(() => window.location.reload(), 1500);
        } else {
            statusText.textContent = `Error: ${data.message}`;
            document.getElementById('ai-generate-btn').disabled = false;
        }
    })
    .catch(() => {
        statusText.textContent = 'Request failed. Please try again.';
        document.getElementById('ai-generate-btn').disabled = false;
    });
});

// ── Tag helpers ───────────────────────────────────────────────────
function buildHiddenTags(displayId, containerId) {
    const display   = document.getElementById(displayId);
    const container = document.getElementById(containerId);
    if (!display || !container) return;
    container.innerHTML = '';
    display.value.split(',').map(t => t.trim()).filter(Boolean).forEach(tag => {
        const input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = 'tags[]';
        input.value = tag;
        container.appendChild(input);
    });
}
document.getElementById('add-tags-display')?.addEventListener('input', () => buildHiddenTags('add-tags-display', 'add-tags-container'));
document.getElementById('edit-tags-display')?.addEventListener('input', () => buildHiddenTags('edit-tags-display', 'edit-tags-container'));
</script>
@endpush
