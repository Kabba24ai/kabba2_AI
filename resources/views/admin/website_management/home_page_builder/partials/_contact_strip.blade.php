{{-- ── Contact Strip Section ──────────────────────────────────────────── --}}
@php
    $routePrefix = $routePrefix ?? 'admin.website-management.home-builder';
    $isActive    = ($section?->status ?? 'Active') === 'Active';
@endphp

@if($section)
{{--
    SECTION FORM — uses html() helpers + old() normally.
    withInput() is called on section validation failure, so old() is correctly
    populated for THIS form's fields only. Item forms below must NOT use old().
--}}
<form method="POST"
      action="{{ route($routePrefix . '.section.update', $section->unique_id) }}"
      data-track-changes>
    @csrf
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5"
         x-data="{ active: @js($isActive) }">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Contact / Rental Specialist Strip</h3>
            <p class="text-xs text-gray-500 mt-0.5">Section heading text and visibility.</p>
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
                <span class="text-xs font-medium w-14" :class="active ? 'text-green-600' : 'text-gray-400'"
                      x-text="active ? 'Active' : 'Inactive'"></span>
            </div>
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium shadow-sm">
                Save Section
            </button>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Main Title</label>
            {!! html()->text('title', old('title', $section->title ?? ''))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                ->attributes(['placeholder' => 'Call Our Rental Specialists']) !!}
            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
            {!! html()->text('subtitle', old('subtitle', $section->subtitle ?? ''))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                ->attributes(['placeholder' => 'Need Help Finding The Right Equipment?']) !!}
            @error('subtitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</form>
@else
<div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-sm text-yellow-800 mb-4">
    Contact Strip section not found. Run the seeder first.
</div>
@endif

{{-- ── Contact Strip Items ──────────────────────────────────────────────── --}}
<div class="border-t border-gray-200 pt-6">
    <div class="mb-3">
        <h4 class="text-sm font-semibold text-gray-800">Strip Cards ({{ $items->count() }})</h4>
    </div>

    @if($items->isEmpty())
        <p class="text-sm text-gray-400 italic">No cards yet.</p>
    @else
        <div class="space-y-2">
            @foreach($items as $item)
            @php
                $isPhoneCard     = $item->item_key === 'phone_card';
                $isStoreCard     = in_array($item->item_key, ['store_1', 'store_2']);
                $selectedStoreId = data_get($item->content, 'store_id');
                $selectedStore   = $stores->firstWhere('id', $selectedStoreId);

                /*
                 * Per-item input scoping
                 * ─────────────────────
                 * failedValidation() in UpdateItemRequest stashes submitted input in
                 * session('_item_input_{unique_id}') — NOT in old() — so it is isolated
                 * to this item and cannot contaminate other forms on the page.
                 *
                 * $iv($key, $fallback) returns:
                 *   • The value the user submitted (if this item's form failed), OR
                 *   • The current database value ($item->field) otherwise.
                 *
                 * IMPORTANT: every item input must be rendered as a raw <input>/<select>
                 * tag — never through html()->text() or html()->select() — because
                 * Laravel Collective's FormBuilder always calls old($name) first and will
                 * override the correct value with the section form's stale old() flash.
                 */
                $itemBagName = 'item_' . $item->unique_id;
                $itemInput   = session('_item_input_' . $item->unique_id, []);
                $iv          = fn(string $key, $fallback = null) =>
                                   array_key_exists($key, $itemInput) ? $itemInput[$key] : $fallback;
                $cardOpen    = $errors->hasBag($itemBagName) && $errors->getBag($itemBagName)->isNotEmpty();
            @endphp
            <div x-data="{ editOpen: @js($cardOpen) }"
                 class="border border-gray-200 rounded-lg overflow-hidden">

                {{-- Card header --}}
                <div class="flex items-center justify-between px-3 py-3 bg-gray-50 cursor-pointer"
                     @click="editOpen = !editOpen">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-xs font-mono bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded shrink-0">{{ $item->item_key }}</span>
                        @if($isStoreCard)
                            <span class="text-sm font-medium text-gray-800 truncate">
                                {{ $selectedStore?->store_name ?? '— no store selected —' }}
                            </span>
                        @else
                            <span class="text-sm font-medium text-gray-800 truncate">{{ $item->title }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $item->status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $item->status }}
                        </span>
                        <span :class="editOpen ? 'rotate-180' : ''" class="inline-flex transition-transform duration-200">
                            <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400"/>
                        </span>
                    </div>
                </div>

                {{-- Edit panel --}}
                <div x-show="editOpen" x-cloak class="p-4 border-t border-gray-100">
                    <form method="POST"
                          action="{{ route($routePrefix . '.item.update', $item->unique_id) }}"
                          data-track-changes>
                        @csrf

                        @if($isPhoneCard)
                        {{-- ── Phone Card ── --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Title <span class="text-red-500">*</span></label>
                                <input type="text" name="title"
                                       value="{{ e($iv('title', $item->title) ?? '') }}"
                                       required
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:outline-none">
                                @error('title', $itemBagName) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Phone Number <span class="text-red-500">*</span></label>
                                <input type="text" name="subtitle"
                                       value="{{ e($iv('subtitle', $item->subtitle) ?? '') }}"
                                       required
                                       placeholder="(xxx) xxx-xxxx"
                                       autocomplete="tel"
                                       pattern="\(\d{3}\) \d{3}-\d{4}"
                                       class="masked-phone w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:outline-none">
                                @error('subtitle', $itemBagName) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                                <input type="text" name="description"
                                       value="{{ e($iv('description', $item->description) ?? '') }}"
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:outline-none">
                                @error('description', $itemBagName) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                                @php $pcStatus = $iv('status', $item->status); @endphp
                                <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:ring-2 focus:outline-none">
                                    <option value="Active"   {{ $pcStatus === 'Active'   ? 'selected' : '' }}>Active</option>
                                    <option value="Inactive" {{ $pcStatus === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>

                        @elseif($isStoreCard)
                        {{-- ── Store Card ── --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Store <span class="text-red-500">*</span></label>
                                @php
                                    $storedContent  = $iv('content', null);
                                    $resolvedStoreId = is_array($storedContent)
                                        ? ($storedContent['store_id'] ?? $selectedStoreId)
                                        : $selectedStoreId;
                                @endphp
                                <select name="content[store_id]" required
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-blue-400">
                                    <option value="">— Select a store —</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}"
                                            {{ (string)$resolvedStoreId === (string)$store->id ? 'selected' : '' }}>
                                            {{ $store->store_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('content.store_id', $itemBagName) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                                @php $scStatus = $iv('status', $item->status); @endphp
                                <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:ring-2 focus:outline-none">
                                    <option value="Active"   {{ $scStatus === 'Active'   ? 'selected' : '' }}>Active</option>
                                    <option value="Inactive" {{ $scStatus === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>

                        @else
                        {{-- ── Other cards (search_card etc.) ── --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                                <input type="text" name="title"
                                       value="{{ e($iv('title', $item->title) ?? '') }}"
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:outline-none">
                                @error('title', $itemBagName) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Sub Title</label>
                                <input type="text" name="subtitle"
                                       value="{{ e($iv('subtitle', $item->subtitle) ?? '') }}"
                                       placeholder="Find What You Need"
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:outline-none">
                                @error('subtitle', $itemBagName) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                                <input type="text" name="description"
                                       value="{{ e($iv('description', $item->description) ?? '') }}"
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:outline-none">
                                @error('description', $itemBagName) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Button Text</label>
                                <input type="text" name="button_text"
                                       value="{{ e($iv('button_text', $item->button_text) ?? '') }}"
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:outline-none">
                                @error('button_text', $itemBagName) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                                @php $srStatus = $iv('status', $item->status); @endphp
                                <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:ring-2 focus:outline-none">
                                    <option value="Active"   {{ $srStatus === 'Active'   ? 'selected' : '' }}>Active</option>
                                    <option value="Inactive" {{ $srStatus === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                        @endif

                        <div class="flex items-center gap-2 flex-wrap">
                            <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                Save
                            </button>
                            <button type="submit"
                                    form="cs-del-{{ $item->unique_id }}"
                                    class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-4 py-2 rounded-md text-sm font-medium">
                                Delete
                            </button>
                        </div>
                    </form>
                    <form id="cs-del-{{ $item->unique_id }}" method="POST"
                          action="{{ route($routePrefix . '.item.delete', $item->unique_id) }}"
                          onsubmit="return confirm('Delete this card?')">
                        @csrf @method('DELETE')
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
