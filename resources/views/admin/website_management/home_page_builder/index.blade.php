@extends('admin.layouts.app')

@section('title', 'Home Page Builder')

@section('content')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
         x-data="{ activeTab: '{{ request('tab', 'hero') }}' }">

        {{-- Page Header --}}
        <div class="bg-white rounded-md p-5 shadow-sm border border-gray-100 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <h1 class="text-2xl font-semibold text-gray-900">Home Page Builder</h1>
                </div>
                <p class="text-gray-600">Manage all content sections of the home page. Run the seeder first if sections are empty.</p>
            </div>
            @if(!$page)
                <div class="flex items-center gap-2 bg-yellow-50 border border-yellow-200 rounded-md px-4 py-2 text-sm text-yellow-800">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4 shrink-0"/>
                    No home page data found. Run: <code class="font-mono bg-yellow-100 px-1 rounded">php artisan db:seed --class="Database\Seeders\WebsiteManagement\HomePageBuilderSeeder"</code>
                </div>
            @endif
        </div>

        {{-- Section Manager --}}
        {{-- @include('admin.website_management.home_page_builder.partials._section_manager') --}}

        {{-- Build shared context array once; each component pulls what it needs --}}
        @php
        $context = compact(
            'page', 'sections', 'itemsByKey',
            'categories', 'selectedCategoryIds',
            'stores', 'allSectionsOrdered'
        );
        @endphp

        {{-- Tab Navigation — driven by ComponentRegistry --}}
        <div class="bg-white rounded-t-xl shadow-sm border border-gray-100 border-b-0">
            <div class="flex overflow-x-auto border-b border-gray-200">
                @foreach($registry->all() as $component)
                    <button
                        @click="activeTab = '{{ $component->key() }}'"
                        :class="activeTab === '{{ $component->key() }}'
                            ? 'border-b-2 border-blue-600 text-blue-600 bg-blue-50/30'
                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                        class="px-5 py-3.5 text-sm font-medium whitespace-nowrap transition-colors">
                        {{ $component->displayName() }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Tab Content — each component owns its admin view and the data it needs --}}
        <div class="bg-white rounded-b-xl shadow-sm border border-gray-100 border-t-0 p-6">
            @foreach($registry->all() as $component)
                <div x-show="activeTab === '{{ $component->key() }}'" x-cloak>
                    @include($component->adminView(), $component->viewData($context))
                </div>
            @endforeach
        </div>

    </div>

@endsection

@push('js')
<script>
/* ── Builder JS ─────────────────────────────────────────────────── */
window.HPBuilder = {
    sortUrl:        @js(route('admin.website-management.home-builder.item.sort')),
    sectionSortUrl: @js(route('admin.website-management.home-builder.section.sort')),
    csrf:           @js(csrf_token()),
};

/* Image preview — show live thumbnail when a file is chosen */
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

/* Unsaved changes warning */
(function () {
    var dirty = false;
    document.addEventListener('input',  function (e) { if (e.target.closest('[data-track-changes]')) dirty = true; });
    document.addEventListener('change', function (e) { if (e.target.closest('[data-track-changes]')) dirty = true; });
    document.addEventListener('submit', function (e) { if (e.target.closest('[data-track-changes]')) dirty = false; });
    window.addEventListener('beforeunload', function (e) {
        if (dirty) { e.preventDefault(); e.returnValue = 'You have unsaved changes.'; }
    });
})();

/* Section-level drag & drop (section manager panel) */
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
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.HPBuilder.csrf,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({ sections: sections }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && window.notyf) window.notyf.success('Section order saved');
            })
            .catch(function () {
                if (window.notyf) window.notyf.error('Failed to save section order.');
            });
        },
    });
}

/* Item-level drag & drop sorting */
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
                headers: {
                    'Content-Type':  'application/json',
                    'X-CSRF-TOKEN':  window.HPBuilder.csrf,
                    'Accept':        'application/json',
                },
                body: JSON.stringify({ items: items }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && window.notyf) window.notyf.success('Order saved');
            })
            .catch(function () {
                if (window.notyf) window.notyf.error('Failed to save order — please refresh.');
            });
        },
    });
}

/* Init sortables after Alpine finishes rendering (small delay) */
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        hpInitSectionSortable('section-manager-cards');
        @json($registry->allSortableIds()).forEach(hpInitSortable);
    }, 300);
});
</script>
@endpush
