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

{{-- ── Category redirect info ───────────────────────────────────────────── --}}
<div class="border-t border-gray-200 pt-6">
    <div class="rounded-md p-4 bg-blue-50 border border-blue-200 text-blue-900 max-w-lg">
        <div class="flex items-start gap-3">
            <x-heroicon-o-information-circle class="w-6 h-6 text-blue-600 shrink-0 mt-0.5"/>
            <div>
                <p class="text-sm font-semibold text-blue-900">Manage Categories</p>
                <p class="text-sm mt-1 text-blue-700">
                    You can manage and update the categories shown in the Featured Rentals grid from the Category Management page.
                </p>
                <a href="{{ route('admin.product-management.categories.create') }}"
                   class="inline-flex items-center gap-1 mt-2 text-sm font-medium text-blue-800 underline hover:text-blue-900">
                    Go to Category Management
                    <x-heroicon-o-arrow-right class="w-4 h-4"/>
                </a>
            </div>
        </div>
    </div>
</div>
