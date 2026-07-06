{{-- ── Branding — Logo & Favicon ────────────────────────────────────────── --}}
@php
    $currentLogo    = \App\Helpers\ConfigurationHelper::getHpBuilderLogo();
    $currentFavicon = \App\Helpers\ConfigurationHelper::getHpBuilderFavicon();
@endphp

<form method="POST"
      action="{{ route('admin.website-management.branding.update') }}"
      enctype="multipart/form-data"
      x-data="{ logoSelected: false, faviconSelected: false, get hasChanges() { return this.logoSelected || this.faviconSelected; } }">
    @csrf
    <input type="hidden" name="_context" value="hp_builder">

    <div class="flex items-center justify-between mb-5">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Branding</h3>
            <p class="text-xs text-gray-500 mt-0.5">Select a new logo or favicon to update. The button activates once a file is chosen.</p>
        </div>
        <button type="submit"
                :disabled="!hasChanges"
                :class="hasChanges
                    ? 'bg-blue-600 hover:bg-blue-700 cursor-pointer'
                    : 'bg-gray-300 cursor-not-allowed'"
                class="text-white px-5 py-2 rounded-md text-sm font-medium shadow-sm transition-colors">
            Save Branding
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Site Logo --}}
        <div class="border border-gray-200 rounded-lg p-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">
                Site Logo
                <span class="font-normal text-gray-400">(Navbar)</span>
            </label>
            <p class="text-xs text-gray-500 mb-3">Required size: 64 × 64 px</p>
            <div class="flex items-center gap-4 flex-wrap">
                @if($currentLogo)
                    <img src="{{ $currentLogo }}" class="h-14 w-14 object-contain border rounded-md bg-gray-50 p-1">
                @else
                    <div class="h-14 w-14 flex items-center justify-center border rounded-md bg-gray-100 text-gray-400 text-xs text-center leading-tight">No<br>logo</div>
                @endif
                {!! html()->file('hp_builder_logo')->class([
                    'block text-sm text-gray-500',
                    'file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0',
                    'file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100',
                    'border border-gray-300 rounded-md',
                ])->attributes(['accept' => 'image/*', '@change' => 'logoSelected = $event.target.files.length > 0']) !!}
            </div>
            <p class="mt-1.5 text-xs text-gray-400" x-show="!logoSelected">No new file selected — existing logo will be kept.</p>
            <p class="mt-1.5 text-xs text-green-600 font-medium" x-show="logoSelected" x-cloak>New logo selected — ready to save.</p>
            @error('hp_builder_logo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Favicon --}}
        <div class="border border-gray-200 rounded-lg p-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">
                Favicon
                <span class="font-normal text-gray-400">(Browser Tab Icon)</span>
            </label>
            <p class="text-xs text-gray-500 mb-3">Required size: 64 × 64 px</p>
            <div class="flex items-center gap-4 flex-wrap">
                @if($currentFavicon)
                    <img src="{{ $currentFavicon }}" class="h-14 w-14 object-contain border rounded-md bg-gray-50 p-1">
                @else
                    <div class="h-14 w-14 flex items-center justify-center border rounded-md bg-gray-100 text-gray-400 text-xs text-center leading-tight">No<br>favicon</div>
                @endif
                {!! html()->file('hp_builder_favicon')->class([
                    'block text-sm text-gray-500',
                    'file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0',
                    'file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100',
                    'border border-gray-300 rounded-md',
                ])->attributes(['accept' => 'image/*', '@change' => 'faviconSelected = $event.target.files.length > 0']) !!}
            </div>
            <p class="mt-1.5 text-xs text-gray-400" x-show="!faviconSelected">No new file selected — existing favicon will be kept.</p>
            <p class="mt-1.5 text-xs text-green-600 font-medium" x-show="faviconSelected" x-cloak>New favicon selected — ready to save.</p>
            @error('hp_builder_favicon') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

    </div>

</form>
