@extends('admin.layouts.app')

@section('title', 'Customer Price List')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    @include('admin.documents.partials._price_list_nav', [
        'plSubtitle' => 'Generate a printable take-home price list for walk-in customers. Informational only — '
            . 'website pricing always governs. Select the categories to include; products print in their '
            . 'website display order with live pricing.',
    ])

    <form method="GET" action="{{ route('admin.documents.price-list.generate') }}" target="_blank">

        {{-- Industry presets — selection shortcuts; staff can still adjust categories after --}}
        @if ($presets->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
                <div class="flex items-center justify-between gap-3 mb-1">
                    <h2 class="text-sm font-semibold text-gray-800">Industry Presets</h2>
                    <span id="pl-preset-count" class="text-xs text-gray-400"></span>
                </div>
                <p class="text-xs text-gray-400 mb-4">
                    Pick the kind of work the customer does — the matching categories check themselves.
                    You can add or remove categories below before generating.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    @foreach ($presets as $preset)
                        <button type="button"
                            class="pl-preset text-left rounded-xl border border-gray-200 hover:border-blue-300 hover:bg-blue-50/50 transition p-3"
                            data-preset="{{ $preset['key'] }}"
                            data-category-ids="{{ implode(',', $preset['category_ids']) }}">
                            @if (!empty($preset['thumbnail_url']))
                                <img src="{{ $preset['thumbnail_url'] }}" alt=""
                                    class="w-full h-20 object-cover rounded-lg mb-2">
                            @endif
                            <span class="block text-sm font-semibold text-gray-800">{{ $preset['label'] }}</span>
                            <span class="block text-xs text-gray-400 mt-1">{{ $preset['description'] }}</span>
                            <span class="block text-[11px] font-medium text-blue-600 mt-1.5">
                                {{ count($preset['category_ids']) }} {{ Str::plural('category', count($preset['category_ids'])) }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <h2 class="text-sm font-semibold text-gray-800">Categories</h2>
                <div class="flex items-center gap-4">
                    <span id="pl-selected-count" class="text-xs text-gray-400"></span>
                    <button type="button" id="pl-clear"
                        class="text-sm font-medium text-gray-500 hover:text-red-600 transition">Clear Selection</button>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" id="pl-select-all" class="rounded border-gray-300">
                        Select all
                    </label>
                </div>
            </div>

            @error('category_ids')
                <p class="text-sm text-red-600 mb-3">{{ $message }}</p>
            @enderror

            @if ($categories->isEmpty())
                <p class="text-sm text-gray-400 italic">No published categories with published products were found.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach ($categories as $category)
                        <label class="flex items-center gap-2.5 px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer text-sm text-gray-800">
                            <input type="checkbox" name="category_ids[]" value="{{ $category->id }}"
                                class="pl-category rounded border-gray-300"
                                @checked(in_array($category->id, old('category_ids', [])))>
                            {{ $category->title }}
                        </label>
                    @endforeach
                </div>
            @endif

            <div class="mt-5 pt-4 border-t border-gray-100 flex items-center gap-3">
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition">
                    <x-heroicon-o-printer class="w-4 h-4" />
                    Generate Price List
                </button>
                <span class="text-xs text-gray-400">Opens in a new tab, ready to print on 8.5×11.</span>
            </div>
        </div>
    </form>

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const all    = document.getElementById('pl-select-all');
    const clear  = document.getElementById('pl-clear');
    const count  = document.getElementById('pl-selected-count');
    const boxes  = Array.from(document.querySelectorAll('.pl-category'));
    const byId   = Object.fromEntries(boxes.map(b => [b.value, b]));

    function refreshCount() {
        const n = boxes.filter(b => b.checked).length;
        if (count) count.textContent = n ? n + ' selected' : '';
    }

    all?.addEventListener('change', () => {
        boxes.forEach(b => { b.checked = all.checked; });
        markActivePreset(null);
        refreshCount();
    });

    clear?.addEventListener('click', () => {
        boxes.forEach(b => { b.checked = false; });
        if (all) all.checked = false;
        markActivePreset(null);
        refreshCount();
    });

    // Presets pre-check their categories; staff can adjust freely afterward.
    function markActivePreset(activeBtn) {
        document.querySelectorAll('.pl-preset').forEach(btn => {
            btn.classList.toggle('border-blue-500', btn === activeBtn);
            btn.classList.toggle('bg-blue-50', btn === activeBtn);
            btn.classList.toggle('border-gray-200', btn !== activeBtn);
        });
    }

    document.querySelectorAll('.pl-preset').forEach(btn => {
        btn.addEventListener('click', () => {
            (btn.dataset.categoryIds || '').split(',').forEach(id => {
                if (byId[id]) byId[id].checked = true;
            });
            markActivePreset(btn);
            refreshCount();
        });
    });

    // Manual edits after a preset clear the "active" highlight — the
    // selection is now custom.
    boxes.forEach(b => b.addEventListener('change', () => {
        markActivePreset(null);
        refreshCount();
    }));

    refreshCount();
});
</script>
@endpush
