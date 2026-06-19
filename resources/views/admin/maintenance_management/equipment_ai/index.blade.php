@extends('admin.layouts.app')

@section('title', 'Equipment Management AI')

@section('content')
    <div class="space-y-6">

        {{-- ─── Page Header ─────────────────────────────────────────────── --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Equipment Management AI</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Scan categories to build AI-ready equipment profiles. Specs are stored per unique make/model — not per
                individual unit.
            </p>
        </div>

        {{-- ─── Flash Messages ───────────────────────────────────────────── --}}
        @if (session('success'))
            <div
                class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700/50 dark:bg-green-900/20 dark:text-green-400">
                <x-heroicon-o-check-circle class="h-5 w-5 shrink-0" />
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div
                class="flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-700/50 dark:bg-red-900/20 dark:text-red-400">
                <x-heroicon-o-exclamation-circle class="h-5 w-5 shrink-0" />
                {{ session('error') }}
            </div>
        @endif

        {{-- ─── Category Filter & Scan ────────────────────────────────────── --}}
        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Category Filter & Scan</h2>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    Select a category to view its AI profiles and comparison criteria.
                </p>
            </div>

            <div class="px-6 py-5">
                {{-- Category dropdown --}}
                <form method="GET" action="{{ route('admin.maintenance-management.equipment-ai.index') }}"
                    class="flex flex-col sm:flex-row gap-3">
                    <select name="category_id"
                        class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                        <option value="">— Select a Category —</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}"
                                {{ $selectedCategory && $selectedCategory->id == $cat->id ? 'selected' : '' }}>
                                {{ $cat->title }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1">
                        <x-heroicon-o-funnel class="h-4 w-4" />
                        Filter
                    </button>
                </form>

                {{-- Scan / Refresh actions (only shown when category selected) --}}
                @if ($selectedCategory)
                    <div
                        class="mt-4 flex flex-col sm:flex-row sm:items-center gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
                        <p class="flex-1 text-sm text-gray-600 dark:text-gray-400">
                            Manage AI profiles for
                            <span
                                class="font-semibold text-gray-800 dark:text-gray-200">{{ $selectedCategory->title }}</span>.
                        </p>
                        <div class="flex items-center gap-2 shrink-0">

                            {{-- Refresh: re-syncs profiles to current equipment data --}}
                            <form method="POST" action="{{ route('admin.maintenance-management.equipment-ai.refresh') }}">
                                @csrf
                                <input type="hidden" name="category_id" value="{{ $selectedCategory->id }}">
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-lg border border-amber-400 px-4 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-1 dark:border-amber-500 dark:text-amber-400 dark:hover:bg-amber-900/20"
                                    onclick="return confirm('Refresh will remove stale profiles that no longer match any equipment record (only if they have no specs). Orphaned profiles WITH specs will be flagged for manual review. Continue?')">
                                    <x-heroicon-o-arrow-path class="h-4 w-4" />
                                    Refresh Category Profiles
                                </button>
                            </form>

                            {{-- Scan: only adds new profiles --}}
                            <form method="POST" action="{{ route('admin.maintenance-management.equipment-ai.scan') }}">
                                @csrf
                                <input type="hidden" name="category_id" value="{{ $selectedCategory->id }}">
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                    onclick="return confirm('Scan {{ addslashes($selectedCategory->title) }} for new unique make/model combinations? Existing profiles are not affected.')">
                                    <x-heroicon-o-magnifying-glass class="h-4 w-4" />
                                    Scan Category for Unique Equipment
                                </button>
                            </form>

                            {{-- Compare & Commonize Matrix --}}
                            <a href="{{ route('admin.maintenance-management.equipment-ai.commonize.matrix', ['category_id' => $selectedCategory->id]) }}"
                               class="inline-flex items-center gap-2 rounded-lg border border-purple-400 px-4 py-2 text-sm font-medium text-purple-700 hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-1 dark:border-purple-500 dark:text-purple-400 dark:hover:bg-purple-900/20">
                                <x-heroicon-o-table-cells class="h-4 w-4" />
                                Compare &amp; Commonize
                            </a>

                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ─── Three-Tab Content (profiles + comparison keys + matrix) ─────── --}}
        @if ($selectedCategory)

            <div x-data="{ activeTab: '{{ request('tab', 'profiles') }}' }">

                {{-- Tab bar ──────────────────────────────────────────────── --}}
                <div class="flex rounded-xl bg-gray-100 p-1 dark:bg-gray-800 gap-1">
                    <button @click="activeTab = 'profiles'"
                        :class="activeTab === 'profiles'
                            ? 'bg-white shadow text-gray-900 dark:bg-gray-700 dark:text-white'
                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium transition-all">
                        <x-heroicon-o-cpu-chip class="h-4 w-4" />
                        Make / Models
                        <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-600 dark:text-gray-300">
                            {{ $profiles->count() }}
                        </span>
                    </button>
                    <button @click="activeTab = 'comparison'"
                        :class="activeTab === 'comparison'
                            ? 'bg-white shadow text-gray-900 dark:bg-gray-700 dark:text-white'
                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium transition-all">
                        <x-heroicon-o-adjustments-horizontal class="h-4 w-4" />
                        Key Comparison Criteria
                        <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-600 dark:text-gray-300">
                            {{ $activeComparisonKeyCount }}
                        </span>
                        @if ($comparisonKeys->where('has_specs', false)->count() > 0)
                            <span class="rounded-full bg-amber-400 px-1.5 py-0.5 text-xs font-bold text-white" title="{{ $comparisonKeys->where('has_specs', false)->count() }} stale key(s) — no matching spec data">
                                !
                            </span>
                        @endif
                    </button>
                    <button @click="activeTab = 'matrix'"
                        :class="activeTab === 'matrix'
                            ? 'bg-white shadow text-gray-900 dark:bg-gray-700 dark:text-white'
                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium transition-all">
                        <x-heroicon-o-table-cells class="h-4 w-4" />
                        Matrix Framework
                        <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-600 dark:text-gray-300">
                            {{ $profiles->count() }}
                        </span>
                    </button>
                    <a href="{{ $selectedCategory ? route('admin.maintenance-management.equipment-ai.intelligence-rules.index', ['category_id' => $selectedCategory->id]) : '#' }}"
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium transition-all text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 {{ !$selectedCategory ? 'opacity-40 pointer-events-none' : '' }}">
                        <x-heroicon-o-light-bulb class="h-4 w-4" />
                        Intelligence Rules
                        @if($intelligenceRuleCount > 0)
                            <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-600 dark:text-gray-300">
                                {{ $intelligenceRuleCount }}
                            </span>
                        @endif
                        @if($intelligenceRulePendingCount > 0)
                            <span class="rounded-full bg-amber-400 px-1.5 py-0.5 text-xs font-bold text-white"
                                  title="{{ $intelligenceRulePendingCount }} pending approval">
                                {{ $intelligenceRulePendingCount }}
                            </span>
                        @endif
                    </a>
                </div>

                {{-- ── Tab 1: Make / Models ──────────────────────────────── --}}
                <div x-show="activeTab === 'profiles'" x-cloak>
                    @include('admin.maintenance_management.equipment_ai.partials._profiles_table')
                </div>

                {{-- ── Tab 2: Key Comparison Criteria ───────────────────── --}}
                <div x-show="activeTab === 'comparison'" x-data="{ showAddKeyForm: false, editingKeyId: null }" x-cloak>

                    {{-- Add form (collapsible) --}}
                    <div x-show="showAddKeyForm" x-transition x-cloak
                        class="rounded-2xl border border-indigo-100 bg-indigo-50/50 shadow-sm dark:border-indigo-700/30 dark:bg-indigo-900/10 mb-4">
                        <div
                            class="border-b border-indigo-100 px-6 py-4 dark:border-indigo-700/30 flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                                    Add Comparison Key — {{ $selectedCategory->title }}
                                </h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    If a key with the same spec_key already exists it will be updated.
                                </p>
                            </div>
                        </div>
                        <form method="POST"
                            action="{{ route('admin.maintenance-management.equipment-ai.comparison-keys.store') }}"
                            class="grid grid-cols-1 gap-4 px-6 py-5 sm:grid-cols-2 lg:grid-cols-3">
                            @csrf
                            <input type="hidden" name="category_id" value="{{ $selectedCategory->id }}">

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Spec Key <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="spec_key" placeholder="platform_height_ft"
                                    value="{{ old('spec_key') }}"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <p class="mt-0.5 text-xs text-gray-400">Must match a spec_key in AI specifications</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Display Label <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="display_label" placeholder="Platform Height"
                                    value="{{ old('display_label') }}"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Importance <span class="text-red-500">*</span>
                                </label>
                                <select name="importance_level"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="critical" {{ old('importance_level') == 'critical' ? 'selected' : '' }}>
                                        Critical</option>
                                    <option value="high" {{ old('importance_level') == 'high' ? 'selected' : '' }}>
                                        High</option>
                                    <option value="medium"
                                        {{ old('importance_level', 'medium') == 'medium' ? 'selected' : '' }}>Medium
                                    </option>
                                    <option value="low" {{ old('importance_level') == 'low' ? 'selected' : '' }}>
                                        Low</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Comparison Type <span class="text-red-500">*</span>
                                </label>
                                <select name="comparison_type"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="higher_is_better"
                                        {{ old('comparison_type') == 'higher_is_better' ? 'selected' : '' }}>Higher is
                                        Better</option>
                                    <option value="lower_is_better"
                                        {{ old('comparison_type') == 'lower_is_better' ? 'selected' : '' }}>Lower is
                                        Better</option>
                                    <option value="must_match"
                                        {{ old('comparison_type') == 'must_match' ? 'selected' : '' }}>Must Match
                                    </option>
                                    <option value="range_acceptable"
                                        {{ old('comparison_type') == 'range_acceptable' ? 'selected' : '' }}>Range
                                        Acceptable</option>
                                    <option value="informational_only"
                                        {{ old('comparison_type', 'informational_only') == 'informational_only' ? 'selected' : '' }}>
                                        Informational Only</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Sort
                                    Order</label>
                                <input type="number" name="sort_order" placeholder="0"
                                    value="{{ old('sort_order', 0) }}" min="0"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            </div>

                            <div class="flex items-center gap-3 pt-5">
                                <label
                                    class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" name="is_required" value="1"
                                        {{ old('is_required') ? 'checked' : '' }}
                                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                                    Required for substitution
                                </label>
                            </div>

                            <div
                                class="sm:col-span-2 lg:col-span-3 rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800/40">
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Key Criteria Flags</p>
                                <div class="mt-2 grid gap-2 md:grid-cols-2">
                                    <label
                                        class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                        <input type="checkbox" name="upgrade_exceeds_value" value="1"
                                            {{ old('upgrade_exceeds_value', true) ? 'checked' : '' }}
                                            class="h-3.5 w-3.5 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                            onclick="if(this.checked) { this.form.elements['upgrade_is_below_value'].checked = false; this.form.elements['caution_if_exceeds_value'].checked = false; }">
                                        Upgrade exceeds value
                                    </label>
                                    <label
                                        class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                        <input type="checkbox" name="caution_if_below_value" value="1"
                                            {{ old('caution_if_below_value', false) ? 'checked' : '' }}
                                            class="h-3.5 w-3.5 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                            onclick="if(this.checked) { this.form.elements['upgrade_is_below_value'].checked = false; this.form.elements['caution_if_exceeds_value'].checked = false; }">
                                        Caution If Below Value
                                    </label>
                                    <label
                                        class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                        <input type="checkbox" name="upgrade_is_below_value" value="1"
                                            {{ old('upgrade_is_below_value', false) ? 'checked' : '' }}
                                            class="h-3.5 w-3.5 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                            onclick="if(this.checked) { this.form.elements['upgrade_exceeds_value'].checked = false; this.form.elements['caution_if_below_value'].checked = false; }">
                                        Upgrade Is Below Value
                                    </label>
                                    <label
                                        class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                        <input type="checkbox" name="caution_if_exceeds_value" value="1"
                                            {{ old('caution_if_exceeds_value', true) ? 'checked' : '' }}
                                            class="h-3.5 w-3.5 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                            onclick="if(this.checked) { this.form.elements['upgrade_exceeds_value'].checked = false; this.form.elements['caution_if_below_value'].checked = false; }">
                                        Caution If Exceeds Value
                                    </label>
                                </div>
                            </div>

                            <div class="sm:col-span-2 lg:col-span-3 flex justify-end gap-3">
                                <button type="button" @click="showAddKeyForm = false"
                                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600">
                                    <x-heroicon-o-check class="h-4 w-4" />
                                    Save Key
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Comparison Keys table --}}
                    <div
                        class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div
                            class="border-b border-gray-100 px-6 py-4 dark:border-gray-700 flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                                    Key Comparison Criteria
                                    <span
                                        class="ml-1 text-sm font-normal text-gray-400">({{ $comparisonKeys->count() }})</span>
                                </h2>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    These specs are used to score substitution compatibility across all
                                    {{ $selectedCategory->title }} in this category.
                                </p>
                            </div>
                            <button @click="showAddKeyForm = !showAddKeyForm"
                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 shrink-0">
                                <x-heroicon-o-plus class="h-4 w-4" />
                                Add Key
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 text-sm dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th
                                            class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Display Label</th>
                                        <th
                                            class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Importance</th>
                                        <th
                                            class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    @forelse ($comparisonKeys as $ck)
                                        {{-- View row --}}
                                        <tr x-show="editingKeyId !== {{ $ck->id }}"
                                            class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors {{ !$ck->has_specs ? 'bg-amber-50/60 dark:bg-amber-900/10' : '' }}">
                                            <td class="px-5 py-3 text-gray-800 dark:text-gray-200">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <p>{{ $ck->display_label }}</p>
                                                    @if (!$ck->has_specs)
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-400"
                                                              title="No specification data found for key '{{ $ck->spec_key }}' — the AI may have used a different key name after regeneration. Delete this entry and re-designate the correct spec as Key Comparison.">
                                                            <x-heroicon-o-exclamation-triangle class="h-3 w-3" />
                                                            Stale — no spec data
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="mt-2 grid grid-cols-2 gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                    <label class="inline-flex items-center gap-1.5">
                                                        <input type="checkbox" class="h-3 w-3 rounded border-gray-300 text-brand-500" disabled {{ $ck->upgrade_exceeds_value ? 'checked' : '' }}>
                                                        <span>Upgrade exceeds value</span>
                                                    </label>
                                                    <label class="inline-flex items-center gap-1.5">
                                                        <input type="checkbox" class="h-3 w-3 rounded border-gray-300 text-brand-500" disabled {{ $ck->caution_if_below_value ? 'checked' : '' }}>
                                                        <span>Caution if below value</span>
                                                    </label>
                                                    <label class="inline-flex items-center gap-1.5">
                                                        <input type="checkbox" class="h-3 w-3 rounded border-gray-300 text-brand-500" disabled {{ $ck->upgrade_is_below_value ? 'checked' : '' }}>
                                                        <span>Upgrade is below value</span>
                                                    </label>
                                                    <label class="inline-flex items-center gap-1.5">
                                                        <input type="checkbox" class="h-3 w-3 rounded border-gray-300 text-brand-500" disabled {{ $ck->caution_if_exceeds_value ? 'checked' : '' }}>
                                                        <span>Caution if exceeds value</span>
                                                    </label>
                                                </div>
                                            </td>
                                            <td class="px-5 py-3">
                                                <span
                                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $ck->importanceBadgeClass() }}">
                                                    {{ ucfirst($ck->importance_level) }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-3">
                                                <div class="flex items-center justify-end gap-2">
                                                    <button @click="editingKeyId = {{ $ck->id }}"
                                                        class="inline-flex items-center justify-center rounded-md p-1.5 text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-300"
                                                        title="Edit">
                                                        <x-heroicon-o-pencil-square class="h-4 w-4" />
                                                    </button>
                                                    <form method="POST"
                                                        action="{{ route('admin.maintenance-management.equipment-ai.comparison-keys.delete', $ck->id) }}"
                                                        class="inline"
                                                        onsubmit="return confirm('Delete comparison key &quot;{{ addslashes($ck->spec_key) }}&quot;?')">
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
                                        <tr x-show="editingKeyId === {{ $ck->id }}" x-cloak
                                            class="bg-blue-50/50 dark:bg-blue-900/10">
                                            <td colspan="3" class="px-5 py-4">
                                                <form method="POST"
                                                    action="{{ route('admin.maintenance-management.equipment-ai.comparison-keys.update', $ck->id) }}"
                                                    class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-7">
                                                    @csrf
                                                    @method('PUT')
                                                    <div>
                                                        <label
                                                            class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Label
                                                            *</label>
                                                        <input type="text" name="display_label"
                                                            value="{{ $ck->display_label }}" required
                                                            class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-xs focus:border-brand-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Importance</label>
                                                        <select name="importance_level"
                                                            class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-xs focus:border-brand-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                                            @foreach (['critical', 'high', 'medium', 'low'] as $lvl)
                                                                <option value="{{ $lvl }}"
                                                                    {{ $ck->importance_level == $lvl ? 'selected' : '' }}>
                                                                    {{ ucfirst($lvl) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="sm:col-span-3 lg:col-span-2">
                                                        <div class="inline-block rounded-md border border-amber-200 bg-amber-50/50 px-3 py-2.5 dark:border-amber-700/40 dark:bg-amber-900/10">
                                                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-200 mb-2">Comparison Flags</p>
                                                            <div class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                                                                <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300 cursor-pointer">
                                                                    <input type="checkbox" name="upgrade_exceeds_value" value="1"
                                                                        {{ $ck->upgrade_exceeds_value ? 'checked' : '' }}
                                                                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                                                        onclick="if(this.checked){this.form.elements['upgrade_is_below_value'].checked=false;this.form.elements['caution_if_exceeds_value'].checked=false;}">
                                                                    <span>Upgrade Exceeds Value</span>
                                                                </label>
                                                                <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300 cursor-pointer">
                                                                    <input type="checkbox" name="caution_if_below_value" value="1"
                                                                        {{ $ck->caution_if_below_value ? 'checked' : '' }}
                                                                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                                                        onclick="if(this.checked){this.form.elements['upgrade_is_below_value'].checked=false;this.form.elements['caution_if_exceeds_value'].checked=false;}">
                                                                    <span>Caution If Below Value</span>
                                                                </label>
                                                                <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300 cursor-pointer">
                                                                    <input type="checkbox" name="upgrade_is_below_value" value="1"
                                                                        {{ $ck->upgrade_is_below_value ? 'checked' : '' }}
                                                                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                                                        onclick="if(this.checked){this.form.elements['upgrade_exceeds_value'].checked=false;this.form.elements['caution_if_below_value'].checked=false;}">
                                                                    <span>Upgrade Is Below Value</span>
                                                                </label>
                                                                <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300 cursor-pointer">
                                                                    <input type="checkbox" name="caution_if_exceeds_value" value="1"
                                                                        {{ $ck->caution_if_exceeds_value ? 'checked' : '' }}
                                                                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                                                        onclick="if(this.checked){this.form.elements['upgrade_exceeds_value'].checked=false;this.form.elements['caution_if_below_value'].checked=false;}">
                                                                    <span>Caution If Exceeds Value</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-end gap-2">
                                                        <button type="submit"
                                                            class="rounded-md bg-brand-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-600">Save</button>
                                                        <button type="button" @click="editingKeyId = null"
                                                            class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700">Cancel</button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3"
                                                class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                                <x-heroicon-o-adjustments-horizontal
                                                    class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-3" />
                                                <p class="font-medium">No comparison keys defined yet for
                                                    {{ $selectedCategory->title }}.</p>
                                                <p class="mt-1 text-xs">
                                                    Check the <strong>Key Comparison</strong> checkbox on any spec row in a
                                                    Make / Model, or click <strong>Add Key</strong> above to define
                                                    manually.
                                                </p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>{{-- end comparison tab --}}

                {{-- ── Tab 3: Matrix Framework ──────────────────────────── --}}
                <div x-show="activeTab === 'matrix'" x-cloak>
                    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">

                        {{-- Header --}}
                        <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700 flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Matrix Framework</h2>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ count($matrix) }} specification(s) &nbsp;·&nbsp; {{ $matrixProfiles->count() }} profile(s) &nbsp;·&nbsp; <strong>{{ $selectedCategory->title }}</strong>
                                </p>
                            </div>
                            <a href="{{ route('admin.maintenance-management.equipment-ai.commonize.matrix', ['category_id' => $selectedCategory->id]) }}"
                               class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-purple-400 px-3 py-1.5 text-xs font-medium text-purple-700 hover:bg-purple-50 dark:border-purple-500 dark:text-purple-400 dark:hover:bg-purple-900/20">
                                <x-heroicon-o-table-cells class="h-3.5 w-3.5" />
                                Open Full Matrix
                            </a>
                        </div>

                        @if ($matrixProfiles->isEmpty())
                            <div class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                <x-heroicon-o-table-cells class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-3" />
                                <p class="font-medium">No profiles with specifications yet.</p>
                                <p class="mt-1 text-xs">Run AI research on at least one profile first.</p>
                            </div>
                        @else
                            @php
                                $keyRows     = array_filter($matrix, fn($r) => $r['is_key_comparison']);
                                $normalRows  = array_filter($matrix, fn($r) => !$r['is_key_comparison'] && !$r['is_ignored']);
                                $ignoredRows = array_filter($matrix, fn($r) => !$r['is_key_comparison'] && $r['is_ignored']);
                                $sections    = [];
                                if (!empty($keyRows))     $sections[] = ['label' => 'Key Comparison',        'type' => 'key',     'rows' => $keyRows,     'count' => count($keyRows)];
                                if (!empty($normalRows))  $sections[] = ['label' => 'Normal Specifications',  'type' => 'normal',  'rows' => $normalRows,  'count' => count($normalRows)];
                                if (!empty($ignoredRows)) $sections[] = ['label' => 'Ignored',               'type' => 'ignored', 'rows' => $ignoredRows, 'count' => count($ignoredRows)];
                            @endphp
                            <div class="max-h-[70vh] overflow-auto relative">
                                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700"
                                       style="min-width: {{ 40 + 288 + 64 + ($matrixProfiles->count() * 112) }}px; width: 100%;">
                                    {{-- Thead --}}
                                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                                        <tr>
                                            <th scope="col" class="sticky left-0 top-0 z-40 w-10 bg-gray-50 px-3 py-3 dark:bg-gray-800/60"></th>
                                            <th scope="col"
                                                class="sticky left-10 top-0 z-40 w-72 border-r border-gray-200 bg-gray-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-400">
                                                Specification Label
                                            </th>
                                            <th scope="col"
                                                class="sticky top-0 z-30 w-16 bg-gray-50 px-1 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                                                Status
                                            </th>
                                            @foreach ($matrixProfiles as $mp)
                                                <th scope="col" class="sticky top-0 z-30 w-28 bg-gray-50 px-3 py-3 text-center dark:bg-gray-800/60">
                                                    <div class="mx-auto max-w-[100px] truncate text-xs font-semibold text-gray-700 dark:text-gray-300"
                                                         title="{{ $mp->make }} {{ $mp->model }}">
                                                        {{ $mp->model ?: $mp->make }}
                                                    </div>
                                                    <div class="mx-auto mt-0.5 max-w-[100px] truncate text-[10px] font-normal text-gray-400 dark:text-gray-500">
                                                        {{ $mp->make }}
                                                    </div>
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                                        @foreach ($sections as $section)
                                            {{-- Section header row --}}
                                            <tr class="{{ $section['type'] === 'key' ? 'bg-amber-50/60 dark:bg-amber-900/10' : ($section['type'] === 'ignored' ? 'bg-gray-50/80 dark:bg-gray-800/40' : 'bg-gray-50/40 dark:bg-gray-800/20') }}">
                                                <td class="sticky left-0 z-10 w-10 px-3 py-2 {{ $section['type'] === 'key' ? 'bg-amber-50/60 dark:bg-amber-900/10' : 'bg-gray-50/80 dark:bg-gray-800/40' }}"></td>
                                                <td colspan="{{ 2 + $matrixProfiles->count() }}"
                                                    class="sticky left-10 z-10 px-4 py-2 {{ $section['type'] === 'key' ? 'bg-amber-50/60 dark:bg-amber-900/10' : 'bg-gray-50/80 dark:bg-gray-800/40' }}">
                                                    <div class="flex items-center gap-2">
                                                        @if ($section['type'] === 'key')
                                                            <svg class="h-3.5 w-3.5 text-amber-500" viewBox="0 0 24 24" fill="currentColor"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354l-4.502 2.826c-.995.608-2.23-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"/></svg>
                                                            <span class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-500">{{ $section['label'] }}</span>
                                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ $section['count'] }}</span>
                                                        @elseif ($section['type'] === 'ignored')
                                                            <svg class="h-3.5 w-3.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><line x1="5" y1="5" x2="19" y2="19" stroke-linecap="round"/></svg>
                                                            <span class="text-xs font-semibold uppercase tracking-wider text-red-400 dark:text-red-500">{{ $section['label'] }}</span>
                                                            <span class="rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-400 dark:bg-red-900/20 dark:text-red-500">{{ $section['count'] }}</span>
                                                            <span class="text-[10px] text-gray-400 dark:text-gray-600">— not sent to AI</span>
                                                        @else
                                                            <span class="h-2 w-2 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                                                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $section['label'] }}</span>
                                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $section['count'] }}</span>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>

                                            {{-- Spec rows --}}
                                            @foreach ($section['rows'] as $row)
                                                @php
                                                    $isKey     = $row['is_key_comparison'];
                                                    $isIgnored = $row['is_ignored'];
                                                    $cellBg    = $isKey ? 'bg-amber-50/40 dark:bg-amber-900/10' : 'bg-white dark:bg-gray-900';
                                                @endphp
                                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors {{ $isIgnored ? '' : '' }}">
                                                    {{-- Col 1: status icon --}}
                                                    <td class="sticky left-0 z-10 w-10 px-3 py-2.5 {{ $cellBg }}">
                                                        @if ($isKey)
                                                            <svg class="h-4 w-4 text-amber-400" viewBox="0 0 24 24" fill="currentColor"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354l-4.502 2.826c-.995.608-2.23-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"/></svg>
                                                        @elseif ($isIgnored)
                                                            <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><line x1="5" y1="5" x2="19" y2="19" stroke-linecap="round"/></svg>
                                                        @endif
                                                    </td>
                                                    {{-- Col 2: label + key --}}
                                                    <td class="sticky left-10 z-10 w-72 border-r border-gray-100 px-4 py-2.5 dark:border-gray-800 {{ $cellBg }}">
                                                        <div class="flex items-center gap-1.5 flex-wrap">
                                                            <span class="font-medium {{ $isIgnored ? 'text-gray-400 line-through dark:text-gray-600' : 'text-gray-800 dark:text-gray-200' }}">
                                                                {{ $row['spec_label'] }}
                                                            </span>
                                                            @if ($row['is_custom'])
                                                                <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">custom</span>
                                                            @endif
                                                        </div>
                                                        <div class="mt-0.5 font-mono text-[10px] {{ $isIgnored ? 'text-gray-300 dark:text-gray-700' : 'text-gray-400 dark:text-gray-500' }}">
                                                            {{ $row['spec_key'] }}
                                                        </div>
                                                    </td>
                                                    {{-- Col 3: status icon (matches full matrix visual language) --}}
                                                    <td class="w-16 px-1 py-2.5 text-center">
                                                        <div class="inline-flex items-center justify-center gap-1">
                                                            @if ($isKey)
                                                                <svg class="h-4.5 w-4.5 text-amber-400" viewBox="0 0 24 24" fill="currentColor" title="Key Comparison" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                                                            @elseif ($isIgnored)
                                                                <svg class="h-4 w-4 text-red-400 dark:text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" title="Ignored — not sent to AI"><circle cx="12" cy="12" r="9"/><line x1="5.5" y1="5.5" x2="18.5" y2="18.5" stroke-linecap="round"/></svg>
                                                            @else
                                                                <span class="h-2 w-2 rounded-full bg-gray-300 dark:bg-gray-600" title="Normal"></span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    {{-- Profile presence columns (matches full matrix icon style) --}}
                                                    @foreach ($matrixProfiles as $mp)
                                                        <td class="w-28 px-2 py-2.5 text-center">
                                                            @if ($row['presence'][$mp->id] ?? false)
                                                                <a href="{{ route('admin.maintenance-management.equipment-ai.profiles.specifications.index', $mp->unique_id) }}"
                                                                   target="_blank"
                                                                   class="inline-flex items-center justify-center text-green-500 hover:text-green-600 transition-colors"
                                                                   title="Has spec — click to view in profile">
                                                                    <svg class="mx-auto h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" /></svg>
                                                                </a>
                                                            @else
                                                                <span class="text-gray-300 dark:text-gray-700" title="Missing">
                                                                    <svg class="mx-auto h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm3 10.5a.75.75 0 000-1.5H9a.75.75 0 000 1.5h6z" /></svg>
                                                                </span>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>{{-- end matrix tab --}}

            </div>{{-- end x-data tabs --}}
        @else
            {{-- No category selected yet --}}
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                    <x-heroicon-o-cpu-chip class="h-12 w-12 text-gray-300 dark:text-gray-600" />
                    <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">Select a category above to view AI
                        profiles and comparison criteria.</p>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        Use "Scan Category" to auto-generate profiles from your existing equipment records.
                    </p>
                </div>
            </div>
        @endif

    </div>
@endsection
