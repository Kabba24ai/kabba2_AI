{{-- ============================================================
     Section: Contact Strip
     White card floated over the hero's bottom edge.
     Each column is controlled by its builder item status.
     Set an item Inactive → that column disappears from the strip.
============================================================ --}}

@php
    $phoneCard  = $hp->contactStrip->phoneCard;
    $storeCard1 = $hp->contactStrip->storeCard1 ?? null;
    $storeCard2 = $hp->contactStrip->storeCard2 ?? null;
    $searchCard = $hp->contactStrip->searchCard;
    $store1     = $storeCard1 ? $stores->firstWhere('id', data_get($storeCard1->content, 'store_id')) : null;
    $store2     = $storeCard2 ? $stores->firstWhere('id', data_get($storeCard2->content, 'store_id')) : null;
    $s1ShowPhone   = (bool)(data_get($storeCard1?->content, 'display_phone') ?? true);
    $s1ShowAddress = (bool)(data_get($storeCard1?->content, 'display_address') ?? true);
    $s2ShowPhone   = (bool)(data_get($storeCard2?->content, 'display_phone') ?? true);
    $s2ShowAddress = (bool)(data_get($storeCard2?->content, 'display_address') ?? true);
    $colCount   = (int)(bool)$phoneCard + (int)(bool)$store1 + (int)(bool)$store2 + (int)(bool)$searchCard;
    $gridCols   = [0 => 'lg:grid-cols-1', 1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4'];
    $lgCols     = $gridCols[min($colCount, 4)];
    $mdCols     = $colCount <= 1 ? '' : 'md:grid-cols-2';
@endphp

<section id="home-v2-contact-strip" class="relative z-20 pb-2 md:pb-0 mb-5">
    <div class="container mx-auto px-4 md:px-6 lg:px-8
                -mt-12 md:-mt-16 lg:-mt-20">
        <div class="text-center uppercase text-[#171636] mt-6 mb-6">
            @if(!empty($hp->contactStrip->subtitle))
            <p class="text-sm md:text-sm font-semibold tracking-wider">
                {{ $hp->contactStrip->subtitle }}
            </p>
            @endif
            <h2 class="mt-1 text-lg md:text-2xl font-semibold tracking-wide leading-none">
                {{ $hp->contactStrip->title }}
            </h2>
        </div>
        @if($colCount > 0)
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">

            <div class="grid grid-cols-1 {{ $mdCols }} {{ $lgCols }} bg-gray-100 gap-px">

                {{-- ── Column 1: Main Sales Line ──────────────────────── --}}
                @if($phoneCard)
                <div class="bg-white px-5 py-6 md:px-6 md:py-7 lg:px-8 lg:py-8 flex flex-col       sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">

                    <div class="shrink-0 mt-0.5 w-11 h-11 rounded-full bg-[#1F1D4E] flex items-center justify-center">
                        <x-heroicon-s-phone class="w-5 h-5 text-white" />
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-900 uppercase tracking-widest leading-none">
                            {{ $phoneCard->title ?? 'Main Sales Line' }}
                        </p>
                        @if(!blank($phoneCard->subtitle))
                            <a href="tel:{{ preg_replace('/[^+\d]/', '', $phoneCard->subtitle) }}"
                               class="mt-2 block text-lg font-bold text-yellow-500 leading-tight hover:text-yellow-600 transition-colors">
                                {{ $phoneCard->subtitle }}
                            </a>
                        @endif
                        @if(!empty($phoneCard->description))
                        <p class="mt-1.5 text-xs text-gray-500 leading-relaxed">
                            {{ $phoneCard->description }}
                        </p>
                        @endif
                    </div>

                </div>
                @endif

                {{-- ── Column 2: Store 1 ───────────────────────────────── --}}
                @if($store1)
                <div class="bg-white px-5 py-6 md:px-6 md:py-7 lg:px-8 lg:py-8 flex flex-col       sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">

                    <div class="shrink-0 mt-0.5 w-11 h-11 rounded-full bg-[#1F1D4E] flex items-center justify-center">
                        <x-heroicon-s-map-pin class="w-5 h-5 text-white" />
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-900 uppercase tracking-widest leading-none">
                            {{ $store1->store_name }}
                        </p>
                        @if ($s1ShowPhone && $store1->phone)
                            <a href="tel:{{ preg_replace('/[^+\d]/', '', $store1->phone) }}"
                               class="mt-2 block text-lg font-bold text-gray-900 leading-tight hover:text-yellow-500 transition-colors">
                                {{ $store1->phone }}
                            </a>
                        @endif
                        @if($s1ShowAddress)
                        <p class="mt-1.5 text-xs text-gray-500 leading-relaxed">
                            {{ $store1->address }}<br>
                            {{ $store1->city }}{{ $store1->state?->name ? ', ' . $store1->state->name : '' }}{{ $store1->zip_code ? ' ' . $store1->zip_code : '' }}
                        </p>
                        @endif
                        <a href="{{ route('front.stores.show', $store1->unique_id) }}"
                           class="mt-1 inline-flex items-center gap-1 text-yellow-500
                                uppercase font-semibold text-xs transition-colors hover:text-yellow-600">
                            View Store
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>

                </div>
                @endif

                {{-- ── Column 3: Store 2 ───────────────────────────────── --}}
                @if($store2)
                <div class="bg-white px-5 py-6 md:px-6 md:py-7 lg:px-8 lg:py-8 flex flex-col       sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">

                    <div class="shrink-0 mt-0.5 w-11 h-11 rounded-full bg-[#1F1D4E] flex items-center justify-center">
                        <x-heroicon-s-map-pin class="w-5 h-5 text-white" />
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-900 uppercase tracking-widest leading-none">
                            {{ $store2->store_name }}
                        </p>
                        @if ($s2ShowPhone && $store2->phone)
                            <a href="tel:{{ preg_replace('/[^+\d]/', '', $store2->phone) }}"
                               class="mt-2 block text-lg font-bold text-gray-900 leading-tight hover:text-yellow-500 transition-colors">
                                {{ $store2->phone }}
                            </a>
                        @endif
                        @if($s2ShowAddress)
                        <p class="mt-1.5 text-xs text-gray-500 leading-relaxed">
                            {{ $store2->address }}<br>
                            {{ $store2->city }}{{ $store2->state?->name ? ', ' . $store2->state->name : '' }}{{ $store2->zip_code ? ' ' . $store2->zip_code : '' }}
                        </p>
                        @endif
                        <a href="{{ route('front.stores.show', $store2->unique_id) }}"
                           class="mt-1 inline-flex items-center gap-1 text-yellow-500
                                uppercase font-semibold text-xs transition-colors hover:text-yellow-600">
                            View Store
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>

                </div>
                @endif

                {{-- ── Column 4: Search Equipment (CTA) ────────────────── --}}
                @if($searchCard)
                <div class="bg-white px-5 py-6 md:px-6 md:py-7 lg:px-8 lg:py-8 flex flex-col       sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">

                    <div class="shrink-0 mt-0.5 w-11 h-11 rounded-full bg-yellow-500 flex items-center justify-center">
                        <x-heroicon-o-magnifying-glass class="w-5 h-5 text-gray-900" />
                    </div>

                    <div class="min-w-0">
                        @if(!empty($searchCard->title))
                        <p class="text-xs font-semibold text-gray-900 uppercase tracking-widest leading-none">
                            {{ $searchCard->title }}
                        </p>
                        @endif
                        @if(!empty($searchCard->subtitle))
                        <p class="mt-2 text-lg font-bold text-gray-900 leading-tight">
                            {{ $searchCard->subtitle }}
                        </p>
                        @endif
                        @if(!empty($searchCard->description))
                        <p class="mt-1.5 text-xs text-gray-600 leading-relaxed">
                            {{ $searchCard->description }}
                        </p>
                        @endif
                        <button onclick="document.getElementById('search-popup').classList.remove('hidden')"
                                class="mt-1 inline-flex items-center gap-1 text-yellow-500
                                       uppercase font-semibold text-xs transition-colors hover:text-yellow-600">
                            {{ $searchCard->button_text ?? 'Search' }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                </div>
                @endif

            </div>
        </div>
        @endif

    </div>
</section>

{{-- ── Search Equipment Popup ──────────────────────────────────────────── --}}
<div id="search-popup"
     class="hidden fixed inset-0 z-[9999] flex items-center justify-center px-4"
     role="dialog" aria-modal="true" aria-label="Search Equipment">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"
         onclick="closeSearchPopup()"></div>

    {{-- Modal box --}}
    <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-900 uppercase tracking-wide">Search Equipment</h2>
            <button onclick="closeSearchPopup()"
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Search input --}}
        <div class="px-6 pt-5 pb-3">
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
                <input id="search-popup-input"
                       type="text"
                       placeholder="Search by name, category…"
                       autocomplete="off"
                       class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-lg
                              focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent
                              placeholder-gray-400">
            </div>
        </div>

        {{-- Results --}}
        <div id="search-popup-results"
             class="px-6 pb-5 max-h-72 overflow-y-auto divide-y divide-gray-50 text-sm">
            <p class="py-4 text-center text-gray-400 text-xs">Start typing to search…</p>
        </div>

    </div>
</div>

<script>
(function () {
    var input   = document.getElementById('search-popup-input');
    var results = document.getElementById('search-popup-results');
    var timer   = null;
    var searchUrl = '{{ route("front.home.search.autocomplete") }}';

    function closeSearchPopup() {
        document.getElementById('search-popup').classList.add('hidden');
        input.value = '';
        results.innerHTML = '<p class="py-4 text-center text-gray-400 text-xs">Start typing to search…</p>';
    }
    window.closeSearchPopup = closeSearchPopup;

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSearchPopup();
    });

    // Focus input when popup opens
    document.querySelector('[onclick*="search-popup"]')
        .addEventListener('click', function () {
            setTimeout(function () { input.focus(); }, 50);
        });

    input.addEventListener('input', function () {
        clearTimeout(timer);
        var q = this.value.trim();

        if (q.length < 2) {
            results.innerHTML = '<p class="py-4 text-center text-gray-400 text-xs">Start typing to search…</p>';
            return;
        }

        results.innerHTML = '<p class="py-4 text-center text-gray-400 text-xs">Searching…</p>';

        timer = setTimeout(function () {
            fetch(searchUrl + '?query=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (data) { renderResults(data, q); })
                .catch(function () {
                    results.innerHTML = '<p class="py-4 text-center text-red-400 text-xs">Something went wrong. Please try again.</p>';
                });
        }, 300);
    });

    function renderResults(data, q) {
        var html = '';
        var total = (data.categories ? data.categories.length : 0)
                  + (data.products   ? data.products.length   : 0);

        if (total === 0) {
            results.innerHTML = '<p class="py-4 text-center text-gray-400 text-xs">No results found for "<strong>' + escHtml(q) + '</strong>"</p>';
            return;
        }

        // Categories
        if (data.categories && data.categories.length > 0) {
            html += '<p class="pt-3 pb-1 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Categories</p>';
            data.categories.forEach(function (cat) {
                var url = '{{ url("/product-categories") }}/' + cat.slug;
                html += '<a href="' + url + '"'
                      + ' onclick="closeSearchPopup()"'
                      + ' class="flex items-center gap-3 py-2.5 hover:bg-yellow-50 rounded-lg px-2 -mx-2 transition-colors group">'
                      + '<span class="w-7 h-7 rounded-full bg-[#1F1D4E] flex items-center justify-center shrink-0">'
                      + '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18"/></svg>'
                      + '</span>'
                      + '<span class="font-medium text-gray-800 group-hover:text-yellow-600 transition-colors">' + escHtml(cat.title) + '</span>'
                      + '</a>';
            });
        }

        // Products
        if (data.products && data.products.length > 0) {
            html += '<p class="pt-3 pb-1 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Equipment</p>';
            data.products.forEach(function (product) {
                var url = product.category_links && product.category_links.length > 0
                        ? product.category_links[0]
                        : '#';
                html += '<a href="' + url + '"'
                      + ' onclick="closeSearchPopup()"'
                      + ' class="flex items-center gap-3 py-2.5 hover:bg-yellow-50 rounded-lg px-2 -mx-2 transition-colors group">'
                      + '<span class="w-7 h-7 rounded-full bg-yellow-500 flex items-center justify-center shrink-0">'
                      + '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>'
                      + '</span>'
                      + '<span class="font-medium text-gray-800 group-hover:text-yellow-600 transition-colors">' + escHtml(product.product_name) + '</span>'
                      + '</a>';
            });
        }

        results.innerHTML = html;
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;');
    }
}());
</script>
