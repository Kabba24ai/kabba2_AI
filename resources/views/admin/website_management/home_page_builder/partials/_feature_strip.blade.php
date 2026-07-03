{{-- ── Feature Strip Section ──────────────────────────────────────────── --}}
@php $routePrefix = $routePrefix ?? 'admin.website-management.home-builder'; @endphp

{{-- Section Status Mini-Form --}}
@if($section)
<form method="POST"
      action="{{ route($routePrefix . '.section.update', $section->unique_id) }}"
      class="mb-5">
    @csrf
    <div class="flex flex-wrap items-center gap-3 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3"
         x-data="{ active: @js(($section->status ?? 'Active') === 'Active') }">
        <div class="flex-1 min-w-0">
            <h3 class="text-base font-semibold text-gray-900">Feature Strip</h3>
            <p class="text-xs text-gray-500">The dark-navy bar below the hero — icon + title + subtitle per item.</p>
        </div>
        <input type="hidden" name="status" :value="active ? 'Active' : 'Inactive'">
        <div class="flex items-center gap-2 cursor-pointer select-none shrink-0" @click="active = !active">
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
                class="text-xs bg-gray-700 hover:bg-gray-800 text-white px-3 py-1.5 rounded-md font-medium shrink-0">
            Save Status
        </button>
    </div>
</form>
@else
<div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-sm text-yellow-800 mb-5">
    Feature Strip section not found. Run the seeder first.
</div>
@endif

{{-- Toolbar --}}
<div class="flex items-center justify-between mb-3">
    <h4 class="text-sm font-semibold text-gray-800">Feature Items ({{ $items->count() }})</h4>
    <button onclick="document.getElementById('add-feature-item').classList.toggle('hidden')"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-md text-xs font-medium">
        + Add Feature
    </button>
</div>

{{-- Add Feature Form --}}
<div id="add-feature-item" class="hidden mb-5 border border-dashed border-blue-300 rounded-lg p-4 bg-blue-50/30">
    <form method="POST" action="{{ route($routePrefix . '.item.store') }}">
        @csrf
        <input type="hidden" name="section_unique_id" value="{{ $section?->unique_id }}">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Title <span class="text-red-500">*</span></label>
                {!! html()->text('title')->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                    ->attributes(['placeholder' => 'Well Maintained Equipment', 'required' => 'required']) !!}
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Subtitle</label>
                {!! html()->text('subtitle')->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                    ->attributes(['placeholder' => 'Reliable. Clean. Job Ready.']) !!}
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Icon</label>
                {!! html()->text('icon')->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                    ->attributes(['placeholder' => 'heroicon-o-shield-check']) !!}
                <p class="text-xs text-gray-400 mt-0.5">heroicon-o-name or fa-class</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Display Order</label>
                {!! html()->number('display_order', $items->count() + 1)
                    ->class('w-24 border border-gray-300 rounded-md px-3 py-2 text-sm') !!}
            </div>
        </div>
        <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Add Feature
        </button>
    </form>
</div>

{{-- Feature Items — sortable --}}
@if($section)
    @if($items->isEmpty())
        <p class="text-sm text-gray-400 italic">No features yet. Add one above.</p>
    @else
        <p class="text-xs text-gray-400 mb-2 flex items-center gap-1">
            <x-heroicon-o-arrows-up-down class="w-3.5 h-3.5"/> Drag rows to reorder — order saves automatically.
        </p>
        <div id="sortable-feature-strip">
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
                        <span class="text-xs font-mono bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded shrink-0">#{{ $item->display_order }}</span>
                        @if($item->icon)
                            <code class="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded hidden sm:block shrink-0">{{ $item->icon }}</code>
                        @endif
                        <span class="text-sm font-medium text-gray-800 truncate">{{ $item->title }}</span>
                        @if($item->subtitle)
                            <span class="text-xs text-gray-400 hidden md:block truncate">— {{ $item->subtitle }}</span>
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
                          action="{{ route($routePrefix . '.item.update', $item->unique_id) }}"
                          data-track-changes>
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                                {!! html()->text('title', old('title', $item->title))
                                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm') !!}
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Subtitle</label>
                                {!! html()->text('subtitle', old('subtitle', $item->subtitle))
                                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm') !!}
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Icon Class</label>
                                {!! html()->text('icon', old('icon', $item->icon))
                                    ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                                    ->attributes(['placeholder' => 'heroicon-o-shield-check']) !!}
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
                                    form="fs-dup-{{ $item->unique_id }}"
                                    class="bg-purple-50 hover:bg-purple-100 text-purple-600 border border-purple-200 px-4 py-2 rounded-md text-sm font-medium">
                                Duplicate
                            </button>
                            <button type="submit"
                                    form="fs-del-{{ $item->unique_id }}"
                                    class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-4 py-2 rounded-md text-sm font-medium">
                                Delete
                            </button>
                        </div>
                    </form>
                    <form id="fs-dup-{{ $item->unique_id }}" method="POST"
                          action="{{ route($routePrefix . '.item.duplicate', $item->unique_id) }}">
                        @csrf
                    </form>
                    <form id="fs-del-{{ $item->unique_id }}" method="POST"
                          action="{{ route($routePrefix . '.item.delete', $item->unique_id) }}"
                          onsubmit="return confirm('Delete this feature?')">
                        @csrf @method('DELETE')
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    @endif
@endif
