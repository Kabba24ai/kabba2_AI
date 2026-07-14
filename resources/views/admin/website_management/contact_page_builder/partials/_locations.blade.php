@php
    $routePrefix = $routePrefix ?? 'admin.website-management.contact-builder';
    $isActive    = ($section?->status ?? 'Active') === 'Active';

    // ── Contact page store DISPLAY order ─────────────────────────────────
    // Saved as store_card items (content.store_id + display_order) on this
    // section — independent of operational Store Management. New stores
    // append to the end until manually reordered; the Primary store
    // defaults to Position 1 only when no order has been saved yet.
    $activeStores = \App\Models\Stores\Store::active()
        ->orderByAdmin()
        ->get(['id', 'store_name', 'is_primary'])
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
            <p class="text-xs text-gray-500 mt-0.5">Section heading only. Which stores appear and all store details are managed from Settings → Stores.</p>
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
        <div id="contact-store-order" class="space-y-2 max-w-2xl">
            @foreach($orderedStores as $store)
                <div data-store-id="{{ $store->id }}"
                     class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-4 py-3 shadow-sm">
                    <span class="store-drag-handle cursor-grab text-gray-400 hover:text-gray-600">
                        <x-heroicon-o-bars-3 class="w-5 h-5"/>
                    </span>
                    <span class="store-pos inline-flex items-center justify-center w-6 h-6 rounded bg-gray-100 text-xs font-bold text-gray-600">
                        {{ $loop->iteration }}
                    </span>
                    <span class="text-sm font-medium text-gray-900">{{ $store->store_name }}</span>
                    @if($store->is_primary === 'Yes')
                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">
                            Primary
                        </span>
                    @endif
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
                    Store details, addresses, hours of operation, and contact info are managed from the Stores management page.
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
