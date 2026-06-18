@extends('admin.layouts.app')

@section('title', 'AI Specifications — ' . $profile->make . ' ' . $profile->model)

@section('content')
    <div class="space-y-6" x-data="{
        showAddForm: false,
        editingId: null,
        generating: false,
        togglingId: null,
        showKeyCriteriaModal: false,
        pendingSpecId: null,
        pendingCheckbox: null,
        keyCriteriaForm: {
            upgrade_exceeds_value: true,
            caution_if_exceeds_value: true,
            upgrade_is_below_value: false,
            caution_if_below_value: false,
        },

        // ── Deep Research modal ──────────────────────────────────────────
        showDeepResearchModal: false,
        deepResearchSpecId:       null,
        deepResearchSpecLabel:    '',
        deepResearchSpecKey:      '',
        deepResearchCurrentValue: '',
        deepResearchCurrentUnit:  '',
        deepResearchMake:  '{{ addslashes($profile->make) }}',
        deepResearchModel: '{{ addslashes($profile->model) }}',
        deepResearchPrompt:    '',
        deepResearching:       false,
        deepResearchResult:    null,
        applyingResearch:      false,
        deepResearchRefUrls:   ['', '', ''],
        deepResearchFollowUp:  '',
        showRefineSection:     false,

        openDeepResearchModal(specId, specLabel, specKey, currentValue, currentUnit) {
            this.deepResearchSpecId       = specId;
            this.deepResearchSpecLabel    = specLabel;
            this.deepResearchSpecKey      = specKey;
            this.deepResearchCurrentValue = currentValue;
            this.deepResearchCurrentUnit  = currentUnit;
            this.deepResearchPrompt = `You are verifying a single equipment specification for a rental/equipment comparison database.\n\nEquipment Make: ${this.deepResearchMake}\nEquipment Model: ${this.deepResearchModel}\nSpecification to Verify: ${specLabel}\nCurrent Value: ${currentValue}\nCurrent Unit: ${currentUnit}\n\nYour task is to perform a deep research review of this one specification only.\n\nResearch Requirements:\n1. Verify the correct value for the specified make and model.\n2. Prioritize manufacturer manuals, official spec sheets, parts/service manuals, dealer literature, and credible equipment databases.\n3. Check for model-year differences, configuration differences, optional packages, regional variations, or attachment-dependent values that may affect the specification.\n4. Do not assume the current value is correct.\n5. If sources conflict, explain the conflict and identify the most reliable value.\n6. If the specification cannot be confidently verified, say so clearly.\n\nImportant:\nOnly research this one specification. Do not return a full equipment specification sheet.`;
            this.deepResearchResult   = null;
            this.deepResearching      = false;
            this.deepResearchRefUrls  = ['', '', ''];
            this.deepResearchFollowUp = '';
            this.showRefineSection    = false;
            this.showDeepResearchModal = true;
        },

        closeDeepResearchModal() {
            this.showDeepResearchModal = false;
            this.deepResearchResult    = null;
            this.deepResearching       = false;
            this.showRefineSection     = false;
        },

        async runDeepResearch() {
            if (!this.deepResearchSpecId || !this.deepResearchPrompt.trim()) return;
            this.deepResearching   = true;
            this.deepResearchResult = null;

            // Build final prompt: base + follow-up note + reference URLs
            let finalPrompt = this.deepResearchPrompt;
            if (this.deepResearchFollowUp.trim()) {
                finalPrompt += '\n\nCorrection Note from User:\n' + this.deepResearchFollowUp.trim();
            }
            const refUrls = this.deepResearchRefUrls.filter(u => u.trim());
            if (refUrls.length > 0) {
                finalPrompt += '\n\nReference Sources — please use these specific pages to find the correct value:\n'
                    + refUrls.map((u, i) => (i + 1) + '. ' + u).join('\n');
            }

            try {
                const res = await fetch(`/maintenance-management/equipment-ai/specifications/${this.deepResearchSpecId}/deep-research`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ prompt: finalPrompt }),
                });
                const data = await res.json();
                if (data.success) {
                    this.deepResearchResult = data;
                    this.showRefineSection  = false;
                } else {
                    alert(data.message || 'Research failed. Please try again.');
                }
            } catch (e) {
                console.error('runDeepResearch error', e);
                alert('Request failed. Please check your connection.');
            } finally {
                this.deepResearching = false;
            }
        },

        async applyResearchResult() {
            if (!this.deepResearchResult || !this.deepResearchSpecId) return;
            this.applyingResearch = true;
            try {
                const url  = `/maintenance-management/equipment-ai/specifications/${this.deepResearchSpecId}`;
                const body = new FormData();
                body.append('_method', 'PUT');
                body.append('_token', '{{ csrf_token() }}');
                body.append('spec_label', this.deepResearchSpecLabel);
                if (this.deepResearchResult.value !== '') body.append('spec_value', this.deepResearchResult.value);
                if (this.deepResearchResult.unit  !== '') body.append('spec_unit',  this.deepResearchResult.unit);
                if (this.deepResearchResult.confidence !== null) body.append('confidence_score', (this.deepResearchResult.confidence / 100).toFixed(2));
                body.append('source', 'ai_openai');
                const res = await fetch(url, { method: 'POST', body });
                if (res.ok) {
                    this.closeDeepResearchModal();
                    window.location.reload();
                } else {
                    const data = await res.json().catch(() => ({}));
                    alert(data.message || 'Failed to save. Please try again.');
                }
            } catch (e) {
                alert('Request failed. Please check your connection.');
            } finally {
                this.applyingResearch = false;
            }
        },

        async generateSpecs() {
            if (!confirm('Use AI to research and generate specifications for {{ addslashes($profile->make . ' ' . $profile->model) }}?\n\nThis calls the OpenAI API and may take 15–30 seconds.')) return;
            this.generating = true;
            try {
                const res = await fetch('{{ route('admin.maintenance-management.equipment-ai.profiles.specifications.generate', $profile->unique_id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Generation failed. Please try again.');
                    this.generating = false;
                }
            } catch (e) {
                alert('Request failed. Please check your connection and try again.');
                this.generating = false;
            }
        },

        openKeyCriteriaModal(specId, checkbox, flags = {}) {
            this.pendingSpecId = specId;
            this.pendingCheckbox = checkbox;
            this.keyCriteriaForm = {
                upgrade_exceeds_value: !!(flags.upgrade_exceeds_value ?? true),
                caution_if_exceeds_value: !!(flags.caution_if_exceeds_value ?? true),
                upgrade_is_below_value: !!(flags.upgrade_is_below_value ?? false),
                caution_if_below_value: !!(flags.caution_if_below_value ?? false),
            };
            this.showKeyCriteriaModal = true;
        },

        closeKeyCriteriaModal() {
            this.showKeyCriteriaModal = false;
            this.pendingSpecId = null;
            this.pendingCheckbox = null;
        },

        async onKeyComparisonChange(specId, checkbox, flags = {}) {
            if (checkbox.checked) {
                checkbox.checked = false;
                this.openKeyCriteriaModal(specId, checkbox, flags);
                return;
            }

            await this.toggleKeyComparison(specId, checkbox, {
                is_key_comparison: false,
            });
        },

        async saveKeyCriteriaFromModal() {
            if (!this.pendingSpecId || !this.pendingCheckbox) {
                this.closeKeyCriteriaModal();
                return;
            }

            await this.toggleKeyComparison(this.pendingSpecId, this.pendingCheckbox, {
                is_key_comparison: true,
                ...this.keyCriteriaForm,
            });
        },

        async toggleKeyComparison(specId, checkbox, payload = {}) {
            this.togglingId = specId;
            try {
                const res = await fetch(`/maintenance-management/equipment-ai/specifications/${specId}/toggle-key-comparison`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) {
                    window.location.reload(); // refresh so sort order, badge & amber tint update
                } else {
                    checkbox.checked = !checkbox.checked; // revert
                    alert(data.message || 'Failed to update. Please try again.');
                    this.togglingId = null;
                }
            } catch (e) {
                checkbox.checked = !checkbox.checked; // revert
                alert('Request failed. Please check your connection.');
                this.togglingId = null;
            } finally {
                this.closeKeyCriteriaModal();
            }
        },
    }" x-init="
        $watch('keyCriteriaForm.upgrade_exceeds_value', value => {
            if (value) {
                keyCriteriaForm.upgrade_is_below_value = false;
                keyCriteriaForm.caution_if_exceeds_value = false;
            }
        });
        $watch('keyCriteriaForm.caution_if_below_value', value => {
            if (value) {
                keyCriteriaForm.upgrade_is_below_value = false;
                keyCriteriaForm.caution_if_exceeds_value = false;
            }
        });
        $watch('keyCriteriaForm.upgrade_is_below_value', value => {
            if (value) {
                keyCriteriaForm.upgrade_exceeds_value = false;
                keyCriteriaForm.caution_if_below_value = false;
            }
        });
        $watch('keyCriteriaForm.caution_if_exceeds_value', value => {
            if (value) {
                keyCriteriaForm.upgrade_exceeds_value = false;
                keyCriteriaForm.caution_if_below_value = false;
            }
        })">

        {{-- ─── Breadcrumb / Back ───────────────────────────────────────── --}}
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <a href="{{ route('admin.maintenance-management.equipment-ai.index', ['category_id' => $profile->category_id]) }}"
                class="hover:text-brand-500">Equipment Management AI</a>
            <x-heroicon-o-chevron-right class="h-4 w-4" />
            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $profile->make }} {{ $profile->model }}</span>
        </div>

        {{-- ─── Flash Messages ───────────────────────────────────────────── --}}
        @if (session('success'))
            <div
                class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700/50 dark:bg-green-900/20 dark:text-green-400">
                <x-heroicon-o-check-circle class="h-5 w-5 shrink-0" />
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-700/50 dark:bg-red-900/20 dark:text-red-400">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ─── Profile Header Card ─────────────────────────────────────── --}}
        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 px-6 py-5">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-900/30">
                        <x-heroicon-o-cpu-chip class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $profile->make }} {{ $profile->model }}
                        </h1>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span>Category: <strong
                                    class="text-gray-700 dark:text-gray-300">{{ $profile->category->title ?? '—' }}</strong></span>
                            <span>·</span>
                            <span>ID: <code
                                    class="rounded bg-gray-100 px-1 text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $profile->unique_id }}</code></span>
                            <span>·</span>
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 font-semibold {{ $profile->statusBadgeClass() }}">
                                {{ $profile->statusLabel() }}
                            </span>
                        </div>
                        @if ($profile->source_notes)
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 italic">{{ $profile->source_notes }}
                            </p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">

                    {{-- ── Back to Profiles list ──────────────────────────────────── --}}
                    <a href="{{ route('admin.maintenance-management.equipment-ai.index', ['category_id' => $profile->category_id]) }}"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-1 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                        <x-heroicon-o-arrow-left class="h-4 w-4" />
                        {{ $profile->category->title ?? 'Profiles' }}
                    </a>

                    {{-- ── AI Generate button ─────────────────────────────────────── --}}
                    <button @click="generateSpecs()" :disabled="generating"
                        class="inline-flex items-center gap-2 rounded-lg border border-purple-400 px-4 py-2 text-sm font-medium text-purple-700 hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-60 dark:border-purple-500 dark:text-purple-400 dark:hover:bg-purple-900/20">
                        <svg x-show="generating" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                            </path>
                        </svg>
                        <x-heroicon-o-sparkles x-show="!generating" class="h-4 w-4" />
                        <span x-text="generating ? 'Generating…' : 'Research & Create General Specification'"></span>
                    </button>

                    {{-- ── Manual Add button ──────────────────────────────────────── --}}
                    <button @click="showAddForm = !showAddForm"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1">
                        <x-heroicon-o-plus class="h-4 w-4" />
                        Add Specification
                    </button>

                </div>
            </div>
        </div>

        {{-- ─── Add Specification Form (collapsible) ───────────────────── --}}
        <div x-show="showAddForm" x-transition x-cloak
            class="rounded-2xl border border-indigo-100 bg-indigo-50/50 shadow-sm dark:border-indigo-700/30 dark:bg-indigo-900/10">
            <div class="border-b border-indigo-100 px-6 py-4 dark:border-indigo-700/30">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Add / Update Specification</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    If a spec with the same key already exists for this profile it will be updated.
                </p>
            </div>
            <form method="POST"
                action="{{ route('admin.maintenance-management.equipment-ai.profiles.specifications.store', $profile->unique_id) }}"
                class="grid grid-cols-1 gap-4 px-6 py-5 sm:grid-cols-2 lg:grid-cols-3">
                @csrf

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Spec Key <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="spec_key" placeholder="engine_hp" value="{{ old('spec_key') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <p class="mt-0.5 text-xs text-gray-400">Lowercase, underscores (e.g. lift_height_ft)</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Display Label <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="spec_label" placeholder="Engine Horsepower" value="{{ old('spec_label') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Value</label>
                    <input type="text" name="spec_value" placeholder="74" value="{{ old('spec_value') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Unit</label>
                    <input type="text" name="spec_unit" placeholder="hp" value="{{ old('spec_unit') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Source</label>
                    <select name="source"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                        <option value="manual" {{ old('source') == 'manual' ? 'selected' : '' }}>Manual Entry</option>
                        <option value="manufacturer_pdf" {{ old('source') == 'manufacturer_pdf' ? 'selected' : '' }}>
                            Manufacturer PDF</option>
                        <option value="manufacturer_website"
                            {{ old('source') == 'manufacturer_website' ? 'selected' : '' }}>Manufacturer Website</option>
                        <option value="ai_openai" {{ old('source') == 'ai_openai' ? 'selected' : '' }}>AI (OpenAI)</option>
                        <option value="ai_other" {{ old('source') == 'ai_other' ? 'selected' : '' }}>AI (Other)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Confidence (0–1)</label>
                    <input type="number" name="confidence_score" placeholder="1.0" min="0" max="1"
                        step="0.01" value="{{ old('confidence_score', '1.0') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                </div>

                <div class="sm:col-span-2 lg:col-span-3 flex justify-end gap-3">
                    <button type="button" @click="showAddForm = false"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600">
                        <x-heroicon-o-check class="h-4 w-4" />
                        Save Specification
                    </button>
                </div>
            </form>
        </div>

        {{-- ─── Specifications Table ────────────────────────────────────── --}}
        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                    Specifications
                    <span class="ml-1 text-sm font-normal text-gray-400">({{ $profile->specifications->count() }})</span>
                </h2>
            </div>

            @php
                $specSections = [
                    [
                        'title' => 'Key Criteria',
                        'description' => 'Critical comparison fields highlighted for this profile.',
                        'items' => $keyCriteriaSpecs,
                        'row_class' => 'bg-amber-50/70 text-amber-800 dark:bg-amber-900/20 dark:text-amber-300',
                    ],
                    [
                        'title' => 'Common Specifications',
                        'description' => 'Category-based specs shared across similar equipment profiles.',
                        'items' => $commonSpecs,
                        'row_class' => 'bg-sky-50/70 text-sky-800 dark:bg-sky-900/20 dark:text-sky-300',
                    ],
                    [
                        'title' => 'Unique Specifications',
                        'description' => 'Profile-specific specs that are not part of the shared category catalog.',
                        'items' => $uniqueSpecs,
                        'row_class' => 'bg-emerald-50/70 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300',
                    ],
                ];
            @endphp

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th
                                class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 w-32">
                                Key Comparison</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Label</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Value</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Unit</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Source</th>
                            <th
                                class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Confidence</th>
                            <th
                                class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Research</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        @if ($profile->specifications->isEmpty())
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-gray-400 dark:text-gray-500">
                                    No specifications yet. Click <strong>Research &amp; Create General
                                        Specification</strong> to generate with AI,
                                    or <strong>Add Specification</strong> to add manually.
                                </td>
                            </tr>
                        @else
                            @foreach ($specSections as $section)
                                @continue($section['items']->isEmpty())

                                <tr class="{{ $section['row_class'] }}">
                                    <td colspan="8" class="px-5 py-3">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <h3 class="text-sm font-semibold">{{ $section['title'] }}</h3>
                                                <p class="mt-0.5 text-xs opacity-80">{{ $section['description'] }}</p>
                                            </div>
                                            <span
                                                class="shrink-0 rounded-full bg-white/70 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                                                {{ $section['items']->count() }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>

                                @foreach ($section['items'] as $spec)
                                    @php
                                        $normalizedSpecKey = mb_strtolower(trim((string) $spec->spec_key));
                                        $criteriaFlags = $comparisonKeySettingsBySpecKey[$normalizedSpecKey] ?? [
                                            'upgrade_exceeds_value' => true,
                                            'caution_if_below_value' => false,
                                            'upgrade_is_below_value' => false,
                                            'caution_if_exceeds_value' => true,
                                        ];
                                        $keyOwner = $keyComparisonOwnersBySpecKey[$normalizedSpecKey] ?? null;
                                        $isLockedByOtherProfile =
                                            ! $spec->is_key_comparison &&
                                            $keyOwner &&
                                            (int) $keyOwner['profile_id'] !== (int) $profile->id;
                                        $lockMessage = $isLockedByOtherProfile
                                            ? 'Already selected as key criteria by ' .
                                                trim(($keyOwner['make'] ?? '') . ' ' . ($keyOwner['model'] ?? ''))
                                            : null;
                                    @endphp
                                    {{-- View row --}}
                                    <tr x-show="editingId !== {{ $spec->id }}"
                                        class="{{ $spec->is_key_comparison ? 'bg-amber-50/40 dark:bg-amber-900/10' : '' }} {{ $isLockedByOtherProfile ? 'opacity-60' : '' }} hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                        <td class="px-5 py-3 text-center">
                                            <input type="checkbox" id="kc_{{ $spec->id }}"
                                                {{ $spec->is_key_comparison ? 'checked' : '' }}
                                                title="{{ $lockMessage ?? '' }}"
                                                :disabled="togglingId === {{ $spec->id }} || {{ $isLockedByOtherProfile ? 'true' : 'false' }}"
                                                @change="onKeyComparisonChange({{ $spec->id }}, $event.target, {{ \Illuminate\Support\Js::from($criteriaFlags) }})"
                                                class="h-4 w-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400 disabled:cursor-not-allowed disabled:opacity-50 {{ $isLockedByOtherProfile ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                        </td>
                                        <td class="px-5 py-3 text-gray-800 dark:text-gray-200 ">
                                            {{ $spec->spec_label }}
                                            @if ($spec->is_key_comparison)
                                                <span
                                                    class="ml-1.5 inline-flex items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">key</span>
                                            @elseif($isLockedByOtherProfile)
                                                <span
                                                    class="ml-1.5 inline-flex items-center rounded-full bg-gray-200 px-1.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">locked</span>
                                                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                    {{ $lockMessage }}
                                                </p>
                                            @endif

                                            @if ($spec->is_key_comparison)
                                                <div
                                                    class="mt-2 grid grid-cols-2 gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                    <label class="inline-flex items-center gap-1.5">
                                                        <input type="checkbox"
                                                            class="h-3 w-3 rounded border-gray-300 text-amber-500" disabled
                                                            {{ $criteriaFlags['upgrade_exceeds_value'] ? 'checked' : '' }}>
                                                        <span>Upgrade exceeds value</span>
                                                    </label>

                                                    <label class="inline-flex items-center gap-1.5">
                                                        <input type="checkbox"
                                                            class="h-3 w-3 rounded border-gray-300 text-amber-500" disabled
                                                            {{ $criteriaFlags['caution_if_below_value'] ? 'checked' : '' }}>
                                                        <span>Caution if below value</span>
                                                    </label>

                                                    <label class="inline-flex items-center gap-1.5">
                                                        <input type="checkbox"
                                                            class="h-3 w-3 rounded border-gray-300 text-amber-500" disabled
                                                            {{ $criteriaFlags['upgrade_is_below_value'] ? 'checked' : '' }}>
                                                        <span>Upgrade is below value</span>
                                                    </label>

                                                    <label class="inline-flex items-center gap-1.5">
                                                        <input type="checkbox"
                                                            class="h-3 w-3 rounded border-gray-300 text-amber-500" disabled
                                                            {{ $criteriaFlags['caution_if_exceeds_value'] ? 'checked' : '' }}>
                                                        <span>Caution if exceeds value</span>
                                                    </label>



                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 font-medium text-gray-900 dark:text-gray-100">
                                            {{ $spec->spec_value ?? '—' }}</td>
                                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                                            {{ $spec->spec_unit ?? '—' }}</td>
                                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $spec->source ?? '—' }}
                                        </td>
                                        <td class="px-5 py-3 text-center">
                                            @php $pct = round(($spec->confidence_score ?? 1) * 100); @endphp
                                            <span
                                                class="text-xs {{ $pct >= 80 ? 'text-green-600' : ($pct >= 50 ? 'text-yellow-600' : 'text-red-500') }}">
                                                {{ $pct }}%
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-center">
                                            <button type="button"
                                                @click="openDeepResearchModal({{ $spec->id }}, '{{ addslashes($spec->spec_label) }}', '{{ addslashes($spec->spec_key) }}', '{{ addslashes($spec->spec_value ?? '') }}', '{{ addslashes($spec->spec_unit ?? '') }}')"
                                                class="inline-flex items-center gap-1 rounded-md border border-amber-300 bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 hover:bg-amber-100 dark:border-amber-600 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/40"
                                                title="Deep Research — verify this spec with AI">
                                                <x-heroicon-o-magnifying-glass class="h-3.5 w-3.5" />
                                                Research
                                            </button>
                                        </td>
                                        <td class="px-5 py-3">
                                            <div class="flex items-center justify-end gap-2">
                                                <button @click="editingId = {{ $spec->id }}"
                                                    class="inline-flex items-center justify-center rounded-md p-1.5 text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-300"
                                                    title="Edit">
                                                    <x-heroicon-o-pencil-square class="h-4 w-4" />
                                                </button>
                                                <form method="POST"
                                                    action="{{ route('admin.maintenance-management.equipment-ai.specifications.delete', $spec->id) }}"
                                                    class="inline"
                                                    onsubmit="return confirm('Delete spec &quot;{{ addslashes($spec->spec_key) }}&quot;?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="inline-flex items-center justify-center rounded-md p-1.5 text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300"
                                                        title="Delete">
                                                        <x-heroicon-o-trash class="h-4 w-4" />
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Inline edit row --}}
                                    <tr x-show="editingId === {{ $spec->id }}" x-cloak
                                        class="bg-blue-50/50 dark:bg-blue-900/10">
                                        <td colspan="8" class="px-5 py-4">
                                            <form method="POST"
                                                action="{{ route('admin.maintenance-management.equipment-ai.specifications.update', $spec->id) }}"
                                                class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-7">
                                                @csrf
                                                @method('PUT')

                                                <div>
                                                    <label
                                                        class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Label
                                                        *</label>
                                                    <input type="text" name="spec_label"
                                                        value="{{ $spec->spec_label }}" required
                                                        class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-xs focus:border-brand-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Value</label>
                                                    <input type="text" name="spec_value"
                                                        value="{{ $spec->spec_value }}"
                                                        class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-xs focus:border-brand-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Unit</label>
                                                    <input type="text" name="spec_unit"
                                                        value="{{ $spec->spec_unit }}"
                                                        class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-xs focus:border-brand-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Source</label>
                                                    <select name="source"
                                                        class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-xs focus:border-brand-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                                        @foreach (['manual', 'manufacturer_pdf', 'manufacturer_website', 'ai_openai', 'ai_other'] as $src)
                                                            <option value="{{ $src }}"
                                                                {{ $spec->source == $src ? 'selected' : '' }}>
                                                                {{ ucwords(str_replace('_', ' ', $src)) }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Confidence</label>
                                                    <input type="number" name="confidence_score"
                                                        value="{{ $spec->confidence_score }}" min="0"
                                                        max="1" step="0.01"
                                                        class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-xs focus:border-brand-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                                </div>
                                                <div
                                                    class="sm:col-span-3 lg:col-span-2 rounded-md border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-800/40">
                                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Flags
                                                    </p>
                                                    <div class="mt-2 grid gap-1 md:grid-cols-2">
                                                        <label
                                                            class="inline-flex items-center gap-1.5 text-[11px] text-gray-600 dark:text-gray-300">
                                                            <input type="checkbox" name="upgrade_exceeds_value"
                                                                value="1"
                                                                {{ $criteriaFlags['upgrade_exceeds_value'] ? 'checked' : '' }}
                                                                class="h-3.5 w-3.5 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                                                onclick="if(this.checked) { this.form.elements['upgrade_is_below_value'].checked = false; this.form.elements['caution_if_exceeds_value'].checked = false; }">
                                                            <span>Upgrade Exceeds Value</span>
                                                        </label>
                                                        <label
                                                            class="inline-flex items-center gap-1.5 text-[11px] text-gray-600 dark:text-gray-300">
                                                            <input type="checkbox" name="caution_if_below_value"
                                                                value="1"
                                                                {{ $criteriaFlags['caution_if_below_value'] ? 'checked' : '' }}
                                                                class="h-3.5 w-3.5 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                                                onclick="if(this.checked) { this.form.elements['upgrade_is_below_value'].checked = false; this.form.elements['caution_if_exceeds_value'].checked = false; }">
                                                            <span>Caution If Below Value</span>
                                                        </label>
                                                        <label
                                                            class="inline-flex items-center gap-1.5 text-[11px] text-gray-600 dark:text-gray-300">
                                                            <input type="checkbox" name="upgrade_is_below_value"
                                                                value="1"
                                                                {{ $criteriaFlags['upgrade_is_below_value'] ? 'checked' : '' }}
                                                                class="h-3.5 w-3.5 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                                                onclick="if(this.checked) { this.form.elements['upgrade_exceeds_value'].checked = false; this.form.elements['caution_if_below_value'].checked = false; }">
                                                            <span>Upgrade Is Below Value</span>
                                                        </label>
                                                        <label
                                                            class="inline-flex items-center gap-1.5 text-[11px] text-gray-600 dark:text-gray-300">
                                                            <input type="checkbox" name="caution_if_exceeds_value"
                                                                value="1"
                                                                {{ $criteriaFlags['caution_if_exceeds_value'] ? 'checked' : '' }}
                                                                class="h-3.5 w-3.5 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                                                onclick="if(this.checked) { this.form.elements['upgrade_exceeds_value'].checked = false; this.form.elements['caution_if_below_value'].checked = false; }">
                                                            <span>Caution If Exceeds Value</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col justify-end gap-2">
                                                    <label class="inline-flex items-center gap-1.5 cursor-pointer" title="Apply this display label to all other equipment models in the same category that share this spec key">
                                                        <input type="checkbox" name="propagate_label" value="1"
                                                               class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-500 focus:ring-indigo-400">
                                                        <span class="text-[11px] text-indigo-600 dark:text-indigo-400 font-medium">Apply label to whole category</span>
                                                    </label>
                                                    <div class="flex items-center gap-2">
                                                        <button type="submit"
                                                            class="rounded-md bg-brand-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-600">
                                                            Save
                                                        </button>
                                                        <button type="button" @click="editingId = null"
                                                            class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700">
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="showKeyCriteriaModal" x-cloak class="fixed inset-0 z-9999 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeKeyCriteriaModal()"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl dark:bg-gray-900">
                <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Set Key Comparison Flags</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Choose how this specification should be
                        treated in upgrade and caution logic.</p>
                </div>

                <div class="space-y-3 px-5 py-4 grid gap-1 md:grid-cols-2">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" x-model="keyCriteriaForm.upgrade_exceeds_value"
                            class="h-4 w-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                        <span>Upgrade Exceeds Value</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" x-model="keyCriteriaForm.caution_if_below_value"
                            class="h-4 w-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                        <span>Caution If Below Value</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" x-model="keyCriteriaForm.upgrade_is_below_value"
                            class="h-4 w-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                        <span>Upgrade Is Below Value</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" x-model="keyCriteriaForm.caution_if_exceeds_value"
                            class="h-4 w-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                        <span>Caution If Exceeds Value</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-5 py-4 dark:border-gray-700">
                    <button type="button" @click="closeKeyCriteriaModal()"
                        class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                        Cancel
                    </button>
                    <button type="button" @click="saveKeyCriteriaFromModal()"
                        class="rounded-md bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">
                        Save
                    </button>
                </div>
            </div>
        </div>

        {{-- ─── Deep Research Modal ─────────────────────────────────────────── --}}
        <div x-show="showDeepResearchModal" x-cloak
             class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
             @keydown.escape.window="closeDeepResearchModal()">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeDeepResearchModal()"></div>
            <div class="relative w-full max-w-2xl rounded-xl bg-white shadow-2xl dark:bg-gray-900 max-h-[90vh] flex flex-col">

                {{-- Header --}}
                <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700 shrink-0">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                            <x-heroicon-o-magnifying-glass class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                        </span>
                        <div>
                            <p class="text-[11px] font-medium uppercase tracking-wide text-amber-600 dark:text-amber-400">Deep Research</p>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white leading-tight" x-text="deepResearchMake + ' ' + deepResearchModel"></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="deepResearchSpecLabel + ' · ' + deepResearchSpecKey"></p>
                        </div>
                    </div>
                </div>

                {{-- Scrollable body --}}
                <div class="overflow-y-auto flex-1 px-5 py-4 space-y-4">

                    {{-- Prompt textarea --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Research prompt <span class="text-red-500">*</span>
                        </label>
                        <textarea x-model="deepResearchPrompt" rows="10"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-xs font-mono leading-relaxed focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 resize-y"></textarea>
                        <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">Review and edit the prompt above before submitting. After seeing a wrong result, edit the prompt to add correction context, then click Research again.</p>
                    </div>

                    {{-- Reference URLs --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Reference URLs
                            <span class="ml-1 font-normal text-gray-400">(optional — paste manufacturer or spec-sheet pages to guide AI)</span>
                        </label>
                        <div class="space-y-1.5">
                            <template x-for="(url, i) in deepResearchRefUrls" :key="i">
                                <div class="flex items-center gap-2">
                                    <span class="w-10 shrink-0 text-right text-[10px] text-gray-400" x-text="'URL ' + (i + 1)"></span>
                                    <input type="url"
                                           x-model="deepResearchRefUrls[i]"
                                           :placeholder="i === 0 ? 'https://www.niftylift.com/products/...' : 'https://'"
                                           class="block flex-1 rounded-md border border-gray-200 px-2.5 py-1.5 text-xs focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:placeholder-gray-600">
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Research result --}}
                    <div x-show="deepResearchResult" class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-700/50 dark:bg-emerald-900/20">
                        <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-300 mb-2">Verified Specification</p>
                        <div class="grid grid-cols-4 gap-2 text-xs mb-3">
                            <div>
                                <span class="block text-gray-500 dark:text-gray-400">Value</span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="deepResearchResult?.value || '—'"></span>
                            </div>
                            <div>
                                <span class="block text-gray-500 dark:text-gray-400">Unit</span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="deepResearchResult?.unit || '—'"></span>
                            </div>
                            <div>
                                <span class="block text-gray-500 dark:text-gray-400">Confidence</span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="deepResearchResult?.confidence !== null ? deepResearchResult.confidence + '%' : '—'"></span>
                            </div>
                            <div>
                                <span class="block text-gray-500 dark:text-gray-400">Source Type</span>
                                <span class="font-medium text-gray-900 dark:text-white capitalize" x-text="deepResearchResult?.source_type || '—'"></span>
                            </div>
                        </div>
                        <div x-show="deepResearchResult?.reasoning" class="mb-2">
                            <span class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-0.5">Reasoning</span>
                            <p class="text-[11px] text-gray-700 dark:text-gray-200" x-text="deepResearchResult?.reasoning"></p>
                        </div>
                        <div x-show="deepResearchResult?.source_notes">
                            <span class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-0.5">Source Notes</span>
                            <p class="text-[11px] text-gray-600 dark:text-gray-300 italic" x-text="deepResearchResult?.source_notes"></p>
                        </div>
                    </div>

                    {{-- Refine section: appears after result, lets user add correction context --}}
                    <div x-show="deepResearchResult">
                        <button type="button"
                                @click="showRefineSection = !showRefineSection"
                                class="text-xs text-amber-600 hover:text-amber-700 hover:underline dark:text-amber-400 dark:hover:text-amber-300">
                            <span x-text="showRefineSection ? '▲ Hide correction notes' : '▼ Result wrong? Add correction notes &amp; re-research'"></span>
                        </button>
                        <div x-show="showRefineSection" x-cloak class="mt-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Correction guidance</label>
                            <textarea x-model="deepResearchFollowUp" rows="3"
                                      placeholder="Describe what's wrong, e.g.: 'The TM34 only comes in 2WD. The 4WD value is incorrect. Please verify using the reference URLs above.'"
                                      class="w-full rounded-md border border-amber-200 px-3 py-2 text-xs focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 dark:border-amber-700/50 dark:bg-gray-800 dark:text-gray-100 resize-y"></textarea>
                            <p class="mt-0.5 text-[11px] text-gray-400">This note + any reference URLs above will be appended to the prompt when you click Research again.</p>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-5 py-4 dark:border-gray-700 shrink-0">
                    <button type="button" @click="closeDeepResearchModal()"
                        class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                        Close
                    </button>
                    <button type="button" @click="runDeepResearch()"
                        :disabled="deepResearching || !deepResearchPrompt.trim()"
                        class="inline-flex items-center gap-1.5 rounded-md bg-amber-500 px-4 py-1.5 text-sm font-medium text-white hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="deepResearching" class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        <x-heroicon-o-magnifying-glass x-show="!deepResearching" class="h-3.5 w-3.5" />
                        <span x-text="deepResearching ? 'Researching…' : 'Research'"></span>
                    </button>
                    <button type="button" @click="applyResearchResult()"
                        x-show="deepResearchResult"
                        :disabled="applyingResearch"
                        class="inline-flex items-center gap-1.5 rounded-md bg-brand-500 px-4 py-1.5 text-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="applyingResearch" class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        <x-heroicon-o-check x-show="!applyingResearch" class="h-3.5 w-3.5" />
                        <span x-text="applyingResearch ? 'Saving…' : 'Apply & Save'"></span>
                    </button>
                </div>
            </div>
        </div>

    </div>
@endsection
