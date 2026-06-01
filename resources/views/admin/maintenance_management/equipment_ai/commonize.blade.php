@extends('admin.layouts.app')

@section('title', 'Compare & Commonize — ' . $category->title)

@section('content')
<div class="space-y-6"
     x-data="{
         groups:   @js($groups),
         selected: @js(array_keys($groups)),   /* all checked by default */

         toggle(index) {
             const pos = this.selected.indexOf(index);
             if (pos === -1) { this.selected.push(index); }
             else            { this.selected.splice(pos, 1); }
         },

         isSelected(index) { return this.selected.includes(index); },

         get selectedGroups() { return this.groups.filter((_, i) => this.selected.includes(i)); },
         get selectedJson()   { return JSON.stringify(this.selectedGroups); },
         get selectedCount()  { return this.selected.length; },

         selectAll()  { this.selected = this.groups.map((_, i) => i); },
         selectNone() { this.selected = []; },
     }">

    {{-- ─── Breadcrumb ─────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ route('admin.maintenance-management.equipment-ai.index', ['category_id' => $category->id]) }}"
           class="hover:text-brand-500">Equipment Management AI</a>
        <x-heroicon-o-chevron-right class="h-4 w-4" />
        <span class="font-medium text-gray-800 dark:text-gray-200">Compare &amp; Commonize — {{ $category->title }}</span>
    </div>

    {{-- ─── Header Card ─────────────────────────────────────────────────── --}}
    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 px-6 py-5">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-100 dark:bg-purple-900/30">
                    <x-heroicon-o-arrows-right-left class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Compare &amp; Commonize</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Category: <strong class="text-gray-700 dark:text-gray-300">{{ $category->title }}</strong>
                        · {{ $specCount }} unique spec keys across {{ $profileCount }} profile(s)
                        @if (count($groups) > 0)
                            · <span class="text-purple-600 dark:text-purple-400 font-medium">{{ count($groups) }} synonym group(s) found</span>
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.maintenance-management.equipment-ai.index', ['category_id' => $category->id]) }}"
               class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 focus:outline-none dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                <x-heroicon-o-arrow-left class="h-4 w-4" />
                Back to Profiles
            </a>
        </div>
    </div>

    @if (count($groups) === 0)

        {{-- ─── Empty state ─────────────────────────────────────────────── --}}
        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <x-heroicon-o-check-badge class="mb-3 h-12 w-12 text-green-400" />
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">No Synonym Groups Found</h2>
                <p class="mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                    All {{ $specCount }} specification keys across this category appear to be unique.
                    No commonization is needed at this time.
                </p>
                <a href="{{ route('admin.maintenance-management.equipment-ai.index', ['category_id' => $category->id]) }}"
                   class="mt-6 inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600">
                    <x-heroicon-o-arrow-left class="h-4 w-4" />
                    Back to Profiles
                </a>
            </div>
        </div>

    @else

        {{-- ─── Info banner ──────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-amber-100 bg-amber-50/60 px-5 py-4 dark:border-amber-700/30 dark:bg-amber-900/10">
            <div class="flex gap-3">
                <x-heroicon-o-information-circle class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />
                <p class="text-sm text-amber-800 dark:text-amber-300">
                    <strong>Review each group below.</strong>
                    Checked groups will be merged — all equipment specs using the listed member keys will be renamed to
                    the canonical key and label. If a profile already has both a member and the canonical key, the
                    duplicate is removed and the canonical is kept.
                    Uncheck any group you do not want to apply.
                </p>
            </div>
        </div>

        {{-- ─── Groups form ──────────────────────────────────────────────── --}}
        <form method="POST"
              action="{{ route('admin.maintenance-management.equipment-ai.commonize.apply') }}">
            @csrf
            <input type="hidden" name="category_id"     value="{{ $category->id }}">
            <input type="hidden" name="approved_groups" :value="selectedJson">

            {{-- Select all / none --}}
            <div class="mb-3 flex items-center gap-4 px-1">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    <span x-text="selectedCount"></span> of {{ count($groups) }} group(s) selected
                </span>
                <button type="button" @click="selectAll()"
                    class="text-sm text-brand-500 hover:underline">Select all</button>
                <button type="button" @click="selectNone()"
                    class="text-sm text-gray-400 hover:underline dark:text-gray-500">Deselect all</button>
            </div>

            {{-- Group cards --}}
            <div class="space-y-3">
                @foreach ($groups as $index => $group)
                    <div class="rounded-2xl border bg-white shadow-sm transition-colors dark:bg-gray-900"
                         :class="isSelected({{ $index }})
                             ? 'border-purple-300 dark:border-purple-600'
                             : 'border-gray-100 dark:border-gray-700'">

                        <div class="flex items-start gap-4 px-5 py-4">

                            {{-- Checkbox ──────────────────────────────────── --}}
                            <div class="mt-0.5 shrink-0">
                                <input type="checkbox"
                                       :checked="isSelected({{ $index }})"
                                       @change="toggle({{ $index }})"
                                       class="h-5 w-5 cursor-pointer rounded border-gray-300 text-purple-600 focus:ring-purple-500 dark:border-gray-600">
                            </div>

                            {{-- Content ────────────────────────────────────── --}}
                            <div class="min-w-0 flex-1">

                                {{-- Canonical target --}}
                                <div class="mb-2 flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Merge into →</span>
                                    <span class="inline-flex items-center rounded-md bg-purple-100 px-2.5 py-1 text-sm font-semibold text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                                        {{ $group['canonical_label'] }}
                                    </span>
                                    <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $group['canonical_key'] }}</code>
                                </div>

                                {{-- Members --}}
                                <div class="mb-2 flex flex-wrap items-center gap-1.5">
                                    <span class="text-xs text-gray-400 dark:text-gray-500">From:</span>
                                    @foreach ($group['members'] as $member)
                                        <span class="inline-flex items-center rounded border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                            {{ $member['spec_label'] }}
                                            <span class="ml-1 text-gray-400 dark:text-gray-500">({{ $member['spec_key'] }})</span>
                                        </span>
                                    @endforeach
                                </div>

                                {{-- AI reason --}}
                                @if (!empty($group['reason']))
                                    <p class="text-xs italic text-gray-400 dark:text-gray-500">{{ $group['reason'] }}</p>
                                @endif

                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ─── Footer actions ─────────────────────────────────────────── --}}
            <div class="mt-6 flex items-center justify-between rounded-2xl border border-gray-100 bg-white px-6 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <a href="{{ route('admin.maintenance-management.equipment-ai.index', ['category_id' => $category->id]) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    Cancel — no changes
                </a>
                <button type="submit"
                        :disabled="selectedCount === 0"
                        class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-5 py-2 text-sm font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50">
                    <x-heroicon-o-arrows-right-left class="h-4 w-4" />
                    Apply
                    <span x-text="selectedCount"></span>
                    Group<span x-show="selectedCount !== 1">s</span>
                </button>
            </div>

        </form>

    @endif

</div>
@endsection
