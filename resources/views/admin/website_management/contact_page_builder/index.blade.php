@extends('admin.layouts.app')

@section('title', 'Contact Us')

@section('content')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
         x-data="{ activeTab: '{{ $activeTab }}' }">

        {{-- Page Header --}}
        <div class="bg-white rounded-md p-5 shadow-sm border border-gray-100 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <h1 class="text-2xl font-semibold text-gray-900">Contact Us</h1>
                </div>
                <p class="text-gray-600">Manage the public Contact Us page. Run the seeder first if sections are empty.</p>
            </div>
            @if(!$page)
                <div class="flex items-center gap-2 bg-yellow-50 border border-yellow-200 rounded-md px-4 py-2 text-sm text-yellow-800">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4 shrink-0"/>
                    No contact page data found. Run:
                    <code class="font-mono bg-yellow-100 px-1 rounded">php artisan db:seed --class="Database\Seeders\WebsiteManagement\ContactPageBuilderSeeder"</code>
                </div>
            @endif
        </div>

        {{-- Build context once; each component pulls what it needs via viewData() --}}
        @php
            $context = compact('page', 'sections', 'itemsByKey', 'stores', 'allSectionsOrdered');
        @endphp

        {{-- Tab Navigation — driven by filtered $components collection --}}
        <div class="bg-white rounded-t-xl shadow-sm border border-gray-100 border-b-0">
            <div class="flex overflow-x-auto border-b border-gray-200">
                @foreach($components as $key => $component)
                    <button
                        @click="activeTab = '{{ $key }}'"
                        :class="activeTab === '{{ $key }}'
                            ? 'border-b-2 border-blue-600 text-blue-600 bg-blue-50/30'
                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                        class="px-5 py-3.5 text-sm font-medium whitespace-nowrap transition-colors">
                        {{ $component->displayName() }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Tab Content --}}
        {{--
            Partial resolution order:
            1. contact_page_builder/partials/_{key}  — contact-specific or wrapper
            2. $component->adminView()               — shared component view (home builder partials)
            3. else                                  — placeholder for Phase 2B sections
        --}}
        <div class="bg-white rounded-b-xl shadow-sm border border-gray-100 border-t-0 p-6">
            @foreach($components as $key => $component)
                @php
                    $contactPartial   = 'admin.website_management.contact_page_builder.partials._' . $key;
                    $componentPartial = $component->adminView();
                @endphp
                <div x-show="activeTab === '{{ $key }}'" x-cloak>
                    @if(\Illuminate\Support\Facades\View::exists($contactPartial))
                        @include($contactPartial, $component->viewData($context))
                    @elseif(\Illuminate\Support\Facades\View::exists($componentPartial))
                        @include($componentPartial, $component->viewData($context))
                    @else
                        {{-- Phase 2B placeholder for locations and question_cta --}}
                        <div class="border-2 border-dashed border-gray-200 rounded-lg py-14 text-center">
                            <div class="mx-auto mb-3 text-gray-300">
                                <x-dynamic-component :component="$component->icon()" class="w-10 h-10 mx-auto"/>
                            </div>
                            <p class="text-sm font-medium text-gray-500">{{ $component->displayName() }}</p>
                            <p class="text-xs text-gray-400 mt-1">Admin UI will be built in Phase 2B</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

    </div>

@endsection

@push('js')
<script>
/* ── Contact Builder JS ─────────────────────────────────────────── */
window.CPBuilder = {
    sortUrl:        @js(route('admin.website-management.contact-builder.item.sort')),
    sectionSortUrl: @js(route('admin.website-management.contact-builder.section.sort')),
    csrf:           @js(csrf_token()),
};

/* Image preview — defined here so the SEO partial's hpPreviewImage() call resolves */
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

/* Section-level drag & drop (section manager) */
function cpInitSectionSortable(containerId) {
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
            fetch(window.CPBuilder.sectionSortUrl, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.CPBuilder.csrf,
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
function cpInitSortable(containerId) {
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
            fetch(window.CPBuilder.sortUrl, {
                method:  'POST',
                headers: {
                    'Content-Type':  'application/json',
                    'X-CSRF-TOKEN':  window.CPBuilder.csrf,
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

/* Init sortables after Alpine finishes rendering.
   locations and question_cta have no item lists to sort. */
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        cpInitSectionSortable('section-manager-cards');
        @php
            $initIds = $components
                ->reject(fn($c, $key) => in_array($key, ['locations', 'question_cta']))
                ->flatMap(fn($c) => $c->sortableIds())
                ->values()
                ->all();
        @endphp
        @json($initIds).forEach(cpInitSortable);
    }, 300);
});
</script>
@endpush
