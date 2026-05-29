@extends('admin.layouts.app')

@section('title', 'Equipment Management AI')

@section('content')
<div class="space-y-6">

    {{-- ─── Page Header ─────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Equipment Management AI</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Scan categories to build AI-ready equipment profiles. Specs are stored per unique make/model — not per individual unit.
            </p>
        </div>
        <a href="{{ route('admin.maintenance-management.equipment-ai.comparison-keys.index') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
            <x-heroicon-o-adjustments-horizontal class="h-4 w-4" />
            Manage Comparison Keys
        </a>
    </div>

    {{-- ─── Flash Messages ───────────────────────────────────────────── --}}
    @if (session('success'))
        <div class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700/50 dark:bg-green-900/20 dark:text-green-400">
            <x-heroicon-o-check-circle class="h-5 w-5 shrink-0" />
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-700/50 dark:bg-red-900/20 dark:text-red-400">
            <x-heroicon-o-exclamation-circle class="h-5 w-5 shrink-0" />
            {{ session('error') }}
        </div>
    @endif

    {{-- ─── Category Selector + Scan ────────────────────────────────── --}}
    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Category Filter & Scan</h2>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                Select a category to view its AI profiles, or scan to generate missing profiles from existing equipment records.
            </p>
        </div>

        <div class="px-6 py-5">
            {{-- View filter --}}
            <form method="GET" action="{{ route('admin.maintenance-management.equipment-ai.index') }}"
                  class="flex flex-col sm:flex-row gap-3">
                <select name="category_id"
                    class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">— Select a Category —</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ ($selectedCategory && $selectedCategory->id == $cat->id) ? 'selected' : '' }}>
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

            {{-- Scan / Refresh actions --}}
            @if ($selectedCategory)
                <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
                    <p class="flex-1 text-sm text-gray-600 dark:text-gray-400">
                        Manage AI profiles for
                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $selectedCategory->title }}</span>.
                    </p>
                    <div class="flex items-center gap-2 shrink-0">

                        {{-- Refresh: re-syncs profiles to current equipment data ──────── --}}
                        {{-- Removes stale (no matching equipment + no specs), flags orphans --}}
                        <form method="POST"
                              action="{{ route('admin.maintenance-management.equipment-ai.refresh') }}">
                            @csrf
                            <input type="hidden" name="category_id" value="{{ $selectedCategory->id }}">
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg border border-amber-400 px-4 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-1 dark:border-amber-500 dark:text-amber-400 dark:hover:bg-amber-900/20"
                                onclick="return confirm('Refresh will remove stale profiles that no longer match any equipment record (only if they have no specs). Orphaned profiles WITH specs will be flagged for manual review — not deleted. Continue?')">
                                <x-heroicon-o-arrow-path class="h-4 w-4" />
                                Refresh Category Profiles
                            </button>
                        </form>

                        {{-- Scan: only adds new profiles, never removes existing ones ──── --}}
                        <form method="POST"
                              action="{{ route('admin.maintenance-management.equipment-ai.scan') }}">
                            @csrf
                            <input type="hidden" name="category_id" value="{{ $selectedCategory->id }}">
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                onclick="return confirm('Scan {{ addslashes($selectedCategory->title) }} for new unique make/model combinations? Existing profiles are not affected.')">
                                <x-heroicon-o-magnifying-glass class="h-4 w-4" />
                                Scan Category for Unique Equipment
                            </button>
                        </form>

                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ─── Profiles Table ──────────────────────────────────────────── --}}
    @if ($selectedCategory)
        @include('admin.maintenance_management.equipment_ai.partials._profiles_table')
    @else
        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <x-heroicon-o-cpu-chip class="h-12 w-12 text-gray-300 dark:text-gray-600" />
                <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">Select a category above to view AI profiles.</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                    Use "Scan Category" to auto-generate profiles from your existing equipment records.
                </p>
            </div>
        </div>
    @endif

</div>
@endsection
