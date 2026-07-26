@extends('admin.layouts.app')

@section('title', 'AI Rules')

@section('content')
    @include('flash::message')

    @php
        use App\Enums\AiRules\AiRuleAuthority;
        use App\Enums\AiRules\AiRuleImplementationStatus;

        $total    = $rules->count();
        $approved = $rules->where('approved_by_admin', true)->count();
        $pending  = $total - $approved;

        // Encoded for the edit modal (textarea lists joined by newlines).
        $ruleData = $rules->keyBy('id')->map(fn ($r) => [
            'id'                        => $r->id,
            'rule_name'                 => $r->rule_name,
            'business_area'             => $r->business_area,
            'purpose'                   => $r->purpose,
            'trigger_description'       => $r->trigger_description,
            'evaluable_inputs'          => implode("\n", $r->evaluable_inputs ?? []),
            'deterministic_action'      => $r->deterministic_action,
            'ai_assessment_instruction' => $r->ai_assessment_instruction,
            'ai_may_recommend'          => implode("\n", $r->ai_may_recommend ?? []),
            'ai_may_execute'            => implode("\n", $r->ai_may_execute ?? []),
            'ai_must_not'               => implode("\n", $r->ai_must_not ?? []),
            'required_human_reviewer'   => $r->required_human_reviewer,
            'escalation_destination'    => $r->escalation_destination,
            'authority'                 => $r->authority?->value,
            'implementation_status'     => $r->implementation_status?->value,
            'is_active'                 => (bool) $r->is_active,
            'effective_date'            => optional($r->effective_date)->format('Y-m-d'),
            'update_url'                => route('admin.crm.ai-rules.update', $r->id),
        ])->toArray();
    @endphp

    <div x-data="aiRules()" x-init="init(@js($ruleData))">
        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div class="flex items-center gap-2">
                <x-heroicon-o-cpu-chip class="w-8 h-8 text-sky-600" />
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">AI Rules</h2>
                    <p class="text-sm text-gray-600">The canonical governed rulebook employees and future AI integrations refer to. Approved rules are the single source of truth; an AI rule never gains execution authority merely by existing here.</p>
                </div>
            </div>
            <button type="button" @click="openCreate()"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold bg-sky-600 text-white hover:bg-sky-700 transition shadow-sm">
                <x-heroicon-o-plus class="w-4 h-4" /> Add Rule
            </button>
        </div>

        {{-- Repository philosophy --}}
        <div class="rounded-xl border border-sky-100 bg-sky-50/60 p-4 mb-6">
            <div class="flex items-start gap-2">
                <x-heroicon-o-shield-check class="w-5 h-5 text-sky-600 mt-0.5 shrink-0" />
                <div class="text-sm text-gray-700 space-y-1">
                    <p class="font-semibold text-gray-900">Repository philosophy</p>
                    <p>This is the canonical location for approved business rules that may eventually be consumed by AI agents and automation. Rules are stored <span class="font-medium">independently of prompts</span> so employees, automation, and AI all operate from the same approved policy.</p>
                    <p><span class="font-medium">A rule existing here does not grant AI authority to execute it.</span> Every rule explicitly defines its deterministic system behavior, human responsibility, AI assessment authority, and AI execution authority. <span class="font-medium">Execution authority must always be granted explicitly.</span></p>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-3 mb-6 max-w-md">
            <div class="rounded-lg border border-gray-200 bg-white p-3 text-center">
                <div class="text-2xl font-semibold text-gray-900">{{ $total }}</div>
                <div class="text-xs text-gray-500">Total</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-3 text-center">
                <div class="text-2xl font-semibold text-green-700">{{ $approved }}</div>
                <div class="text-xs text-gray-500">Approved</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-3 text-center">
                <div class="text-2xl font-semibold text-amber-700">{{ $pending }}</div>
                <div class="text-xs text-gray-500">Pending</div>
            </div>
        </div>

        {{-- Cards --}}
        @forelse ($rules as $rule)
            <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $rule->rule_name }}</h3>
                            <span class="text-xs font-mono text-gray-400">{{ $rule->rule_key }}</span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap mt-2">
                            @if ($rule->business_area)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $rule->business_area }}</span>
                            @endif
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $rule->authority?->badgeColor() }}">{{ $rule->authority?->label() }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $rule->implementation_status?->badgeColor() }}">{{ $rule->implementation_status?->label() }}</span>
                            @if ($rule->approved_by_admin)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Approved · v{{ $rule->version }}</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Pending Approval · v{{ $rule->version }}</span>
                            @endif
                            @unless ($rule->is_active)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600">Inactive</span>
                            @endunless
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @unless ($rule->approved_by_admin)
                            <form method="POST" action="{{ route('admin.crm.ai-rules.approve', $rule->id) }}">
                                @csrf
                                <button class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-600 text-white hover:bg-green-700">Approve</button>
                            </form>
                        @endunless
                        <button type="button" @click="openEdit({{ $rule->id }})"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200">Edit</button>
                        <form method="POST" action="{{ route('admin.crm.ai-rules.destroy', $rule->id) }}"
                            onsubmit="return confirm('Remove this AI rule?');">
                            @csrf @method('DELETE')
                            <button class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100">Delete</button>
                        </form>
                    </div>
                </div>

                @if ($rule->purpose)
                    <p class="text-sm text-gray-700 mt-3">{{ $rule->purpose }}</p>
                @endif

                <div class="grid md:grid-cols-2 gap-4 mt-4 text-sm">
                    @if ($rule->trigger_description)
                        <div><span class="font-semibold text-gray-800">Trigger:</span> <span class="text-gray-600">{{ $rule->trigger_description }}</span></div>
                    @endif
                    @if ($rule->deterministic_action)
                        <div><span class="font-semibold text-gray-800">System does:</span> <span class="text-gray-600">{{ $rule->deterministic_action }}</span></div>
                    @endif
                    @if ($rule->ai_assessment_instruction)
                        <div class="md:col-span-2"><span class="font-semibold text-gray-800">AI assessment:</span> <span class="text-gray-600">{{ $rule->ai_assessment_instruction }}</span></div>
                    @endif
                </div>

                <div class="grid md:grid-cols-3 gap-4 mt-4">
                    @if (!empty($rule->ai_may_recommend))
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-sky-700 mb-1">AI may recommend</div>
                            <ul class="list-disc list-inside text-sm text-gray-600 space-y-0.5">
                                @foreach ($rule->ai_may_recommend as $item)<li>{{ $item }}</li>@endforeach
                            </ul>
                        </div>
                    @endif
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-green-700 mb-1">AI may execute</div>
                        @if (!empty($rule->ai_may_execute))
                            <ul class="list-disc list-inside text-sm text-gray-600 space-y-0.5">
                                @foreach ($rule->ai_may_execute as $item)<li>{{ $item }}</li>@endforeach
                            </ul>
                        @else
                            <p class="text-sm text-gray-400 italic">None — assessment/recommendation only.</p>
                        @endif
                    </div>
                    @if (!empty($rule->ai_must_not))
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-red-700 mb-1">AI must not</div>
                            <ul class="list-disc list-inside text-sm text-gray-600 space-y-0.5">
                                @foreach ($rule->ai_must_not as $item)<li>{{ $item }}</li>@endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap gap-4 mt-4 pt-3 border-t border-gray-100 text-xs text-gray-500">
                    @if ($rule->required_human_reviewer)<span>Reviewer: <span class="font-medium text-gray-700">{{ $rule->required_human_reviewer }}</span></span>@endif
                    @if ($rule->escalation_destination)<span>Escalation: <span class="font-medium text-gray-700">{{ $rule->escalation_destination }}</span></span>@endif
                    @if ($rule->approver)<span>Approved by {{ $rule->approver->first_name ?? '' }} {{ $rule->approver->last_name ?? '' }}</span>@endif
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">
                No AI rules yet. Add the first governed rule to start the repository.
            </div>
        @endforelse

        {{-- Create / Edit modal --}}
        <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background: rgba(0,0,0,0.4)" @keydown.escape.window="showModal = false">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" @click.outside="showModal = false">
                <form :action="form.action" method="POST">
                    @csrf
                    <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 sticky top-0 bg-white">
                        <h3 class="text-lg font-semibold text-gray-900" x-text="mode === 'edit' ? 'Edit AI Rule' : 'Add AI Rule'"></h3>
                        <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                            <x-heroicon-o-x-mark class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="px-6 py-4 space-y-4">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div x-show="mode === 'create'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Rule Key <span class="text-red-500">*</span></label>
                                <input type="text" name="rule_key" x-model="form.rule_key" placeholder="lowercase_with_underscores"
                                    class="w-full rounded-lg border-gray-300 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Rule Name <span class="text-red-500">*</span></label>
                                <input type="text" name="rule_name" x-model="form.rule_name" class="w-full rounded-lg border-gray-300 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Business Area</label>
                                <input type="text" name="business_area" x-model="form.business_area" class="w-full rounded-lg border-gray-300 text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Purpose</label>
                            <textarea name="purpose" x-model="form.purpose" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Trigger</label>
                            <textarea name="trigger_description" x-model="form.trigger_description" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Deterministic System Action</label>
                            <textarea name="deterministic_action" x-model="form.deterministic_action" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">AI Assessment Instruction</label>
                            <textarea name="ai_assessment_instruction" x-model="form.ai_assessment_instruction" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Evaluable Inputs <span class="text-xs text-gray-400">(one per line)</span></label>
                                <textarea name="evaluable_inputs" x-model="form.evaluable_inputs" rows="4" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">AI May Recommend <span class="text-xs text-gray-400">(one per line)</span></label>
                                <textarea name="ai_may_recommend" x-model="form.ai_may_recommend" rows="4" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">AI May Execute <span class="text-xs text-gray-400">(explicit grant — usually empty)</span></label>
                                <textarea name="ai_may_execute" x-model="form.ai_may_execute" rows="4" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">AI Must Not <span class="text-xs text-gray-400">(one per line)</span></label>
                                <textarea name="ai_must_not" x-model="form.ai_must_not" rows="4" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Required Human Reviewer</label>
                                <input type="text" name="required_human_reviewer" x-model="form.required_human_reviewer" class="w-full rounded-lg border-gray-300 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Escalation Destination</label>
                                <input type="text" name="escalation_destination" x-model="form.escalation_destination" class="w-full rounded-lg border-gray-300 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Authority <span class="text-red-500">*</span></label>
                                <select name="authority" x-model="form.authority" class="w-full rounded-lg border-gray-300 text-sm">
                                    @foreach (AiRuleAuthority::cases() as $case)
                                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Implementation Status <span class="text-red-500">*</span></label>
                                <select name="implementation_status" x-model="form.implementation_status" class="w-full rounded-lg border-gray-300 text-sm">
                                    @foreach (AiRuleImplementationStatus::cases() as $case)
                                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Effective Date</label>
                                <input type="date" name="effective_date" x-model="form.effective_date" class="w-full rounded-lg border-gray-300 text-sm">
                            </div>
                            <div class="flex items-center gap-2 pt-6">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" x-model="form.is_active" id="ai_rule_is_active" class="rounded border-gray-300">
                                <label for="ai_rule_is_active" class="text-sm text-gray-700">Active</label>
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 bg-amber-50 border border-amber-100 rounded-lg p-2" x-show="mode === 'edit'">
                            Editing resets this rule's approval and bumps its version — re-approve to make it canonical again.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-200 sticky bottom-0 bg-white">
                        <button type="button" @click="showModal = false" class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-sky-600 text-white hover:bg-sky-700" x-text="mode === 'edit' ? 'Save Changes' : 'Create Rule'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('js')
        <script>
            function aiRules() {
                return {
                    showModal: false,
                    mode: 'create',
                    data: {},
                    form: {},
                    storeUrl: @js(route('admin.crm.ai-rules.store')),
                    init(data) { this.data = data; },
                    blankForm() {
                        return {
                            action: this.storeUrl,
                            rule_key: '', rule_name: '', business_area: '', purpose: '',
                            trigger_description: '', evaluable_inputs: '', deterministic_action: '',
                            ai_assessment_instruction: '', ai_may_recommend: '', ai_may_execute: '',
                            ai_must_not: '', required_human_reviewer: '', escalation_destination: '',
                            authority: 'recommend_only', implementation_status: 'defined_not_automated',
                            is_active: true, effective_date: '',
                        };
                    },
                    openCreate() {
                        this.mode = 'create';
                        this.form = this.blankForm();
                        this.showModal = true;
                    },
                    openEdit(id) {
                        const r = this.data[id];
                        this.mode = 'edit';
                        this.form = {
                            action: r.update_url,
                            rule_key: '', rule_name: r.rule_name || '', business_area: r.business_area || '',
                            purpose: r.purpose || '', trigger_description: r.trigger_description || '',
                            evaluable_inputs: r.evaluable_inputs || '', deterministic_action: r.deterministic_action || '',
                            ai_assessment_instruction: r.ai_assessment_instruction || '',
                            ai_may_recommend: r.ai_may_recommend || '', ai_may_execute: r.ai_may_execute || '',
                            ai_must_not: r.ai_must_not || '', required_human_reviewer: r.required_human_reviewer || '',
                            escalation_destination: r.escalation_destination || '',
                            authority: r.authority || 'recommend_only',
                            implementation_status: r.implementation_status || 'defined_not_automated',
                            is_active: !!r.is_active, effective_date: r.effective_date || '',
                        };
                        this.showModal = true;
                    },
                };
            }
        </script>
    @endpush
@endsection
