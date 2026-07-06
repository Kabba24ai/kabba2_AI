@php
    $routePrefix = $routePrefix ?? 'admin.website-management.contact-builder';
    $isActive    = ($section?->status ?? 'Active') === 'Active';

    $phoneCard  = $items->firstWhere('item_key', 'phone_card');
    $storeCard1 = $items->firstWhere('item_key', 'store_1');
    $storeCard2 = $items->firstWhere('item_key', 'store_2');
@endphp

{{--
    SECTION FORM — uses html() helpers + old() normally.
    withInput() is called on section validation failure, so old() is correctly
    populated for THIS form's fields only. Item forms below must NOT use html()
    helpers or old() — they use raw <input>/<select> tags with per-item session values.
--}}
@if($section)
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

{{-- ── Strip Cards ──────────────────────────────────────────────────────── --}}
<div class="border-t border-gray-200 pt-6">
    <h4 class="text-sm font-semibold text-gray-800 mb-4">Strip Cards</h4>

    @if($items->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-sm text-yellow-800">
            No cards found. Run the seeder to create the default phone and store cards.
        </div>
    @else
    <div class="space-y-2">

        {{-- ── Phone Card ── --}}
        @if($phoneCard)
        @php
            /*
             * Per-item scoping: read from session('_item_input_{unique_id}') set by
             * UpdateItemRequest::failedValidation(). Falls back to DB value.
             * Raw <input>/<select> tags bypass FormBuilder::getValueAttribute() which
             * would otherwise override the value with old($name) from the section form.
             */
            $pcBag   = 'item_' . $phoneCard->unique_id;
            $pcInput = session('_item_input_' . $phoneCard->unique_id, []);
            $pcv     = fn(string $key, $fallback = null) =>
                           array_key_exists($key, $pcInput) ? $pcInput[$key] : $fallback;
            $pcOpen  = $errors->hasBag($pcBag) && $errors->getBag($pcBag)->isNotEmpty();
        @endphp
        <div x-data="{ editOpen: @js($pcOpen) }" class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="flex items-center justify-between px-3 py-3 bg-gray-50 cursor-pointer"
                 @click="editOpen = !editOpen">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-mono bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded">phone_card</span>
                    <span class="text-sm font-medium text-gray-800">{{ $phoneCard->title ?: 'Main Sales Line' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $phoneCard->status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $phoneCard->status }}
                    </span>
                    <span :class="editOpen ? 'rotate-180' : ''" class="inline-flex transition-transform duration-200">
                        <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400"/>
                    </span>
                </div>
            </div>
            <div x-show="editOpen" x-cloak class="p-4 border-t border-gray-100">
                <form method="POST"
                      action="{{ route($routePrefix . '.item.update', $phoneCard->unique_id) }}"
                      data-track-changes>
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title"
                                   value="{{ e($pcv('title', $phoneCard->title) ?? '') }}"
                                   required
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:outline-none">
                            @error('title', $pcBag) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Phone Number <span class="text-red-500">*</span></label>
                            <input type="text" name="subtitle"
                                   value="{{ e($pcv('subtitle', $phoneCard->subtitle) ?? '') }}"
                                   required
                                   placeholder="(xxx) xxx-xxxx"
                                   autocomplete="tel"
                                   pattern="\(\d{3}\) \d{3}-\d{4}"
                                   class="masked-phone w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:outline-none">
                            @error('subtitle', $pcBag) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                            @php $pcStatus = $pcv('status', $phoneCard->status); @endphp
                            <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:ring-2 focus:outline-none">
                                <option value="Active"   {{ $pcStatus === 'Active'   ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ $pcStatus === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Save
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- ── Store Card 1 ── --}}
        @if($storeCard1)
        @php
            $sc1Bag          = 'item_' . $storeCard1->unique_id;
            $sc1Input        = session('_item_input_' . $storeCard1->unique_id, []);
            $sc1v            = fn(string $key, $fallback = null) =>
                                   array_key_exists($key, $sc1Input) ? $sc1Input[$key] : $fallback;
            $sc1Content      = $sc1v('content', null);
            $sc1StoreId      = is_array($sc1Content)
                                   ? ($sc1Content['store_id'] ?? data_get($storeCard1->content, 'store_id'))
                                   : data_get($storeCard1->content, 'store_id');
            $sc1DisplayPhone = is_array($sc1Content) && array_key_exists('display_phone', $sc1Content)
                                   ? (bool) $sc1Content['display_phone']
                                   : (bool) (data_get($storeCard1->content, 'display_phone') ?? true);
            $sc1DisplayAddr  = is_array($sc1Content) && array_key_exists('display_address', $sc1Content)
                                   ? (bool) $sc1Content['display_address']
                                   : (bool) (data_get($storeCard1->content, 'display_address') ?? true);
            $sc1Open         = $errors->hasBag($sc1Bag) && $errors->getBag($sc1Bag)->isNotEmpty();
        @endphp
        <div x-data="{ editOpen: @js($sc1Open) }" class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="flex items-center justify-between px-3 py-3 bg-gray-50 cursor-pointer"
                 @click="editOpen = !editOpen">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-mono bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded">store_1</span>
                    @php $s1 = $stores->firstWhere('id', data_get($storeCard1->content, 'store_id')); @endphp
                    <span class="text-sm font-medium text-gray-800">{{ $s1?->store_name ?? '— no store selected —' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $storeCard1->status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $storeCard1->status }}
                    </span>
                    <span :class="editOpen ? 'rotate-180' : ''" class="inline-flex transition-transform duration-200">
                        <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400"/>
                    </span>
                </div>
            </div>
            <div x-show="editOpen" x-cloak class="p-4 border-t border-gray-100">
                <form method="POST"
                      action="{{ route($routePrefix . '.item.update', $storeCard1->unique_id) }}"
                      data-track-changes>
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Store <span class="text-red-500">*</span></label>
                            <select name="content[store_id]" required
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-blue-400">
                                <option value="">— Select a store —</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}"
                                        {{ (string)$sc1StoreId === (string)$store->id ? 'selected' : '' }}>
                                        {{ $store->store_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('content.store_id', $sc1Bag) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                            @php $sc1Status = $sc1v('status', $storeCard1->status); @endphp
                            <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:ring-2 focus:outline-none">
                                <option value="Active"   {{ $sc1Status === 'Active'   ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ $sc1Status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="md:col-span-2 flex flex-wrap items-center gap-5 pt-1">
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input type="hidden" name="content[display_phone]" value="0">
                                <input type="checkbox" name="content[display_phone]" value="1"
                                       {{ $sc1DisplayPhone ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-xs font-medium text-gray-700">Display Phone</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input type="hidden" name="content[display_address]" value="0">
                                <input type="checkbox" name="content[display_address]" value="1"
                                       {{ $sc1DisplayAddr ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-xs font-medium text-gray-700">Display Address</span>
                            </label>
                        </div>
                    </div>
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Save
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- ── Store Card 2 ── --}}
        @if($storeCard2)
        @php
            $sc2Bag          = 'item_' . $storeCard2->unique_id;
            $sc2Input        = session('_item_input_' . $storeCard2->unique_id, []);
            $sc2v            = fn(string $key, $fallback = null) =>
                                   array_key_exists($key, $sc2Input) ? $sc2Input[$key] : $fallback;
            $sc2Content      = $sc2v('content', null);
            $sc2StoreId      = is_array($sc2Content)
                                   ? ($sc2Content['store_id'] ?? data_get($storeCard2->content, 'store_id'))
                                   : data_get($storeCard2->content, 'store_id');
            $sc2DisplayPhone = is_array($sc2Content) && array_key_exists('display_phone', $sc2Content)
                                   ? (bool) $sc2Content['display_phone']
                                   : (bool) (data_get($storeCard2->content, 'display_phone') ?? true);
            $sc2DisplayAddr  = is_array($sc2Content) && array_key_exists('display_address', $sc2Content)
                                   ? (bool) $sc2Content['display_address']
                                   : (bool) (data_get($storeCard2->content, 'display_address') ?? true);
            $sc2Open         = $errors->hasBag($sc2Bag) && $errors->getBag($sc2Bag)->isNotEmpty();
        @endphp
        <div x-data="{ editOpen: @js($sc2Open) }" class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="flex items-center justify-between px-3 py-3 bg-gray-50 cursor-pointer"
                 @click="editOpen = !editOpen">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-mono bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded">store_2</span>
                    @php $s2 = $stores->firstWhere('id', data_get($storeCard2->content, 'store_id')); @endphp
                    <span class="text-sm font-medium text-gray-800">{{ $s2?->store_name ?? '— no store selected —' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $storeCard2->status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $storeCard2->status }}
                    </span>
                    <span :class="editOpen ? 'rotate-180' : ''" class="inline-flex transition-transform duration-200">
                        <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400"/>
                    </span>
                </div>
            </div>
            <div x-show="editOpen" x-cloak class="p-4 border-t border-gray-100">
                <form method="POST"
                      action="{{ route($routePrefix . '.item.update', $storeCard2->unique_id) }}"
                      data-track-changes>
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Store <span class="text-red-500">*</span></label>
                            <select name="content[store_id]" required
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-blue-400">
                                <option value="">— Select a store —</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}"
                                        {{ (string)$sc2StoreId === (string)$store->id ? 'selected' : '' }}>
                                        {{ $store->store_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('content.store_id', $sc2Bag) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                            @php $sc2Status = $sc2v('status', $storeCard2->status); @endphp
                            <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:ring-2 focus:outline-none">
                                <option value="Active"   {{ $sc2Status === 'Active'   ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ $sc2Status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="md:col-span-2 flex flex-wrap items-center gap-5 pt-1">
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input type="hidden" name="content[display_phone]" value="0">
                                <input type="checkbox" name="content[display_phone]" value="1"
                                       {{ $sc2DisplayPhone ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-xs font-medium text-gray-700">Display Phone</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input type="hidden" name="content[display_address]" value="0">
                                <input type="checkbox" name="content[display_address]" value="1"
                                       {{ $sc2DisplayAddr ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-xs font-medium text-gray-700">Display Address</span>
                            </label>
                        </div>
                    </div>
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Save
                    </button>
                </form>
            </div>
        </div>
        @endif

    </div>
    @endif
</div>
