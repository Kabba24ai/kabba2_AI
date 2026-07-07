@extends('admin.layouts.app')

@section('title', 'Customer Price List')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    <div class="max-w-[1440px] mx-auto">

        @include('admin.documents.partials._price_list_nav')

        <div class="flex flex-col lg:flex-row gap-6 items-start">

            {{-- Main workflow column --}}
            <div class="flex-1 min-w-0 w-full">
                <form method="GET" action="{{ route('admin.documents.price-list.generate') }}" target="_blank">

                    {{-- Section 1 — Choose an Industry Preset --}}
                    @if ($presets->isNotEmpty())
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
                            <div class="flex flex-wrap items-start justify-between gap-3 mb-1">
                                <div>
                                    <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2.5">
                                        <span class="w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold grid place-items-center shrink-0">1</span>
                                        Choose an Industry Preset
                                        <span class="text-sm font-normal text-gray-400">(optional)</span>
                                    </h2>
                                    <p class="text-xs text-gray-400 mt-1.5 ml-[34px]">
                                        Select one or more presets to quickly load relevant categories. You can always add or remove categories below.
                                    </p>
                                </div>
                                <button type="button" id="pl-clear-presets"
                                    class="text-sm font-medium text-gray-600 border border-gray-200 rounded-lg px-3.5 py-2 hover:text-red-600 hover:border-red-200 transition">
                                    Clear All Presets
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3 mt-4">
                                @foreach ($presets as $preset)
                                    <button type="button"
                                        class="pl-preset group text-left rounded-xl border-2 border-gray-200 hover:border-blue-300 transition overflow-hidden bg-white"
                                        data-preset="{{ $preset['key'] }}"
                                        data-category-ids="{{ implode(',', $preset['category_ids']) }}"
                                        data-label="{{ $preset['label'] }}"
                                        aria-pressed="false">
                                        <span class="pl-preset-thumb relative block aspect-square w-full bg-gray-100">
                                            @if (!empty($preset['thumbnail_url']))
                                                <img src="{{ $preset['thumbnail_url'] }}" alt=""
                                                    class="absolute inset-0 w-full h-full object-cover">
                                            @else
                                                <span class="absolute inset-0 grid place-items-center text-gray-300">
                                                    <x-heroicon-o-photo class="w-10 h-10" />
                                                </span>
                                            @endif
                                            <span class="pl-preset-check absolute top-2 right-2 w-5 h-5 rounded-md border-2 border-white bg-white/80 shadow grid place-items-center text-transparent">
                                                <x-heroicon-s-check class="w-3.5 h-3.5" />
                                            </span>
                                        </span>
                                        <span class="block p-3">
                                            <span class="block text-sm font-semibold text-gray-800">{{ $preset['label'] }}</span>
                                            @if ($preset['description'] !== '')
                                                <span class="block text-xs text-gray-400 mt-1">{{ $preset['description'] }}</span>
                                            @endif
                                            <span class="block text-[11px] font-medium text-blue-600 mt-1.5">
                                                {{ count($preset['category_ids']) }} {{ Str::plural('category', count($preset['category_ids'])) }}
                                            </span>
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Section 2 — Choose Categories --}}
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
                        <div class="flex flex-wrap items-start justify-between gap-3 mb-1">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2.5">
                                    <span class="w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold grid place-items-center shrink-0">2</span>
                                    Choose Categories
                                </h2>
                                <p class="text-xs text-gray-400 mt-1.5 ml-[34px]">
                                    Select the categories to include on the price list.
                                </p>
                            </div>
                            <div class="flex items-center gap-4">
                                <button type="button" id="pl-clear"
                                    class="text-sm font-medium text-blue-600 hover:text-red-600 transition">Clear Selection</button>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                                    <input type="checkbox" id="pl-select-all" class="rounded border-gray-300">
                                    Select All
                                </label>
                            </div>
                        </div>

                        @error('category_ids')
                            <p class="text-sm text-red-600 mt-3 ml-[34px]">{{ $message }}</p>
                        @enderror

                        @if ($categories->isEmpty())
                            <p class="text-sm text-gray-400 italic mt-4 ml-[34px]">No published categories with published products were found.</p>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-6 gap-y-2.5 mt-4 ml-[34px]">
                                @foreach ($categories as $category)
                                    <label class="flex items-center gap-2.5 text-sm text-gray-800 cursor-pointer hover:text-gray-950">
                                        <input type="checkbox" name="category_ids[]" value="{{ $category->id }}"
                                            class="pl-category rounded border-gray-300"
                                            data-label="{{ $category->title }}"
                                            @checked(in_array($category->id, old('category_ids', [])))>
                                        {{ $category->title }}
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-6 pt-4 border-t border-gray-100 flex items-center gap-3">
                            <button type="submit"
                                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg font-medium text-sm transition">
                                <x-heroicon-o-printer class="w-4 h-4" />
                                Generate Price List
                            </button>
                            <span class="text-xs text-gray-400">Opens in a new tab, ready to print.</span>
                        </div>
                    </div>
                </form>

                {{-- Bottom info bar --}}
                <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3.5 mb-6 flex items-start gap-2.5">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                    <p class="text-sm text-blue-800">
                        The price list uses live pricing from your website.
                        <span class="block text-blue-700/80">Prices and availability can change at any time.</span>
                    </p>
                </div>
            </div>

            {{-- Right sidebar — Your Selection --}}
            <aside class="w-full lg:w-72 xl:w-80 shrink-0 lg:sticky lg:top-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Your Selection</h2>

                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Presets Selected</h3>
                    <ul id="pl-summary-presets" class="text-sm text-gray-700 space-y-1 mb-5">
                        <li class="text-gray-400" data-empty>None</li>
                    </ul>

                    <div class="flex items-center gap-2 mb-2">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Categories Selected</h3>
                        <span id="pl-summary-count"
                            class="inline-flex items-center justify-center min-w-6 h-6 px-1.5 rounded-md bg-blue-600 text-white text-xs font-bold">0</span>
                    </div>
                    <ul id="pl-summary-categories" class="text-sm text-gray-700 space-y-1.5">
                        <li class="text-gray-400" data-empty>None</li>
                    </ul>

                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2.5 flex items-start gap-2">
                            <x-heroicon-o-information-circle class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
                            <p class="text-xs text-blue-800">Products will print in the same order as on the website.</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const boxes       = Array.from(document.querySelectorAll('.pl-category'));
    const byId        = Object.fromEntries(boxes.map(b => [b.value, b]));
    const presetBtns  = Array.from(document.querySelectorAll('.pl-preset'));
    const selectAll   = document.getElementById('pl-select-all');
    const clearCats   = document.getElementById('pl-clear');
    const clearPresets = document.getElementById('pl-clear-presets');
    const sumPresets  = document.getElementById('pl-summary-presets');
    const sumCats     = document.getElementById('pl-summary-categories');
    const sumCount    = document.getElementById('pl-summary-count');

    const presetIds = btn => (btn.dataset.categoryIds || '').split(',').filter(Boolean);
    const isOn      = btn => btn.getAttribute('aria-pressed') === 'true';

    // Selected preset cards: blue border, light highlight, checked indicator.
    function paintPreset(btn, on) {
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        btn.classList.toggle('border-blue-500', on);
        btn.classList.toggle('bg-blue-50/50', on);
        btn.classList.toggle('border-gray-200', !on);
        const check = btn.querySelector('.pl-preset-check');
        if (check) {
            check.classList.toggle('bg-blue-600', on);
            check.classList.toggle('border-blue-600', on);
            check.classList.toggle('text-white', on);
            check.classList.toggle('bg-white/80', !on);
            check.classList.toggle('border-white', !on);
            check.classList.toggle('text-transparent', !on);
        }
    }

    function renderList(ul, items) {
        ul.innerHTML = '';
        if (!items.length) {
            const li = document.createElement('li');
            li.className = 'text-gray-400';
            li.textContent = 'None';
            ul.appendChild(li);
            return;
        }
        items.forEach(text => {
            const li = document.createElement('li');
            li.textContent = '• ' + text;
            ul.appendChild(li);
        });
    }

    // Live "Your Selection" summary — always reflects the actual checkboxes.
    function refreshSummary() {
        renderList(sumPresets, presetBtns.filter(isOn).map(b => b.dataset.label));
        const checked = boxes.filter(b => b.checked);
        renderList(sumCats, checked.map(b => b.dataset.label));
        if (sumCount) sumCount.textContent = checked.length;
        if (selectAll) selectAll.checked = boxes.length > 0 && checked.length === boxes.length;
    }

    // Presets are additive toggles: selecting checks its categories;
    // deselecting unchecks them unless another selected preset still
    // covers them. Manual category edits never flip preset cards.
    presetBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const turningOn = !isOn(btn);
            paintPreset(btn, turningOn);

            if (turningOn) {
                presetIds(btn).forEach(id => { if (byId[id]) byId[id].checked = true; });
            } else {
                const stillCovered = new Set(presetBtns.filter(isOn).flatMap(presetIds));
                presetIds(btn).forEach(id => {
                    if (byId[id] && !stillCovered.has(id)) byId[id].checked = false;
                });
            }
            refreshSummary();
        });
    });

    clearPresets?.addEventListener('click', () => {
        const presetCovered = new Set(presetBtns.filter(isOn).flatMap(presetIds));
        presetBtns.forEach(btn => paintPreset(btn, false));
        presetCovered.forEach(id => { if (byId[id]) byId[id].checked = false; });
        refreshSummary();
    });

    selectAll?.addEventListener('change', () => {
        boxes.forEach(b => { b.checked = selectAll.checked; });
        refreshSummary();
    });

    clearCats?.addEventListener('click', () => {
        boxes.forEach(b => { b.checked = false; });
        presetBtns.forEach(btn => paintPreset(btn, false));
        refreshSummary();
    });

    boxes.forEach(b => b.addEventListener('change', refreshSummary));

    refreshSummary();
});
</script>
@endpush
