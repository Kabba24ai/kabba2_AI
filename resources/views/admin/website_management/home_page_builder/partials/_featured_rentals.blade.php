{{-- ── Featured Rentals Section ──────────────────────────────────────────── --}}
@php
    $routePrefix = $routePrefix ?? 'admin.website-management.home-builder';
    $isActive    = ($section?->status ?? 'Active') === 'Active';
@endphp
@if($section)
<form method="POST"
      action="{{ route($routePrefix . '.section.update', $section->unique_id) }}"
      enctype="multipart/form-data" data-parsley-validate data-track-changes>
    @csrf
    <input type="hidden" name="status" value="{{ $isActive ? 'Active' : 'Inactive' }}">
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
            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle (optional)</label>
            {!! html()->text('subtitle', old('subtitle', $section->subtitle ?? ''))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                ->attributes(['placeholder' => 'Browse our rental categories']) !!}
            @error('subtitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
    @error('categories') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
</form>
@else
    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-sm text-yellow-800 mb-4">Featured Rentals section not found. Run the seeder first.</div>
@endif

{{-- ── Category picker ──────────────────────────────────────────────────── --}}
<div class="border-t border-gray-200 pt-6">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h3 class="text-sm font-semibold text-gray-900">Select Categories to Feature</h3>
            <p class="text-xs text-gray-500 mt-0.5">
                Click a category to add or remove it from the Featured Rentals grid on the homepage.
                Only published, top-level categories with at least one product are shown here.
            </p>
        </div>
        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium shrink-0">
            {{ $selectedCategoryIds->count() }} selected
        </span>
    </div>

    @if(!$section)
        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-sm text-yellow-800">
            Featured Rentals section not found. Run the seeder first.
        </div>
    @elseif($categories->isEmpty())
        <p class="text-sm text-gray-400">
            No eligible categories found. A category must be top-level, published, and have at least one product.
        </p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach($categories as $category)
                @php
                    $selectedItem = $items->first(fn ($i) => (int) data_get($i->content, 'category_id') === (int) $category->id);
                @endphp
                <div class="flex items-center justify-between gap-2 border border-gray-200 rounded-md px-3 py-2">
                    <span class="text-sm text-gray-700 truncate">{{ $category->title }}</span>

                    @if($selectedItem)
                        <form method="POST"
                              action="{{ route('admin.website-management.home-builder.item.delete', $selectedItem->unique_id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700 hover:bg-red-100 hover:text-red-700 transition-colors shrink-0">
                                Featured &check;
                            </button>
                        </form>
                    @else
                        <form method="POST"
                              action="{{ route('admin.website-management.home-builder.item.store') }}">
                            @csrf
                            <input type="hidden" name="section_unique_id" value="{{ $section->unique_id }}">
                            <input type="hidden" name="content[category_id]" value="{{ $category->id }}">
                            <button type="submit"
                                    class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-500 hover:bg-green-100 hover:text-green-700 transition-colors shrink-0">
                                Add
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
