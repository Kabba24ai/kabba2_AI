{{-- ── Footer Section ──────────────────────────────────────────────────── --}}
@php $routePrefix = $routePrefix ?? 'admin.website-management.home-builder'; @endphp
@php
    $footerContent = $section?->content ?? [];
    $copyrightText = $footerContent['copyright_text'] ?? '';
    $poweredByText = $footerContent['powered_by_text'] ?? '';
    $quickLinks    = $items->where('item_key', 'quick_link');
    $otherLinks    = $items->where('item_key', 'other_link');
    $socialLinks   = $items->where('item_key', 'social_link');
    $isActive      = ($section?->status ?? 'Active') === 'Active';
@endphp

@if($section)
<form method="POST"
      action="{{ route($routePrefix . '.section.update', $section->unique_id) }}"
      data-track-changes>
    @csrf
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5"
         x-data="{ active: @js($isActive) }">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Footer Content</h3>
            <p class="text-xs text-gray-500 mt-0.5">Copyright, powered-by text, and footer links.</p>
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
                Save Footer
            </button>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Copyright Text</label>
            {!! html()->text('content[copyright_text]', old('content.copyright_text', $copyrightText))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                ->attributes(['placeholder' => "© 2024 Rent 'n King. All rights reserved."]) !!}
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Powered By Text</label>
            {!! html()->text('content[powered_by_text]', old('content.powered_by_text', $poweredByText))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                ->attributes(['placeholder' => 'Development360']) !!}
        </div>
    </div>
</form>
@else
<div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-sm text-yellow-800 mb-4">
    Footer section not found. Run the seeder first.
</div>
@endif

{{-- ── Links Sections ──────────────────────────────────────────────── --}}
@foreach([
    ['key' => 'quick_link',  'label' => 'Quick Links',  'sortId' => 'sortable-footer-quick',  'items' => $quickLinks],
    ['key' => 'other_link',  'label' => 'Other Links',  'sortId' => 'sortable-footer-other',  'items' => $otherLinks],
    ['key' => 'social_link', 'label' => 'Social Links', 'sortId' => 'sortable-footer-social', 'items' => $socialLinks],
] as $group)
<div class="border-t border-gray-200 pt-5 mt-5">
    <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-semibold text-gray-800">{{ $group['label'] }} ({{ $group['items']->count() }})</h4>
        <button onclick="document.getElementById('add-{{ $group['key'] }}').classList.toggle('hidden')"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-md text-xs font-medium">
            + Add Link
        </button>
    </div>

    <div id="add-{{ $group['key'] }}" class="hidden mb-4 border border-dashed border-blue-300 rounded-lg p-3 bg-blue-50/30">
        <form method="POST" action="{{ route($routePrefix . '.item.store') }}">
            @csrf
            <input type="hidden" name="section_unique_id" value="{{ $section?->unique_id }}">
            <input type="hidden" name="item_key" value="{{ $group['key'] }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-2">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Label <span class="text-red-500">*</span></label>
                    {!! html()->text('title')->class('w-full border border-gray-300 rounded px-2 py-1.5 text-sm')
                        ->attributes(['placeholder' => 'Home', 'required' => 'required']) !!}
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">URL</label>
                    {!! html()->text('button_url')->class('w-full border border-gray-300 rounded px-2 py-1.5 text-sm')
                        ->attributes(['placeholder' => '/home']) !!}
                </div>
                @if($group['key'] === 'social_link')
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Icon Class</label>
                    {!! html()->text('icon')->class('w-full border border-gray-300 rounded px-2 py-1.5 text-sm')
                        ->attributes(['placeholder' => 'fa-facebook']) !!}
                </div>
                @endif
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Order</label>
                    {!! html()->number('display_order', $group['items']->count() + 1)
                        ->class('w-full border border-gray-300 rounded px-2 py-1.5 text-sm') !!}
                </div>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-3 py-1.5 rounded text-xs font-medium">Add</button>
        </form>
    </div>

    @if($group['items']->isNotEmpty())
        <p class="text-xs text-gray-400 mb-2 flex items-center gap-1">
            <x-heroicon-o-arrows-up-down class="w-3.5 h-3.5"/> Drag to reorder — saves automatically.
        </p>
    @endif

    <div id="{{ $group['sortId'] }}" class="space-y-1.5">
        @forelse($group['items']->sortBy('display_order') as $item)
            <div class="relative flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5"
                 data-item-id="{{ $item->unique_id }}"
                 x-data="{ editOpen: false }">
                <div class="flex items-center gap-3 text-sm min-w-0">
                    <span class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 shrink-0">
                        <x-heroicon-o-bars-3 class="w-4 h-4"/>
                    </span>
                    <span class="text-gray-400 text-xs shrink-0">#{{ $item->display_order }}</span>
                    @if($item->icon)
                        <code class="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded shrink-0">{{ $item->icon }}</code>
                    @endif
                    <span class="font-medium text-gray-800 truncate">{{ $item->title }}</span>
                    @if($item->button_url)
                        <span class="text-xs text-gray-400 hidden md:inline truncate">→ {{ $item->button_url }}</span>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button @click="editOpen = !editOpen"
                            class="text-xs text-blue-600 hover:underline">Edit</button>
                    <form method="POST"
                          action="{{ route($routePrefix . '.item.delete', $item->unique_id) }}"
                          onsubmit="return confirm('Delete this link?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:underline">Delete</button>
                    </form>
                </div>
                {{-- Inline edit panel (below the row) --}}
                <div x-show="editOpen" x-cloak
                     class="absolute left-0 right-0 mt-1 z-20 bg-white border border-gray-200 rounded-lg shadow-xl p-4"
                     style="top: 100%; min-width: 320px; max-width: 500px;">
                    <form method="POST" action="{{ route($routePrefix . '.item.update', $item->unique_id) }}">
                        @csrf
                        <div class="space-y-2 mb-2">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Label</label>
                                {!! html()->text('title', $item->title)->class('w-full border border-gray-300 rounded px-2 py-1.5 text-sm') !!}
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">URL</label>
                                {!! html()->text('button_url', $item->button_url)->class('w-full border border-gray-300 rounded px-2 py-1.5 text-sm') !!}
                            </div>
                            @if($group['key'] === 'social_link')
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Icon</label>
                                {!! html()->text('icon', $item->icon)->class('w-full border border-gray-300 rounded px-2 py-1.5 text-sm') !!}
                            </div>
                            @endif
                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 mb-1">Order</label>
                                    {!! html()->number('display_order', $item->display_order)->class('w-full border border-gray-300 rounded px-2 py-1.5 text-sm') !!}
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 mb-1">Status</label>
                                    {!! html()->select('status', ['Active' => 'Active', 'Inactive' => 'Inactive'], $item->status)
                                        ->class('w-full border border-gray-300 rounded px-2 py-1.5 text-sm') !!}
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                    class="flex-1 bg-blue-600 text-white px-3 py-1.5 rounded text-xs font-medium">
                                Save
                            </button>
                            <button type="button" @click="editOpen = false"
                                    class="px-3 py-1.5 border border-gray-300 rounded text-xs text-gray-600 hover:bg-gray-50">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400 italic">No {{ strtolower($group['label']) }} yet.</p>
        @endforelse
    </div>
</div>
@endforeach
