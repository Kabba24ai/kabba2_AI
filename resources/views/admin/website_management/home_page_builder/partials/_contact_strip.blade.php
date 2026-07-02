{{-- ── Contact Strip Section ──────────────────────────────────────────── --}}
@php
    $storeMap = ['store_1' => $stores->get(0), 'store_2' => $stores->get(1)];
    $isActive = ($section?->status ?? 'Active') === 'Active';
@endphp

@if($section)
<form method="POST"
      action="{{ route('admin.website-management.home-builder.section.update', $section->unique_id) }}"
      data-track-changes>
    @csrf
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5"
         x-data="{ active: @js($isActive) }">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Contact / Rental Specialist Strip</h3>
            <p class="text-xs text-gray-500 mt-0.5">Section heading text and visibility.</p>
        </div>
        <div class="flex items-center gap-3">
            <input type="hidden" name="status" :value="active ? 'Active' : 'Inactive'">
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
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
            {!! html()->text('subtitle', old('subtitle', $section->subtitle ?? ''))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                ->attributes(['placeholder' => 'Need Help Finding The Right Equipment?']) !!}
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
    <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-semibold text-gray-800">Strip Cards ({{ $items->count() }})</h4>
        <button onclick="document.getElementById('add-contact-item').classList.toggle('hidden')"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-md text-xs font-medium">
            + Add Card
        </button>
    </div>

    {{-- Add Item Form --}}
    <div id="add-contact-item" class="hidden mb-5 border border-dashed border-blue-300 rounded-lg p-4 bg-blue-50/30">
        <form method="POST" action="{{ route('admin.website-management.home-builder.item.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="section_unique_id" value="{{ $section?->unique_id }}">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Item Key</label>
                    {!! html()->text('item_key')->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                        ->attributes(['placeholder' => 'phone_card']) !!}
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                    {!! html()->text('title')->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                        ->attributes(['placeholder' => 'Main Sales Line']) !!}
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Phone Number</label>
                    {!! html()->text('subtitle')->class('masked-phone w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                        ->attributes(['placeholder' => '(xxx) xxx-xxxx', 'autocomplete' => 'tel']) !!}
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                    {!! html()->text('description')->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                        ->attributes(['placeholder' => "Questions? We're here to help!"]) !!}
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Icon</label>
                    {!! html()->text('icon')->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                        ->attributes(['placeholder' => 'heroicon-s-phone']) !!}
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Button Text</label>
                    {!! html()->text('button_text')->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                        ->attributes(['placeholder' => 'View Store']) !!}
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Button URL</label>
                    {!! html()->text('button_url')->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                        ->attributes(['placeholder' => '/stores/...']) !!}
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Display Order</label>
                    {!! html()->number('display_order', $items->count() + 1)
                        ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm') !!}
                </div>
            </div>
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                Add Card
            </button>
        </form>
    </div>

    {{-- Items List — sortable --}}
    @if($items->isEmpty())
        <p class="text-sm text-gray-400 italic">No cards yet. Add one above.</p>
    @else
        <p class="text-xs text-gray-400 mb-2 flex items-center gap-1">
            <x-heroicon-o-arrows-up-down class="w-3.5 h-3.5"/> Drag to reorder — saves automatically.
        </p>
        <div id="sortable-contact-strip">
            @foreach($items as $item)
            <div x-data="{ editOpen: false }"
                 data-item-id="{{ $item->unique_id }}"
                 class="border border-gray-200 rounded-lg mb-2 overflow-hidden">
                <div class="flex items-center justify-between px-3 py-3 bg-gray-50"
                     @click="editOpen = !editOpen">
                    <div class="flex items-center gap-3 min-w-0 cursor-pointer">
                        <span class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 shrink-0"
                              @click.stop>
                            <x-heroicon-o-bars-3 class="w-4 h-4"/>
                        </span>
                        <span class="text-xs font-mono bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded shrink-0">{{ $item->item_key }}</span>
                        <span class="text-sm font-medium text-gray-800 truncate">{{ $item->title }}</span>
                        @if(isset($storeMap[$item->item_key]) && $storeMap[$item->item_key])
                            <span class="text-xs bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded-full hidden sm:inline shrink-0">
                                {{ $storeMap[$item->item_key]->store_name }}
                            </span>
                        @elseif($item->subtitle)
                            <span class="text-xs text-gray-400 hidden md:inline truncate">— {{ $item->subtitle }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 shrink-0 cursor-pointer">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $item->status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $item->status }}
                        </span>
                        <span :class="editOpen ? 'rotate-180' : ''" class="inline-flex transition-transform duration-200">
                            <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400"/>
                        </span>
                    </div>
                </div>
                <div x-show="editOpen" x-cloak class="p-4 border-t border-gray-100">
                    <form method="POST"
                          action="{{ route('admin.website-management.home-builder.item.update', $item->unique_id) }}"
                          enctype="multipart/form-data"
                          data-track-changes>
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                                {!! html()->text('title', old('title', $item->title))
                                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm') !!}
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Phone Number</label>
                                {!! html()->text('subtitle', old('subtitle', $item->subtitle))
                                    ->class('masked-phone w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                                    ->attributes(['placeholder' => '(xxx) xxx-xxxx', 'autocomplete' => 'tel']) !!}
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                                {!! html()->text('description', old('description', $item->description))
                                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm') !!}
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Icon</label>
                                {!! html()->text('icon', old('icon', $item->icon))
                                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm') !!}
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Button Text</label>
                                {!! html()->text('button_text', old('button_text', $item->button_text))
                                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm') !!}
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Button URL</label>
                                {!! html()->text('button_url', old('button_url', $item->button_url))
                                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm') !!}
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Display Order</label>
                                {!! html()->number('display_order', old('display_order', $item->display_order))
                                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm') !!}
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                                {!! html()->select('status', ['Active' => 'Active', 'Inactive' => 'Inactive'], old('status', $item->status))
                                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm') !!}
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                Save
                            </button>
                            <button type="submit"
                                    form="cs-dup-{{ $item->unique_id }}"
                                    class="bg-purple-50 hover:bg-purple-100 text-purple-600 border border-purple-200 px-4 py-2 rounded-md text-sm font-medium">
                                Duplicate
                            </button>
                            <button type="submit"
                                    form="cs-del-{{ $item->unique_id }}"
                                    class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-4 py-2 rounded-md text-sm font-medium">
                                Delete
                            </button>
                        </div>
                    </form>
                    <form id="cs-dup-{{ $item->unique_id }}" method="POST"
                          action="{{ route('admin.website-management.home-builder.item.duplicate', $item->unique_id) }}">
                        @csrf
                    </form>
                    <form id="cs-del-{{ $item->unique_id }}" method="POST"
                          action="{{ route('admin.website-management.home-builder.item.delete', $item->unique_id) }}"
                          onsubmit="return confirm('Delete this card?')">
                        @csrf @method('DELETE')
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
