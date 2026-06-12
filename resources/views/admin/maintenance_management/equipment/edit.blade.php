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
                    <div class="space-y-5">
                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div
                                class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">Matching AI Profile</h3>
                                    <p class="mt-0.5 text-xs text-gray-500">Specifications are shown from the profile that
                                        matches this equipment's category, brand, and model.</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                        {{ optional($equipment->productCategory)->title ?? 'Uncategorised' }}
                                    </span>
                                    @if ($matchingAiProfile)
                                        <span
                                            class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                            {{ $matchingAiProfile->make }} {{ $matchingAiProfile->model }}
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                            No matching profile found
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="px-5 py-4 text-sm text-gray-600">
                                @if ($matchingAiProfile)
                                    <div class="grid gap-4 md:grid-cols-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Profile
                                            </p>
                                            <p class="mt-1 font-medium text-gray-900">{{ $matchingAiProfile->unique_id }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Make</p>
                                            <p class="mt-1 font-medium text-gray-900">{{ $matchingAiProfile->make }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Model</p>
                                            <p class="mt-1 font-medium text-gray-900">{{ $matchingAiProfile->model }}</p>
                                        </div>
                                    </div>
                                @else
                                    <p>No AI profile was found for this equipment yet. Run the AI scan/refresh flow after
                                        the brand and model are set to populate this tab.</p>
                                @endif
                            </div>
                        </div>

                        @if ($matchingAiProfile && $matchingAiProfile->specifications->isNotEmpty())
                            @php
                                $specSections = [
                                    [
                                        'title' => 'Key Criteria',
                                        'description' => 'Critical comparison fields highlighted for this profile.',
                                        'items' => $keyCriteriaSpecs,
                                        'row_class' => 'bg-amber-50/70 text-amber-800',
                                    ],
                                    [
                                        'title' => 'Common Specifications',
                                        'description' =>
                                            'Category-based specs shared across similar equipment profiles.',
                                        'items' => $commonSpecs,
                                        'row_class' => 'bg-sky-50/70 text-sky-800',
                                    ],
                                    [
                                        'title' => 'Unique Specifications',
                                        'description' =>
                                            'Profile-specific specs that are not part of the shared category catalog.',
                                        'items' => $uniqueSpecs,
                                        'row_class' => 'bg-emerald-50/70 text-emerald-800',
                                    ],
                                ];
                            @endphp

                            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                                <div class="border-b border-gray-100 px-6 py-4">
                                    <h2 class="text-base font-semibold text-gray-900">
                                        Specifications
                                        <span
                                            class="ml-1 text-sm font-normal text-gray-400">({{ $matchingAiProfile->specifications->count() }})</span>
                                    </h2>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th
                                                    class="w-32 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Key Comparison</th>
                                                <th
                                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Label</th>
                                                <th
                                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Value</th>
                                                <th
                                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Source</th>
                                                <th
                                                    class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Confidence</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            @foreach ($specSections as $section)
                                                @continue($section['items']->isEmpty())

                                                <tr class="{{ $section['row_class'] }}">
                                                    <td colspan="5" class="px-5 py-3">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <div>
                                                                <h3 class="text-sm font-semibold">{{ $section['title'] }}
                                                                </h3>
                                                                <p class="mt-0.5 text-xs opacity-80">
                                                                    {{ $section['description'] }}</p>
                                                            </div>
                                                            <span
                                                                class="shrink-0 rounded-full bg-white/70 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                                                {{ $section['items']->count() }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                </tr>

                                                @foreach ($section['items'] as $spec)
                                                    @php
                                                        $criteriaFlags = $comparisonKeySettingsBySpecKey[
                                                            trim((string) $spec->spec_key)
                                                        ] ?? [
                                                            'upgrade_exceeds_value' => true,
                                                            'caution_if_exceeds_value' => true,
                                                            'upgrade_is_below_value' => false,
                                                            'caution_if_below_value' => false,
                                                        ];
                                                    @endphp
                                                    <tr
                                                        class="{{ $spec->is_key_comparison ? 'bg-amber-50/40' : '' }} hover:bg-gray-50 transition-colors">
                                                        <td class="px-5 py-3 text-center">
                                                            <span
                                                                class="inline-flex h-4 w-4 items-center justify-center rounded border border-gray-300 bg-white text-[10px] font-semibold {{ $spec->is_key_comparison ? 'text-amber-600' : 'text-gray-300' }}">
                                                                {{ $spec->is_key_comparison ? '✓' : '—' }}
                                                            </span>
                                                        </td>
                                                        <td class="px-5 py-3 text-gray-800">
                                                            {{ $spec->spec_label }}
                                                            @if ($spec->is_key_comparison)
                                                                <span
                                                                    class="ml-1.5 inline-flex items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-700">key</span>
                                                                <div
                                                                    class="mt-2 grid grid-cols-2 gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                                    <label class="inline-flex items-center gap-1.5">
                                                                        <input type="checkbox"
                                                                            class="h-3 w-3 rounded border-gray-300 text-amber-500"
                                                                            disabled
                                                                            {{ $criteriaFlags['upgrade_exceeds_value'] ? 'checked' : '' }}>
                                                                        <span>Upgrade exceeds value</span>
                                                                    </label>
                                                                    <label class="inline-flex items-center gap-1.5">
                                                                        <input type="checkbox"
                                                                            class="h-3 w-3 rounded border-gray-300 text-amber-500"
                                                                            disabled
                                                                            {{ $criteriaFlags['caution_if_exceeds_value'] ? 'checked' : '' }}>
                                                                        <span>Caution if exceeds value</span>
                                                                    </label>
                                                                    <label class="inline-flex items-center gap-1.5">
                                                                        <input type="checkbox"
                                                                            class="h-3 w-3 rounded border-gray-300 text-amber-500"
                                                                            disabled
                                                                            {{ $criteriaFlags['upgrade_is_below_value'] ? 'checked' : '' }}>
                                                                        <span>Upgrade is below value</span>
                                                                    </label>
                                                                    <label class="inline-flex items-center gap-1.5">
                                                                        <input type="checkbox"
                                                                            class="h-3 w-3 rounded border-gray-300 text-amber-500"
                                                                            disabled
                                                                            {{ $criteriaFlags['caution_if_below_value'] ? 'checked' : '' }}>
                                                                        <span>Caution if below value</span>
                                                                    </label>
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td class="px-5 py-3 font-medium text-gray-900">
                                                            {{ $spec->spec_value ?? '—' }}
                                                            @if ($spec->spec_unit)
                                                                <span class="font-normal text-gray-400">{{ $spec->spec_unit }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-5 py-3 text-gray-500">{{ $spec->source ?? '—' }}
                                                        </td>
                                                        <td class="px-5 py-3 text-center">
                                                            @php $pct = round(($spec->confidence_score ?? 1) * 100); @endphp
                                                            <span
                                                                class="text-xs {{ $pct >= 80 ? 'text-green-600' : ($pct >= 50 ? 'text-yellow-600' : 'text-red-500') }}">{{ $pct }}%</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @elseif ($matchingAiProfile)
                            <div
                                class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center text-sm text-gray-500">
                                The matching AI profile exists, but it does not have any specifications yet.
                            </div>
                        @endif
                    </div>
                </div>

                <div data-tab-panel="key-comparison" class="hidden">
                    <div class="space-y-5">

                        {{-- ── Substitution Class ─────────────────────────────────── --}}
                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-4">
                                <svg class="h-4 w-4 flex-shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                <h3 class="text-sm font-semibold text-gray-900">Substitution Class</h3>
                            </div>
                            <div class="px-5 py-4 space-y-2">
                                <div class="grid gap-4 md:grid-cols-4">
                                    <div>
                                        <p class="text-xs font-semibold text-blue-600">Primary Category</p>
                                        <div
                                            class="mt-1 inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-700">
                                            {{ optional($equipment->productCategory)->title ?? 'Uncategorised' }}
                                        </div>
                                    </div>

                                    <div>
                                        <p class="text-xs font-semibold text-gray-600">Equipment Name</p>
                                        <p class="mt-2 text-sm font-medium text-gray-900">
                                            {{ $equipment->equipment_name ?: '-' }}</p>
                                    </div>

                                    <div>
                                        <p class="text-xs font-semibold text-gray-600">Brand</p>
                                        <p class="mt-2 text-sm font-medium text-gray-900">{{ $equipment->brand ?: '-' }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs font-semibold text-gray-600">Model</p>
                                        <p class="mt-2 text-sm font-medium text-gray-900">{{ $equipment->model ?: '-' }}
                                        </p>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500">All substitution equipment is shown from this category</p>
                            </div>
                        </div>

                        {{-- ── Product Assignment (Layout First) ───────────────── --}}
                        @php
                            $productAssignmentOptions = $productAssignmentOptions ?? [];
                            $selectedAssignedProductId = (string) old(
                                'assigned_product_id',
                                $equipment->assigned_product_id ?? '',
                            );
                        @endphp
                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">Direct Assignment To Product(s)</h3>
                                    <p class="mt-0.5 text-xs text-gray-500">Choose one product from the same category for
                                        this equipment</p>
                                </div>
                                <span
                                    class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">1
                                    Product Assignment</span>
                            </div>

                            <div class="px-5 py-4">
                                <label for="assigned_product_id"
                                    class="mb-1 block text-xs font-semibold text-gray-600">Assigned Product</label>
                                <select name="assigned_product_id" id="assigned_product_id"
                                    class="choices-select w-full md:w-80 px-2 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white border-gray-300">
                                    <option value="">Select Product</option>
                                    @foreach ($productAssignmentOptions as $product)
                                        <option value="{{ $product['id'] }}"
                                            {{ (string) $product['id'] === $selectedAssignedProductId ? 'selected' : '' }}>
                                            {{ $product['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- ── Comparable AI Profiles ────────────────────────────── --}}
                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-100 px-5 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="text-sm font-semibold text-gray-900">Comparable AI Profiles</h3>
                                    <div class="flex items-center gap-2">
                                        <button type="button" id="kc-profile-select-all"
                                            class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition-colors whitespace-nowrap">
                                            Select All
                                        </button>
                                        <span id="kc-profile-count"
                                            class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 whitespace-nowrap">0
                                            selected</span>
                                    </div>
                                </div>
                                <p class="mt-0.5 text-xs text-gray-500">AI profiles from the same category that can serve
                                    as comparable substitution references.</p>
                            </div>
                            <div class="px-5 py-4">
                                <div class="rounded-lg border border-gray-200 overflow-hidden">
                                    <div class="overflow-x-auto">
                                        <table class="w-full table-fixed">
                                            <colgroup>
                                                <col class="w-12">
                                                <col>
                                                <col class="w-44">
                                                <col class="w-44">
                                                <col class="w-28">
                                                <col class="w-28">
                                                <col class="w-32">
                                            </colgroup>
                                            <thead class="bg-gray-50">
                                                <tr class="border-b border-gray-200 text-xs font-semibold text-gray-500">
                                                    <th scope="col" class="px-4 py-2 text-left"></th>
                                                    <th scope="col" class="px-4 py-2 text-left">Profile</th>
                                                    <th scope="col" class="px-2 py-2 text-left">Make</th>
                                                    <th scope="col" class="px-2 py-2 text-left">Model</th>
                                                    <th scope="col" class="px-2 py-2 text-left">Specs</th>
                                                    <th scope="col" class="px-2 py-2 text-left">Key Specs</th>
                                                    <th scope="col" class="px-2 py-2 text-left">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 bg-white">
                                                @forelse ($comparableAiProfiles as $profile)
                                                    <tr class="hover:bg-gray-50 transition-colors">
                                                        <td class="px-4 py-3 text-center align-middle">
                                                            <input type="checkbox" name="comparable_ai_profile_ids[]"
                                                                value="{{ $profile->id }}" @checked(in_array((string) $profile->id, $selectedComparableAiProfileIds, true))
                                                                class="kc-profile-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <div class="min-w-0">
                                                                <p class="truncate text-sm font-semibold text-gray-900">
                                                                    {{ $profile->unique_id }}</p>
                                                                <p class="truncate text-xs text-gray-500">
                                                                    {{ $profile->make }} {{ $profile->model }}</p>
                                                            </div>
                                                        </td>
                                                        <td class="px-2 py-3 text-sm text-gray-700">
                                                            {{ $profile->make ?: '—' }}</td>
                                                        <td class="px-2 py-3 text-sm text-gray-700">
                                                            {{ $profile->model ?: '—' }}</td>
                                                        <td class="px-2 py-3 text-sm text-gray-700">
                                                            {{ $profile->specifications_count }}</td>
                                                        <td class="px-2 py-3 text-sm text-gray-700">
                                                            {{ $profile->key_specifications_count }}</td>
                                                        <td class="px-2 py-3">
                                                            <span
                                                                class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                                                {{ $profile->statusLabel() }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="7"
                                                            class="px-6 py-10 text-center text-sm text-gray-400">
                                                            No comparable AI profiles found for this category.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ── Critical Matching Criteria ──────────────────────── --}}
                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">Critical Matching Criteria</h3>
                                    <p class="mt-0.5 text-xs text-gray-500">Enable criteria and set both the threshold
                                        value and AI matching weight for each</p>
                                </div>
                            </div>

                            @if ($keyCriteriaSpecs->isNotEmpty())
                                <div class="border-b border-gray-100">
                                    <div class="px-5 py-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">AI Key
                                            Comparison Criteria</p>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th
                                                        class="w-32 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                        Key Comparison</th>
                                                    <th
                                                        class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                        Label
                                                    </th>
                                                    <th
                                                        class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                        Value
                                                    </th>
                                                    <th
                                                        class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                        Source
                                                    </th>
                                                    <th
                                                        class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                        Confidence
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 bg-white">
                                                @foreach ($keyCriteriaSpecs as $spec)
                                                    @php
                                                        $criteriaFlags = $comparisonKeySettingsBySpecKey[
                                                            trim((string) $spec->spec_key)
                                                        ] ?? [
                                                            'upgrade_exceeds_value' => true,
                                                            'caution_if_exceeds_value' => true,
                                                            'upgrade_is_below_value' => false,
                                                            'caution_if_below_value' => false,
                                                        ];
                                                    @endphp
                                                    <tr
                                                        class="{{ $spec->is_key_comparison ? 'bg-amber-50/40' : '' }} hover:bg-gray-50 transition-colors">
                                                        <td class="px-5 py-3 text-center">
                                                            <span
                                                                class="inline-flex h-4 w-4 items-center justify-center rounded border border-gray-300 bg-white text-[10px] font-semibold {{ $spec->is_key_comparison ? 'text-amber-600' : 'text-gray-300' }}">
                                                                {{ $spec->is_key_comparison ? '✓' : '—' }}
                                                            </span>
                                                        </td>
                                                        <td class="px-5 py-3 text-gray-800">
                                                            {{ $spec->spec_label }}
                                                            @if ($spec->is_key_comparison)
                                                                <span
                                                                    class="ml-1.5 inline-flex items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-700">key</span>
                                                            @endif
                                                            <div
                                                                class="mt-2 grid grid-cols-2 gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                                <label class="inline-flex items-center gap-1.5">
                                                                    <input type="checkbox"
                                                                        class="h-3 w-3 rounded border-gray-300 text-amber-500"
                                                                        disabled
                                                                        {{ $criteriaFlags['upgrade_exceeds_value'] ? 'checked' : '' }}>
                                                                    <span>Upgrade exceeds value</span>
                                                                </label>
                                                                <label class="inline-flex items-center gap-1.5">
                                                                    <input type="checkbox"
                                                                        class="h-3 w-3 rounded border-gray-300 text-amber-500"
                                                                        disabled
                                                                        {{ $criteriaFlags['caution_if_exceeds_value'] ? 'checked' : '' }}>
                                                                    <span>Caution if exceeds value</span>
                                                                </label>
                                                                <label class="inline-flex items-center gap-1.5">
                                                                    <input type="checkbox"
                                                                        class="h-3 w-3 rounded border-gray-300 text-amber-500"
                                                                        disabled
                                                                        {{ $criteriaFlags['upgrade_is_below_value'] ? 'checked' : '' }}>
                                                                    <span>Upgrade is below value</span>
                                                                </label>
                                                                <label class="inline-flex items-center gap-1.5">
                                                                    <input type="checkbox"
                                                                        class="h-3 w-3 rounded border-gray-300 text-amber-500"
                                                                        disabled
                                                                        {{ $criteriaFlags['caution_if_below_value'] ? 'checked' : '' }}>
                                                                    <span>Caution if below value</span>
                                                                </label>
                                                            </div>
                                                        </td>
                                                        <td class="px-5 py-3 font-medium text-gray-900">
                                                            {{ $spec->spec_value ?? '—' }}
                                                            @if ($spec->spec_unit)
                                                                <span class="font-normal text-gray-400">{{ $spec->spec_unit }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-5 py-3 text-gray-500">{{ $spec->source ?? '—' }}
                                                        </td>
                                                        <td class="px-5 py-3 text-center">
                                                            @php $pct = round(($spec->confidence_score ?? 1) * 100); @endphp
                                                            <span
                                                                class="text-xs {{ $pct >= 80 ? 'text-green-600' : ($pct >= 50 ? 'text-yellow-600' : 'text-red-500') }}">{{ $pct }}%</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif

                            <div id="kc-criteria-rows" class="divide-y divide-gray-100">

                            </div>
                        </div>

                        {{-- ── Criteria Modal ─────────────────────────────────────── --}}
                        <div id="kc-criteria-modal" class="fixed inset-0 z-[9999] hidden">
                            <div id="kc-criteria-modal-overlay" class="absolute inset-0 bg-gray-900/45"></div>
                            <div class="relative z-10 flex min-h-full items-center justify-center p-4">
                                <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
                                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-400">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </span>
                                            <h3 class="text-base font-semibold text-gray-800">Manage Matching Criteria</h3>
                                        </div>
                                        <button type="button" id="kc-criteria-modal-close"
                                            class="rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="max-h-[70vh] overflow-y-auto px-6 py-4">
                                        <p class="text-xs text-gray-500">
                                            <span class="font-semibold">CATEGORY:</span>
                                            <span
                                                class="ml-2 font-semibold text-gray-700">{{ optional($equipment->productCategory)->title ?? 'Uncategorised' }}</span>
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500">Manage the criteria library for this
                                            category. These criteria will be applied to all equipment in this category.</p>

                                        <div id="kc-modal-criteria-list" class="mt-4 space-y-2"></div>

                                        <div id="kc-modal-form"
                                            class="mt-4 hidden rounded-xl border border-teal-200 bg-teal-50/30 p-4">
                                            <div>
                                                <label
                                                    class="mb-1 block text-xs font-semibold text-gray-600">Name</label>
                                                <input type="text" id="kc-modal-name" placeholder="e.g. Engine HP"
                                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                            </div>

                                            <div class="mt-3">
                                                <p id="kc-modal-weight-label" class="text-xs font-semibold text-gray-600">
                                                    Default Weight: 50</p>
                                                <input type="range" id="kc-modal-weight" min="0" max="100"
                                                    value="50" class="mt-2 h-2 w-full accent-teal-400">
                                            </div>

                                            <div class="mt-3 space-y-2">
                                                <div class="grid gap-2 md:grid-cols-2">
                                                    <label class="flex items-center gap-2 cursor-pointer select-none">
                                                        <input type="checkbox" id="kc-modal-upgrade-exceeds"
                                                            data-row="1"
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
                                                        <input type="checkbox" id="kc-modal-caution-if-change"
                                                            data-row="2"
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
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
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
                                    <svg class="h-4 w-4 flex-shrink-0 text-gray-500" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4" />
                                    </svg>
                                    <h3 class="text-sm font-semibold text-gray-900">Substitution Rules</h3>
                                </div>
                                <div class="divide-y divide-gray-100 px-5">
                                    @foreach ([
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
                                            <input type="hidden" class="kc-rule-hidden-input"
                                                name="{{ $rule['name'] }}" value="{{ $rule['value'] ? '1' : '0' }}">
                                            <button type="button" id="{{ $rule['id'] }}" role="switch"
                                                aria-checked="{{ $rule['value'] ? 'true' : 'false' }}"
                                                class="kc-rule-toggle relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none {{ $rule['value'] ? 'bg-teal-400' : 'bg-gray-200' }}">
                                                <span
                                                    class="inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform duration-200 {{ $rule['value'] ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Substitution Notes --}}
                            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                                <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-4">
                                    <svg class="h-4 w-4 flex-shrink-0 text-gray-500" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 10h.01M12 10h.01M16 10h.01M21 16c0 1.1-.9 2-2 2H5l-4 4V5a2 2 0 012-2h14a2 2 0 012 2v11z" />
                                    </svg>
                                    <h3 class="text-sm font-semibold text-gray-900">Substitution Notes</h3>
                                </div>
                                <div class="px-5 py-4">
                                    <p class="mb-2 text-xs font-semibold text-gray-500">Notes</p>
                                    <textarea id="kc-substitution-notes" name="equipment_key_comparison_notes" rows="5"
                                        placeholder="e.g. Can replace any 19ft scissor lift but not suitable for indoor slab work"
                                        class="w-full resize-none rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('equipment_key_comparison_notes', $equipment->equipment_key_comparison_notes) }}</textarea>
                                    <p class="mt-2 text-xs text-gray-400">Used by Kabba AI for intelligent scheduling
                                        decisions</p>
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

@push('css')
    <style>
        .kc-weight-slider {
            --kc-weight-progress: 0%;
            appearance: none;
            -webkit-appearance: none;
            border-radius: 9999px;
            background: linear-gradient(to right, #2dd4bf 0%, #2dd4bf var(--kc-weight-progress), #e5e7eb var(--kc-weight-progress), #e5e7eb 100%);
        }

        .kc-weight-slider::-webkit-slider-runnable-track {
            height: 0.375rem;
            border-radius: 9999px;
            background: transparent;
        }

        .kc-weight-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 0;
            height: 0;
            opacity: 0;
        }

        .kc-weight-slider::-moz-range-track,
        .kc-weight-slider::-moz-range-progress {
            height: 0.375rem;
            border-radius: 9999px;
        }

        .kc-weight-slider::-moz-range-track {
            background: #e5e7eb;
        }

        .kc-weight-slider::-moz-range-progress {
            background: #2dd4bf;
        }

        .kc-weight-slider::-moz-range-thumb {
            width: 0;
            height: 0;
            border: 0;
            opacity: 0;
        }
    </style>
@endpush

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            /* ──────────────── TAB SWITCHING ──────────────── */
            const tabButtons = Array.from(document.querySelectorAll('[data-tab-btn]'));
            const tabPanels = Array.from(document.querySelectorAll('[data-tab-panel]'));

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

            tabButtons.forEach(btn => btn.addEventListener('click', () => setActive(btn.getAttribute(
                'data-tab-btn'))));
            setActive('overview');

            /* ──────────────── KEY COMPARISON ──────────────── */
            (function() {
                // ── Criteria row toggles + sliders ──────────────────────
                const profileSelectAllBtn = document.getElementById('kc-profile-select-all');
                const profileCountBadge = document.getElementById('kc-profile-count');
                const profileList = document.querySelectorAll('.kc-profile-checkbox');
                const profileTotal = profileList.length;

                function updateProfileCount() {
                    if (!profileCountBadge) return;
                    const checked = document.querySelectorAll('.kc-profile-checkbox:checked').length;
                    profileCountBadge.textContent = checked + ' selected';

                    if (profileSelectAllBtn) {
                        profileSelectAllBtn.textContent = profileTotal > 0 && checked === profileTotal ?
                            'Deselect All' :
                            'Select All';
                    }
                }

                profileList.forEach((checkbox) => {
                    checkbox.addEventListener('change', updateProfileCount);
                });

                profileSelectAllBtn?.addEventListener('click', function() {
                    const shouldSelectAll = document.querySelectorAll('.kc-profile-checkbox:checked')
                        .length !== profileTotal;
                    document.querySelectorAll('.kc-profile-checkbox').forEach((checkbox) => {
                        checkbox.checked = shouldSelectAll;
                    });
                    updateProfileCount();
                });

                updateProfileCount();

                function wireCriteriaRow(row) {
                    return row;
                }

                document.querySelectorAll('.kc-criteria-row').forEach(wireCriteriaRow);


                const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                const categorySelect = document.getElementById('product_category_id');

                let editingCriteriaIndex = null;
                let criteriaLibrary = [];
                const criteriaValueState = {};

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
                        const key = row.dataset.key || 'criteria';
                        const thresholdText = row.dataset.threshold || '';
                        const weightText = row.querySelector('.kc-weight-value')?.textContent?.trim() ||
                            '50';
                        criteriaValueState[key] = {
                            threshold: thresholdText,
                            weight: Number(weightText || 50),
                        };

                        return {
                            id: null,
                            key,
                            name: row.querySelector('.kc-criteria-label')?.textContent?.trim() ||
                                'Criteria',
                            unit: row.querySelector('.kc-criteria-unit')?.textContent?.trim() || '',
                            defaultWeight: Number(weightText || 50),
                            upgradeExceedsValue: row.querySelector('input[data-flag="upgrade-exceeds"]')
                                ?.checked ?? true,
                            cautionIfChangeValue: row.querySelector('input[data-flag="caution-change"]')
                                ?.checked ?? false,
                            upgradeIsBelowValue: row.querySelector('input[data-flag="upgrade-below"]')
                                ?.checked ?? false,
                            cautionIfBelowValue: row.querySelector('input[data-flag="caution-below"]')
                                ?.checked ?? false,
                        };
                    });
                }

                function renderCriteriaRowsFromLibrary() {
                    if (!criteriaRowsWrap) return;

                    if (!criteriaLibrary.length) {
                        criteriaRowsWrap.innerHTML =
                            '<div class="px-5 py-6 text-center text-sm text-gray-400">No active criteria found for this category.</div>';

                        return;
                    }

                    criteriaRowsWrap.innerHTML = criteriaLibrary.map((item) => {
                        const key = item.key || criteriaKeyFromName(item.name);
                        const safeName = escapeHtml(item.name || 'Criteria');
                        const safeUnit = escapeHtml(item.unit || '');
                        const state = criteriaValueState[key] || {};
                        const threshold = String(state.threshold ?? '');
                        const weight = Number(state.weight ?? item.defaultWeight ?? 50);
                        const upgradeExceeds = item.upgradeExceedsValue !== false;
                        const cautionIfChange = item.cautionIfChangeValue === true;
                        const upgradeBelow = item.upgradeIsBelowValue === true;
                        const cautionBelow = item.cautionIfBelowValue === true;

                        return `
                        <div class="kc-criteria-row flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3" data-key="${key}" data-threshold="${escapeHtml(threshold)}">
                            <span class="kc-criteria-label w-36 text-sm text-gray-700">${safeName}</span>
                            <span class="kc-criteria-unit w-8 text-xs text-gray-500">${safeUnit}</span>
                            <span class="text-xs font-medium text-gray-500">Weight:</span>
                            <div class="flex-1 min-w-[100px] h-1.5 bg-gray-200 rounded-full overflow-hidden relative">
                                <div class="absolute left-0 top-0 h-full bg-teal-400 rounded-full" style="width: ${weight}%"></div>
                            </div>
                            <span class="kc-weight-value w-6 text-right text-xs font-semibold text-gray-700">${weight}</span>
                            <div class="w-full pt-1 space-y-2">
                                <div class="grid gap-2 md:grid-cols-2">
                                    <label class="flex items-center gap-2 select-none">
                                        <input type="checkbox" disabled readonly tabindex="-1"
                                            data-flag="upgrade-exceeds"
                                            class="kc-row-exclusive h-3.5 w-3.5 rounded border-gray-300 text-teal-500 focus:ring-0"
                                            ${upgradeExceeds ? 'checked' : ''}>
                                        <span class="text-xs text-gray-600">Upgrade exceeds value</span>
                                    </label>
                                    <label class="flex items-center gap-2 select-none">
                                        <input type="checkbox" disabled readonly tabindex="-1"
                                            data-flag="caution-below"
                                            class="kc-row-exclusive h-3.5 w-3.5 rounded border-gray-300 text-teal-500 focus:ring-0"
                                            ${cautionBelow ? 'checked' : ''}>
                                        <span class="text-xs text-gray-600">Caution if below value</span>
                                    </label>
                                </div>
                                <div class="grid gap-2 md:grid-cols-2">
                                    <label class="flex items-center gap-2 select-none">
                                        <input type="checkbox" disabled readonly tabindex="-1"
                                            data-flag="upgrade-below"
                                            class="kc-row-exclusive h-3.5 w-3.5 rounded border-gray-300 text-teal-500 focus:ring-0"
                                            ${upgradeBelow ? 'checked' : ''}>
                                        <span class="text-xs text-gray-600">Upgrade is below value</span>
                                    </label>
                                    <label class="flex items-center gap-2 select-none">
                                        <input type="checkbox" disabled readonly tabindex="-1"
                                            data-flag="caution-change"
                                            class="kc-row-exclusive h-3.5 w-3.5 rounded border-gray-300 text-teal-500 focus:ring-0"
                                            ${cautionIfChange ? 'checked' : ''}>
                                        <span class="text-xs text-gray-600">Caution if exceeds value</span>
                                    </label>
                                </div>
                            </div>
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
                    const categoryId = currentCategoryId();

                    if (!categoryId) {
                        criteriaLibrary = [];
                        return true;
                    }

                    try {
                        const url = new URL(CRITERIA_LIST_URL, window.location.origin);
                        url.searchParams.set('category_id', categoryId);

                        const res = await fetch(url.toString(), {
                            headers: {
                                'Accept': 'application/json'
                            },
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
                            cautionIfExceedsValue: item.caution_if_exceeds_value === true,
                            upgradeIsBelowValue: item.upgrade_is_below_value === true,
                            cautionIfBelowValue: item.caution_if_below_value === true,
                        }));

                        return true;
                    } catch (error) {
                        return false;
                    }
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
                    btn.addEventListener('click', function() {
                        const on = this.getAttribute('aria-checked') !== 'true';
                        this.setAttribute('aria-checked', on ? 'true' : 'false');
                        this.classList.toggle('bg-teal-400', on);
                        this.classList.toggle('bg-gray-200', !on);
                        const thumb = this.querySelector('span');
                        if (thumb) {
                            thumb.classList.toggle('translate-x-5', on);
                            thumb.classList.toggle('translate-x-0', !on);
                        }
                        const hiddenInput = this.parentElement?.querySelector(
                            '.kc-rule-hidden-input');
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
                    document.querySelectorAll(`.kc-modal-row-exclusive[data-row="${otherRow}"]`)
                        .forEach((checkbox) => {
                            checkbox.checked = false;
                        });
                });
            })();

            /* ──────────────── SPECIFICATION AI ──────────────── */


            const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';


            let keyComparisonState = []; // array of key comparison objects

            const genBtn = document.getElementById('btn-spec-update');
            const btnLabel = document.getElementById('spec-btn-label');
            const spinner = document.getElementById('spec-spinner');
            const bolt = document.getElementById('spec-bolt');
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
                    category: selectedText('[name="product_category_id"]') === 'Select Category' ? '' :
                        selectedText('[name="product_category_id"]'),
                    equipment_name: valueOf('[name="equipment_name"]'),
                    equipment_id: valueOf('[name="equipment_id"]'),
                    serial_number: valueOf('[name="serial_number"]') || null,
                    vin: valueOf('[name="vehicle_identification_number"]') || null,
                };
            }

            function escHtml(str) {
                if (!str) return '';
                return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g,
                    '&quot;');
            }

            function fmtSpecValue(value, unit = '') {
                const cleanedValue = String(value ?? '').trim();
                const cleanedUnit = String(unit ?? '').trim();
                if (!cleanedValue) return '<span class="text-gray-400">-</span>';
                if (!cleanedUnit)
                    return `<span class="font-semibold text-gray-800">${escHtml(cleanedValue)}</span>`;
                return `<span class="font-semibold text-gray-800">${escHtml(cleanedValue)}</span> <span class="ml-1 text-xs text-gray-400">${escHtml(cleanedUnit)}</span>`;
            }


            async function addSpecToKeyComparisons(spec) {
                if (!spec || !spec.spec_label) return;

                const alreadyExists = keyComparisonState.some(kc => kc.spec_label === spec.spec_label);
                if (alreadyExists) return;

                keyComparisonPreview.innerHTML = `
                    <div class="flex items-center gap-2 px-5 py-3 text-sm text-gray-400">
                        <svg class="h-4 w-4 animate-spin text-teal-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Adding…
                    </div>`;

                try {
                    const res = await fetch(KEY_COMPARISON_STORE_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            spec_label: spec.spec_label,
                            spec_value: spec.value,
                            spec_unit: spec.unit ?? '',
                            is_manual_override: false,
                        }),
                    });
                    const data = await res.json();

                    if (res.ok && data.success && data.item) {
                        keyComparisonState.push(data.item);
                    }
                } catch (err) {
                    console.error('Error adding key comparison:', err);
                } finally {


                }
            }

            async function removeKeyComparison(e) {
                const deleteButton = e.currentTarget;
                const comparisonId = deleteButton.dataset.comparisonId;
                if (!comparisonId) return;

                const originalButtonHtml = deleteButton.innerHTML;
                deleteButton.disabled = true;
                deleteButton.classList.add('opacity-60', 'cursor-not-allowed');
                deleteButton.innerHTML = `
                    <svg class="h-3.5 w-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                `;

                try {
                    const url = KEY_COMPARISON_DESTROY_URL.replace(':id', comparisonId);
                    const res = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                    });
                    const data = await res.json();

                    if (res.ok && data.success) {
                        keyComparisonState = keyComparisonState.filter(kc => kc.id !== parseInt(comparisonId));


                    }
                } catch (err) {
                    console.error('Error removing key comparison:', err);
                } finally {
                    if (document.body.contains(deleteButton)) {
                        deleteButton.disabled = false;
                        deleteButton.classList.remove('opacity-60', 'cursor-not-allowed');
                        deleteButton.innerHTML = originalButtonHtml;
                    }
                }
            }

            // ...existing code...
            function renderEquipmentSpecsPreview(specs) {
                if (!equipmentSpecsColLeft || !equipmentSpecsColRight) return;

                const cleanedSpecs = (specs || []).filter((spec) => String(spec?.value ?? '').trim() !== '');
                if (cleanedSpecs.length === 0) {
                    equipmentSpecsColLeft.innerHTML =
                        '<div class="px-5 py-3 text-sm text-gray-500">No specification values available yet.</div>';
                    equipmentSpecsColRight.innerHTML = '';
                    return;
                }

                const midpoint = Math.ceil(cleanedSpecs.length / 2);
                const leftRows = cleanedSpecs.slice(0, midpoint);
                const rightRows = cleanedSpecs.slice(midpoint);

                const renderRows = (items) => {
                    return items.map((spec) => {
                        const isAlreadyInKeyComparison = keyComparisonState.some(kc => kc.spec_label ===
                            spec.spec_label);
                        const addButtonHtml = isAlreadyInKeyComparison ? '' : `
                                <button type="button" class="kc-add-spec inline-flex h-7 w-7 items-center justify-center rounded-md border border-teal-200 text-teal-600 hover:bg-teal-50" data-spec-id="${spec.id}" title="Add to key comparison" aria-label="Add to key comparison">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                        `;

                        return `
                        <div class="spec-item flex items-center justify-between gap-3 px-5 py-3 text-sm bg-white" data-spec-id="${spec.id}">
                            <div class="flex flex-1 items-center gap-3">
                                ${addButtonHtml}
                                <span class="text-gray-600 block">${escHtml(spec.spec_label || '-')}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-right">${fmtSpecValue(spec.value, spec.unit)}</span>
                            </div>
                        </div>
                    `;
                    }).join('');
                };

                equipmentSpecsColLeft.innerHTML = renderRows(leftRows);
                equipmentSpecsColRight.innerHTML = rightRows.length ? renderRows(rightRows) : '';

                document.querySelectorAll('.kc-add-spec').forEach(btn => {
                    btn.addEventListener('click', async (e) => {
                        const specId = e.currentTarget.dataset.specId;
                        if (!specId) return;
                        const spec = specState.find(s => String(s.id) === String(specId));
                        if (!spec) return;
                        await addSpecToKeyComparisons(spec);
                    });
                });
            }

            function applyGeneratedCriteria(criteriaState) {
                if (!criteriaState || typeof criteriaState !== 'object') return;

                document.querySelectorAll('.kc-criteria-row').forEach((row) => {
                    const key = row.dataset.key;
                    if (!key || !Object.prototype.hasOwnProperty.call(criteriaState, key)) return;

                    const nextState = criteriaState[key] || {};
                    const thresholdText = String(nextState.threshold ?? '').trim();
                    const current = criteriaValueState[key] || {};
                    criteriaValueState[key] = {
                        threshold: thresholdText,
                        weight: nextState.weight !== undefined ? Number(nextState.weight) : Number(
                            current.weight ?? 50),
                    };
                    row.dataset.threshold = thresholdText;

                    if (nextState.weight !== undefined) {
                        const target = criteriaLibrary.find((item) => item.key === key);
                        if (target) target.defaultWeight = Number(nextState.weight);
                    }
                });

            }

            /* ---- generate all ---- */
            async function generateSpecs() {
                if (genBtn) genBtn.disabled = true;
                spinner?.classList.remove('hidden');
                bolt?.classList.add('hidden');
                if (btnLabel) btnLabel.textContent = 'Updating…';

                const lookupPayload = buildLookupPayload();

                try {
                    const res = await fetch(GENERATE_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            lookup_payload: lookupPayload
                        }),
                    });
                    const data = await res.json();

                    if (!res.ok || !data.success) return;

                    specState = data.specs;
                    renderEquipmentSpecsPreview(specState);
                    applyGeneratedCriteria(data.criteria || {});
                    if (lastUpdateEl) {
                        const now = new Date();
                        lastUpdateEl.textContent = now.toLocaleString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric',
                            hour: 'numeric',
                            minute: '2-digit'
                        });
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

        });
    </script>
@endpush
