@extends('admin.layouts.app')

@section('title', 'Compare & Commonize — ' . $category->title)

@section('content')
<div class="space-y-6" x-data="specMatrix()">

    {{-- ─── Breadcrumb ─────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ route('admin.maintenance-management.equipment-ai.index', ['category_id' => $category->id]) }}"
           class="hover:text-brand-500">Equipment Management AI</a>
        <x-heroicon-o-chevron-right class="h-4 w-4" />
        <span class="font-medium text-gray-800 dark:text-gray-200">Compare &amp; Commonize — {{ $category->title }}</span>
    </div>

    {{-- ─── Header Card ─────────────────────────────────────────────────── --}}
    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-900/30">
                    <x-heroicon-o-table-cells class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Compare &amp; Commonize</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        <strong class="text-gray-700 dark:text-gray-300">{{ $category->title }}</strong>
                        &nbsp;·&nbsp;<span x-text="rows.length"></span> unique spec label(s)
                        &nbsp;·&nbsp;{{ $profiles->count() }} equipment model(s)
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.maintenance-management.equipment-ai.index', ['category_id' => $category->id]) }}"
               class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                <x-heroicon-o-arrow-left class="h-4 w-4" />
                Back to Profiles
            </a>
        </div>
    </div>

    {{-- ─── Toolbar ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-3">

        <div class="hidden">
            <button type="button"
                    @click="openGroupModal()"
                    :disabled="selected.length < 2"
                    class="inline-flex items-center gap-2 rounded-lg border border-indigo-400 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-indigo-500 dark:text-indigo-400 dark:hover:bg-indigo-900/20">
                <x-heroicon-o-arrows-right-left class="h-4 w-4" />
                Group Selected
                <span x-show="selected.length >= 2" x-cloak x-text="'(' + selected.length + ')'" class="text-xs font-normal"></span>
            </button>
        </div>

        <div class="hidden">
            <button type="button"
                    @click="selected = []"
                    x-show="selected.length > 0"
                    x-cloak
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700">
                <x-heroicon-o-x-mark class="h-4 w-4" />
                Clear (<span x-text="selected.length"></span>)
            </button>
        </div>

        <form method="POST"
              action="{{ route('admin.maintenance-management.equipment-ai.commonize.analyze') }}"
              class="hidden">
            @csrf
            <input type="hidden" name="category_id" value="{{ $category->id }}">
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-lg border border-purple-400 px-4 py-2 text-sm font-medium text-purple-700 hover:bg-purple-50 dark:border-purple-500 dark:text-purple-400 dark:hover:bg-purple-900/20">
                <x-heroicon-o-sparkles class="h-4 w-4" />
                AI Synonymize
            </button>
        </form>

        {{-- Add New Specification --}}
        <button type="button"
                @click="openAddSpecModal()"
                class="inline-flex items-center gap-2 rounded-lg border border-emerald-400 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50 dark:border-emerald-500 dark:text-emerald-400 dark:hover:bg-emerald-900/20">
            <x-heroicon-o-plus-circle class="h-4 w-4" />
            Add New Specification
        </button>

        {{-- Fill Gaps --}}
        <button type="button"
                @click="fillCategoryGaps()"
                :disabled="fillingGaps"
                class="inline-flex items-center gap-2 rounded-lg border border-blue-300 px-4 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50 disabled:opacity-50 dark:border-blue-600 dark:text-blue-400 dark:hover:bg-blue-900/20"
                title="Insert placeholder rows for every missing model × spec combination so the matrix is fully populated">
            <template x-if="fillingGaps">
                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </template>
            <template x-if="!fillingGaps">
                <x-heroicon-o-table-cells class="h-4 w-4" />
            </template>
            <span x-text="fillingGaps ? 'Filling…' : 'Fill Gaps'"></span>
        </button>

        {{-- AI Compare Equipment --}}
        <button type="button"
                @click="runComparison()"
                :disabled="comparing"
                class="inline-flex items-center gap-2 rounded-lg border border-amber-400 px-4 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50 disabled:opacity-50 dark:border-amber-600 dark:text-amber-400 dark:hover:bg-amber-900/20"
                title="Send Key Comparison specs to AI for a side-by-side equipment analysis">
            <template x-if="comparing">
                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </template>
            <template x-if="!comparing">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354l-4.502 2.826c-.995.608-2.23-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"/></svg>
            </template>
            <span x-text="comparing ? 'Analyzing…' : 'AI Compare Equipment'"></span>
        </button>

        <div class="flex-1"></div>

        {{-- Stats chips --}}
        <div class="flex items-center gap-3 text-sm">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-amber-700 dark:border-amber-700/50 dark:bg-amber-900/20 dark:text-amber-400">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354l-4.502 2.826c-.995.608-2.23-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"/></svg>
                <span x-text="keyCount + ' key'"></span>
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                <span class="h-2 w-2 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                <span x-text="normalCount + ' normal'"></span>
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full border border-red-100 bg-red-50 px-3 py-1 text-red-500 dark:border-red-800/50 dark:bg-red-900/20 dark:text-red-400">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><line x1="5" y1="5" x2="19" y2="19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span x-text="ignoredCount + ' ignored'"></span>
            </span>
        </div>

        <form method="POST"
              action="{{ route('admin.maintenance-management.equipment-ai.commonize.save-framework') }}"
              @submit.prevent="submitFramework($el)"
              class="hidden">
            @csrf
            <input type="hidden" name="category_id" value="{{ $category->id }}">
            <input type="hidden" name="framework" :value="frameworkJson">
            <button type="submit"
                    :disabled="saving"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 disabled:opacity-50">
                <x-heroicon-o-check class="h-4 w-4" />
                <span x-text="saving ? 'Saving…' : 'Save Framework'"></span>
            </button>
        </form>
    </div>

    {{-- ─── Matrix Table ──────────────────────────────────────────────────── --}}
    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="max-h-[70vh] overflow-auto relative">
            <table class="divide-y divide-gray-200 dark:divide-gray-700"
                   style="min-width: {{ 40 + 288 + 64 + ($profiles->count() * 112) }}px; width: 100%;">

                {{-- ── Column headers ──────────────────────────────────── --}}
                <thead class="bg-gray-50 dark:bg-gray-800/60">
                    <tr>
                        <th scope="col" class="sticky left-0 top-0 z-40 w-10 bg-gray-50 px-3 py-3 dark:bg-gray-800/60"></th>
                        <th scope="col"
                            class="sticky left-10 top-0 z-40 w-72 border-r border-gray-200 bg-gray-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-400">
                            Specification Label
                        </th>
                        <th scope="col"
                            class="sticky top-0 z-30 w-16 bg-gray-50 px-1 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-800/60 dark:text-gray-400"
                            title="Key = sent to AI · Normal = default · Ignored = excluded">
                            Status
                        </th>
                        @foreach ($profiles as $profile)
                            <th scope="col" class="sticky top-0 z-30 w-28 bg-gray-50 px-3 py-3 text-center dark:bg-gray-800/60">
                                <div class="mx-auto max-w-[100px] truncate text-xs font-semibold text-gray-700 dark:text-gray-300"
                                     title="{{ $profile->make }} {{ $profile->model }}">
                                    {{ $profile->model ?: $profile->make }}
                                </div>
                                <div class="mx-auto mt-0.5 max-w-[100px] truncate text-[10px] font-normal text-gray-400 dark:text-gray-500">
                                    {{ $profile->make }}
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                {{-- ── Table body ──────────────────────────────────────── --}}
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <template x-for="(item, itemIdx) in displayItems" :key="'item-' + itemIdx">
                        <tr :class="rowClass(item)">

                            {{-- Col 1: selector / indicator (sticky) --}}
                            <td class="sticky left-0 z-10 w-10 px-3 py-2.5" :class="stickyBg(item)">
                                <template x-if="item.type === 'section_header'"><span></span></template>

                                <template x-if="item.type === 'group_header'">
                                    <div class="flex h-6 w-6 items-center justify-center rounded"
                                         :class="colorDefs[item.gIdx % colorDefs.length].badgeBg">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                    </div>
                                </template>

                                <template x-if="item.type === 'group_member'">
                                    <div class="ml-2 flex h-5 w-4 items-center justify-center">
                                        <div class="h-full w-0.5 rounded-full opacity-40" :class="colorDefs[item.gIdx % colorDefs.length].accentLine"></div>
                                    </div>
                                </template>

                                {{-- Custom spec rows: no checkbox (can't group them) --}}
                                <template x-if="item.type === 'ungrouped' && item.row.is_custom">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30" title="Custom Specification">
                                        <svg class="h-3 w-3 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/>
                                        </svg>
                                    </div>
                                </template>

                                <template x-if="item.type === 'ungrouped' && !item.row.is_custom">
                                    <input type="checkbox"
                                           :checked="isSelected(item.row.spec_key)"
                                           @change="toggleSelected(item.row.spec_key)"
                                           class="hidden h-4 w-4 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600">
                                </template>
                            </td>

                            {{-- Col 2: spec label (sticky) --}}
                            <td class="sticky left-10 z-10 w-72 px-4 py-2.5"
                                :class="[stickyBg(item), item.type !== 'section_header' ? 'border-r border-gray-100 dark:border-gray-800' : '']">

                                {{-- Section header --}}
                                <template x-if="item.type === 'section_header'">
                                    <div class="flex items-center gap-2">
                                        <template x-if="item.section === 'key'">
                                            <div class="flex items-center gap-2">
                                                <svg class="h-3.5 w-3.5 text-amber-500" viewBox="0 0 24 24" fill="currentColor"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354l-4.502 2.826c-.995.608-2.23-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"/></svg>
                                                <span class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-500">Key Comparison</span>
                                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400" x-text="keyCount"></span>
                                            </div>
                                        </template>
                                        <template x-if="item.section === 'normal'">
                                            <div class="flex items-center gap-2">
                                                <span class="h-2 w-2 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                                                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Normal Specifications</span>
                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500 dark:bg-gray-800 dark:text-gray-400" x-text="normalCount"></span>
                                            </div>
                                        </template>
                                        <template x-if="item.section === 'ignored'">
                                            <div class="flex items-center gap-2">
                                                <svg class="h-3.5 w-3.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><line x1="5" y1="5" x2="19" y2="19" stroke-linecap="round"/></svg>
                                                <span class="text-xs font-semibold uppercase tracking-wider text-red-400 dark:text-red-500">Ignored</span>
                                                <span class="rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-400 dark:bg-red-900/20 dark:text-red-500" x-text="ignoredCount"></span>
                                                <span class="text-[10px] text-gray-400 dark:text-gray-600">— not sent to AI</span>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                {{-- Group header --}}
                                <template x-if="item.type === 'group_header'">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <span class="truncate text-sm font-semibold"
                                              :class="item.status === 'ignored' ? 'text-gray-400 line-through dark:text-gray-500' : 'text-gray-900 dark:text-white'"
                                              x-text="item.group.master_label"></span>
                                        <code class="shrink-0 rounded px-1.5 py-0.5 text-[10px]"
                                              :class="colorDefs[item.gIdx % colorDefs.length].badge"
                                              x-text="item.group.master_key"></code>
                                        <div class="ml-auto flex shrink-0 items-center gap-0.5">
                                            <button type="button" @click.stop="editGroup(item.gIdx)"
                                                class="rounded p-1 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400" title="Edit group label">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button type="button" @click.stop="removeGroup(item.gIdx)"
                                                class="rounded p-1 text-gray-400 hover:text-red-500" title="Ungroup">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                {{-- Group member --}}
                                <template x-if="item.type === 'group_member'">
                                    <div class="flex items-center gap-1.5 pl-5">
                                        <span class="text-sm"
                                              :class="item.status === 'ignored' ? 'text-gray-400 line-through dark:text-gray-500' : 'text-gray-500 dark:text-gray-400'"
                                              x-text="item.row ? item.row.spec_label : item.specKey"></span>
                                        <code class="font-mono text-[10px] text-gray-400 dark:text-gray-500" x-text="item.specKey"></code>
                                    </div>
                                </template>

                                {{-- Ungrouped extracted spec --}}
                                <template x-if="item.type === 'ungrouped' && !item.row.is_custom">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm"
                                              :class="item.status === 'ignored' ? 'line-through text-gray-400 dark:text-gray-500' : 'text-gray-800 dark:text-gray-200'"
                                              x-text="item.row.spec_label"></span>
                                        <code class="font-mono text-[10px] text-gray-400 dark:text-gray-500" x-text="item.row.spec_key"></code>
                                    </div>
                                </template>

                                {{-- Ungrouped custom spec --}}
                                <template x-if="item.type === 'ungrouped' && item.row.is_custom">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="truncate text-sm font-medium"
                                                      :class="item.status === 'ignored' ? 'line-through text-gray-400 dark:text-gray-500' : 'text-gray-800 dark:text-gray-200'"
                                                      x-text="item.row.spec_label"></span>
                                                <span class="shrink-0 rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">custom</span>
                                            </div>
                                            <div x-show="item.row.description" class="mt-0.5 truncate text-[11px] text-gray-400 dark:text-gray-600" x-text="item.row.description"></div>
                                        </div>
                                        <div class="ml-auto flex shrink-0 items-center gap-1">
                                            {{-- Research with AI --}}
                                            <button type="button"
                                                    @click.stop="researchSpec(item.row.custom_spec_id)"
                                                    :disabled="researchingSpecId === item.row.custom_spec_id"
                                                    class="inline-flex items-center gap-1 rounded-md border border-purple-200 bg-purple-50 px-2 py-0.5 text-[11px] font-medium text-purple-600 hover:bg-purple-100 disabled:opacity-50 dark:border-purple-700/50 dark:bg-purple-900/20 dark:text-purple-400"
                                                    title="Research this specification for all equipment using AI">
                                                <template x-if="researchingSpecId === item.row.custom_spec_id">
                                                    <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                                    </svg>
                                                </template>
                                                <template x-if="researchingSpecId !== item.row.custom_spec_id">
                                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354l-4.502 2.826c-.995.608-2.23-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"/></svg>
                                                </template>
                                                <span x-text="researchingSpecId === item.row.custom_spec_id ? 'Researching…' : 'AI Research'"></span>
                                            </button>
                                            {{-- Delete custom spec --}}
                                            <button type="button"
                                                    @click.stop="deleteCustomSpec(item.row.custom_spec_id, item.row.spec_key)"
                                                    class="rounded p-1 text-gray-400 hover:text-red-500" title="Remove custom specification">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                            </td>

                            {{-- Col 3: Status buttons (★ / ⊘) --}}
                            <td class="w-16 px-1 py-2.5 text-center" x-show="item.type !== 'section_header'">

                                <template x-if="item.type === 'group_header'">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" @click.stop="toggleKey(item.group)"
                                                :title="item.group.is_key_comparison ? 'Key — click to set Normal' : 'Mark as Key Comparison'"
                                                :class="item.group.is_key_comparison ? 'text-amber-400 hover:text-amber-500' : 'text-gray-400 hover:text-amber-400 dark:text-gray-500 dark:hover:text-amber-400'">
                                            <svg :fill="item.group.is_key_comparison ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="width:18px;height:18px">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                            </svg>
                                        </button>
                                        <button type="button" @click.stop="toggleIgnore(item.group)"
                                                x-show="!item.group.is_key_comparison"
                                                :title="item.group.is_ignored ? 'Ignored — click to set Normal' : 'Mark as Ignored'"
                                                :class="item.group.is_ignored ? 'text-red-400 hover:text-red-500' : 'text-gray-400 hover:text-red-400 dark:text-gray-500 dark:hover:text-red-400'">
                                            <svg style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><line x1="5.5" y1="5.5" x2="18.5" y2="18.5" stroke-linecap="round"/></svg>
                                        </button>
                                    </div>
                                </template>

                                <template x-if="item.type === 'ungrouped'">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" @click.stop="toggleKey(item.row)"
                                                :title="item.row.is_key_comparison ? 'Key — click to set Normal' : 'Mark as Key Comparison'"
                                                :class="item.row.is_key_comparison ? 'text-amber-400 hover:text-amber-500' : 'text-gray-400 hover:text-amber-400 dark:text-gray-500 dark:hover:text-amber-400'">
                                            <svg :fill="item.row.is_key_comparison ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="width:18px;height:18px">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                            </svg>
                                        </button>
                                        <button type="button" @click.stop="toggleIgnore(item.row)"
                                                x-show="!item.row.is_key_comparison"
                                                :title="item.row.is_ignored ? 'Ignored — click to set Normal' : 'Mark as Ignored'"
                                                :class="item.row.is_ignored ? 'text-red-400 hover:text-red-500' : 'text-gray-400 hover:text-red-400 dark:text-gray-500 dark:hover:text-red-400'">
                                            <svg style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><line x1="5.5" y1="5.5" x2="18.5" y2="18.5" stroke-linecap="round"/></svg>
                                        </button>
                                    </div>
                                </template>

                            </td>

                            {{-- ── Equipment presence / value cells ──────────── --}}
                            @foreach ($profiles as $profile)
                                <td class="w-28 px-2 py-2 text-center"
                                    x-show="item.type !== 'section_header'">

                                    {{-- Group header combined presence --}}
                                    <template x-if="item.type === 'group_header'">
                                        <span :class="groupPresence(item.group, {{ $profile->id }}) ? 'text-green-500' : 'text-gray-300 dark:text-gray-700'">
                                            <template x-if="groupPresence(item.group, {{ $profile->id }})">
                                                <a :href="profileSpecUrls[{{ $profile->id }}] + '?edit=' + item.group.master_key"
                                                   target="_blank"
                                                   class="inline-flex items-center justify-center text-green-500 hover:text-green-600 transition-colors"
                                                   title="Open spec in profile — click to edit">
                                                    <svg class="mx-auto h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" /></svg>
                                                </a>
                                            </template>
                                            <template x-if="!groupPresence(item.group, {{ $profile->id }})">
                                                <a :href="profileSpecUrls[{{ $profile->id }}]"
                                                   target="_blank"
                                                   class="inline-flex items-center justify-center text-gray-300 hover:text-gray-500 dark:text-gray-700 dark:hover:text-gray-400 transition-colors"
                                                   title="Missing — click to open profile and add spec">
                                                    <svg class="mx-auto h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm3 10.5a.75.75 0 000-1.5H9a.75.75 0 000 1.5h6z" /></svg>
                                                </a>
                                            </template>
                                        </span>
                                    </template>

                                    {{-- Group member individual presence --}}
                                    <template x-if="item.type === 'group_member'">
                                        <span :class="(item.row && item.row.presence[{{ $profile->id }}]) ? 'text-green-400' : 'text-gray-300 dark:text-gray-700'">
                                            <template x-if="item.row && item.row.presence[{{ $profile->id }}]">
                                                <a :href="profileSpecUrls[{{ $profile->id }}] + '?edit=' + item.specKey"
                                                   target="_blank"
                                                   class="inline-flex items-center justify-center text-green-400 hover:text-green-500 transition-colors"
                                                   title="Open spec in profile — click to edit">
                                                    <svg class="mx-auto h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M19.916 4.626a.75.75 0 01.208 1.04l-9 13.5a.75.75 0 01-1.154.114l-6-6a.75.75 0 011.06-1.06l5.353 5.353 8.493-12.739a.75.75 0 011.04-.208z" /></svg>
                                                </a>
                                            </template>
                                            <template x-if="!(item.row && item.row.presence[{{ $profile->id }}])">
                                                <a :href="profileSpecUrls[{{ $profile->id }}] + '?edit=' + item.specKey"
                                                   target="_blank"
                                                   class="inline-flex items-center justify-center text-gray-300 hover:text-gray-500 dark:text-gray-700 dark:hover:text-gray-400 transition-colors"
                                                   title="Missing — click to open profile and add spec">
                                                    <svg class="mx-auto h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm3 10.5a.75.75 0 000-1.5H9a.75.75 0 000 1.5h6z" /></svg>
                                                </a>
                                            </template>
                                        </span>
                                    </template>

                                    {{-- Ungrouped extracted spec: presence --}}
                                    <template x-if="item.type === 'ungrouped' && !item.row.is_custom">
                                        <span :class="item.row.presence[{{ $profile->id }}] ? 'text-green-500' : 'text-gray-300 dark:text-gray-700'">
                                            <template x-if="item.row.presence[{{ $profile->id }}]">
                                                <a :href="profileSpecUrls[{{ $profile->id }}] + '?edit=' + item.row.spec_key"
                                                   target="_blank"
                                                   class="inline-flex items-center justify-center text-green-500 hover:text-green-600 transition-colors"
                                                   title="Open spec in profile — click to edit">
                                                    <svg class="mx-auto h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" /></svg>
                                                </a>
                                            </template>
                                            <template x-if="!item.row.presence[{{ $profile->id }}]">
                                                <div class="inline-flex items-center justify-center gap-0.5">
                                                    <a :href="profileSpecUrls[{{ $profile->id }}] + '?edit=' + item.row.spec_key"
                                                       target="_blank"
                                                       class="inline-flex items-center justify-center text-gray-300 hover:text-gray-500 dark:text-gray-700 dark:hover:text-gray-400 transition-colors"
                                                       title="Missing — click to open profile and add spec manually">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm3 10.5a.75.75 0 000-1.5H9a.75.75 0 000 1.5h6z" /></svg>
                                                    </a>
                                                    <button type="button"
                                                            @click.stop="researchForProfile(item.row, {{ $profile->id }})"
                                                            :disabled="researchingCell !== null"
                                                            class="inline-flex items-center justify-center text-gray-300 hover:text-purple-500 dark:text-gray-700 dark:hover:text-purple-400 transition-colors disabled:opacity-30"
                                                            title="Research this spec with AI">
                                                        <template x-if="researchingCell === (item.row.spec_key + ':{{ $profile->id }}')">
                                                            <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                        </template>
                                                        <template x-if="researchingCell !== (item.row.spec_key + ':{{ $profile->id }}')">
                                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354l-4.502 2.826c-.995.608-2.23-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"/></svg>
                                                        </template>
                                                    </button>
                                                </div>
                                            </template>
                                        </span>
                                    </template>

                                    {{-- Ungrouped custom spec: editable value cell --}}
                                    <template x-if="item.type === 'ungrouped' && item.row.is_custom">
                                        <div class="group relative flex items-center justify-center">
                                            <button type="button"
                                                    @click.stop="openCellEditor(item.row, {{ $profile->id }})"
                                                    class="inline-flex min-w-[48px] items-center justify-center gap-1 rounded-md px-1.5 py-0.5 text-xs transition-colors hover:bg-gray-100 dark:hover:bg-gray-800"
                                                    :class="customCellClass(item.row.values[{{ $profile->id }}])"
                                                    :title="cellTooltip(item.row.values[{{ $profile->id }}])">
                                                <span x-text="cellDisplayValue(item.row.values[{{ $profile->id }}])"></span>
                                                <template x-if="item.row.values[{{ $profile->id }}] && item.row.values[{{ $profile->id }}].source === 'ai'">
                                                    <svg class="h-2.5 w-2.5 opacity-60" viewBox="0 0 24 24" fill="currentColor"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354l-4.502 2.826c-.995.608-2.23-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"/></svg>
                                                </template>
                                                <template x-if="item.row.values[{{ $profile->id }}] && item.row.values[{{ $profile->id }}].confirmed">
                                                    <svg class="h-2.5 w-2.5 opacity-60" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M19.916 4.626a.75.75 0 01.208 1.04l-9 13.5a.75.75 0 01-1.154.114l-6-6a.75.75 0 011.06-1.06l5.353 5.353 8.493-12.739a.75.75 0 011.04-.208z" /></svg>
                                                </template>
                                            </button>
                                        </div>
                                    </template>

                                </td>
                            @endforeach

                        </tr>
                    </template>

                    <tr x-show="displayItems.length === 0" x-cloak>
                        <td colspan="{{ 3 + $profiles->count() }}" class="px-6 py-16 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">No specifications found. Run AI research on profiles first.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Legend --}}
        <div class="flex flex-wrap items-center gap-6 border-t border-gray-100 px-6 py-3 dark:border-gray-800">
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z"/></svg>
                Has spec
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-medium text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400">Yes</span>
                Custom: AI-filled value
                <svg class="h-3 w-3 text-amber-400" viewBox="0 0 24 24" fill="currentColor"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354l-4.502 2.826c-.995.608-2.23-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"/></svg>
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span class="rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-medium text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400">Yes</span>
                Custom: manually confirmed
                <svg class="h-3 w-3 text-emerald-500" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M19.916 4.626a.75.75 0 01.208 1.04l-9 13.5a.75.75 0 01-1.154.114l-6-6a.75.75 0 011.06-1.06l5.353 5.353 8.493-12.739a.75.75 0 011.04-.208z" /></svg>
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-400 dark:bg-gray-800">—</span>
                Custom: unknown / not researched (click to set)
            </div>
        </div>
    </div>

    {{-- ─── Cell Editor Popover ─────────────────────────────────────────── --}}
    <div x-show="showCellEditor"
         x-cloak
         class="fixed inset-0 z-40 flex items-center justify-center bg-black/30 backdrop-blur-sm"
         @keydown.escape.window="showCellEditor = false">
        <div class="w-80 rounded-xl bg-white p-5 shadow-xl dark:border dark:border-gray-700 dark:bg-gray-900"
             @click.outside="showCellEditor = false">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-white">
                Edit Value — <span x-text="cellEditorLabel"></span>
            </h4>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="cellEditorProfileName"></p>

            <div class="mt-4">
                {{-- Enum / Boolean: show buttons --}}
                <template x-if="cellEditorAllowedValues.length > 0">
                    <div class="flex flex-wrap gap-2">
                        <template x-for="opt in cellEditorAllowedValues" :key="opt">
                            <button type="button"
                                    @click="cellEditorValue = opt"
                                    :class="cellEditorValue === opt
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : 'bg-white border-gray-300 text-gray-700 hover:border-indigo-400 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300'"
                                    class="rounded-lg border px-4 py-2 text-sm font-medium transition-colors"
                                    x-text="opt"></button>
                        </template>
                    </div>
                </template>
                {{-- Free text / numeric --}}
                <template x-if="cellEditorAllowedValues.length === 0">
                    <input type="text" x-model="cellEditorValue"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                           placeholder="Enter value…">
                </template>
            </div>

            <div class="mt-5 flex justify-end gap-3">
                <button type="button" @click="showCellEditor = false"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    Cancel
                </button>
                <button type="button" @click="saveCellValue()"
                        :disabled="cellEditorSaving"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                    <x-heroicon-o-check class="h-4 w-4" />
                    <span x-text="cellEditorSaving ? 'Saving…' : 'Save'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ─── Legend ──────────────────────────────────────────────────────── --}}
    <div class="rounded-xl border border-sky-100 bg-sky-50/60 px-5 py-4 dark:border-sky-700/30 dark:bg-sky-900/10">
        <div class="flex gap-3">
            <x-heroicon-o-information-circle class="mt-0.5 h-5 w-5 shrink-0 text-sky-600 dark:text-sky-400" />
            <div class="space-y-1 text-sm text-sky-800 dark:text-sky-300">
                <p>
                    <strong>Status:</strong>
                    <span class="font-mono font-semibold text-amber-600">★</span> <strong>Key</strong> — sent to AI for conflict analysis.
                    <span class="font-mono font-semibold text-red-400">⊘</span> <strong>Ignored</strong> — excluded from AI.
                    Unmarked = <strong>Normal</strong>.
                </p>
                <p>
                    <strong>Custom specs:</strong> Click <strong>Add New Specification</strong> to define a spec not extracted automatically. Use <strong>Research with AI</strong> to fill values per equipment model.
                </p>
            </div>
        </div>
    </div>

    {{-- ─── Key Comparison Criteria Modal ─────────────────────────────── --}}
    <div x-show="showKeyCriteriaModal"
         x-cloak
         class="fixed inset-0 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm"
         style="z-index: 9999;"
         @keydown.escape.window="cancelKeyModal()">
        <div class="relative w-full max-w-md rounded-xl bg-white shadow-2xl dark:bg-gray-900 dark:border dark:border-gray-700"
             @click.outside="cancelKeyModal()">

            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                        <svg class="h-4 w-4 text-amber-500" viewBox="0 0 24 24" fill="currentColor"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354l-4.502 2.826c-.995.608-2.23-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Set Key Comparison Flags</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Choose how this spec is treated in upgrade and caution logic.</p>
                    </div>
                </div>
            </div>

            <div class="px-5 py-5">
                <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-700/40 dark:bg-amber-900/10">
                    <div class="grid grid-cols-2 gap-x-6 gap-y-3">
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" x-model="keyCriteriaForm.upgrade_exceeds_value"
                                   @change="if(keyCriteriaForm.upgrade_exceeds_value){ keyCriteriaForm.upgrade_is_below_value=false; keyCriteriaForm.caution_if_exceeds_value=false; }"
                                   class="h-4 w-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                            <span>Upgrade exceeds value</span>
                        </label>
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" x-model="keyCriteriaForm.caution_if_below_value"
                                   @change="if(keyCriteriaForm.caution_if_below_value){ keyCriteriaForm.upgrade_is_below_value=false; }"
                                   class="h-4 w-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                            <span>Caution if below value</span>
                        </label>
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" x-model="keyCriteriaForm.upgrade_is_below_value"
                                   @change="if(keyCriteriaForm.upgrade_is_below_value){ keyCriteriaForm.upgrade_exceeds_value=false; keyCriteriaForm.caution_if_below_value=false; }"
                                   class="h-4 w-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                            <span>Upgrade is below value</span>
                        </label>
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" x-model="keyCriteriaForm.caution_if_exceeds_value"
                                   @change="if(keyCriteriaForm.caution_if_exceeds_value){ keyCriteriaForm.upgrade_exceeds_value=false; }"
                                   class="h-4 w-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                            <span>Caution if exceeds value</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-5 py-4 dark:border-gray-700">
                <button type="button" @click="cancelKeyModal()"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    Cancel
                </button>
                <button type="button" @click="confirmKeyFromModal()"
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354l-4.502 2.826c-.995.608-2.23-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"/></svg>
                    Mark as Key
                </button>
            </div>
        </div>
    </div>

    {{-- ─── Add New Specification Modal ────────────────────────────────── --}}
    <div x-show="showAddSpecModal"
         x-cloak
         class="fixed inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm"
         style="z-index: 9999;"
         @keydown.escape.window="showAddSpecModal = false">
        <div class="w-full max-w-xl rounded-2xl border border-emerald-100 bg-white shadow-xl dark:border-emerald-700/30 dark:bg-gray-900"
             @click.outside="showAddSpecModal = false">

            {{-- Modal header --}}
            <div class="flex items-center justify-between border-b border-emerald-100 px-6 py-4 dark:border-emerald-700/30">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                        <x-heroicon-o-plus-circle class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Add New Specification</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Define a spec not extracted automatically. AI Research will fill values per profile.</p>
                    </div>
                </div>
                <button type="button" @click="showAddSpecModal = false"
                        class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Fields --}}
            <div class="grid grid-cols-1 gap-4 px-6 py-5 sm:grid-cols-2">

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Display Label <span class="text-red-500">*</span>
                    </label>
                    <input type="text" x-model="addSpec.label"
                           @input="addSpec.keyPreview = addSpec.label.toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,'')"
                           placeholder="e.g. Outriggers Required"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Spec Key <span class="text-gray-400 font-normal">(auto-derived)</span>
                    </label>
                    <div class="flex items-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
                        <span class="font-mono text-sm text-gray-500 dark:text-gray-400" x-text="addSpec.keyPreview || '—'"></span>
                    </div>
                    <p class="mt-0.5 text-xs text-gray-400">Generated from the label — lowercase with underscores.</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Description / Research Instruction
                    </label>
                    <textarea x-model="addSpec.description" rows="2"
                              placeholder="e.g. Determine whether this boom lift requires outriggers/stabilizers for operation."
                              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"></textarea>
                    <p class="mt-0.5 text-xs text-gray-400">Sent to AI when you click "Research with AI" on the row.</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select x-model="addSpec.status"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="normal">Normal (Default)</option>
                        <option value="key">Key Comparison</option>
                        <option value="ignored">Ignored</option>
                    </select>
                </div>

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                <button type="button" @click="showAddSpecModal = false"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    Cancel
                </button>
                <button type="button" @click="saveNewSpec()"
                        :disabled="!addSpec.label.trim() || addSpecSaving"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <x-heroicon-o-check class="h-4 w-4" />
                    <span x-text="addSpecSaving ? 'Adding…' : 'Add Specification'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ─── Group Modal ─────────────────────────────────────────────────── --}}
    <div x-show="showGroupModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
         @keydown.escape.window="showGroupModal = false">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:border dark:border-gray-700 dark:bg-gray-900"
             @click.outside="showGroupModal = false">

            <h3 class="text-lg font-semibold text-gray-900 dark:text-white"
                x-text="editingGroupIdx !== null ? 'Edit Group' : 'Create Specification Group'"></h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-show="editingGroupIdx === null" x-cloak>
                <span class="font-medium text-gray-700 dark:text-gray-300" x-text="selected.length"></span>
                raw label(s) will be merged under one master specification.
            </p>

            <div class="mt-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Master Label <span class="text-red-500">*</span></label>
                    <input type="text" x-model="groupModalLabel" @input="autoFillKey()"
                           placeholder="e.g. Horsepower"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Master Key <span class="text-xs font-normal text-gray-400">(snake_case)</span></label>
                    <input type="text" x-model="groupModalKey"
                           class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 font-mono text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                </div>
                <div x-show="editingGroupIdx === null" x-cloak class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800">
                    <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Raw labels being grouped:</p>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="key in selected" :key="key">
                            <span class="inline-flex items-center rounded border border-gray-200 bg-white px-2 py-0.5 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                <span x-text="rowByKey(key) ? rowByKey(key).spec_label : key"></span>
                                <span class="ml-1 font-mono text-gray-400" x-text="'[' + key + ']'"></span>
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" @click="showGroupModal = false"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">Cancel</button>
                <button type="button" @click="applyGroup()"
                        :disabled="!groupModalLabel.trim()"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    <span x-text="editingGroupIdx !== null ? 'Update Group' : 'Create Group'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── AI Comparison Modal ─────────────────────────────────────────────── --}}
    <div x-show="showCompareModal"
         x-cloak
         x-transition
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 backdrop-blur-sm p-4 pt-10"
         @keydown.escape.window="showCompareModal = false">
        <div class="relative w-full max-w-4xl rounded-2xl bg-white shadow-2xl dark:border dark:border-gray-700 dark:bg-gray-900"
             @click.outside="showCompareModal = false">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="currentColor"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354l-4.502 2.826c-.995.608-2.23-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">AI Equipment Comparison</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $category->title }} · Key Comparison specs only
                        </p>
                    </div>
                </div>
                <button type="button" @click="showCompareModal = false"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5">
                {{-- Loading --}}
                <template x-if="comparing">
                    <div class="flex flex-col items-center justify-center gap-4 py-16">
                        <svg class="h-10 w-10 animate-spin text-amber-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Analyzing equipment specs with AI — this may take 15–30 seconds…</p>
                    </div>
                </template>

                {{-- Error --}}
                <template x-if="!comparing && compareError">
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-700 dark:border-red-700/50 dark:bg-red-900/20 dark:text-red-400" x-text="compareError"></div>
                </template>

                {{-- Result --}}
                <template x-if="!comparing && compareResult">
                    <div class="prose prose-sm max-w-none dark:prose-invert prose-headings:text-gray-800 prose-strong:text-gray-700 dark:prose-headings:text-gray-200 dark:prose-strong:text-gray-300">
                        <div class="whitespace-pre-wrap text-sm leading-relaxed text-gray-700 dark:text-gray-300" x-text="compareResult"></div>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <template x-if="!comparing && compareResult">
                <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                    <p class="text-xs text-gray-400 dark:text-gray-600">AI analysis is based on the Key Comparison specs in this matrix. Always verify critical values before making rental decisions.</p>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection

@push('js')
<script>
function specMatrix() {
    return {
        // ── Raw data from PHP ─────────────────────────────────────────────
        rows: @js($matrix),
        categoryId: {{ $category->id }},

        // ── Profile name map (for cell editor) ───────────────────────────
        profileNames: {
            @foreach ($profiles as $profile)
                {{ $profile->id }}: '{{ addslashes($profile->make . ' ' . $profile->model) }}',
            @endforeach
        },

        // ── Profile spec-page base URLs (for check-icon links) ───────────
        profileSpecUrls: {
            @foreach ($profiles as $profile)
                {{ $profile->id }}: '{{ route('admin.maintenance-management.equipment-ai.profiles.specifications.index', $profile->unique_id) }}',
            @endforeach
        },

        // ── Groups ───────────────────────────────────────────────────────
        groups: [],
        nextGroupId: 1,

        // ── Selection ────────────────────────────────────────────────────
        selected: [],

        // ── Group modal ──────────────────────────────────────────────────
        showGroupModal:  false,
        groupModalLabel: '',
        groupModalKey:   '',
        editingGroupIdx: null,

        // ── Key criteria modal ────────────────────────────────────────────
        showKeyCriteriaModal: false,
        pendingKeyTarget: null,
        keyCriteriaForm: {
            upgrade_exceeds_value:    true,
            caution_if_exceeds_value: false,
            upgrade_is_below_value:   false,
            caution_if_below_value:   false,
        },

        // ── Add spec modal ────────────────────────────────────────────────
        showAddSpecModal: false,
        addSpecSaving:    false,
        addSpec: {
            label:      '',
            keyPreview: '',
            description:'',
            status:     'normal',
        },

        // ── Cell editor ──────────────────────────────────────────────────
        showCellEditor:         false,
        cellEditorSaving:       false,
        cellEditorRowSpecKey:   null,
        cellEditorProfileId:    null,
        cellEditorValueId:      null,
        cellEditorValue:        '',
        cellEditorLabel:        '',
        cellEditorProfileName:  '',
        cellEditorAllowedValues: [],

        // ── AI research state ────────────────────────────────────────────
        researchingSpecId: null,
        researchingCell: null,   // "spec_key:profileId" while per-cell research runs
        fillingGaps: false,

        // ── Save state ────────────────────────────────────────────────────
        saving: false,

        // ── AI Comparison ─────────────────────────────────────────────────
        comparing:        false,
        showCompareModal: false,
        compareResult:    null,
        compareError:     null,

        // ── Color palette ────────────────────────────────────────────────
        colorDefs: [
            { headerBg: 'bg-purple-50 dark:bg-purple-900/10', memberBg: 'bg-purple-50/40 dark:bg-purple-900/5', badgeBg: 'bg-purple-100 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400', badge: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300', accentLine: 'bg-purple-300 dark:bg-purple-700' },
            { headerBg: 'bg-blue-50 dark:bg-blue-900/10',   memberBg: 'bg-blue-50/40 dark:bg-blue-900/5',   badgeBg: 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400',     badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',   accentLine: 'bg-blue-300 dark:bg-blue-700' },
            { headerBg: 'bg-green-50 dark:bg-green-900/10', memberBg: 'bg-green-50/40 dark:bg-green-900/5', badgeBg: 'bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400', badge: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300', accentLine: 'bg-green-300 dark:bg-green-700' },
            { headerBg: 'bg-orange-50 dark:bg-orange-900/10', memberBg: 'bg-orange-50/40 dark:bg-orange-900/5', badgeBg: 'bg-orange-100 text-orange-600 dark:bg-orange-900/40 dark:text-orange-400', badge: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300', accentLine: 'bg-orange-300 dark:bg-orange-700' },
            { headerBg: 'bg-teal-50 dark:bg-teal-900/10',   memberBg: 'bg-teal-50/40 dark:bg-teal-900/5',   badgeBg: 'bg-teal-100 text-teal-600 dark:bg-teal-900/40 dark:text-teal-400',     badge: 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',   accentLine: 'bg-teal-300 dark:bg-teal-700' },
            { headerBg: 'bg-pink-50 dark:bg-pink-900/10',   memberBg: 'bg-pink-50/40 dark:bg-pink-900/5',   badgeBg: 'bg-pink-100 text-pink-600 dark:bg-pink-900/40 dark:text-pink-400',     badge: 'bg-pink-100 text-pink-700 dark:bg-pink-900/40 dark:text-pink-300',   accentLine: 'bg-pink-300 dark:bg-pink-700' },
        ],

        // ── Status helpers ────────────────────────────────────────────────
        getStatus(obj) {
            if (obj.is_key_comparison) return 'key';
            if (obj.is_ignored)        return 'ignored';
            return 'normal';
        },
        statusOrder(status) {
            return status === 'key' ? 0 : status === 'ignored' ? 2 : 1;
        },
        toggleKey(target) {
            if (target.is_key_comparison) {
                // Toggle OFF — no modal needed
                target.is_key_comparison = false;
                if (target.custom_spec_id) {
                    this.persistCustomSpecStatus(target);
                } else {
                    this.persistExtractedSpecStatus(target);
                }
            } else {
                // Toggle ON — open criteria modal first
                this.pendingKeyTarget = target;
                this.keyCriteriaForm = {
                    upgrade_exceeds_value:    target.criteria_flags?.upgrade_exceeds_value    ?? true,
                    caution_if_exceeds_value: target.criteria_flags?.caution_if_exceeds_value ?? false,
                    upgrade_is_below_value:   target.criteria_flags?.upgrade_is_below_value   ?? false,
                    caution_if_below_value:   target.criteria_flags?.caution_if_below_value   ?? false,
                };
                this.showKeyCriteriaModal = true;
            }
        },

        confirmKeyFromModal() {
            const target = this.pendingKeyTarget;
            if (!target) return;
            target.is_key_comparison = true;
            target.is_ignored        = false;
            target.criteria_flags    = { ...this.keyCriteriaForm };
            this.showKeyCriteriaModal = false;
            this.pendingKeyTarget     = null;
            if (target.custom_spec_id) {
                this.persistCustomSpecStatus(target);
            } else {
                this.persistExtractedSpecStatus(target);
            }
        },

        cancelKeyModal() {
            this.showKeyCriteriaModal = false;
            this.pendingKeyTarget     = null;
        },
        toggleIgnore(target) {
            if (target.is_ignored) {
                target.is_ignored = false;
            } else {
                target.is_ignored        = true;
                target.is_key_comparison = false;
            }
            if (target.custom_spec_id) {
                this.persistCustomSpecStatus(target);
            } else {
                this.persistExtractedSpecStatus(target);
            }
        },
        persistCustomSpecStatus(row) {
            fetch(`/maintenance-management/equipment-ai/commonize/custom-specs/${row.custom_spec_id}/status`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({
                    is_key_comparison: row.is_key_comparison,
                    is_ignored:        row.is_ignored,
                    ...(row.is_key_comparison && row.criteria_flags ? row.criteria_flags : {}),
                }),
            });
        },

        persistExtractedSpecStatus(row) {
            fetch('/maintenance-management/equipment-ai/commonize/specs/status', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({
                    category_id:       this.categoryId,
                    spec_key:          row.spec_key,
                    is_key_comparison: row.is_key_comparison,
                    is_ignored:        row.is_ignored,
                    ...(row.is_key_comparison && row.criteria_flags ? row.criteria_flags : {}),
                }),
            });
        },

        // ── displayItems ─────────────────────────────────────────────────
        get displayItems() {
            const items   = [];
            const grouped = new Set(this.groups.flatMap(g => g.member_keys));
            const topItems = [];

            this.groups.forEach((group, gIdx) => {
                topItems.push({ kind: 'group', group, gIdx, status: this.getStatus(group) });
            });

            this.rows.forEach(row => {
                if (!grouped.has(row.spec_key)) {
                    topItems.push({ kind: 'row', row, status: this.getStatus(row) });
                }
            });

            topItems.sort((a, b) => {
                const aO = this.statusOrder(a.status);
                const bO = this.statusOrder(b.status);
                if (aO !== bO) return aO - bO;
                const aL = a.kind === 'group' ? a.group.master_label : a.row.spec_label;
                const bL = b.kind === 'group' ? b.group.master_label : b.row.spec_label;
                return aL.localeCompare(bL, undefined, { sensitivity: 'base' });
            });

            let lastSection = null;
            topItems.forEach(topItem => {
                if (topItem.status !== lastSection) {
                    items.push({ type: 'section_header', section: topItem.status });
                    lastSection = topItem.status;
                }
                if (topItem.kind === 'group') {
                    const { group, gIdx, status } = topItem;
                    group.member_keys.forEach((specKey, mIdx) => {
                        items.push({
                            type: mIdx === 0 ? 'group_header' : 'group_member',
                            gIdx, group, specKey, mIdx,
                            row: this.rowByKey(specKey),
                            status,
                        });
                    });
                } else {
                    items.push({ type: 'ungrouped', row: topItem.row, status: topItem.status });
                }
            });

            return items;
        },

        // ── Counts ───────────────────────────────────────────────────────
        get keyCount() {
            const grouped = new Set(this.groups.flatMap(g => g.member_keys));
            return this.groups.filter(g => g.is_key_comparison).length
                 + this.rows.filter(r => !grouped.has(r.spec_key) && r.is_key_comparison).length;
        },
        get normalCount() {
            const grouped = new Set(this.groups.flatMap(g => g.member_keys));
            return this.groups.filter(g => !g.is_key_comparison && !g.is_ignored).length
                 + this.rows.filter(r => !grouped.has(r.spec_key) && !r.is_key_comparison && !r.is_ignored).length;
        },
        get ignoredCount() {
            const grouped = new Set(this.groups.flatMap(g => g.member_keys));
            return this.groups.filter(g => g.is_ignored).length
                 + this.rows.filter(r => !grouped.has(r.spec_key) && r.is_ignored).length;
        },

        // ── Framework JSON (extracted specs only — custom specs auto-persist) ──
        get frameworkJson() {
            const grouped = new Set(this.groups.flatMap(g => g.member_keys));
            const items = [];

            this.groups.forEach(group => {
                items.push({
                    master_key:        group.master_key,
                    master_label:      group.master_label,
                    raw_keys:          [...group.member_keys],
                    is_key_comparison: group.is_key_comparison,
                    is_ignored:        group.is_ignored && !group.is_key_comparison,
                    criteria_flags:    group.criteria_flags ?? {},
                });
            });

            this.rows.forEach(row => {
                if (!grouped.has(row.spec_key) && !row.is_custom) {
                    items.push({
                        master_key:        row.spec_key,
                        master_label:      row.spec_label,
                        raw_keys:          [row.spec_key],
                        is_key_comparison: row.is_key_comparison,
                        is_ignored:        row.is_ignored && !row.is_key_comparison,
                        criteria_flags:    row.criteria_flags ?? {},
                    });
                }
            });

            return JSON.stringify(items);
        },

        // ── Row / cell styling ────────────────────────────────────────────
        rowClass(item) {
            if (!item) return '';
            if (item.type === 'section_header') {
                if (item.section === 'key')     return 'bg-amber-50/60 dark:bg-amber-900/5';
                if (item.section === 'ignored') return 'bg-gray-50/80 dark:bg-gray-800/40';
                return 'bg-gray-50/40 dark:bg-gray-800/20';
            }
            if (item.type === 'group_header') return this.colorDefs[item.gIdx % this.colorDefs.length].headerBg;
            if (item.type === 'group_member') return this.colorDefs[item.gIdx % this.colorDefs.length].memberBg;
            if (item.type === 'ungrouped') {
                if (item.status === 'ignored') return 'bg-gray-50/60 dark:bg-gray-800/30';
                if (item.row.is_custom)        return 'bg-emerald-50/20 hover:bg-emerald-50/40 dark:bg-emerald-900/5 dark:hover:bg-emerald-900/10';
                if (this.isSelected(item.row.spec_key)) return 'bg-indigo-50 dark:bg-indigo-900/10';
                if (item.status === 'key')     return 'bg-amber-50/30 dark:bg-amber-900/5 hover:bg-amber-50/60';
                return 'hover:bg-gray-50/70 dark:hover:bg-gray-800/30';
            }
            return '';
        },

        stickyBg(item) {
            if (!item) return 'bg-white dark:bg-gray-900';
            if (item.type === 'section_header') {
                if (item.section === 'key')     return 'bg-amber-50/60 dark:bg-amber-900/5';
                if (item.section === 'ignored') return 'bg-gray-50/80 dark:bg-gray-800/40';
                return 'bg-gray-50/40 dark:bg-gray-800/20';
            }
            if (item.type === 'group_header') return this.colorDefs[item.gIdx % this.colorDefs.length].headerBg;
            if (item.type === 'group_member') return this.colorDefs[item.gIdx % this.colorDefs.length].memberBg;
            if (item.type === 'ungrouped') {
                if (item.status === 'ignored') return 'bg-gray-50/60 dark:bg-gray-800/30';
                if (item.row.is_custom)        return 'bg-emerald-50/20 dark:bg-emerald-900/5';
                if (this.isSelected(item.row.spec_key)) return 'bg-indigo-50 dark:bg-indigo-900/10';
            }
            return 'bg-white dark:bg-gray-900';
        },

        // ── Custom spec cell helpers ──────────────────────────────────────
        cellDisplayValue(cell) {
            if (!cell || cell.value === null || cell.value === undefined || cell.value === '') return '—';
            return cell.value;
        },
        customCellClass(cell) {
            if (!cell || !cell.value) return 'text-gray-300 dark:text-gray-700';
            if (cell.confirmed) return 'text-emerald-700 bg-emerald-50 dark:text-emerald-300 dark:bg-emerald-900/20';
            if (cell.source === 'ai') return 'text-indigo-700 bg-indigo-50 dark:text-indigo-300 dark:bg-indigo-900/20';
            return 'text-gray-600 dark:text-gray-300';
        },
        cellTooltip(cell) {
            if (!cell || !cell.value) return 'Click to set value';
            let tip = cell.source === 'ai' ? 'AI-filled' : 'Manual';
            if (cell.confidence) tip += ` · confidence ${Math.round(cell.confidence * 100)}%`;
            if (cell.reason)     tip += `\n${cell.reason}`;
            return tip;
        },

        // ── Cell editor ───────────────────────────────────────────────────
        openCellEditor(row, profileId) {
            this.cellEditorRowSpecKey    = row.spec_key;
            this.cellEditorProfileId     = profileId;
            const cell                   = row.values[profileId] || {};
            this.cellEditorValueId       = cell.id || null;
            this.cellEditorValue         = cell.value || '';
            this.cellEditorLabel         = row.spec_label;
            this.cellEditorProfileName   = this.profileNames[profileId] || `Profile ${profileId}`;
            this.cellEditorAllowedValues = row.allowed_values || [];
            this.cellEditorSaving        = false;
            this.showCellEditor          = true;
        },

        async saveCellValue() {
            if (!this.cellEditorValueId) return;
            this.cellEditorSaving = true;
            try {
                const resp = await fetch(
                    `/maintenance-management/equipment-ai/commonize/custom-spec-values/${this.cellEditorValueId}`,
                    {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ spec_value: this.cellEditorValue || null }),
                    }
                );
                const data = await resp.json();
                if (data.success) {
                    // Update the local row
                    const row = this.rows.find(r => r.spec_key === this.cellEditorRowSpecKey);
                    if (row && row.values) {
                        row.values[this.cellEditorProfileId].value     = data.value;
                        row.values[this.cellEditorProfileId].source    = data.source;
                        row.values[this.cellEditorProfileId].confirmed = true;
                        row.presence[this.cellEditorProfileId]         = data.value !== null;
                    }
                    this.showCellEditor = false;
                }
            } finally {
                this.cellEditorSaving = false;
            }
        },

        // ── Add New Specification ─────────────────────────────────────────
        openAddSpecModal() {
            this.addSpec = { label: '', keyPreview: '', description: '', status: 'normal' };
            this.addSpecSaving    = false;
            this.showAddSpecModal = true;
        },

        async saveNewSpec() {
            if (!this.addSpec.label.trim()) return;
            this.addSpecSaving = true;
            try {
                const resp = await fetch('/maintenance-management/equipment-ai/commonize/custom-specs', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        category_id:       this.categoryId,
                        spec_label:        this.addSpec.label.trim(),
                        description:       this.addSpec.description.trim() || null,
                        is_key_comparison: this.addSpec.status === 'key',
                        is_ignored:        this.addSpec.status === 'ignored',
                    }),
                });
                const data = await resp.json();
                if (data.success) {
                    this.rows.push(data.row);
                    this.showAddSpecModal = false;
                }
            } finally {
                this.addSpecSaving = false;
            }
        },

        // ── Per-cell matrix research (extracted specs) ────────────────────
        async researchForProfile(row, profileId) {
            const cellKey = row.spec_key + ':' + profileId;
            if (this.researchingCell) return;
            this.researchingCell = cellKey;
            try {
                const resp = await fetch('/maintenance-management/equipment-ai/specifications/research-for-profile', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        profile_id: profileId,
                        spec_key:   row.spec_key,
                        spec_label: row.spec_label,
                    }),
                });
                const data = await resp.json();
                if (data.success && data.found) {
                    const liveRow = this.rows.find(r => r.spec_key === row.spec_key);
                    if (liveRow) liveRow.presence[profileId] = true;
                } else if (data.success && !data.found) {
                    alert('AI could not verify this specification with sufficient confidence for this model.');
                } else {
                    alert(data.message || 'Research failed.');
                }
            } catch (e) {
                console.error('researchForProfile error', e);
                alert('Request failed. Please check your connection.');
            } finally {
                this.researchingCell = null;
            }
        },

        // ── Fill gaps across entire category ─────────────────────────────
        async fillCategoryGaps() {
            if (this.fillingGaps) return;
            if (!confirm('Insert placeholder rows for every missing model × spec combination in this category?\n\nThis ensures the matrix is fully populated. It may take a moment.')) return;
            this.fillingGaps = true;
            try {
                const resp = await fetch('/maintenance-management/equipment-ai/category-fill-gaps', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ category_id: this.categoryId }),
                });
                const data = await resp.json();
                if (data.success) {
                    alert(data.message || 'Done.');
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed.');
                }
            } catch (e) {
                console.error('fillCategoryGaps error', e);
                alert('Request failed.');
            } finally {
                this.fillingGaps = false;
            }
        },

        // ── AI Research ───────────────────────────────────────────────────
        async researchSpec(customSpecId) {
            if (this.researchingSpecId) return;
            this.researchingSpecId = customSpecId;
            try {
                const resp = await fetch(
                    `/maintenance-management/equipment-ai/commonize/custom-specs/${customSpecId}/research`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({}),
                    }
                );
                const data = await resp.json();
                if (data.success) {
                    // Merge returned values into the row
                    const row = this.rows.find(r => r.custom_spec_id === customSpecId);
                    if (row && data.values) {
                        Object.entries(data.values).forEach(([pid, val]) => {
                            row.values[pid]   = val;
                            row.presence[pid] = val.value !== null;
                        });
                    }
                } else {
                    alert(data.message || 'AI research failed.');
                }
            } finally {
                this.researchingSpecId = null;
            }
        },

        // ── AI Equipment Comparison ───────────────────────────────────────
        async runComparison() {
            this.compareResult    = null;
            this.compareError     = null;
            this.comparing        = true;
            this.showCompareModal = true;
            try {
                const resp = await fetch('/maintenance-management/equipment-ai/compare', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ category_id: this.categoryId }),
                });
                const data = await resp.json();
                if (data.success) {
                    this.compareResult = data.analysis;
                } else {
                    this.compareError = data.message || 'Comparison failed.';
                }
            } catch (e) {
                console.error('runComparison error', e);
                this.compareError = 'Request failed. Please check your connection.';
            } finally {
                this.comparing = false;
            }
        },

        // ── Delete custom spec ────────────────────────────────────────────
        async deleteCustomSpec(customSpecId, specKey) {
            if (!confirm('Remove this custom specification and all its values?')) return;
            const resp = await fetch(
                `/maintenance-management/equipment-ai/commonize/custom-specs/${customSpecId}`,
                {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                }
            );
            if ((await resp.json()).success) {
                this.rows = this.rows.filter(r => r.spec_key !== specKey);
            }
        },

        // ── General helpers ───────────────────────────────────────────────
        rowByKey(specKey) {
            return this.rows.find(r => r.spec_key === specKey) || null;
        },
        isSelected(specKey) {
            return this.selected.includes(specKey);
        },
        toggleSelected(specKey) {
            const idx = this.selected.indexOf(specKey);
            if (idx === -1) this.selected.push(specKey);
            else            this.selected.splice(idx, 1);
        },
        groupPresence(group, profileId) {
            return group.member_keys.some(k => {
                const row = this.rowByKey(k);
                return row ? (row.presence[profileId] || false) : false;
            });
        },

        // ── Group modal ───────────────────────────────────────────────────
        openGroupModal() {
            if (this.selected.length < 2) return;
            const first          = this.rowByKey(this.selected[0]);
            this.groupModalLabel = first ? first.spec_label : '';
            this.groupModalKey   = first ? first.spec_key   : '';
            this.editingGroupIdx = null;
            this.showGroupModal  = true;
        },
        editGroup(gIdx) {
            const g              = this.groups[gIdx];
            this.groupModalLabel = g.master_label;
            this.groupModalKey   = g.master_key;
            this.editingGroupIdx = gIdx;
            this.showGroupModal  = true;
        },
        autoFillKey() {
            this.groupModalKey = (this.groupModalLabel || '')
                .toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        },
        applyGroup() {
            if (!this.groupModalLabel.trim()) return;
            const masterLabel = this.groupModalLabel.trim();
            const masterKey   = (this.groupModalKey.trim() || masterLabel)
                .toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');

            if (this.editingGroupIdx !== null) {
                this.groups[this.editingGroupIdx].master_label = masterLabel;
                this.groups[this.editingGroupIdx].master_key   = masterKey;
            } else {
                this.groups.forEach(g => {
                    g.member_keys = g.member_keys.filter(k => !this.selected.includes(k));
                });
                this.groups = this.groups.filter(g => g.member_keys.length > 0);

                const anyKey     = this.selected.some(k => this.rowByKey(k)?.is_key_comparison);
                const anyIgnored = !anyKey && this.selected.every(k => this.rowByKey(k)?.is_ignored);

                this.groups.push({
                    id: this.nextGroupId++,
                    master_label: masterLabel,
                    master_key:   masterKey,
                    is_key_comparison: anyKey,
                    is_ignored:        anyIgnored,
                    member_keys: [...this.selected],
                });
                this.selected = [];
            }
            this.showGroupModal  = false;
            this.editingGroupIdx = null;
        },
        removeGroup(gIdx) {
            this.groups.splice(gIdx, 1);
        },

        submitFramework(form) {
            this.saving = true;
            form.submit();
        },
    };
}
</script>
@endpush
