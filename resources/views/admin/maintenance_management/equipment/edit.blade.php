@extends('admin.layouts.app')

@section('title', 'Update Equipment')

@section('content')
    <div class="min-h-screen bg-gray-50">
        <div>
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

                {{-- Tabs + Actions Bar --}}
                <div class="mb-4 flex items-center justify-between gap-4">
                    {{-- Tabs on the left --}}
                    <nav class="inline-flex items-center gap-1 rounded-xl bg-gray-100 p-1" aria-label="Equipment tabs">
                        <button type="button" data-tab-btn="overview"
                            class="tab-btn inline-flex items-center rounded-lg bg-white px-4 py-1.5 text-sm font-semibold text-gray-900 shadow-sm transition-all">
                            General
                        </button>
                        <button type="button" data-tab-btn="specification"
                            class="tab-btn inline-flex items-center rounded-lg px-4 py-1.5 text-sm font-semibold text-gray-500 transition-all hover:text-gray-700">
                            Specifications
                        </button>
                        <button type="button" data-tab-btn="key-comparison"
                            class="tab-btn inline-flex items-center rounded-lg px-4 py-1.5 text-sm font-semibold text-gray-500 transition-all hover:text-gray-700">
                            Key Comparisons
                        </button>
                    </nav>

                    {{-- Action buttons on the right --}}
                    <div class="flex items-center gap-3">
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
                </div>

                {{-- Tab Panels --}}
                <div data-tab-panel="overview">
                    @include('admin.maintenance_management.equipment.partials._form')
                </div>

                <div data-tab-panel="specification" class="hidden">

                                {{-- Top bar: last update + Update Data button --}}
                                <div class="mb-4 flex items-center justify-between">
                                    <div class="flex items-center gap-1.5 text-xs text-gray-400">
                                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Last AI update: <span id="spec-last-update">{{ $equipment->specifications->max('updated_at') ? \Carbon\Carbon::parse($equipment->specifications->max('updated_at'))->format('M j, Y, g:i A') : '—' }}</span></span>
                                    </div>
                                    <button type="button" id="btn-spec-update"
                                        class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 transition-colors disabled:opacity-60">
                                        <svg id="spec-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <svg id="spec-bolt" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span id="spec-btn-label">Update Data</span>
                                    </button>
                                </div>

                                {{-- Display Cards --}}
                                <div class="space-y-5">
                                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                                        <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-100 text-red-500">
                                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.05 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.955a1 1 0 00.95.69h4.159c.969 0 1.371 1.24.588 1.81l-3.365 2.445a1 1 0 00-.364 1.118l1.286 3.955c.3.921-.755 1.688-1.54 1.118l-3.365-2.445a1 1 0 00-1.176 0L7.95 18.018c-.784.57-1.838-.197-1.539-1.118l1.286-3.955a1 1 0 00-.364-1.118L3.968 9.382c-.783-.57-.38-1.81.588-1.81h4.159a1 1 0 00.95-.69l1.286-3.955z" />
                                                    </svg>
                                                </span>
                                                <div>
                                                    <h3 class="text-sm font-semibold text-gray-900">Key Comparison Data</h3>
                                                    <p class="text-xs text-gray-500">Critical specifications used for equipment matching</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="spec-key-comparison-list" class="divide-y divide-gray-200">
                                            <div class="px-5 py-3 text-sm text-gray-500">No key comparison values available yet.</div>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                                        <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-cyan-100 text-cyan-600">
                                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7L12 3 4 7m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                    </svg>
                                                </span>
                                                <div>
                                                    <h3 class="text-sm font-semibold text-gray-900">Equipment Specifications</h3>
                                                    <p class="text-xs text-gray-500">Detailed technical data for this equipment</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 divide-y divide-gray-200 md:grid-cols-2 md:divide-x md:divide-y-0">
                                            <div id="spec-equipment-col-left" class="divide-y divide-gray-200">
                                                <div class="px-5 py-3 text-sm text-gray-500">No specification values available yet.</div>
                                            </div>
                                            <div id="spec-equipment-col-right" class="divide-y divide-gray-200"></div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Bottom disclaimer --}}
                                <p class="mt-3 flex items-center gap-1.5 text-xs text-gray-400">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Specification data is sourced via AI and may require verification
                                </p>

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
                                        <div class="grid gap-4 md:grid-cols-4">
                                            <div>
                                                <p class="text-xs font-semibold text-blue-600">Primary Category</p>
                                                <div class="mt-1 inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-700">
                                                    {{ optional($equipment->productCategory)->title ?? 'Uncategorised' }}
                                                </div>
                                            </div>

                                            <div>
                                                <p class="text-xs font-semibold text-gray-600">Equipment Name</p>
                                                <p class="mt-2 text-sm font-medium text-gray-900">{{ $equipment->equipment_name ?: '-' }}</p>
                                            </div>

                                            <div>
                                                <p class="text-xs font-semibold text-gray-600">Brand</p>
                                                <p class="mt-2 text-sm font-medium text-gray-900">{{ $equipment->brand ?: '-' }}</p>
                                            </div>

                                            <div>
                                                <p class="text-xs font-semibold text-gray-600">Model</p>
                                                <p class="mt-2 text-sm font-medium text-gray-900">{{ $equipment->model ?: '-' }}</p>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500">All substitution equipment is shown from this category</p>
                                    </div>
                                </div>

                                {{-- ── Product Assignment (Layout First) ───────────────── --}}
                                @php
                                    $productAssignmentOptions = $productAssignmentOptions ?? [];
                                    $selectedAssignedProductId = (string) old('assigned_product_id', $equipment->assigned_product_id ?? '');
                                @endphp
                                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900">Direct Assignment To Product(s)</h3>
                                            <p class="mt-0.5 text-xs text-gray-500">Choose one product from the same category for this equipment</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">1 Product Assignment</span>
                                    </div>

                                    <div class="px-5 py-4">
                                        <label for="assigned_product_id" class="mb-1 block text-xs font-semibold text-gray-600">Assigned Product</label>
                                        <select name="assigned_product_id" id="assigned_product_id"
                                            class="choices-select w-full md:w-80 px-2 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white border-gray-300">
                                            <option value="">Select Product</option>
                                            @foreach($productAssignmentOptions as $product)
                                                <option value="{{ $product['id'] }}" {{ (string) $product['id'] === $selectedAssignedProductId ? 'selected' : '' }}>
                                                    {{ $product['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- ── Comparable Equipment ─────────────────────────────── --}}
                                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                                    <div class="border-b border-gray-100 px-5 py-4">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <h3 class="text-sm font-semibold text-gray-900">Comparable Equipment</h3>
                                            <div class="flex items-center gap-2">
                                                <button type="button" id="kc-eq-select-all"
                                                    class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition-colors whitespace-nowrap">
                                                    Select All
                                                </button>
                                                <span id="kc-eq-count"
                                                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 whitespace-nowrap">0 selected</span>
                                            </div>
                                        </div>
                                        <p class="mt-0.5 text-xs text-gray-500">Select equipment from the same category that can serve as substitutes</p>
                                    </div>
                                    <div class="px-5 py-4 space-y-3">

                                        {{-- table --}}
                                        <div class="rounded-lg border border-gray-200 overflow-hidden">
                                            <div class="overflow-x-auto">
                                                <table class="w-full table-fixed">
                                                    <colgroup>
                                                        <col class="w-12">
                                                        <col>
                                                        <col class="w-40">
                                                        <col class="w-40">
                                                    </colgroup>
                                                    <thead class="bg-gray-50">
                                                        <tr class="border-b border-gray-200 text-xs font-semibold text-gray-500">
                                                            <th scope="col" class="px-4 py-2 text-left"></th>
                                                            <th scope="col" class="px-2 py-2 text-left">Equipment Name</th>
                                                            <th scope="col" class="px-2 py-2 text-left">Brand</th>
                                                            <th scope="col" class="px-2 py-2 text-left">Model</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="kc-eq-list" class="divide-y divide-gray-100 bg-white">
                                            @php
                                                $selectedSimilarEquipmentIds = array_map(
                                                    'strval',
                                                    old('similar_equipment_ids', $equipment->similar_equipment_ids ?? ($defaultSimilarEquipmentIds ?? []))
                                                );
                                                $criteriaState = old('critical_matching_criteria', $equipment->critical_matching_criteria ?? []);
                                                $criteriaRows = $criteriaRows->sortByDesc(function ($row) use ($criteriaState) {
                                                    return filter_var(($criteriaState[$row->criteria_key]['enabled'] ?? true), FILTER_VALIDATE_BOOLEAN);
                                                })->values();
                                            @endphp
                                            @forelse($similarEquipmentOptions as $eqId => $eqOption)
                                                @php
                                                    $eqLabel = is_array($eqOption) ? ($eqOption['label'] ?? '') : $eqOption;
                                                    $eqBrand = is_array($eqOption) ? ($eqOption['brand'] ?? null) : null;
                                                    $eqModel = is_array($eqOption) ? ($eqOption['model'] ?? null) : null;
                                                @endphp
                                                <tr class="kc-eq-item hover:bg-gray-50 transition-colors" data-label="{{ strtolower($eqLabel) }}">
                                                    <td class="px-4 py-3 align-middle">
                                                        <input type="checkbox" name="similar_equipment_ids[]" value="{{ $eqId }}"
                                                            class="kc-eq-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                            @if(in_array((string)$eqId, $selectedSimilarEquipmentIds)) checked @endif>
                                                    </td>
                                                    <td class="px-2 py-3 align-middle text-sm text-gray-800">{{ $eqLabel }}</td>
                                                    <td class="px-2 py-3 align-middle text-xs text-gray-500">{{ $eqBrand ?: '' }}</td>
                                                    <td class="px-2 py-3 align-middle text-xs text-gray-500">{{ $eqModel ?: '' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">
                                                        No equipment found in this category.
                                                    </td>
                                                </tr>
                                            @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>{{-- end wrapper --}}
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
                                        @forelse($criteriaRows as $row)
                                            @php
                                                $rowState = $criteriaState[$row->criteria_key] ?? [];
                                                $rowEnabled = true;
                                                $rowThreshold = $rowState['threshold'] ?? '';
                                                $rowWeight = isset($rowState['weight']) ? (int) $rowState['weight'] : (int) $row->default_weight;
                                                $rowUpgradeExceeds = filter_var($rowState['upgrade_exceeds_value'] ?? $row->upgrade_exceeds_value ?? true, FILTER_VALIDATE_BOOLEAN);
                                                $rowCautionIfChange = filter_var($rowState['caution_if_change_value'] ?? $row->caution_if_change_value ?? true, FILTER_VALIDATE_BOOLEAN);
                                                $rowUpgradeBelow = filter_var($rowState['upgrade_is_below_value'] ?? $row->upgrade_is_below_value ?? false, FILTER_VALIDATE_BOOLEAN);
                                                $rowCautionBelow = filter_var($rowState['caution_if_below_value'] ?? $row->caution_if_below_value ?? false, FILTER_VALIDATE_BOOLEAN);
                                            @endphp
                                            <div class="kc-criteria-row flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3" data-key="{{ $row->criteria_key }}">
                                                <input type="hidden" class="kc-hidden-enabled" name="critical_matching_criteria[{{ $row->criteria_key }}][enabled]" value="1">
                                                <input type="hidden" class="kc-hidden-threshold" name="critical_matching_criteria[{{ $row->criteria_key }}][threshold]" value="{{ $rowThreshold }}">
                                                <input type="hidden" class="kc-hidden-weight" name="critical_matching_criteria[{{ $row->criteria_key }}][weight]" value="{{ $rowWeight }}">
                                                {{-- enable toggle --}}
                                                <button type="button" role="switch" aria-checked="true" style="display:none;"
                                                    class="kc-criteria-toggle relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none bg-teal-400">
                                                    <span class="inline-block h-4 w-4 rounded-full bg-white shadow transition-transform duration-200 translate-x-4"></span>
                                                </button>

                                                {{-- label --}}
                                                <span class="kc-criteria-label w-36 text-sm text-gray-700">{{ $row->name }}</span>

                                                {{-- weight label + slider --}}
                                                <span class="text-xs font-medium text-gray-500">Weight</span>
                                                <input type="range" min="0" max="100" value="{{ $rowWeight }}"
                                                    class="kc-weight-slider h-1.5 flex-1 min-w-[100px] accent-teal-400 disabled:opacity-40"
                                                    disabled>
                                                <span class="kc-weight-value w-6 text-right text-xs font-semibold text-gray-700">{{ $rowWeight }}</span>

                                                {{-- per-criteria flags --}}
                                                <div class="w-full pt-1 space-y-2">
                                                    <div class="grid gap-2 md:grid-cols-2">
                                                        <label class="flex items-center gap-2 cursor-pointer select-none">
                                                            <input type="hidden" name="critical_matching_criteria[{{ $row->criteria_key }}][upgrade_exceeds_value]" value="0">
                                                            <input type="checkbox" name="critical_matching_criteria[{{ $row->criteria_key }}][upgrade_exceeds_value]" value="1"
                                                                data-row="1"
                                                                class="kc-row-exclusive h-3.5 w-3.5 rounded border-gray-300 text-teal-500 focus:ring-teal-400"
                                                                {{ $rowUpgradeExceeds ? 'checked' : '' }} disabled>
                                                            <span class="text-xs text-gray-600">Upgrade exceeds value</span>
                                                        </label>
                                                        <label class="flex items-center gap-2 cursor-pointer select-none">
                                                            <input type="hidden" name="critical_matching_criteria[{{ $row->criteria_key }}][caution_if_below_value]" value="0">
                                                            <input type="checkbox" name="critical_matching_criteria[{{ $row->criteria_key }}][caution_if_below_value]" value="1"
                                                                data-row="1"
                                                                class="kc-row-exclusive h-3.5 w-3.5 rounded border-gray-300 text-teal-500 focus:ring-teal-400"
                                                                {{ $rowCautionBelow ? 'checked' : '' }} disabled>
                                                            <span class="text-xs text-gray-600">Caution if below value</span>
                                                        </label>
                                                    </div>
                                                    <div class="grid gap-2 md:grid-cols-2">
                                                        <label class="flex items-center gap-2 cursor-pointer select-none">
                                                            <input type="hidden" name="critical_matching_criteria[{{ $row->criteria_key }}][upgrade_is_below_value]" value="0">
                                                            <input type="checkbox" name="critical_matching_criteria[{{ $row->criteria_key }}][upgrade_is_below_value]" value="1"
                                                                data-row="2"
                                                                class="kc-row-exclusive h-3.5 w-3.5 rounded border-gray-300 text-teal-500 focus:ring-teal-400"
                                                                {{ $rowUpgradeBelow ? 'checked' : '' }} disabled>
                                                            <span class="text-xs text-gray-600">Upgrade is below value</span>
                                                        </label>
                                                        <label class="flex items-center gap-2 cursor-pointer select-none">
                                                            <input type="hidden" name="critical_matching_criteria[{{ $row->criteria_key }}][caution_if_change_value]" value="0">
                                                            <input type="checkbox" name="critical_matching_criteria[{{ $row->criteria_key }}][caution_if_change_value]" value="1"
                                                                data-row="2"
                                                                class="kc-row-exclusive h-3.5 w-3.5 rounded border-gray-300 text-teal-500 focus:ring-teal-400"
                                                                {{ $rowCautionIfChange ? 'checked' : '' }} disabled>
                                                            <span class="text-xs text-gray-600">Caution if exceeds value</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="px-5 py-6 text-center text-sm text-gray-400">No active criteria found for this category.</div>
                                        @endforelse
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

                                                    <div class="mt-3 space-y-2">
                                                        <div class="grid gap-2 md:grid-cols-2">
                                                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                                                <input type="checkbox" id="kc-modal-upgrade-exceeds" data-row="1"
                                                                    class="kc-modal-row-exclusive h-3.5 w-3.5 rounded border-gray-300 text-teal-500 focus:ring-teal-400">
                                                                <span class="text-xs text-gray-600">Upgrade exceeds value</span>
                                                            </label>
                                                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                                                <input type="checkbox" id="kc-modal-caution-below" data-row="1"
                                                                    class="kc-modal-row-exclusive h-3.5 w-3.5 rounded border-gray-300 text-teal-500 focus:ring-teal-400">
                                                                <span class="text-xs text-gray-600">Caution if below value</span>
                                                            </label>
                                                        </div>
                                                        <div class="grid gap-2 md:grid-cols-2">
                                                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                                                <input type="checkbox" id="kc-modal-upgrade-below" data-row="2"
                                                                    class="kc-modal-row-exclusive h-3.5 w-3.5 rounded border-gray-300 text-teal-500 focus:ring-teal-400">
                                                                <span class="text-xs text-gray-600">Upgrade is below value</span>
                                                            </label>
                                                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                                                <input type="checkbox" id="kc-modal-caution-if-change" data-row="2"
                                                                    class="kc-modal-row-exclusive h-3.5 w-3.5 rounded border-gray-300 text-teal-500 focus:ring-teal-400">
                                                                <span class="text-xs text-gray-600">Caution if exceeds value</span>
                                                            </label>
                                                        </div>
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
                                                [
                                                    'id' => 'kc-rule-upgrades',
                                                    'label' => 'Allow Upgrades',
                                                    'name' => 'allow_upgrades',
                                                    'value' => filter_var(old('allow_upgrades', $equipment->allow_upgrades ?? true), FILTER_VALIDATE_BOOLEAN),
                                                ],
                                                [
                                                    'id' => 'kc-rule-downgrades',
                                                    'label' => 'Allow Downgrades',
                                                    'name' => 'allow_downgrades',
                                                    'value' => filter_var(old('allow_downgrades', $equipment->allow_downgrades ?? false), FILTER_VALIDATE_BOOLEAN),
                                                ],
                                                [
                                                    'id' => 'kc-rule-approval',
                                                    'label' => 'Downgrade Requires Approval',
                                                    'name' => 'downgrade_requires_approval',
                                                    'value' => filter_var(old('downgrade_requires_approval', $equipment->downgrade_requires_approval ?? true), FILTER_VALIDATE_BOOLEAN),
                                                ],
                                            ] as $rule)
                                                <div class="flex items-center justify-between py-3">
                                                    <span class="text-sm text-gray-700">{{ $rule['label'] }}</span>
                                                    <input type="hidden" class="kc-rule-hidden-input" name="{{ $rule['name'] }}" value="{{ $rule['value'] ? '1' : '0' }}">
                                                    <button type="button" id="{{ $rule['id'] }}" role="switch"
                                                        aria-checked="{{ $rule['value'] ? 'true' : 'false' }}"
                                                        class="kc-rule-toggle relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none {{ $rule['value'] ? 'bg-teal-400' : 'bg-gray-200' }}">
                                                        <span class="inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform duration-200 {{ $rule['value'] ? 'translate-x-5' : 'translate-x-0' }}"></span>
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
                                                class="w-full resize-none rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('equipment_key_comparison_notes', $equipment->equipment_key_comparison_notes) }}</textarea>
                                            <p class="mt-2 text-xs text-gray-400">Used by Kabba AI for intelligent scheduling decisions</p>
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
                btn.classList.toggle('bg-white', on);
                btn.classList.toggle('text-gray-900', on);
                btn.classList.toggle('shadow-sm', on);
                btn.classList.toggle('text-gray-500', !on);
            });
        }

        tabButtons.forEach(btn => btn.addEventListener('click', () => setActive(btn.getAttribute('data-tab-btn'))));
        setActive('overview');

        /* ──────────────── KEY COMPARISON ──────────────── */
        (function () {
            // ── Comparable Equipment select all ─────────────────────
            const selectAllBtn = document.getElementById('kc-eq-select-all');
            const countBadge = document.getElementById('kc-eq-count');
            const eqList = document.getElementById('kc-eq-list');

            function updateCount() {
                if (!countBadge || !eqList) return;
                const checked = eqList.querySelectorAll('.kc-eq-checkbox:checked').length;
                countBadge.textContent = checked + ' selected';
            }

            eqList?.addEventListener('change', updateCount);

            let allSelected = false;
            selectAllBtn?.addEventListener('click', function () {
                allSelected = !allSelected;
                eqList?.querySelectorAll('.kc-eq-item').forEach(item => {
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
                const hiddenEnabled = row.querySelector('.kc-hidden-enabled');
                const hiddenThreshold = row.querySelector('.kc-hidden-threshold');
                const hiddenWeight = row.querySelector('.kc-hidden-weight');

                function syncHiddenValues() {
                    if (hiddenEnabled) hiddenEnabled.value = '1';
                    if (hiddenThreshold && threshold) hiddenThreshold.value = threshold.value;
                    if (hiddenWeight && slider) hiddenWeight.value = slider.value;
                }

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
                    syncHiddenValues();
                }

                toggle?.addEventListener('click', function () {
                    const current = this.getAttribute('aria-checked') === 'true';
                    setRowEnabled(!current);
                });

                slider?.addEventListener('input', function () {
                    if (weightVal) weightVal.textContent = this.value;
                    syncHiddenValues();
                });

                threshold?.addEventListener('input', function () {
                    syncHiddenValues();
                });

                if (slider && weightVal) {
                    weightVal.textContent = slider.value;
                }
                setRowEnabled(true);
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
            const criteriaModalUpgradeExceeds = document.getElementById('kc-modal-upgrade-exceeds');
            const criteriaModalCautionIfChange = document.getElementById('kc-modal-caution-if-change');
            const criteriaModalUpgradeBelow = document.getElementById('kc-modal-upgrade-below');
            const criteriaModalCautionBelow = document.getElementById('kc-modal-caution-below');

            const CRITERIA_LIST_URL = @json(route('admin.maintenance-management.equipment.critical-matching-criteria.index', $equipment->unique_id));
            const CRITERIA_STORE_URL = @json(route('admin.maintenance-management.equipment.critical-matching-criteria.store', $equipment->unique_id));
            const CRITERIA_BASE_URL = @json(route('admin.maintenance-management.equipment.critical-matching-criteria.index', $equipment->unique_id));
            const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const categorySelect = document.getElementById('product_category_id');

            let editingCriteriaIndex = null;
            let criteriaLibrary = [];

            function currentCategoryId() {
                const value = String(categorySelect?.value ?? '').trim();
                return value !== '' ? value : null;
            }

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
                        upgradeExceedsValue: row.querySelector('input[type="checkbox"][name*="[upgrade_exceeds_value]"]')?.checked ?? true,
                        cautionIfChangeValue: row.querySelector('input[type="checkbox"][name*="[caution_if_change_value]"]')?.checked ?? false,
                        upgradeIsBelowValue: row.querySelector('input[type="checkbox"][name*="[upgrade_is_below_value]"]')?.checked ?? false,
                        cautionIfBelowValue: row.querySelector('input[type="checkbox"][name*="[caution_if_below_value]"]')?.checked ?? false,
                    };
                });
            }

            function renderCriteriaRowsFromLibrary() {
                if (!criteriaRowsWrap) return;

                if (!criteriaLibrary.length) {
                    criteriaRowsWrap.innerHTML = '<div class="px-5 py-6 text-center text-sm text-gray-400">No active criteria found for this category.</div>';
                    renderKeyComparisonPreview();
                    return;
                }

                const currentStateByKey = {};
                criteriaRowsWrap.querySelectorAll('.kc-criteria-row').forEach((row) => {
                    const key = row.dataset.key;
                    if (!key) return;
                    currentStateByKey[key] = {
                        enabled: true,
                        threshold: row.querySelector('.kc-hidden-threshold')?.value ?? '',
                        weight: row.querySelector('.kc-hidden-weight')?.value ?? row.querySelector('.kc-weight-slider')?.value ?? '50',
                        upgradeExceeds: row.querySelector('input[type="checkbox"][name*="[upgrade_exceeds_value]"]')?.checked ?? true,
                        cautionIfChange: row.querySelector('input[type="checkbox"][name*="[caution_if_change_value]"]')?.checked ?? false,
                        upgradeBelow: row.querySelector('input[type="checkbox"][name*="[upgrade_is_below_value]"]')?.checked ?? false,
                        cautionBelow: row.querySelector('input[type="checkbox"][name*="[caution_if_below_value]"]')?.checked ?? false,
                    };
                });

                criteriaRowsWrap.innerHTML = criteriaLibrary.map((item) => {
                    const key = item.key || criteriaKeyFromName(item.name);
                    const safeName = escapeHtml(item.name || 'Criteria');
                    const safeUnit = escapeHtml(item.unit || '');
                    const state = currentStateByKey[key] || {};
                    const enabled = true;
                    const threshold = String(state.threshold ?? '');
                    const weight = Number(state.weight ?? item.defaultWeight ?? 50);
                    const upgradeExceeds = key in currentStateByKey ? Boolean(state.upgradeExceeds) : (item.upgradeExceedsValue !== false);
                    const cautionIfChange = key in currentStateByKey ? Boolean(state.cautionIfChange) : (item.cautionIfChangeValue === true);
                    const upgradeBelow = key in currentStateByKey ? Boolean(state.upgradeBelow) : (item.upgradeIsBelowValue === true);
                    const cautionBelow = key in currentStateByKey ? Boolean(state.cautionBelow) : (item.cautionIfBelowValue === true);

                    return `
                        <div class="kc-criteria-row flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3" data-key="${key}">
                            <input type="hidden" class="kc-hidden-enabled" name="critical_matching_criteria[${key}][enabled]" value="1">
                            <input type="hidden" class="kc-hidden-threshold" name="critical_matching_criteria[${key}][threshold]" value="${escapeHtml(threshold)}">
                            <input type="hidden" class="kc-hidden-weight" name="critical_matching_criteria[${key}][weight]" value="${weight}">
                            <button type="button" role="switch" aria-checked="true" style="display:none;"
                                class="kc-criteria-toggle relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-teal-400 transition-colors duration-200 focus:outline-none">
                                <span class="inline-block h-4 w-4 translate-x-4 rounded-full bg-white shadow transition-transform duration-200"></span>
                            </button>
                            <span class="kc-criteria-label w-36 text-sm text-gray-700">${safeName}</span>
                            <input type="number" min="0" placeholder="0" value="${escapeHtml(threshold)}"
                                class="kc-threshold-input w-20 rounded-md border border-gray-300 px-2 py-1.5 text-center text-sm text-gray-700 focus:border-blue-500 focus:outline-none disabled:opacity-40">
                            <span class="kc-criteria-unit w-8 text-xs text-gray-500">${safeUnit}</span>
                            <span class="text-xs font-medium text-gray-500">Weight</span>
                            <input type="range" min="0" max="100" value="${weight}"
                                class="kc-weight-slider h-1.5 flex-1 min-w-[100px] accent-teal-400 opacity-60 cursor-not-allowed pointer-events-none" tabindex="-1" disabled readonly>
                            <span class="kc-weight-value w-6 text-right text-xs font-semibold text-gray-700">${weight}</span>
                            <div class="w-full pt-1 space-y-2">
                                <div class="grid gap-2 md:grid-cols-2">
                                    <input type="hidden" name="critical_matching_criteria[${key}][upgrade_exceeds_value]" value="${upgradeExceeds ? '1' : '0'}">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-3.5 w-3.5 items-center justify-center rounded ${upgradeExceeds ? 'bg-teal-500' : 'bg-gray-300'} text-white">
                                            ${upgradeExceeds ? '<svg class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.415 0l-3-3a1 1 0 111.415-1.42l2.293 2.295 6.543-6.545a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>' : ''}
                                        </span>
                                        <span class="text-xs text-gray-600">Upgrade exceeds value</span>
                                    </div>
                                    <input type="hidden" name="critical_matching_criteria[${key}][caution_if_below_value]" value="${cautionBelow ? '1' : '0'}">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-3.5 w-3.5 items-center justify-center rounded ${cautionBelow ? 'bg-teal-500' : 'bg-gray-300'} text-white">
                                            ${cautionBelow ? '<svg class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.415 0l-3-3a1 1 0 111.415-1.42l2.293 2.295 6.543-6.545a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>' : ''}
                                        </span>
                                        <span class="text-xs text-gray-600">Caution if below value</span>
                                    </div>
                                </div>
                                <div class="grid gap-2 md:grid-cols-2">
                                    <input type="hidden" name="critical_matching_criteria[${key}][upgrade_is_below_value]" value="${upgradeBelow ? '1' : '0'}">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-3.5 w-3.5 items-center justify-center rounded ${upgradeBelow ? 'bg-teal-500' : 'bg-gray-300'} text-white">
                                            ${upgradeBelow ? '<svg class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.415 0l-3-3a1 1 0 111.415-1.42l2.293 2.295 6.543-6.545a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>' : ''}
                                        </span>
                                        <span class="text-xs text-gray-600">Upgrade is below value</span>
                                    </div>
                                    <input type="hidden" name="critical_matching_criteria[${key}][caution_if_change_value]" value="${cautionIfChange ? '1' : '0'}">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-3.5 w-3.5 items-center justify-center rounded ${cautionIfChange ? 'bg-teal-500' : 'bg-gray-300'} text-white">
                                            ${cautionIfChange ? '<svg class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.415 0l-3-3a1 1 0 111.415-1.42l2.293 2.295 6.543-6.545a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>' : ''}
                                        </span>
                                        <span class="text-xs text-gray-600">Caution if exceeds value</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');

                criteriaRowsWrap.querySelectorAll('.kc-criteria-row').forEach(wireCriteriaRow);
                renderKeyComparisonPreview();
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
                const categoryId = currentCategoryId();

                if (!categoryId) {
                    criteriaLibrary = [];
                    return true;
                }

                try {
                    const url = new URL(CRITERIA_LIST_URL, window.location.origin);
                    url.searchParams.set('category_id', categoryId);

                    const res = await fetch(url.toString(), {
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
                        upgradeExceedsValue: item.upgrade_exceeds_value !== false,
                        cautionIfChangeValue: item.caution_if_change_value === true,
                        upgradeIsBelowValue: item.upgrade_is_below_value === true,
                        cautionIfBelowValue: item.caution_if_below_value === true,
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
                if (criteriaModalUpgradeExceeds) criteriaModalUpgradeExceeds.checked = true;
                if (criteriaModalCautionIfChange) criteriaModalCautionIfChange.checked = false;
                if (criteriaModalUpgradeBelow) criteriaModalUpgradeBelow.checked = false;
                if (criteriaModalCautionBelow) criteriaModalCautionBelow.checked = false;
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
                    const categoryId = currentCategoryId();
                    if (!categoryId) return;

                    const res = await fetch(`${CRITERIA_BASE_URL}/${item.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ category_id: categoryId }),
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
                if (criteriaModalUpgradeExceeds) criteriaModalUpgradeExceeds.checked = item.upgradeExceedsValue !== false;
                if (criteriaModalCautionIfChange) criteriaModalCautionIfChange.checked = item.cautionIfChangeValue === true;
                if (criteriaModalUpgradeBelow) criteriaModalUpgradeBelow.checked = item.upgradeIsBelowValue === true;
                if (criteriaModalCautionBelow) criteriaModalCautionBelow.checked = item.cautionIfBelowValue === true;
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
                    category_id: currentCategoryId(),
                    name,
                    unit,
                    default_weight: defaultWeight,
                    upgrade_exceeds_value: criteriaModalUpgradeExceeds?.checked ?? true,
                    caution_if_change_value: criteriaModalCautionIfChange?.checked ?? false,
                    upgrade_is_below_value: criteriaModalUpgradeBelow?.checked ?? false,
                    caution_if_below_value: criteriaModalCautionBelow?.checked ?? false,
                };

                if (!payload.category_id) {
                    categorySelect?.focus();
                    return;
                }

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
                            upgradeExceedsValue: data.item.upgrade_exceeds_value !== false,
                            cautionIfChangeValue: data.item.caution_if_change_value === true,
                            upgradeIsBelowValue: data.item.upgrade_is_below_value === true,
                            cautionIfBelowValue: data.item.caution_if_below_value === true,
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
                            upgradeExceedsValue: data.item.upgrade_exceeds_value !== false,
                            cautionIfChangeValue: data.item.caution_if_change_value === true,
                            upgradeIsBelowValue: data.item.upgrade_is_below_value === true,
                            cautionIfBelowValue: data.item.caution_if_below_value === true,
                        };
                    }
                }

                renderModalCriteriaList();
                resetCriteriaForm();
            });

            categorySelect?.addEventListener('change', async () => {
                const loaded = await loadCriteriaFromServer();

                if (!loaded) {
                    criteriaLibrary = [];
                }

                renderCriteriaRowsFromLibrary();

                if (!criteriaModal?.classList.contains('hidden')) {
                    renderModalCriteriaList();
                }
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
                    const hiddenInput = this.parentElement?.querySelector('.kc-rule-hidden-input');
                    if (hiddenInput) {
                        hiddenInput.value = on ? '1' : '0';
                    }
                });
            });

            document?.addEventListener('change', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLInputElement)) return;
                if (!target.classList.contains('kc-modal-row-exclusive')) return;
                if (!target.checked) return;

                const row = target.dataset.row;
                if (!row) return;

                const currentRow = Number(row);
                const otherRow = currentRow === 1 ? 2 : 1;

                // If this checkbox is being checked, uncheck all in the other row
                document.querySelectorAll(`.kc-modal-row-exclusive[data-row="${otherRow}"]`).forEach((checkbox) => {
                    checkbox.checked = false;
                });
            });
        })();

        /* ──────────────── SPECIFICATION AI ──────────────── */
        const GENERATE_URL     = @json($specGenerateUrl);
        const APPROVE_BASE_URL = @json($specApproveBaseUrl);
        const CSRF             = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        let specState = @json(json_decode($existingSpecs) ?? []); // array of spec objects

        const genBtn      = document.getElementById('btn-spec-update');
        const btnLabel    = document.getElementById('spec-btn-label');
        const spinner     = document.getElementById('spec-spinner');
        const bolt        = document.getElementById('spec-bolt');
        const lastUpdateEl = document.getElementById('spec-last-update');
        const keyComparisonPreview = document.getElementById('spec-key-comparison-list');
        const equipmentSpecsColLeft = document.getElementById('spec-equipment-col-left');
        const equipmentSpecsColRight = document.getElementById('spec-equipment-col-right');

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

        function escHtml(str) {
            if (!str) return '';
            return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function fmtSpecValue(value, unit = '') {
            const cleanedValue = String(value ?? '').trim();
            const cleanedUnit = String(unit ?? '').trim();
            if (!cleanedValue) return '<span class="text-gray-400">-</span>';
            if (!cleanedUnit) return `<span class="font-semibold text-gray-800">${escHtml(cleanedValue)}</span>`;
            return `<span class="font-semibold text-gray-800">${escHtml(cleanedValue)}</span> <span class="ml-1 text-xs text-gray-400">${escHtml(cleanedUnit)}</span>`;
        }

        function renderKeyComparisonPreview() {
            if (!keyComparisonPreview) return;

            const rows = Array.from(document.querySelectorAll('.kc-criteria-row')).map((row) => {
                const label = row.querySelector('.kc-criteria-label')?.textContent?.trim() || '';
                const threshold = row.querySelector('.kc-hidden-threshold')?.value ?? row.querySelector('.kc-threshold-input')?.value ?? '';
                const unit = row.querySelector('.kc-criteria-unit')?.textContent?.trim() || '';
                const enabled = row.querySelector('.kc-hidden-enabled')?.value === '1';
                return { label, threshold, unit, enabled };
            }).filter((item) => item.enabled && String(item.threshold ?? '').trim() !== '');

            if (rows.length === 0) {
                keyComparisonPreview.innerHTML = '<div class="px-5 py-3 text-sm text-gray-500">No key comparison values available yet.</div>';
                return;
            }

            keyComparisonPreview.innerHTML = rows.map((item) => {
                return `
                    <div class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <span class="text-gray-700">${escHtml(item.label)}</span>
                        <span>${fmtSpecValue(item.threshold, item.unit)}</span>
                    </div>
                `;
            }).join('');
        }

        function renderEquipmentSpecsPreview(specs) {
            if (!equipmentSpecsColLeft || !equipmentSpecsColRight) return;

            const cleanedSpecs = (specs || []).filter((spec) => String(spec?.value ?? '').trim() !== '');
            if (cleanedSpecs.length === 0) {
                equipmentSpecsColLeft.innerHTML = '<div class="px-5 py-3 text-sm text-gray-500">No specification values available yet.</div>';
                equipmentSpecsColRight.innerHTML = '';
                return;
            }

            const midpoint = Math.ceil(cleanedSpecs.length / 2);
            const leftRows = cleanedSpecs.slice(0, midpoint);
            const rightRows = cleanedSpecs.slice(midpoint);

            const renderRows = (items) => {
                return items.map((spec) => {
                    return `
                        <div class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <span class="text-gray-600">${escHtml(spec.spec_label || '-')}</span>
                            <span class="text-right">${fmtSpecValue(spec.value, spec.unit)}</span>
                        </div>
                    `;
                }).join('');
            };

            equipmentSpecsColLeft.innerHTML = renderRows(leftRows);
            equipmentSpecsColRight.innerHTML = rightRows.length ? renderRows(rightRows) : '';
        }

        function applyGeneratedCriteria(criteriaState) {
            if (!criteriaState || typeof criteriaState !== 'object') return;

            document.querySelectorAll('.kc-criteria-row').forEach((row) => {
                const key = row.dataset.key;
                if (!key || !Object.prototype.hasOwnProperty.call(criteriaState, key)) return;

                const nextState = criteriaState[key] || {};
                const hiddenThreshold = row.querySelector('.kc-hidden-threshold');
                const thresholdInput = row.querySelector('.kc-threshold-input');
                const hiddenWeight = row.querySelector('.kc-hidden-weight');
                const weightSlider = row.querySelector('.kc-weight-slider');
                const weightValue = row.querySelector('.kc-weight-value');

                if (hiddenThreshold) hiddenThreshold.value = nextState.threshold ?? '';
                if (thresholdInput) thresholdInput.value = nextState.threshold ?? '';

                if (hiddenWeight && nextState.weight !== undefined) hiddenWeight.value = nextState.weight;
                if (weightSlider && nextState.weight !== undefined) weightSlider.value = nextState.weight;
                if (weightValue && nextState.weight !== undefined) weightValue.textContent = String(nextState.weight);
            });

            renderKeyComparisonPreview();
        }

        /* ---- generate all ---- */
        async function generateSpecs() {
            if (genBtn) genBtn.disabled = true;
            spinner?.classList.remove('hidden');
            bolt?.classList.add('hidden');
            if (btnLabel) btnLabel.textContent = 'Updating…';

            const lookupPayload = buildLookupPayload();

            try {
                const res  = await fetch(GENERATE_URL, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ lookup_payload: lookupPayload }),
                });
                const data = await res.json();

                if (!res.ok || !data.success) return;

                specState = data.specs;
                renderEquipmentSpecsPreview(specState);
                applyGeneratedCriteria(data.criteria || {});
                if (lastUpdateEl) {
                    const now = new Date();
                    lastUpdateEl.textContent = now.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
                }

            } catch (err) {
                // silent fail — data unchanged
            } finally {
                if (genBtn) genBtn.disabled = false;
                spinner?.classList.add('hidden');
                bolt?.classList.remove('hidden');
                if (btnLabel) btnLabel.textContent = 'Update Data';
            }
        }

        genBtn?.addEventListener('click', () => generateSpecs());

        renderKeyComparisonPreview();
        renderEquipmentSpecsPreview(specState || []);

        document.addEventListener('input', (event) => {
            if (event.target.closest('.kc-threshold-input')) {
                renderKeyComparisonPreview();
            }
        });

        document.addEventListener('click', (event) => {
            if (event.target.closest('.kc-criteria-toggle') || event.target.closest('#kc-modal-done')) {
                requestAnimationFrame(renderKeyComparisonPreview);
            }
        });

        /* ---- initial render from server-side data ---- */
        renderEquipmentSpecsPreview(specState || []);
    });
</script>
@endpush
