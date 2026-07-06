@php
    $routePrefix = $routePrefix ?? 'admin.website-management.contact-builder';
    $isActive    = ($section?->status ?? 'Active') === 'Active';

    // Split items by key for deterministic ordering: phone_card, store_1, store_2
    $phoneCard = $items->firstWhere('item_key', 'phone_card');
    $storeCard1 = $items->firstWhere('item_key', 'store_1');
    $storeCard2 = $items->firstWhere('item_key', 'store_2');
@endphp

{{-- ── Section form ────────────────────────────────────────────────────── --}}
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
        @php $phoneCardOpen = $errors->hasAny(['title', 'subtitle', 'description']); @endphp
        <div x-data="{ editOpen: @js($phoneCardOpen) }" class="border border-gray-200 rounded-lg overflow-hidden">
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
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Title <span class="text-red-500">*</span></label>
                            {!! html()->text('title', old('title', $phoneCard->title))
                                ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                                ->attributes(['required' => true]) !!}
                            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Phone Number <span class="text-red-500">*</span></label>
                            {!! html()->text('subtitle', old('subtitle', $phoneCard->subtitle))
                                ->class('masked-phone w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                                ->attributes(['placeholder' => '(xxx) xxx-xxxx', 'autocomplete' => 'tel', 'required' => true, 'pattern' => '\(\d{3}\) \d{3}-\d{4}']) !!}
                            @error('subtitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                            {!! html()->text('description', old('description', $phoneCard->description))
                                ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm') !!}
                            @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                            {!! html()->select('status', ['Active' => 'Active', 'Inactive' => 'Inactive'], old('status', $phoneCard->status))
                                ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white') !!}
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
        @php $selectedStore1 = $stores->firstWhere('id', data_get($storeCard1->content, 'store_id')); @endphp
        @php $store1Open = $errors->has('content.store_id'); @endphp
        <div x-data="{ editOpen: @js($store1Open) }" class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="flex items-center justify-between px-3 py-3 bg-gray-50 cursor-pointer"
                 @click="editOpen = !editOpen">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-mono bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded">store_1</span>
                    <span class="text-sm font-medium text-gray-800">{{ $selectedStore1?->store_name ?? '— no store selected —' }}</span>
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
                                        {{ (string)data_get($storeCard1->content, 'store_id') === (string)$store->id ? 'selected' : '' }}>
                                        {{ $store->store_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('content.store_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                            {!! html()->select('status', ['Active' => 'Active', 'Inactive' => 'Inactive'], old('status', $storeCard1->status))
                                ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white') !!}
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
        @php $selectedStore2 = $stores->firstWhere('id', data_get($storeCard2->content, 'store_id')); @endphp
        @php $store2Open = $errors->has('content.store_id'); @endphp
        <div x-data="{ editOpen: @js($store2Open) }" class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="flex items-center justify-between px-3 py-3 bg-gray-50 cursor-pointer"
                 @click="editOpen = !editOpen">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-mono bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded">store_2</span>
                    <span class="text-sm font-medium text-gray-800">{{ $selectedStore2?->store_name ?? '— no store selected —' }}</span>
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
                                        {{ (string)data_get($storeCard2->content, 'store_id') === (string)$store->id ? 'selected' : '' }}>
                                        {{ $store->store_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('content.store_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                            {!! html()->select('status', ['Active' => 'Active', 'Inactive' => 'Inactive'], old('status', $storeCard2->status))
                                ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white') !!}
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
