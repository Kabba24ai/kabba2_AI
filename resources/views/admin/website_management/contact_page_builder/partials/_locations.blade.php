@php
    $routePrefix = $routePrefix ?? 'admin.website-management.contact-builder';
    $isActive    = ($section?->status ?? 'Active') === 'Active';
@endphp

@if($section)
<form method="POST"
      action="{{ route($routePrefix . '.section.update', $section->unique_id) }}"
      data-track-changes>
    @csrf

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5"
         x-data="{ active: @js($isActive) }">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Store Locations</h3>
            <p class="text-xs text-gray-500 mt-0.5">Section heading only. Which stores appear and all store details are managed from Settings → Stores.</p>
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
                <span class="text-xs font-medium w-14"
                      :class="active ? 'text-green-600' : 'text-gray-400'"
                      x-text="active ? 'Active' : 'Inactive'"></span>
            </div>
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium shadow-sm">
                Save Section
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Section Heading</label>
            <input type="text" name="title"
                   value="{{ old('title', $section->title ?? 'Our Locations') }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none">
            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Section Subtitle</label>
            <input type="text" name="subtitle"
                   value="{{ old('subtitle', $section->subtitle ?? '') }}"
                   placeholder="e.g. Find a location near you"
                   class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none">
            @error('subtitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

</form>

<div class="border-t border-gray-200 pt-6 mt-2">
    <div class="rounded-md p-4 bg-blue-50 border border-blue-200 text-blue-900 max-w-lg">
        <div class="flex items-start gap-3">
            <x-heroicon-o-information-circle class="w-6 h-6 text-blue-600 shrink-0 mt-0.5"/>
            <div>
                <p class="text-sm font-semibold text-blue-900">Manage Stores</p>
                <p class="text-sm mt-1 text-blue-700">
                    Store details, addresses, hours of operation, and contact info are managed from the Stores management page.
                </p>
                <a href="{{ route('admin.stores.index') }}"
                   class="inline-flex items-center gap-1 mt-2 text-sm font-medium text-blue-800 underline hover:text-blue-900">
                    Go to Store Management
                    <x-heroicon-o-arrow-right class="w-4 h-4"/>
                </a>
            </div>
        </div>
    </div>
</div>
@else
<div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-sm text-yellow-800">
    Locations section not found. Run the seeder first.
</div>
@endif
