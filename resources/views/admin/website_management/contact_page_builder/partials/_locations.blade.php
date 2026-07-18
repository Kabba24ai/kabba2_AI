@php
    $routePrefix = $routePrefix ?? 'admin.website-management.contact-builder';
    $isActive    = ($section?->status ?? 'Active') === 'Active';

    // ── Contact page store DISPLAY order ─────────────────────────────────
    // Saved as store_card items (content.store_id + display_order) on this
    // section — independent of operational Store Management. New stores
    // append to the end until manually reordered; the Primary store
    // defaults to Position 1 only when no order has been saved yet.
    $activeStores = \App\Models\Stores\Store::active()
        ->with(['page.image', 'page.ogImage'])
        ->orderByAdmin()
        ->get(['id', 'unique_id', 'slug', 'store_name', 'is_primary'])
        ->keyBy('id');

    $savedIds = ($items ?? collect())
        ->where('item_key', 'store_card')
        ->sortBy('display_order')
        ->map(fn ($i) => (int) data_get($i->content, 'store_id'))
        ->filter()
        ->values();

    if ($savedIds->isEmpty()) {
        $orderedStores = $activeStores->values()
            ->sortByDesc(fn ($s) => $s->is_primary === 'Yes')
            ->values();
    } else {
        $orderedStores = $savedIds
            ->map(fn ($id) => $activeStores->get($id))
            ->filter()
            ->concat($activeStores->values()->reject(fn ($s) => $savedIds->contains($s->id)))
            ->values();
    }
@endphp

@if($section)
<form method="POST"
      action="{{ route($routePrefix . '.section.update', $section->unique_id) }}"
      data-track-changes>
    @csrf

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5"
         x-data="{ active: @js($isActive) }">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Store Locations</h3>
            <p class="text-xs text-gray-500 mt-0.5">
                Section heading only. Operational details (address, hours, service areas) are managed from
                Settings → Stores — expand a store below to edit its public page content.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <input type="hidden" name="status"
                   value="{{ $isActive ? 'Active' : 'Inactive' }}"
                   :value="active ? 'Active' : 'Inactive'">
            <div class="flex items-center gap-2 cursor-pointer select-none" @click="active = !active">
                <button type="button"
                        :class="active ? 'bg-green-500' : 'bg-gray-300'"
                        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors focus:outline-none">
                    <span :class="active ? 'translate-x-4' : 'translate-x-0.5'"
                          class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm"></span>
                </button>
                <span class="text-xs font-medium w-14"
                      :class="active ? 'text-green-600' : 'text-gray-400'"
                      x-text="active ? 'Active' : 'Inactive'"></span>
            </div>
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium shadow-sm">
                Save Section
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Section Heading</label>
            <input type="text" name="title"
                   value="{{ old('title', $section->title ?? '') }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none">
            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Section Subtitle</label>
            <input type="text" name="subtitle"
                   value="{{ old('subtitle', $section->subtitle ?? '') }}"
                   placeholder="e.g. Find a location near you"
                   class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none">
            @error('subtitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

</form>

{{-- ── Store display order ─────────────────────────────────────────────── --}}
<div class="border-t border-gray-200 pt-6 mt-6">
    <div class="flex items-center justify-between mb-1">
        <h4 class="text-sm font-semibold text-gray-900">Store Display Order</h4>
        <span id="store-order-status" class="text-xs text-gray-400"></span>
    </div>
    <p class="text-xs text-gray-500 mb-4">
        Drag stores to control their display order on the Contact Us page.
        Stores fill the grid from left to right, then top to bottom.
    </p>

    @if($orderedStores->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-sm text-yellow-800">
            No active stores found. Add stores in Store Management first.
        </div>
    @else
        <div id="contact-store-order" class="space-y-2 max-w-4xl">
            @foreach($orderedStores as $store)
                @php
                    $storePage = $store->page;
                    // Same fallback chain as App\Http\Controllers\Front\Stores\ShowController —
                    // shown as live placeholders so admins can see what renders when a field is left blank.
                    $defaultHeading = $store->store_name;
                    $defaultSeoTitle = $defaultHeading . ' - ' . config('app.name');
                    $defaultCanonicalUrl = route('front.stores.show', $store->slug);
                    $defaultMetaDescription = trim(sprintf(
                        '%s — equipment rentals at %s.%s',
                        $store->store_name,
                        $store->full_address,
                        $store->phone ? ' Call ' . $store->phone . ' for availability, hours, and directions.' : ''
                    ));
                @endphp
                <div data-store-id="{{ $store->id }}"
                     class="bg-white border border-gray-200 rounded-lg shadow-sm"
                     x-data="{ open: false }">

                    <div class="flex items-center gap-3 px-4 py-3">
                        <span class="store-drag-handle cursor-grab text-gray-400 hover:text-gray-600">
                            <x-heroicon-o-bars-3 class="w-5 h-5"/>
                        </span>
                        <span class="store-pos inline-flex items-center justify-center w-6 h-6 rounded bg-gray-100 text-xs font-bold text-gray-600">
                            {{ $loop->iteration }}
                        </span>
                        <span class="text-sm font-medium text-gray-900 flex-1">{{ $store->store_name }}</span>
                        @if($store->is_primary === 'Yes')
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">
                                Primary
                            </span>
                        @endif
                        <a href="{{ route('front.stores.show', $store->slug) }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-700">
                            <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5"/>
                            View Page
                        </a>
                        <button type="button" @click="open = !open"
                                class="inline-flex items-center justify-center w-7 h-7 rounded hover:bg-gray-100 text-gray-500">
                            <span :class="open ? 'rotate-180' : ''" class="inline-block transition-transform">
                                <x-heroicon-o-chevron-down class="w-4 h-4"/>
                            </span>
                        </button>
                    </div>

                    <div x-show="open" x-cloak class="border-t border-gray-200 px-4 py-5"
                         x-data="{
                            imageId:  '{{ $storePage->image_media_id ?? '' }}',
                            imageUrl: '{{ $storePage?->image?->url ?? '' }}',
                            ogId:     '{{ $storePage->og_image_media_id ?? '' }}',
                            ogUrl:    '{{ $storePage?->ogImage?->url ?? '' }}',
                         }">
                        <form method="POST"
                              action="{{ route('admin.website-management.contact-builder.store-page.update', $store->unique_id) }}">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-700">Display on Website</label>
                                    {!! html()->select('page_status', ['Active' => 'Active', 'Inactive' => 'Inactive'],
                                            $storePage->status ?? 'Active')
                                        ->class('w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500') !!}
                                    <p class="mt-1 text-xs text-gray-500">Inactive hides this store's public page (visitors get a 404).</p>
                                </div>

                                <div>
                                    <span class="block mb-2 text-sm font-medium text-gray-700">Show Shared Contact Strip</span>
                                    <label class="inline-flex items-center gap-2 mt-2">
                                        <input type="checkbox" name="show_contact_strip" value="1" class="w-4 h-4"
                                               {{ ($storePage->show_contact_strip ?? true) ? 'checked' : '' }}>
                                        <span class="text-sm text-gray-700">Active</span>
                                    </label>
                                    <p class="mt-1 text-xs text-gray-500">
                                        The strip's content is managed globally (Contact Strip tab); this only toggles
                                        whether it appears on this store's page.
                                    </p>
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-700">Page Heading</label>
                                    <p class="text-xs text-gray-500 mb-1.5">
                                        <x-heroicon-o-eye class="w-3 h-3 inline -mt-0.5"/>
                                        Shown as the big title and breadcrumb on the store page.
                                    </p>
                                    {!! html()->text('page_heading', $storePage->page_heading ?? null)
                                        ->class('w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500')
                                        ->attributes(['maxlength' => 240, 'placeholder' => 'Blank shows: "' . $defaultHeading . '"', 'autocomplete' => 'off']) !!}
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block mb-2 text-sm font-medium text-gray-700">Introductory Text</label>
                                    <p class="text-xs text-gray-500 mb-1.5">
                                        <x-heroicon-o-eye class="w-3 h-3 inline -mt-0.5"/>
                                        Shown as the short welcome line right under the heading, next to the store image.
                                        Leave blank and that line simply doesn't appear.
                                    </p>
                                    {!! html()->textarea('intro_text', $storePage->intro_text ?? null)
                                        ->class('w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500')
                                        ->attributes(['rows' => 2, 'maxlength' => 500, 'placeholder' => 'e.g. Your local rental headquarters in ' . $store->city]) !!}
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block mb-2 text-sm font-medium text-gray-700">Store Description</label>
                                    <p class="text-xs text-gray-500 mb-1.5">
                                        <x-heroicon-o-eye class="w-3 h-3 inline -mt-0.5"/>
                                        Shown as its own "About {{ $defaultHeading }}" section further down the page.
                                        Leave blank and that whole section is skipped.
                                    </p>
                                    {!! html()->textarea('page_description', $storePage->description ?? null)
                                        ->class('w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500')
                                        ->attributes(['rows' => 4, 'maxlength' => 5000, 'placeholder' => 'e.g. A longer paragraph about equipment, service area, and what makes this location worth visiting.']) !!}
                                </div>

                                <div class="md:col-span-3 border border-gray-200 rounded-lg p-4">
                                    <span class="block text-sm font-semibold text-gray-700 mb-1">Store Image</span>
                                    <p class="text-xs text-gray-500 mb-3">
                                        <x-heroicon-o-eye class="w-3 h-3 inline -mt-0.5"/>
                                        Shown next to the introductory text at the top of the page. Recommended: landscape,
                                        about 1200 × 800 px (3:2). Leave empty and that column just doesn't render — no placeholder shown.
                                    </p>
                                    <div class="flex items-center gap-5 flex-wrap">
                                        <template x-if="imageUrl">
                                            <img :src="imageUrl" alt="Store image preview"
                                                 class="h-24 w-36 object-cover border rounded-md bg-gray-50">
                                        </template>
                                        <template x-if="!imageUrl">
                                            <div class="h-24 w-36 border border-dashed border-gray-300 rounded-md bg-gray-50 flex items-center justify-center text-xs text-gray-400">No image</div>
                                        </template>
                                        <div class="flex flex-col gap-2">
                                            <button type="button"
                                                    @click="window.MediaPicker.open(m => { imageId = m.id; imageUrl = m.url; })"
                                                    class="text-sm text-blue-600 border border-blue-200 hover:bg-blue-50 rounded-md px-3 py-1.5">
                                                Choose from Library / Upload New
                                            </button>
                                            <button type="button" x-show="imageId" x-cloak
                                                    @click="imageId = ''; imageUrl = ''"
                                                    class="text-sm text-red-500 hover:underline text-left">
                                                Remove Image
                                            </button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="page_image_media_id" :value="imageId">
                                </div>

                                <div class="md:col-span-3 border border-gray-200 rounded-lg p-4">
                                    <span class="block text-sm font-semibold text-gray-700 mb-1">SEO &amp; Social Sharing</span>
                                    <p class="text-xs text-gray-500 mb-4">
                                        <x-heroicon-o-eye class="w-3 h-3 inline -mt-0.5"/>
                                        Not visible on the page itself — these only affect the browser tab title, Google search
                                        results, and link previews when this page is shared on social media. All optional.
                                    </p>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div>
                                            <label class="block mb-2 text-sm font-medium text-gray-700">SEO Title</label>
                                            <p class="text-xs text-gray-400 mb-1.5">Browser tab title &amp; Google search result title.</p>
                                            {!! html()->text('seo_title', $storePage->seo_title ?? null)
                                                ->class('w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500')
                                                ->attributes(['maxlength' => 240, 'placeholder' => 'Blank shows: "' . $defaultSeoTitle . '"']) !!}
                                        </div>
                                        <div>
                                            <label class="block mb-2 text-sm font-medium text-gray-700">Canonical URL</label>
                                            <p class="text-xs text-gray-400 mb-1.5">Tells search engines the "official" URL for this page.</p>
                                            {!! html()->text('canonical_url', $storePage->canonical_url ?? null)
                                                ->class('w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500')
                                                ->attributes(['maxlength' => 500, 'placeholder' => 'Blank shows: ' . $defaultCanonicalUrl]) !!}
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block mb-2 text-sm font-medium text-gray-700">Meta Description</label>
                                            <p class="text-xs text-gray-400 mb-1.5">
                                                The snippet shown under the title in Google search results. Falls back to
                                                Introductory Text, then an auto-generated summary, if left blank.
                                            </p>
                                            {!! html()->textarea('page_meta_description', $storePage->meta_description ?? null)
                                                ->class('w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500')
                                                ->attributes(['rows' => 2, 'maxlength' => 500, 'placeholder' => 'Blank shows: "' . \Illuminate\Support\Str::limit($defaultMetaDescription, 120) . '"']) !!}
                                        </div>
                                        <div>
                                            <label class="block mb-2 text-sm font-medium text-gray-700">OG Title</label>
                                            <p class="text-xs text-gray-400 mb-1.5">Title shown when this page is shared on Facebook/LinkedIn/etc.</p>
                                            {!! html()->text('og_title', $storePage->og_title ?? null)
                                                ->class('w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500')
                                                ->attributes(['maxlength' => 240, 'placeholder' => 'Blank uses the SEO Title above']) !!}
                                        </div>
                                        <div>
                                            <label class="block mb-2 text-sm font-medium text-gray-700">OG Description</label>
                                            <p class="text-xs text-gray-400 mb-1.5">Description shown alongside the OG Title on social shares.</p>
                                            {!! html()->text('og_description', $storePage->og_description ?? null)
                                                ->class('w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500')
                                                ->attributes(['maxlength' => 500, 'placeholder' => 'Blank uses the Meta Description above']) !!}
                                        </div>
                                        <div class="md:col-span-2">
                                            <span class="block mb-2 text-sm font-medium text-gray-700">OG Image</span>
                                            <p class="text-xs text-gray-400 mb-2">
                                                The thumbnail shown on social shares. Recommended 1200 × 630 px.
                                                Blank falls back to the Store Image above, then the site's default social image.
                                            </p>
                                            <div class="flex items-center gap-4 flex-wrap">
                                                <template x-if="ogUrl">
                                                    <img :src="ogUrl" alt="OG image preview" class="h-16 w-28 object-cover border rounded-md bg-gray-50">
                                                </template>
                                                <button type="button"
                                                        @click="window.MediaPicker.open(m => { ogId = m.id; ogUrl = m.url; })"
                                                        class="text-sm text-blue-600 border border-blue-200 hover:bg-blue-50 rounded-md px-3 py-1.5">
                                                    Choose from Library / Upload New
                                                </button>
                                                <button type="button" x-show="ogId" x-cloak
                                                        @click="ogId = ''; ogUrl = ''"
                                                        class="text-sm text-red-500 hover:underline text-left">
                                                    Remove
                                                </button>
                                            </div>
                                            <input type="hidden" name="page_og_image_media_id" :value="ogId">
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="flex justify-end mt-4">
                                <button type="submit"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium shadow-sm">
                                    Save Store Page
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        @push('js')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var el = document.getElementById('contact-store-order');
            if (!el || !window.Sortable) return;

            new window.Sortable(el, {
                handle:     '.store-drag-handle',
                animation:  150,
                ghostClass: 'opacity-40',
                dragClass:  'shadow-lg',
                onEnd: function () {
                    var rows = Array.from(el.querySelectorAll('[data-store-id]'));

                    // Renumber position badges immediately
                    rows.forEach(function (row, idx) {
                        var pos = row.querySelector('.store-pos');
                        if (pos) pos.textContent = idx + 1;
                    });

                    var status = document.getElementById('store-order-status');
                    if (status) status.textContent = 'Saving…';

                    fetch(@js(route('admin.website-management.contact-builder.store-order.update')), {
                        method:  'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                            'Accept':       'application/json',
                        },
                        body: JSON.stringify({
                            store_ids: rows.map(function (row) { return parseInt(row.dataset.storeId, 10); }),
                        }),
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (status) status.textContent = data.success ? 'Order saved' : 'Save failed';
                        if (data.success && window.notyf) window.notyf.success('Store order saved');
                    })
                    .catch(function () {
                        if (status) status.textContent = 'Save failed — please refresh';
                        if (window.notyf) window.notyf.error('Failed to save store order.');
                    });
                },
            });
        });
        </script>
        @endpush
    @endif
</div>

<div class="border-t border-gray-200 pt-6 mt-6">
    <div class="rounded-md p-4 bg-blue-50 border border-blue-200 text-blue-900 max-w-lg">
        <div class="flex items-start gap-3">
            <x-heroicon-o-information-circle class="w-6 h-6 text-blue-600 shrink-0 mt-0.5"/>
            <div>
                <p class="text-sm font-semibold text-blue-900">Manage Stores</p>
                <p class="text-sm mt-1 text-blue-700">
                    Operational details — address, hours of operation, phone, and service areas — are managed from the
                    Stores management page. Expand a store above to edit its public page content instead.
                </p>
                <a href="{{ route('admin.stores.index') }}"
                   class="inline-flex items-center gap-1 mt-2 text-sm font-medium text-blue-800 underline hover:text-blue-900">
                    Go to Store Management
                    <x-heroicon-o-arrow-right class="w-4 h-4"/>
                </a>
            </div>
        </div>
    </div>
</div>
@else
<div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-sm text-yellow-800">
    Locations section not found. Run the seeder first.
</div>
@endif
