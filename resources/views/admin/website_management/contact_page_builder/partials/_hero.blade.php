{{-- ── Contact Page Header (section_key `hero`) ─────────────────────────────
     Simple title band editor. The hero IMAGE is homepage-only, so this
     deliberately exposes NO background-image, overlay, color/position, or
     readability controls. Filename stays `_hero` because the contact
     builder resolves partials by section key. --}}
@php
    $title       = old('title', $section->title ?? '');
    $subtitle    = old('subtitle', $section->subtitle ?? '');
    $description = old('content.description', $section->content['description'] ?? '');
    $isActive    = ($section->status ?? 'Active') === 'Active';
@endphp

@if($section)
<form method="POST"
      action="{{ route($routePrefix . '.section.update', $section->unique_id) }}"
      data-track-changes
      x-data="{ active: @js($isActive) }">
    @csrf

    {{-- Header row --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Page Header</h3>
            <p class="text-xs text-gray-500 mt-0.5">Title band shown at the top of the contact page. The hero image is homepage-only.</p>
        </div>
        <div class="flex items-center gap-3">
            <input type="hidden" name="status"
                   value="{{ $isActive ? 'Active' : 'Inactive' }}"
                   :value="active ? 'Active' : 'Inactive'">
            <div class="flex items-center gap-2 cursor-pointer select-none" @click="active = !active">
                <button type="button"
                        :class="active ? 'bg-green-500' : 'bg-gray-300'"
                        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-green-400">
                    <span :class="active ? 'translate-x-4' : 'translate-x-0.5'"
                          class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm"></span>
                </button>
                <span class="text-xs font-medium w-12"
                      :class="active ? 'text-green-600' : 'text-gray-400'"
                      x-text="active ? 'Active' : 'Inactive'"></span>
            </div>
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium shadow-sm">
                Save Header
            </button>
        </div>
    </div>

    {{-- Title --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Heading / Title
            <span class="text-gray-400 font-normal text-xs ml-1">— press Enter to add a line break</span>
        </label>
        <textarea name="title" rows="2"
                  placeholder="CONTACT US"
                  class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm shadow-sm focus:ring-2 focus:outline-none resize-none">{{ $title }}</textarea>
        @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Subtitle --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Subtitle
        </label>
        <textarea name="subtitle" rows="2"
                  placeholder="REAL PEOPLE. REAL SUPPORT."
                  class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm shadow-sm focus:ring-2 focus:outline-none resize-none">{{ $subtitle }}</textarea>
        @error('subtitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Description --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Description
            <span class="text-gray-400 font-normal text-xs ml-1">— optional introductory line below the subtitle</span>
        </label>
        <textarea name="content[description]" rows="2"
                  placeholder="We might be on the phone with others when you call, but we'll always call you back as quickly as possible."
                  class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm shadow-sm focus:ring-2 focus:outline-none resize-none">{{ $description }}</textarea>
        @error('content.description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

</form>
@else
    <div class="flex items-center gap-2 bg-yellow-50 border border-yellow-200 rounded-md px-4 py-3 text-sm text-yellow-800">
        <x-heroicon-o-exclamation-triangle class="w-4 h-4 shrink-0"/>
        No header section found. Run the contact page seeder first.
    </div>
@endif
