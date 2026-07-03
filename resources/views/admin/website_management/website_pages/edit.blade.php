@extends('admin.layouts.app')

@section('title', 'Edit Page — ' . ($page->title ?? 'Page Builder'))

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="{ activeTab: '{{ request('tab', '_settings') }}', showPickerModal: false, pickerKey: '' }">

    {{-- Page Header --}}
    <div class="bg-white rounded-md p-5 shadow-sm border border-gray-100 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.website-management.pages.index') }}"
               class="text-gray-400 hover:text-gray-600 transition-colors shrink-0">
                <x-heroicon-o-arrow-left class="w-5 h-5"/>
            </a>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ $page->title }}</h1>
                <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $page->slug }}</p>
            </div>
        </div>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium shrink-0
            {{ $page->status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
            {{ $page->status }}
        </span>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-md px-4 py-3 text-sm mb-6 flex items-center gap-2">
        <x-heroicon-o-check-circle class="w-4 h-4 shrink-0"/>
        {{ session('success') }}
    </div>
    @endif

    {{-- Section Manager --}}
    @include('admin.website_management.home_page_builder.partials._section_manager', [
        'routePrefix'    => $routePrefix,
        'builderIndexUrl'=> $builderIndexUrl,
    ])

    {{-- Add Section Button --}}
    @php $existingSectionTypes = $allSectionsOrdered->map(fn($s) => $s->section_type ?? $s->section_key)->toArray(); @endphp
    <div class="mb-4 flex justify-end">
        <button @click="showPickerModal = true"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm transition-colors">
            <x-heroicon-o-plus class="w-4 h-4"/>
            Add Section
        </button>
    </div>

    {{-- Component Picker Modal --}}
    <div x-show="showPickerModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="showPickerModal = false">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"
             @click="showPickerModal = false"></div>

        {{-- Dialog --}}
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col"
             @click.stop>

            {{-- Modal header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Add Section</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Choose a component to add to this page.</p>
                </div>
                <button @click="showPickerModal = false"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                    <x-heroicon-o-x-mark class="w-5 h-5"/>
                </button>
            </div>

            {{-- Component grid --}}
            <div class="overflow-y-auto p-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($registry->forPicker() as $pickerComponent)
                    @php $alreadyAdded = in_array($pickerComponent->key(), $existingSectionTypes); @endphp
                    <div class="relative border rounded-xl p-4 flex gap-3 transition-colors
                                {{ $alreadyAdded ? 'border-gray-200 bg-gray-50 opacity-60 cursor-not-allowed' : 'border-gray-200 hover:border-blue-400 hover:bg-blue-50/30 cursor-pointer' }}"
                         @if(!$alreadyAdded)
                         @click="pickerKey = '{{ $pickerComponent->key() }}'"
                         :class="pickerKey === '{{ $pickerComponent->key() }}' ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-200' : ''"
                         @endif>

                        <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center shrink-0 mt-0.5">
                            <x-dynamic-component :component="'{{ $pickerComponent->icon() }}'" class="w-5 h-5 text-gray-600"/>
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800">{{ $pickerComponent->displayName() }}</p>
                            @if($pickerComponent->description())
                                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $pickerComponent->description() }}</p>
                            @endif
                        </div>

                        @if($alreadyAdded)
                            <span class="absolute top-2 right-2 text-xs bg-gray-200 text-gray-500 px-2 py-0.5 rounded-full font-medium">
                                Added
                            </span>
                        @else
                            <span x-show="pickerKey === '{{ $pickerComponent->key() }}'"
                                  class="absolute top-2 right-2 text-xs bg-blue-600 text-white px-2 py-0.5 rounded-full font-medium">
                                Selected
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Modal footer --}}
            <div class="px-6 py-4 border-t border-gray-100 shrink-0 flex items-center gap-3">
                <form method="POST" action="{{ route('admin.website-management.pages.section.store') }}" class="flex-1">
                    @csrf
                    <input type="hidden" name="page_unique_id" value="{{ $page->unique_id }}">
                    <input type="hidden" name="component_key" :value="pickerKey">
                    <button type="submit"
                            :disabled="!pickerKey"
                            class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white px-5 py-2.5 rounded-md text-sm font-medium transition-colors">
                        Add Section
                    </button>
                </form>
                <button @click="showPickerModal = false"
                        class="px-4 py-2.5 border border-gray-300 rounded-md text-sm text-gray-600 hover:bg-gray-50 transition-colors whitespace-nowrap">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="bg-white rounded-t-xl shadow-sm border border-gray-100 border-b-0">
        <div class="flex overflow-x-auto border-b border-gray-200">

            {{-- Page Settings tab (always first) --}}
            <button
                @click="activeTab = '_settings'"
                :class="activeTab === '_settings'
                    ? 'border-b-2 border-blue-600 text-blue-600 bg-blue-50/30'
                    : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                class="px-5 py-3.5 text-sm font-medium whitespace-nowrap transition-colors">
                Page Settings
            </button>

            {{-- Section tabs — only for sections that exist on this page --}}
            @foreach($allSectionsOrdered as $section)
                @php $component = $registry->find($section->section_type ?? $section->section_key); @endphp
                @if($component)
                <button
                    @click="activeTab = '{{ $section->section_key }}'"
                    :class="activeTab === '{{ $section->section_key }}'
                        ? 'border-b-2 border-blue-600 text-blue-600 bg-blue-50/30'
                        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                    class="px-5 py-3.5 text-sm font-medium whitespace-nowrap transition-colors">
                    {{ $component->displayName() }}
                </button>
                @endif
            @endforeach

        </div>
    </div>

    {{-- Tab Content --}}
    <div class="bg-white rounded-b-xl shadow-sm border border-gray-100 border-t-0 p-6">

        {{-- Page Settings tab --}}
        <div x-show="activeTab === '_settings'" x-cloak>
            <form method="POST"
                  action="{{ route('admin.website-management.pages.update', $page->unique_id) }}"
                  data-parsley-validate data-track-changes>
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                        {!! html()->text('title', old('title', $page->title))
                            ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                            ->attributes(['required' => 'required']) !!}
                        @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slug <span class="text-red-500">*</span></label>
                        {!! html()->text('slug', old('slug', $page->slug))
                            ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none font-mono')
                            ->attributes(['required' => 'required']) !!}
                        @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Page Key</label>
                        {!! html()->text('page_key', old('page_key', $page->page_key))
                            ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none font-mono') !!}
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        {!! html()->select('status', ['Active' => 'Active', 'Inactive' => 'Inactive'], old('status', $page->status))
                            ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none') !!}
                    </div>

                </div>

                <div class="border-t border-gray-100 pt-5 mb-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">SEO</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                            {!! html()->text('meta_title', old('meta_title', $page->meta_title))
                                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none') !!}
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                            {!! html()->text('meta_keywords', old('meta_keywords', $page->meta_keywords))
                                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                                ->attributes(['placeholder' => 'keyword1, keyword2']) !!}
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                            {!! html()->textarea('meta_description', old('meta_description', $page->meta_description))
                                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none resize-none')
                                ->rows(3) !!}
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">OG Title</label>
                            {!! html()->text('og_title', old('og_title', $page->og_title))
                                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none') !!}
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Canonical URL</label>
                            {!! html()->text('canonical_url', old('canonical_url', $page->canonical_url))
                                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none') !!}
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">OG Description</label>
                            {!! html()->textarea('og_description', old('og_description', $page->og_description))
                                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none resize-none')
                                ->rows(2) !!}
                        </div>

                    </div>
                </div>

                {{-- Header Message (page-specific hero overlay text) --}}
                <div class="bg-white rounded-xl border border-gray-100 p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <x-heroicon-o-megaphone class="w-4 h-4 text-gray-400"/> Header / Hero Message
                    </h3>
                    <p class="text-xs text-gray-400 mb-4">These values appear in the page hero overlay. Leave blank to use the builder section values.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Primary Message</label>
                            {!! html()->text('header_message', old('header_message', $page->header_message))
                                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                                ->placeholder('e.g. CONTACT US') !!}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Highlight / Yellow Text</label>
                            {!! html()->text('header_highlight', old('header_highlight', $page->header_highlight))
                                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                                ->placeholder('e.g. REAL PEOPLE. REAL SUPPORT.') !!}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Badge Label</label>
                            {!! html()->text('header_badge', old('header_badge', $page->header_badge))
                                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                                ->placeholder('e.g. NEW') !!}
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Callout / Subtext</label>
                            {!! html()->text('header_callout', old('header_callout', $page->header_callout))
                                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                                ->placeholder('e.g. We might be on the phone…') !!}
                        </div>
                    </div>
                </div>

                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-md text-sm font-medium shadow-sm transition-colors">
                    Save Settings
                </button>
            </form>
        </div>

        {{-- Build shared context array once --}}
        @php
        $context = compact(
            'page', 'sections', 'itemsByKey',
            'categories', 'selectedCategoryIds',
            'stores', 'allSectionsOrdered'
        );
        @endphp

        {{-- Section tabs --}}
        @foreach($allSectionsOrdered as $section)
            @php $component = $registry->find($section->section_type ?? $section->section_key); @endphp
            @if($component)
            <div x-show="activeTab === '{{ $section->section_key }}'" x-cloak>
                @include($component->adminView(), $component->viewData($context))
            </div>
            @endif
        @endforeach

    </div>

</div>

@endsection

@push('js')
<script>
window.HPBuilder = {
    sortUrl:        @js(route('admin.website-management.pages.item.sort')),
    sectionSortUrl: @js(route('admin.website-management.pages.section.sort')),
    csrf:           @js(csrf_token()),
};

function hpPreviewImage(input, previewId, placeholderId) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function (e) {
        var preview = document.getElementById(previewId);
        var holder  = placeholderId ? document.getElementById(placeholderId) : null;
        if (preview) { preview.src = e.target.result; preview.classList.remove('hidden'); }
        if (holder)  { holder.classList.add('hidden'); }
    };
    reader.readAsDataURL(input.files[0]);
}

(function () {
    var dirty = false;
    document.addEventListener('input',  function (e) { if (e.target.closest('[data-track-changes]')) dirty = true; });
    document.addEventListener('change', function (e) { if (e.target.closest('[data-track-changes]')) dirty = true; });
    document.addEventListener('submit', function (e) { if (e.target.closest('[data-track-changes]')) dirty = false; });
    window.addEventListener('beforeunload', function (e) {
        if (dirty) { e.preventDefault(); e.returnValue = 'You have unsaved changes.'; }
    });
})();

function hpInitSectionSortable(containerId) {
    var el = document.getElementById(containerId);
    if (!el || !window.Sortable) return;
    new window.Sortable(el, {
        handle:     '.section-drag-handle',
        animation:  150,
        ghostClass: 'opacity-40',
        dragClass:  'shadow-lg',
        onEnd: function () {
            var sections = Array.from(el.querySelectorAll('[data-section-id]')).map(function (row, idx) {
                return { id: row.dataset.sectionId, order: idx + 1 };
            });
            fetch(window.HPBuilder.sectionSortUrl, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.HPBuilder.csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ sections: sections }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) { if (data.success && window.notyf) window.notyf.success('Section order saved'); })
            .catch(function () { if (window.notyf) window.notyf.error('Failed to save section order.'); });
        },
    });
}

function hpInitSortable(containerId) {
    var el = document.getElementById(containerId);
    if (!el || !window.Sortable) return;
    new window.Sortable(el, {
        handle:     '.drag-handle',
        animation:  150,
        ghostClass: 'opacity-40',
        dragClass:  'shadow-lg',
        onEnd: function () {
            var items = Array.from(el.querySelectorAll('[data-item-id]')).map(function (row, idx) {
                return { id: row.dataset.itemId, order: idx + 1 };
            });
            fetch(window.HPBuilder.sortUrl, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.HPBuilder.csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ items: items }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) { if (data.success && window.notyf) window.notyf.success('Order saved'); })
            .catch(function () { if (window.notyf) window.notyf.error('Failed to save order — please refresh.'); });
        },
    });
}

document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        hpInitSectionSortable('section-manager-cards');
        @php
            $sortableIds = collect($allSectionsOrdered)
                ->map(fn($s) => $registry->find($s->section_key))
                ->filter()
                ->flatMap(fn($c) => $c->sortableIds())
                ->unique()
                ->values()
                ->toArray();
        @endphp
        @json($sortableIds).forEach(hpInitSortable);
    }, 300);
});
</script>
@endpush
