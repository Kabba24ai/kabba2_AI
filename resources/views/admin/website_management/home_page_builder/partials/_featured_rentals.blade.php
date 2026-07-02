{{-- ── Featured Rentals Section ──────────────────────────────────────────── --}}
@if($section)
<form method="POST"
      action="{{ route('admin.website-management.home-builder.section.update', $section->unique_id) }}"
      enctype="multipart/form-data" data-parsley-validate>
    @csrf
    <div class="flex items-center justify-between mb-5">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Featured Rentals Section</h3>
            <p class="text-xs text-gray-500 mt-0.5">Section heading and which categories to show.</p>
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium shadow-sm">
            Save Section
        </button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Section Title</label>
            {!! html()->text('title', old('title', $section->title ?? 'FEATURED RENTALS'))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                ->attributes(['placeholder' => 'FEATURED RENTALS']) !!}
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle (optional)</label>
            {!! html()->text('subtitle', old('subtitle', $section->subtitle ?? ''))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                ->attributes(['placeholder' => 'Browse our rental categories']) !!}
        </div>
    </div>
</form>
@else
    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-sm text-yellow-800 mb-4">Featured Rentals section not found. Run the seeder first.</div>
@endif

{{-- ── Category Selection ──────────────────────────────────────────────── --}}
<div class="border-t border-gray-200 pt-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h4 class="text-sm font-semibold text-gray-800">Selected Categories</h4>
            <p class="text-xs text-gray-500">Check categories to show in the Featured Rentals grid. Use display order to sequence them.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($categories as $cat)
            @php
                $existingItem = $items->first(fn($i) => data_get($i->content, 'category_id') == $cat->id);
                $isSelected   = $selectedCategoryIds->contains($cat->id);
            @endphp
            <div x-data="{ open: {{ $isSelected ? 'true' : 'false' }} }"
                 class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50"
                     @click="open = !open">
                    <span class="{{ $isSelected ? 'bg-blue-600' : 'bg-gray-200' }} w-3.5 h-3.5 rounded-full shrink-0 transition-colors"></span>
                    <span class="text-sm font-medium text-gray-800 flex-1">{{ $cat->title }}</span>
                    @if($isSelected)
                        <span class="text-xs text-blue-600 font-semibold">Active</span>
                    @endif
                </div>
                <div x-show="open" x-cloak class="border-t border-gray-100 bg-gray-50 p-3">
                    @if($existingItem)
                        <form method="POST"
                              action="{{ route('admin.website-management.home-builder.item.update', $existingItem->unique_id) }}">
                            @csrf
                            <div class="flex items-center gap-2 mb-2">
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 mb-1">Display Order</label>
                                    {!! html()->number('display_order', $existingItem->display_order)->class('w-full border border-gray-300 rounded px-2 py-1.5 text-xs') !!}
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 mb-1">Status</label>
                                    {!! html()->select('status', ['Active' => 'Active', 'Inactive' => 'Inactive'], $existingItem->status)->class('w-full border border-gray-300 rounded px-2 py-1.5 text-xs') !!}
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="bg-blue-600 text-white px-3 py-1.5 rounded text-xs font-medium">Update</button>
                                <form method="POST" action="{{ route('admin.website-management.home-builder.item.delete', $existingItem->unique_id) }}"
                                      onsubmit="return confirm('Remove this category?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bg-red-50 text-red-600 border border-red-200 px-3 py-1.5 rounded text-xs font-medium">Remove</button>
                                </form>
                            </div>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.website-management.home-builder.item.store') }}">
                            @csrf
                            <input type="hidden" name="section_unique_id" value="{{ $section?->unique_id }}">
                            <input type="hidden" name="item_key" value="category_{{ $cat->id }}">
                            <input type="hidden" name="title" value="{{ $cat->title }}">
                            <input type="hidden" name="content[category_id]" value="{{ $cat->id }}">
                            <div class="mb-2">
                                <label class="block text-xs text-gray-500 mb-1">Display Order</label>
                                {!! html()->number('display_order', $loop->iteration)->class('w-full border border-gray-300 rounded px-2 py-1.5 text-xs') !!}
                            </div>
                            <button type="submit" class="bg-green-600 text-white px-3 py-1.5 rounded text-xs font-medium w-full">
                                Add to Featured
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
