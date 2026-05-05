@extends('admin.layouts.app')

@section('title', 'Update Equipment')

@section('content')
    <div class="h-screen bg-gray-50 flex flex-col overflow-hidden">
        <div class="flex-1">
            {{-- Header --}}
            <div class="bg-white border-b border-gray-200 px-6 py-4">
                <div class="max-w-5xl flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('admin.maintenance-management.equipment.index') }}"
                            class="flex items-center space-x-2 text-gray-600 hover:text-gray-800 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Back to Equipment</span>
                        </a>
                        <div class="h-6 border-l border-gray-300"></div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Equipment Details</h1>
                            <p class="text-sm text-gray-600">Edit equipment information</p>
                        </div>
                    </div>

                    <button type="submit" form="productForm" name="save_as_new" value="1"
                        class="inline-flex items-center px-5 py-2 rounded-md text-white bg-green-600 hover:bg-green-700 text-sm font-semibold shadow transition">
                        Save as New
                        <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Main Content --}}
            <div class="p-6">
                {{ html()->modelForm($equipment, 'PUT')->attributes([
                        'action' => route('admin.maintenance-management.equipment.edit', $equipment->unique_id),
                        'id' => 'productForm',
                        'autocomplete' => 'off',
                        'data-parsley-validate' => true,
                        'class' => 'max-w-5xl',
                    ])->acceptsFiles()->open() }}
                @csrf
                @method('PUT')

                {{-- Main actions --}}
                <div class="mb-6 flex justify-end gap-3">
                    <button type="submit" name="action" value="save"
                        class="inline-flex items-center px-6 py-2 rounded-md border border-blue-600 text-blue-600 bg-white hover:bg-blue-50 text-sm font-semibold shadow-sm transition">
                        Save
                    </button>

                    <button type="submit" name="action" value="save_exit"
                        class="inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700 text-sm font-semibold shadow transition">
                        Save &amp; Exit
                        <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9">
                            </path>
                        </svg>
                    </button>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-6 pt-5">
                        <nav class="-mb-px flex flex-wrap gap-6" aria-label="Equipment tabs">
                            <button type="button" data-tab-btn="overview"
                                class="tab-btn inline-flex items-center border-b-2 border-blue-600 px-1 pb-3 text-sm font-semibold text-blue-600 transition-colors">
                                Overview
                            </button>
                            <button type="button" data-tab-btn="specification"
                                class="tab-btn inline-flex items-center border-b-2 border-transparent px-1 pb-3 text-sm font-semibold text-gray-500 transition-colors hover:border-gray-300 hover:text-gray-700">
                                Specification
                            </button>
                            <button type="button" data-tab-btn="key-comparison"
                                class="tab-btn inline-flex items-center border-b-2 border-transparent px-1 pb-3 text-sm font-semibold text-gray-500 transition-colors hover:border-gray-300 hover:text-gray-700">
                                Key Comparison
                            </button>
                        </nav>
                    </div>

                    <div class="p-6 max-h-[calc(100vh-270px)] overflow-y-auto">
                        <div data-tab-panel="overview">
                            @include('admin.maintenance_management.equipment.partials._form')
                        </div>

                        <div data-tab-panel="specification" class="hidden">
                            <div class="space-y-6">

                                {{-- Section A: AI Lookup Input --}}
                                <div class="rounded-xl border border-blue-100 bg-blue-50 p-5">
                                    <div class="mb-3 flex items-center gap-2">
                                        <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span class="text-sm font-semibold text-blue-800">AI Lookup Input — what will be sent to ChatGPT</span>
                                    </div>
                                    <pre id="spec-lookup-payload" class="rounded-lg bg-white border border-blue-200 px-4 py-3 text-xs text-gray-700 font-mono overflow-x-auto whitespace-pre-wrap">{{ $equipmentLookupPayload }}</pre>
                                    <div class="mt-4 flex items-center justify-between gap-3">
                                        <span id="spec-status-msg" class="text-xs text-gray-500"></span>
                                        <button type="button" id="btn-spec-generate"
                                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700 disabled:opacity-60">
                                            <svg id="spec-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                            </svg>
                                            <svg id="spec-bolt" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            <span id="spec-btn-label">AI Generate Specifications</span>
                                        </button>
                                    </div>
                                </div>

                                {{-- Section B + C: Results table --}}
                                <div id="spec-results-wrap" class="{{ count(json_decode($existingSpecs, true) ?? []) === 0 ? 'hidden' : '' }}">
                                    <div class="mb-3 flex items-center gap-2">
                                        <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m-6 0h6" />
                                        </svg>
                                        <span class="text-sm font-semibold text-gray-700">AI Retrieved Specifications</span>
                                        <span class="text-xs text-gray-400 ml-2">Approved values become Kabba-owned data</span>
                                    </div>
                                    <div class="rounded-xl border border-gray-200 overflow-hidden">
                                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-48">Specification</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Value</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-20">Unit</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Confidence</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Source</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Status</th>
                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-48">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="spec-table-body" class="divide-y divide-gray-100 bg-white">
                                                {{-- Rows injected by JS --}}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div data-tab-panel="key-comparison" class="hidden">
                            <div class="space-y-5">

                                {{-- ── Substitution Class ─────────────────────────────────── --}}
                                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                                    <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-4">
                                        <svg class="h-4 w-4 flex-shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                        </svg>
                                        <h3 class="text-sm font-semibold text-gray-900">Substitution Class</h3>
                                    </div>
                                    <div class="px-5 py-4 space-y-2">
                                        <p class="text-xs font-semibold text-blue-600">Primary Category</p>
                                        <div class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-700">
                                            {{ optional($equipment->productCategory)->title ?? 'Uncategorised' }}
                                        </div>
                                        <p class="text-xs text-gray-500">All substitution equipment is shown from this category</p>
                                    </div>
                                </div>

                                {{-- ── Comparable Equipment ─────────────────────────────── --}}
                                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                                    <div class="border-b border-gray-100 px-5 py-4">
                                        <h3 class="text-sm font-semibold text-gray-900">Comparable Equipment</h3>
                                        <p class="mt-0.5 text-xs text-gray-500">Select equipment from the same category that can serve as substitutes</p>
                                    </div>
                                    <div class="px-5 py-4 space-y-3">
                                        <div class="flex items-center gap-2">
                                            <div class="relative flex-1">
                                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
                                                </svg>
                                                <input id="kc-eq-search" type="text" placeholder="Search equipment…"
                                                    class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                            </div>
                                            <button type="button" id="kc-eq-select-all"
                                                class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition-colors whitespace-nowrap">
                                                Select All
                                            </button>
                                            <span id="kc-eq-count"
                                                class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 whitespace-nowrap">0 selected</span>
                                        </div>

                                        <div id="kc-eq-list" class="max-h-64 overflow-y-auto divide-y divide-gray-100 rounded-lg border border-gray-200">
                                            @php
                                                $selectedSimilarEquipmentIds = array_map('strval', old('similar_equipment_ids', $defaultSimilarEquipmentIds ?? []));
                                            @endphp
                                            @forelse($similarEquipmentOptions as $eqId => $eqLabel)
                                                <label class="kc-eq-item flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors" data-label="{{ strtolower($eqLabel) }}">
                                                    <input type="checkbox" name="similar_equipment_ids[]" value="{{ $eqId }}"
                                                        class="kc-eq-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                        @if(in_array((string)$eqId, $selectedSimilarEquipmentIds)) checked @endif>
                                                    <span class="flex-1 truncate text-sm text-gray-800">{{ $eqLabel }}</span>
                                                </label>
                                            @empty
                                                <div class="px-4 py-6 text-center text-sm text-gray-500">
                                                    No equipment found in this category.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>

                                {{-- ── Critical Matching Criteria ──────────────────────── --}}
                                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900">Critical Matching Criteria</h3>
                                            <p class="mt-0.5 text-xs text-gray-500">Enable criteria and set both the threshold value and AI matching weight for each</p>
                                        </div>
                                        <button type="button" id="kc-criteria-edit-btn"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-amber-400 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-500 transition-colors">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Add / Edit
                                        </button>
                                    </div>

                                    <div id="kc-criteria-rows" class="divide-y divide-gray-100">
                                        @php
                                            $criteriaRows = [
                                                ['key' => 'engine_horsepower',        'label' => 'Engine HP',          'unit' => 'HP'],
                                                ['key' => 'operating_weight',         'label' => 'Operating Weight',   'unit' => 'lbs'],
                                                ['key' => 'tipping_load',             'label' => 'Tipping Load',       'unit' => 'lbs'],
                                                ['key' => 'rated_operating_capacity', 'label' => 'Lifting Capacity',   'unit' => 'lbs'],
                                                ['key' => 'travel_speed',             'label' => 'Travel Speed',       'unit' => 'mph'],
                                            ];
                                        @endphp

                                        @foreach($criteriaRows as $row)
                                            <div class="kc-criteria-row flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3" data-key="{{ $row['key'] }}">
                                                {{-- enable toggle --}}
                                                <button type="button" role="switch" aria-checked="false"
                                                    class="kc-criteria-toggle relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-gray-200 transition-colors duration-200 focus:outline-none">
                                                    <span class="inline-block h-4 w-4 translate-x-0 rounded-full bg-white shadow transition-transform duration-200"></span>
                                                </button>

                                                {{-- label --}}
                                                <span class="kc-criteria-label w-36 text-sm text-gray-700">{{ $row['label'] }}</span>

                                                {{-- threshold --}}
                                                <input type="number" min="0" placeholder="0"
                                                    class="kc-threshold-input w-20 rounded-md border border-gray-300 px-2 py-1.5 text-center text-sm text-gray-700 focus:border-blue-500 focus:outline-none disabled:opacity-40"
                                                    disabled>
                                                <span class="kc-criteria-unit w-8 text-xs text-gray-500">{{ $row['unit'] }}</span>

                                                {{-- weight label + slider --}}
                                                <span class="text-xs font-medium text-gray-500">Weight</span>
                                                <input type="range" min="0" max="100" value="50"
                                                    class="kc-weight-slider h-1.5 flex-1 min-w-[100px] accent-teal-400 disabled:opacity-40"
                                                    disabled>
                                                <span class="kc-weight-value w-6 text-right text-xs font-semibold text-gray-700">50</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- ── Criteria Modal ─────────────────────────────────────── --}}
                                <div id="kc-criteria-modal" class="fixed inset-0 z-[9999] hidden">
                                    <div id="kc-criteria-modal-overlay" class="absolute inset-0 bg-gray-900/45"></div>
                                    <div class="relative z-10 flex min-h-full items-center justify-center p-4">
                                        <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
                                            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-400">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                    </span>
                                                    <h3 class="text-base font-semibold text-gray-800">Manage Matching Criteria</h3>
                                                </div>
                                                <button type="button" id="kc-criteria-modal-close" class="rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>

                                            <div class="max-h-[70vh] overflow-y-auto px-6 py-4">
                                                <p class="text-xs text-gray-500">
                                                    <span class="font-semibold">CATEGORY:</span>
                                                    <span class="ml-2 font-semibold text-gray-700">{{ optional($equipment->productCategory)->title ?? 'Uncategorised' }}</span>
                                                </p>
                                                <p class="mt-1 text-xs text-gray-500">Manage the criteria library for this category. These criteria will be applied to all equipment in this category.</p>

                                                <div id="kc-modal-criteria-list" class="mt-4 space-y-2"></div>

                                                <div id="kc-modal-form" class="mt-4 hidden rounded-xl border border-teal-200 bg-teal-50/30 p-4">
                                                    <div class="grid gap-3 md:grid-cols-2">
                                                        <div>
                                                            <label class="mb-1 block text-xs font-semibold text-gray-600">Name</label>
                                                            <input type="text" id="kc-modal-name" placeholder="e.g. Engine HP"
                                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                                        </div>
                                                        <div>
                                                            <label class="mb-1 block text-xs font-semibold text-gray-600">Unit</label>
                                                            <input type="text" id="kc-modal-unit" placeholder="e.g. HP, lbs"
                                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                                        </div>
                                                    </div>

                                                    <div class="mt-3">
                                                        <p id="kc-modal-weight-label" class="text-xs font-semibold text-gray-600">Default Weight: 50</p>
                                                        <input type="range" id="kc-modal-weight" min="0" max="100" value="50"
                                                            class="mt-2 h-2 w-full accent-teal-400">
                                                    </div>

                                                    <div class="mt-3 flex items-center justify-end gap-2">
                                                        <button type="button" id="kc-modal-cancel"
                                                            class="rounded-lg px-4 py-2 text-xs font-semibold text-gray-500 hover:bg-gray-100">Cancel</button>
                                                        <button type="button" id="kc-modal-add"
                                                            class="rounded-lg bg-teal-400 px-5 py-2 text-xs font-semibold text-white hover:bg-teal-500">Add</button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between border-t border-gray-100 px-6 py-4">
                                                <button type="button" id="kc-modal-add-criterion"
                                                    class="inline-flex items-center gap-2 rounded-lg bg-teal-50 px-4 py-2 text-xs font-semibold text-teal-600 hover:bg-teal-100">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                    Add Criterion
                                                </button>
                                                <button type="button" id="kc-modal-done"
                                                    class="rounded-lg bg-slate-900 px-6 py-2 text-xs font-semibold text-white hover:bg-slate-800">Done</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- ── Bottom row: Rules + Notes ─────────────────────── --}}
                                <div class="grid gap-5 lg:grid-cols-2">

                                    {{-- Substitution Rules --}}
                                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                                        <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-4">
                                            <svg class="h-4 w-4 flex-shrink-0 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/>
                                            </svg>
                                            <h3 class="text-sm font-semibold text-gray-900">Substitution Rules</h3>
                                        </div>
                                        <div class="divide-y divide-gray-100 px-5">
                                            @foreach([
                                                ['id' => 'kc-rule-upgrades',   'label' => 'Allow Upgrades',              'default' => true],
                                                ['id' => 'kc-rule-downgrades', 'label' => 'Allow Downgrades',            'default' => false],
                                                ['id' => 'kc-rule-approval',   'label' => 'Downgrade Requires Approval', 'default' => true],
                                            ] as $rule)
                                                <div class="flex items-center justify-between py-3">
                                                    <span class="text-sm text-gray-700">{{ $rule['label'] }}</span>
                                                    <button type="button" id="{{ $rule['id'] }}" role="switch"
                                                        aria-checked="{{ $rule['default'] ? 'true' : 'false' }}"
                                                        class="kc-rule-toggle relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none {{ $rule['default'] ? 'bg-teal-400' : 'bg-gray-200' }}">
                                                        <span class="inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform duration-200 {{ $rule['default'] ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Substitution Notes --}}
                                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                                        <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-4">
                                            <svg class="h-4 w-4 flex-shrink-0 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 16c0 1.1-.9 2-2 2H5l-4 4V5a2 2 0 012-2h14a2 2 0 012 2v11z"/>
                                            </svg>
                                            <h3 class="text-sm font-semibold text-gray-900">Substitution Notes</h3>
                                        </div>
                                        <div class="px-5 py-4">
                                            <p class="mb-2 text-xs font-semibold text-gray-500">Notes</p>
                                            <textarea id="kc-substitution-notes" name="equipment_key_comparison_notes" rows="5"
                                                placeholder="e.g. Can replace any 19ft scissor lift but not suitable for indoor slab work"
                                                class="w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('equipment_key_comparison_notes') }}</textarea>
                                            <p class="mt-2 text-xs text-gray-400">Used by Kabba AI for intelligent scheduling decisions</p>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                {{ html()->form()->close() }}
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', () => {

        /* ──────────────── TAB SWITCHING ──────────────── */
        const tabButtons = Array.from(document.querySelectorAll('[data-tab-btn]'));
        const tabPanels  = Array.from(document.querySelectorAll('[data-tab-panel]'));

        function setActive(tabName) {
            tabPanels.forEach(p => p.classList.toggle('hidden', p.getAttribute('data-tab-panel') !== tabName));
            tabButtons.forEach(btn => {
                const on = btn.getAttribute('data-tab-btn') === tabName;
                btn.classList.toggle('border-blue-600', on);
                btn.classList.toggle('text-blue-600', on);
                btn.classList.toggle('border-transparent', !on);
                btn.classList.toggle('text-gray-500', !on);
            });
        }

        tabButtons.forEach(btn => btn.addEventListener('click', () => setActive(btn.getAttribute('data-tab-btn'))));
        setActive('overview');

        /* ──────────────── KEY COMPARISON ──────────────── */
        (function () {
            // ── Comparable Equipment search + select all ────────────
            const eqSearch = document.getElementById('kc-eq-search');
            const selectAllBtn = document.getElementById('kc-eq-select-all');
            const countBadge = document.getElementById('kc-eq-count');
            const eqList = document.getElementById('kc-eq-list');

            function updateCount() {
                if (!countBadge || !eqList) return;
                const checked = eqList.querySelectorAll('.kc-eq-checkbox:checked').length;
                countBadge.textContent = checked + ' selected';
            }

            eqList?.addEventListener('change', updateCount);

            eqSearch?.addEventListener('input', function () {
                const q = this.value.toLowerCase();
                eqList?.querySelectorAll('.kc-eq-item').forEach(item => {
                    const match = !q || (item.dataset.label ?? '').includes(q);
                    item.style.display = match ? '' : 'none';
                });
            });

            let allSelected = false;
            selectAllBtn?.addEventListener('click', function () {
                allSelected = !allSelected;
                eqList?.querySelectorAll('.kc-eq-item').forEach(item => {
                    if (item.style.display === 'none') return;
                    const cb = item.querySelector('.kc-eq-checkbox');
                    if (cb) cb.checked = allSelected;
                });
                this.textContent = allSelected ? 'Deselect All' : 'Select All';
                updateCount();
            });

            updateCount();

            // ── Criteria row toggles + sliders ──────────────────────
            function wireCriteriaRow(row) {
                const toggle = row.querySelector('.kc-criteria-toggle');
                const threshold = row.querySelector('.kc-threshold-input');
                const slider = row.querySelector('.kc-weight-slider');
                const weightVal = row.querySelector('.kc-weight-value');

                function setRowEnabled(enabled) {
                    toggle?.setAttribute('aria-checked', enabled ? 'true' : 'false');
                    toggle?.classList.toggle('bg-teal-400', enabled);
                    toggle?.classList.toggle('bg-gray-200', !enabled);
                    const thumb = toggle?.querySelector('span');
                    if (thumb) {
                        thumb.classList.toggle('translate-x-4', enabled);
                        thumb.classList.toggle('translate-x-0', !enabled);
                    }
                    if (threshold) threshold.disabled = !enabled;
                    if (slider) slider.disabled = !enabled;
                }

                toggle?.addEventListener('click', function () {
                    const current = this.getAttribute('aria-checked') === 'true';
                    setRowEnabled(!current);
                });

                slider?.addEventListener('input', function () {
                    if (weightVal) weightVal.textContent = this.value;
                });
            }

            document.querySelectorAll('.kc-criteria-row').forEach(wireCriteriaRow);

            // ── Manage Criteria Modal ───────────────────────────────
            const criteriaEditBtn = document.getElementById('kc-criteria-edit-btn');
            const criteriaRowsWrap = document.getElementById('kc-criteria-rows');
            const criteriaModal = document.getElementById('kc-criteria-modal');
            const criteriaModalOverlay = document.getElementById('kc-criteria-modal-overlay');
            const criteriaModalClose = document.getElementById('kc-criteria-modal-close');
            const criteriaModalDone = document.getElementById('kc-modal-done');
            const criteriaModalList = document.getElementById('kc-modal-criteria-list');
            const criteriaModalForm = document.getElementById('kc-modal-form');
            const criteriaModalShowFormBtn = document.getElementById('kc-modal-add-criterion');
            const criteriaModalCancel = document.getElementById('kc-modal-cancel');
            const criteriaModalSave = document.getElementById('kc-modal-add');
            const criteriaModalName = document.getElementById('kc-modal-name');
            const criteriaModalUnit = document.getElementById('kc-modal-unit');
            const criteriaModalWeight = document.getElementById('kc-modal-weight');
            const criteriaModalWeightLabel = document.getElementById('kc-modal-weight-label');

            const CRITERIA_LIST_URL = @json(route('admin.maintenance-management.equipment.critical-matching-criteria.index', $equipment->unique_id));
            const CRITERIA_STORE_URL = @json(route('admin.maintenance-management.equipment.critical-matching-criteria.store', $equipment->unique_id));
            const CRITERIA_BASE_URL = @json(route('admin.maintenance-management.equipment.critical-matching-criteria.index', $equipment->unique_id));
            const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            let editingCriteriaIndex = null;
            let criteriaLibrary = [];

            function criteriaKeyFromName(name) {
                return String(name || '')
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/^_+|_+$/g, '') || 'criteria';
            }

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function readCriteriaLibraryFromRows() {
                criteriaLibrary = Array.from(document.querySelectorAll('.kc-criteria-row')).map((row) => {
                    return {
                        id: null,
                        key: row.dataset.key || 'criteria',
                        name: row.querySelector('.kc-criteria-label')?.textContent?.trim() || 'Criteria',
                        unit: row.querySelector('.kc-criteria-unit')?.textContent?.trim() || '',
                        defaultWeight: Number(row.querySelector('.kc-weight-slider')?.value || 50),
                    };
                });
            }

            function renderCriteriaRowsFromLibrary() {
                if (!criteriaRowsWrap) return;

                criteriaRowsWrap.innerHTML = criteriaLibrary.map((item) => {
                    const key = item.key || criteriaKeyFromName(item.name);
                    const safeName = escapeHtml(item.name || 'Criteria');
                    const safeUnit = escapeHtml(item.unit || '');
                    const weight = Number(item.defaultWeight ?? 50);

                    return `
                        <div class="kc-criteria-row flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3" data-key="${key}">
                            <button type="button" role="switch" aria-checked="false"
                                class="kc-criteria-toggle relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-gray-200 transition-colors duration-200 focus:outline-none">
                                <span class="inline-block h-4 w-4 translate-x-0 rounded-full bg-white shadow transition-transform duration-200"></span>
                            </button>
                            <span class="kc-criteria-label w-36 text-sm text-gray-700">${safeName}</span>
                            <input type="number" min="0" placeholder="0"
                                class="kc-threshold-input w-20 rounded-md border border-gray-300 px-2 py-1.5 text-center text-sm text-gray-700 focus:border-blue-500 focus:outline-none disabled:opacity-40"
                                disabled>
                            <span class="kc-criteria-unit w-8 text-xs text-gray-500">${safeUnit}</span>
                            <span class="text-xs font-medium text-gray-500">Weight</span>
                            <input type="range" min="0" max="100" value="${weight}"
                                class="kc-weight-slider h-1.5 flex-1 min-w-[100px] accent-teal-400 disabled:opacity-40"
                                disabled>
                            <span class="kc-weight-value w-6 text-right text-xs font-semibold text-gray-700">${weight}</span>
                        </div>
                    `;
                }).join('');

                criteriaRowsWrap.querySelectorAll('.kc-criteria-row').forEach(wireCriteriaRow);
            }

            function renderModalCriteriaList() {
                if (!criteriaModalList) return;

                criteriaModalList.innerHTML = criteriaLibrary.map((item, index) => {
                    const safeName = escapeHtml(item.name || 'Criteria');
                    const safeUnit = escapeHtml(item.unit || '-');
                    const weight = Number(item.defaultWeight ?? 50);

                    return `
                        <div class="flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-3 hover:bg-gray-50 transition-colors">
                            <button type="button" data-criteria-index="${index}" class="flex-1 text-left">
                                <p class="text-sm font-semibold text-gray-800">${safeName}</p>
                                <p class="mt-0.5 text-xs text-gray-500">Unit: ${safeUnit} | Default Weight: ${weight}</p>
                            </button>
                            ${item.id ? `<button type="button" data-delete-index="${index}" class="rounded-md border border-red-200 px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>` : ''}
                        </div>
                    `;
                }).join('');
            }

            async function loadCriteriaFromServer() {
                try {
                    const res = await fetch(CRITERIA_LIST_URL, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await res.json();

                    if (!res.ok || !data.success) {
                        return false;
                    }

                    criteriaLibrary = (data.items || []).map(item => ({
                        id: item.id,
                        key: item.key,
                        name: item.name,
                        unit: item.unit || '',
                        defaultWeight: Number(item.default_weight ?? 50),
                    }));

                    return true;
                } catch (error) {
                    return false;
                }
            }

            function resetCriteriaForm() {
                editingCriteriaIndex = null;
                if (criteriaModalName) criteriaModalName.value = '';
                if (criteriaModalUnit) criteriaModalUnit.value = '';
                if (criteriaModalWeight) criteriaModalWeight.value = '50';
                if (criteriaModalWeightLabel) criteriaModalWeightLabel.textContent = 'Default Weight: 50';
                if (criteriaModalSave) criteriaModalSave.textContent = 'Add';
                criteriaModalForm?.classList.add('hidden');
            }

            async function openCriteriaModal() {
                const loaded = await loadCriteriaFromServer();
                if (!loaded) {
                    readCriteriaLibraryFromRows();
                }
                renderModalCriteriaList();
                resetCriteriaForm();
                criteriaModal?.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeCriteriaModal() {
                criteriaModal?.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            criteriaEditBtn?.addEventListener('click', openCriteriaModal);
            criteriaModalOverlay?.addEventListener('click', closeCriteriaModal);
            criteriaModalClose?.addEventListener('click', closeCriteriaModal);
            criteriaModalDone?.addEventListener('click', () => {
                renderCriteriaRowsFromLibrary();
                closeCriteriaModal();
            });

            criteriaModalShowFormBtn?.addEventListener('click', () => {
                resetCriteriaForm();
                criteriaModalForm?.classList.remove('hidden');
                criteriaModalName?.focus();
            });

            criteriaModalCancel?.addEventListener('click', resetCriteriaForm);

            criteriaModalWeight?.addEventListener('input', function () {
                if (criteriaModalWeightLabel) {
                    criteriaModalWeightLabel.textContent = `Default Weight: ${this.value}`;
                }
            });

            criteriaModalList?.addEventListener('click', async function (event) {
                const deleteBtn = event.target.closest('[data-delete-index]');
                if (deleteBtn) {
                    const idx = Number(deleteBtn.dataset.deleteIndex);
                    const item = criteriaLibrary[idx];
                    if (!item?.id) return;

                    const res = await fetch(`${CRITERIA_BASE_URL}/${item.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                        },
                    });
                    const data = await res.json();

                    if (res.ok && data.success) {
                        criteriaLibrary.splice(idx, 1);
                        renderModalCriteriaList();
                    }
                    return;
                }

                const btn = event.target.closest('[data-criteria-index]');
                if (!btn) return;

                const idx = Number(btn.dataset.criteriaIndex);
                const item = criteriaLibrary[idx];
                if (!item) return;

                editingCriteriaIndex = idx;
                if (criteriaModalName) criteriaModalName.value = item.name || '';
                if (criteriaModalUnit) criteriaModalUnit.value = item.unit || '';
                if (criteriaModalWeight) criteriaModalWeight.value = String(item.defaultWeight ?? 50);
                if (criteriaModalWeightLabel) criteriaModalWeightLabel.textContent = `Default Weight: ${item.defaultWeight ?? 50}`;
                if (criteriaModalSave) criteriaModalSave.textContent = 'Save';
                criteriaModalForm?.classList.remove('hidden');
            });

            criteriaModalSave?.addEventListener('click', async () => {
                const name = (criteriaModalName?.value || '').trim();
                const unit = (criteriaModalUnit?.value || '').trim();
                const defaultWeight = Number(criteriaModalWeight?.value || 50);

                if (!name) {
                    criteriaModalName?.focus();
                    return;
                }

                const payload = {
                    name,
                    unit,
                    default_weight: defaultWeight,
                };

                if (editingCriteriaIndex === null) {
                    const res = await fetch(CRITERIA_STORE_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });
                    const data = await res.json();

                    if (res.ok && data.success && data.item) {
                        criteriaLibrary.push({
                            id: data.item.id,
                            key: data.item.key,
                            name: data.item.name,
                            unit: data.item.unit || '',
                            defaultWeight: Number(data.item.default_weight ?? 50),
                        });
                    }
                } else {
                    const item = criteriaLibrary[editingCriteriaIndex];
                    if (!item?.id) return;

                    const res = await fetch(`${CRITERIA_BASE_URL}/${item.id}`, {
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });
                    const data = await res.json();

                    if (res.ok && data.success && data.item) {
                        criteriaLibrary[editingCriteriaIndex] = {
                            id: data.item.id,
                            key: data.item.key,
                            name: data.item.name,
                            unit: data.item.unit || '',
                            defaultWeight: Number(data.item.default_weight ?? 50),
                        };
                    }
                }

                renderModalCriteriaList();
                resetCriteriaForm();
            });

            // ── Rule toggles ────────────────────────────────────────
            document.querySelectorAll('.kc-rule-toggle').forEach(btn => {
                btn.addEventListener('click', function () {
                    const on = this.getAttribute('aria-checked') !== 'true';
                    this.setAttribute('aria-checked', on ? 'true' : 'false');
                    this.classList.toggle('bg-teal-400', on);
                    this.classList.toggle('bg-gray-200', !on);
                    const thumb = this.querySelector('span');
                    if (thumb) {
                        thumb.classList.toggle('translate-x-5', on);
                        thumb.classList.toggle('translate-x-0', !on);
                    }
                });
            });
        })();

        /* ──────────────── SPECIFICATION AI ──────────────── */
        const GENERATE_URL     = @json($specGenerateUrl);
        const APPROVE_BASE_URL = @json($specApproveBaseUrl);
        const CSRF             = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        let specState = @json(json_decode($existingSpecs)); // array of spec objects

        const tbody       = document.getElementById('spec-table-body');
        const resultsWrap = document.getElementById('spec-results-wrap');
        const genBtn      = document.getElementById('btn-spec-generate');
        const btnLabel    = document.getElementById('spec-btn-label');
        const spinner     = document.getElementById('spec-spinner');
        const bolt        = document.getElementById('spec-bolt');
        const statusMsg   = document.getElementById('spec-status-msg');
        const lookupPayloadEl = document.getElementById('spec-lookup-payload');

        function valueOf(selector) {
            const el = document.querySelector(selector);
            return el ? String(el.value ?? '').trim() : '';
        }

        function selectedText(selector) {
            const el = document.querySelector(selector);
            if (!el || el.selectedIndex < 0) return '';
            return String(el.options[el.selectedIndex]?.text ?? '').trim();
        }

        function buildLookupPayload() {
            return {
                brand: valueOf('[name="brand"]'),
                model: valueOf('[name="model"]'),
                model_year: valueOf('[name="model_year"]'),
                category: selectedText('[name="product_category_id"]') === 'Select Category' ? '' : selectedText('[name="product_category_id"]'),
                equipment_name: valueOf('[name="equipment_name"]'),
                equipment_id: valueOf('[name="equipment_id"]'),
                serial_number: valueOf('[name="serial_number"]') || null,
                vin: valueOf('[name="vehicle_identification_number"]') || null,
            };
        }

        function refreshLookupPayloadPreview() {
            if (!lookupPayloadEl) return;
            lookupPayloadEl.textContent = JSON.stringify(buildLookupPayload(), null, 2);
        }

        /* ---- helpers ---- */
        function confidenceColor(score) {
            if (score === null || score === undefined) return 'text-gray-400';
            if (score >= 0.85) return 'text-green-600';
            if (score >= 0.6)  return 'text-yellow-600';
            return 'text-red-500';
        }

        function confidenceLabel(score) {
            if (score === null || score === undefined) return '—';
            return Math.round(score * 100) + '%';
        }

        function escHtml(str) {
            if (!str) return '';
            return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        /* ---- render all rows ---- */
        function renderSpecs(specs) {
            tbody.innerHTML = '';
            specs.forEach(spec => renderRow(spec));
            resultsWrap.classList.toggle('hidden', specs.length === 0);
        }

        /* ---- render single row (or replace) ---- */
        function renderRow(spec) {
            const existing = document.getElementById('spec-row-' + spec.id);
            const row = buildRow(spec);
            if (existing) {
                existing.replaceWith(row);
            } else {
                tbody.appendChild(row);
            }
        }

        function buildRow(spec) {
            const tr = document.createElement('tr');
            tr.id = 'spec-row-' + spec.id;
            tr.className = spec.is_approved ? 'bg-green-50' : '';

            const approvedBadge = spec.is_approved
                ? `<span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                       <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z" clip-rule="evenodd"/></svg>
                       Approved</span>`
                : `<span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">Pending</span>`;

            const overrideBadge = spec.is_manual_override
                ? `<span class="ml-1 inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">Manual</span>`
                : '';

            const sourceCell = spec.source_url
                ? `<a href="${escHtml(spec.source_url)}" target="_blank" rel="noopener" class="text-blue-600 hover:underline truncate block max-w-xs text-xs">${escHtml(spec.source_url.replace(/^https?:\/\//, ''))}</a>`
                : '<span class="text-gray-400 text-xs">—</span>';

            const approvedMeta = spec.is_approved && spec.approved_by_name
                ? `<div class="text-xs text-gray-400 mt-0.5">by ${escHtml(spec.approved_by_name)} · ${escHtml(spec.approved_at ?? '')}</div>`
                : '';

            // action buttons – hide Approve if already approved
            const approveBtn = spec.is_approved
                ? ''
                : `<button type="button" data-action="approve" data-id="${spec.id}"
                       class="inline-flex items-center gap-1 rounded-md bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700 transition-colors">
                       <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                       Approve
                   </button>`;

            tr.innerHTML = `
                <td class="px-4 py-3 font-medium text-gray-800 align-top">${escHtml(spec.spec_label)}</td>
                <td class="px-4 py-3 align-top">
                    <span class="spec-display-value">${escHtml(spec.value ?? '—')}</span>
                    <div class="spec-edit-row hidden mt-1 flex gap-2">
                        <input type="text" class="spec-value-input rounded-md border border-gray-300 px-2 py-1 text-xs w-32 focus:ring-2 focus:ring-blue-500" value="${escHtml(spec.value ?? '')}" placeholder="value">
                        <input type="text" class="spec-unit-input rounded-md border border-gray-300 px-2 py-1 text-xs w-20 focus:ring-2 focus:ring-blue-500" value="${escHtml(spec.unit ?? '')}" placeholder="unit">
                        <input type="text" class="spec-source-input rounded-md border border-gray-300 px-2 py-1 text-xs w-48 focus:ring-2 focus:ring-blue-500" value="${escHtml(spec.source_url ?? '')}" placeholder="source URL">
                        <button type="button" data-action="save" data-id="${spec.id}" class="rounded-md bg-blue-600 px-3 py-1 text-xs font-semibold text-white hover:bg-blue-700">Save</button>
                        <button type="button" data-action="cancel-edit" data-id="${spec.id}" class="rounded-md border border-gray-300 px-3 py-1 text-xs text-gray-600 hover:bg-gray-50">Cancel</button>
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-600 align-top">
                    <span class="spec-display-unit">${escHtml(spec.unit ?? '—')}</span>
                </td>
                <td class="px-4 py-3 align-top">
                    <span class="${confidenceColor(spec.confidence_score)} font-semibold text-xs">${confidenceLabel(spec.confidence_score)}</span>
                    ${spec.last_verified_at ? `<div class="text-xs text-gray-400">${escHtml(spec.last_verified_at)}</div>` : ''}
                </td>
                <td class="px-4 py-3 align-top">${sourceCell}</td>
                <td class="px-4 py-3 align-top">
                    ${approvedBadge}${overrideBadge}
                    ${approvedMeta}
                </td>
                <td class="px-4 py-3 align-top text-right">
                    <div class="inline-flex items-center gap-1.5 flex-wrap justify-end">
                        ${approveBtn}
                        <button type="button" data-action="edit" data-id="${spec.id}"
                            class="inline-flex items-center gap-1 rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit
                        </button>
                        <button type="button" data-action="retry" data-id="${spec.id}" data-key="${escHtml(spec.spec_key)}"
                            class="inline-flex items-center gap-1 rounded-md border border-orange-300 px-3 py-1.5 text-xs font-semibold text-orange-600 hover:bg-orange-50 transition-colors">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Try Again
                        </button>
                    </div>
                </td>`;

            return tr;
        }

        /* ---- generate all ---- */
        async function generateSpecs(forceKey = null) {
            genBtn.disabled = true;
            spinner.classList.remove('hidden');
            bolt.classList.add('hidden');
            btnLabel.textContent = 'Generating…';
            statusMsg.textContent = '';
            statusMsg.classList.remove('text-red-500');

            const url = forceKey ? GENERATE_URL + '?force_key=' + encodeURIComponent(forceKey) : GENERATE_URL;
            const lookupPayload = buildLookupPayload();
            refreshLookupPayloadPreview();

            try {
                const res  = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ lookup_payload: lookupPayload }),
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    statusMsg.textContent = data.message ?? 'Generation failed.';
                    statusMsg.classList.add('text-red-500');
                    return;
                }

                specState = data.specs;
                renderSpecs(specState);
                statusMsg.textContent = '✓ Specifications updated.';
                statusMsg.classList.remove('text-red-500');

            } catch (err) {
                statusMsg.textContent = 'Network error: ' + err.message;
                statusMsg.classList.add('text-red-500');
            } finally {
                genBtn.disabled = false;
                spinner.classList.add('hidden');
                bolt.classList.remove('hidden');
                btnLabel.textContent = 'AI Generate Specifications';
            }
        }

        /* ---- approve ---- */
        async function approveSpec(id) {
            const btn = tbody.querySelector(`[data-action="approve"][data-id="${id}"]`);
            if (btn) btn.disabled = true;

            try {
                const res  = await fetch(`${APPROVE_BASE_URL}/${id}/approve`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) renderRow(data.spec);
            } catch (err) {
                if (btn) btn.disabled = false;
            }
        }

        /* ---- save edit ---- */
        async function saveSpec(id, row) {
            const value  = row.querySelector('.spec-value-input').value;
            const unit   = row.querySelector('.spec-unit-input').value;
            const source = row.querySelector('.spec-source-input').value;

            try {
                const res  = await fetch(`${APPROVE_BASE_URL}/${id}`, {
                    method: 'PUT',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ value, unit, source_url: source }),
                });
                const data = await res.json();
                if (data.success) renderRow(data.spec);
            } catch (err) {}
        }

        /* ---- event delegation on tbody ---- */
        tbody.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;

            const action = btn.dataset.action;
            const id     = parseInt(btn.dataset.id);
            const row    = document.getElementById('spec-row-' + id);

            if (action === 'approve') await approveSpec(id);
            if (action === 'retry')   await generateSpecs(btn.dataset.key);

            if (action === 'edit') {
                row.querySelector('.spec-display-value').classList.add('hidden');
                row.querySelector('.spec-display-unit').classList.add('hidden');
                row.querySelector('.spec-edit-row').classList.remove('hidden');
            }

            if (action === 'cancel-edit') {
                row.querySelector('.spec-display-value').classList.remove('hidden');
                row.querySelector('.spec-display-unit').classList.remove('hidden');
                row.querySelector('.spec-edit-row').classList.add('hidden');
            }

            if (action === 'save') await saveSpec(id, row);
        });

        genBtn.addEventListener('click', () => generateSpecs());

        ['input', 'change'].forEach((eventName) => {
            ['[name="brand"]', '[name="model"]', '[name="model_year"]', '[name="product_category_id"]', '[name="equipment_name"]', '[name="equipment_id"]', '[name="serial_number"]', '[name="vehicle_identification_number"]']
                .forEach((selector) => {
                    const el = document.querySelector(selector);
                    if (el) {
                        el.addEventListener(eventName, refreshLookupPayloadPreview);
                    }
                });
        });

        refreshLookupPayloadPreview();

        /* ---- initial render from server-side data ---- */
        if (specState && specState.length > 0) {
            renderSpecs(specState);
        }
    });
</script>
@endpush
