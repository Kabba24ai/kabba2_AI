{{-- ============================================================
     Admin Partial: Locations Section
     Editable: section title/subtitle, unlimited location cards.
     Each card supports: name, badge color, address, city, state,
     zip, phone, maps URL, lat/lng, description, store link, hours.
============================================================ --}}

@php $sectionKey = 'locations'; @endphp

<div id="tab-{{ $sectionKey }}" class="tab-content hidden">

    {{-- ── Section Meta ─────────────────────────────────────────────── --}}
    <div class="bg-white border border-gray-100 rounded-xl p-5 mb-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <x-heroicon-o-map-pin class="w-4 h-4 text-gray-400"/> Section Settings
        </h3>
        @if(!$section)
            <p class="text-sm text-gray-400 text-center py-4">Section not initialised yet. Reload the page after saving to activate this tab.</p>
        @else
        <form method="POST" action="{{ route('admin.website-management.pages.section.update', $section->unique_id) }}">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Section Heading</label>
                    <input type="text" name="title" value="{{ $section?->title ?? 'Our Locations' }}"
                           class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Section Subtitle</label>
                    <input type="text" name="subtitle" value="{{ $section?->subtitle ?? '' }}"
                           class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white text-xs px-4 py-1.5 rounded-md hover:bg-blue-700">Save Settings</button>
            </div>
        </form>
        @endif
    </div>

    {{-- ── Location Cards ────────────────────────────────────────────── --}}
    <div class="bg-white border border-gray-100 rounded-xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                <x-heroicon-o-building-office class="w-4 h-4 text-gray-400"/> Location Cards
            </h3>
            <button type="button"
                    onclick="openAddItemPanel('{{ $section?->unique_id }}', 'locations', null)"
                    class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-md hover:bg-blue-700 flex items-center gap-1">
                <x-heroicon-o-plus class="w-3 h-3"/> Add Location
            </button>
        </div>

        <ul id="sortable-locations" class="space-y-2">
            @forelse($items->where('status', 'Active')->sortBy('display_order') as $item)
            @php $c = $item->content ?? []; @endphp
            <li data-id="{{ $item->unique_id }}"
                class="flex items-center gap-3 bg-gray-50 border border-gray-100 rounded-lg p-3 cursor-move">
                <span class="drag-handle text-gray-300 cursor-grab">
                    <x-heroicon-o-bars-2 class="w-4 h-4"/>
                </span>
                <div class="w-3 h-3 rounded-full shrink-0" style="background:{{ $c['badge_color'] ?? '#1F1D4E' }}"></div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $item->title }}</p>
                    @if(!empty($c['address']))
                    <p class="text-xs text-gray-400 truncate">{{ $c['address'] }}, {{ $c['city'] ?? '' }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button"
                            onclick="openEditItemPanel('{{ $item->unique_id }}')"
                            class="text-xs text-blue-600 border border-blue-200 px-2 py-1 rounded hover:bg-blue-50">Edit</button>
                    <button type="button"
                            onclick="duplicateItem('{{ $item->unique_id }}')"
                            class="text-xs text-gray-500 border border-gray-200 px-2 py-1 rounded hover:bg-gray-50">Dup</button>
                    <button type="button"
                            onclick="deleteItem('{{ $item->unique_id }}')"
                            class="text-xs text-red-500 border border-red-100 px-2 py-1 rounded hover:bg-red-50">Del</button>
                </div>
            </li>
            @empty
            <li class="text-sm text-gray-400 text-center py-6">No locations yet. Add one above.</li>
            @endforelse
        </ul>
    </div>

    {{-- ── Add / Edit Slide-over Panel ────────────────────────────────── --}}
    <div id="locations-item-panel" class="hidden fixed inset-y-0 right-0 z-50 w-[520px] bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h4 id="locations-panel-title" class="text-sm font-semibold text-gray-800">Add Location</h4>
            <button onclick="closeLocationsPanel()" class="text-gray-400 hover:text-gray-600">
                <x-heroicon-o-x-mark class="w-5 h-5"/>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
            <input type="hidden" id="loc-item-unique-id">
            <input type="hidden" id="loc-section-unique-id" value="{{ $section?->unique_id }}">

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Location Name *</label>
                <input type="text" id="loc-title" placeholder="e.g. Bon Aqua"
                       class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Badge Color</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="loc-badge-color" value="#1F1D4E"
                               class="h-8 w-12 border border-gray-200 rounded cursor-pointer p-0.5">
                        <input type="text" id="loc-badge-color-text" value="#1F1D4E"
                               oninput="document.getElementById('loc-badge-color').value=this.value"
                               class="flex-1 text-xs border border-gray-200 rounded-md px-2 py-1.5 font-mono focus:outline-none focus:ring-1 focus:ring-blue-400">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Phone</label>
                    <input type="text" id="loc-phone" placeholder="(615) 000-0000"
                           class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Address</label>
                <input type="text" id="loc-address" placeholder="123 Main Street"
                       class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">City</label>
                    <input type="text" id="loc-city"
                           class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">State</label>
                    <input type="text" id="loc-state" placeholder="TN"
                           class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">ZIP</label>
                    <input type="text" id="loc-zip"
                           class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Latitude</label>
                    <input type="text" id="loc-latitude" placeholder="36.0000"
                           class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Longitude</label>
                    <input type="text" id="loc-longitude" placeholder="-87.0000"
                           class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Google Maps URL (optional override)</label>
                <input type="text" id="loc-maps-url" placeholder="https://maps.google.com/..."
                       class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Description / Info bar</label>
                <textarea id="loc-description" rows="2" placeholder='Full service location with "cash and carry"...'
                          class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 resize-none"></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Link to Store (optional)</label>
                <select id="loc-store-id"
                        class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
                    <option value="">— None (use manual fields above) —</option>
                    @foreach($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->store_name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">If linked, hours are pulled live from the Store module. Manual fields still used for the map embed and buttons.</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select id="loc-status"
                        class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2">
            <button onclick="closeLocationsPanel()" class="text-sm text-gray-600 border border-gray-200 px-4 py-2 rounded-md hover:bg-gray-50">Cancel</button>
            <button onclick="saveLocationItem()" class="text-sm bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Save Location</button>
        </div>
    </div>

</div>

<script>
(function () {
    const csrf = '{{ csrf_token() }}';
    const storeUrl = '{{ route("admin.website-management.pages.item.store") }}';
    const updateBase = '{{ url("admin/website-management/pages/item") }}';
    const sortUrl = '{{ route("admin.website-management.pages.item.sort") }}';

    // ── SortableJS init ─────────────────────────────────────────────
    const sortableEl = document.getElementById('sortable-locations');
    if (sortableEl && window.Sortable) {
        Sortable.create(sortableEl, {
            handle: '.drag-handle',
            animation: 150,
            onEnd() {
                const order = [...sortableEl.querySelectorAll('[data-id]')].map(el => el.dataset.id);
                fetch(sortUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ ordered_ids: order }),
                });
            }
        });
    }

    // ── Panel helpers ────────────────────────────────────────────────
    function openPanel(mode, data) {
        document.getElementById('locations-panel-title').textContent = mode === 'edit' ? 'Edit Location' : 'Add Location';
        document.getElementById('loc-item-unique-id').value    = data.unique_id ?? '';
        document.getElementById('loc-title').value             = data.title ?? '';
        const c = data.content ?? {};
        document.getElementById('loc-badge-color').value       = c.badge_color ?? '#1F1D4E';
        document.getElementById('loc-badge-color-text').value  = c.badge_color ?? '#1F1D4E';
        document.getElementById('loc-phone').value             = c.phone ?? '';
        document.getElementById('loc-address').value           = c.address ?? '';
        document.getElementById('loc-city').value              = c.city ?? '';
        document.getElementById('loc-state').value             = c.state ?? '';
        document.getElementById('loc-zip').value               = c.zip ?? '';
        document.getElementById('loc-latitude').value          = c.latitude ?? '';
        document.getElementById('loc-longitude').value         = c.longitude ?? '';
        document.getElementById('loc-maps-url').value          = c.maps_url ?? '';
        document.getElementById('loc-description').value       = c.description ?? '';
        document.getElementById('loc-store-id').value          = c.store_id ?? '';
        document.getElementById('loc-status').value            = data.status ?? 'Active';
        document.getElementById('locations-item-panel').classList.remove('hidden');
    }

    window.openAddItemPanel = function(sectionUniqueId, key, _unused) {
        if (key !== 'locations') return;
        openPanel('add', {});
    };
    window.openEditItemPanel = function(uniqueId) {
        fetch(`${updateBase}/${uniqueId}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => openPanel('edit', d.item ?? {}))
            .catch(() => alert('Could not load item.'));
    };
    window.closeLocationsPanel = function() {
        document.getElementById('locations-item-panel').classList.add('hidden');
    };

    window.saveLocationItem = function() {
        const uniqueId = document.getElementById('loc-item-unique-id').value;
        const isEdit   = !!uniqueId;
        const payload  = {
            section_unique_id: document.getElementById('loc-section-unique-id').value,
            item_key : 'location_' + Date.now(),
            title    : document.getElementById('loc-title').value,
            status   : document.getElementById('loc-status').value,
            content  : {
                badge_color : document.getElementById('loc-badge-color').value,
                phone       : document.getElementById('loc-phone').value,
                address     : document.getElementById('loc-address').value,
                city        : document.getElementById('loc-city').value,
                state       : document.getElementById('loc-state').value,
                zip         : document.getElementById('loc-zip').value,
                latitude    : document.getElementById('loc-latitude').value,
                longitude   : document.getElementById('loc-longitude').value,
                maps_url    : document.getElementById('loc-maps-url').value,
                description : document.getElementById('loc-description').value,
                store_id    : document.getElementById('loc-store-id').value || null,
            },
        };

        const url    = isEdit ? `${updateBase}/${uniqueId}/update` : storeUrl;
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        })
        .then(r => r.json())
        .then(d => { if (d.success !== false) location.reload(); else alert(d.message ?? 'Error saving.'); })
        .catch(() => alert('Network error.'));
    };

    window.deleteItem = function(uniqueId) {
        if (!confirm('Delete this location?')) return;
        fetch(`${updateBase}/${uniqueId}/delete`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).then(() => location.reload());
    };

    window.duplicateItem = function(uniqueId) {
        fetch(`${updateBase}/${uniqueId}/duplicate`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).then(() => location.reload());
    };
}());
</script>
