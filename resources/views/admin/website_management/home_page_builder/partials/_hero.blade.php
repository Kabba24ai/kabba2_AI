@php
    $heroContent    = $section?->content ?? [];
    $overlayEnabled = (bool) ($heroContent['overlay_enabled'] ?? true);
    $overlayOpacity = (int)  ($heroContent['overlay_opacity'] ?? 65);
    $buttonEnabled  = (bool) ($heroContent['button_enabled']  ?? false);
    $isActive       = ($section?->status ?? 'Active') === 'Active';
@endphp

@if(!$section)
    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-sm text-yellow-800">
        Hero section not found. Run the seeder first.
    </div>
@else
{{-- Separate form for image removal — must NOT be nested inside the main form --}}
<form id="hero-remove-img-form" method="POST"
      action="{{ route('admin.website-management.home-builder.section.remove-image', $section->unique_id) }}">
    @csrf @method('DELETE')
</form>

<form method="POST"
      action="{{ route('admin.website-management.home-builder.section.update', $section->unique_id) }}"
      enctype="multipart/form-data"
      data-track-changes>
    @csrf

    {{-- Header row --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6"
         x-data="{ active: @js($isActive) }">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Hero Section</h3>
            <p class="text-xs text-gray-500 mt-0.5">Background image, heading, and overlay settings.</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Section enable/disable toggle --}}
            <input type="hidden" name="status" :value="active ? 'Active' : 'Inactive'">
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
                Save Hero
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Background Image --}}
        <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Background Image</label>
            <div class="flex items-start gap-5 flex-wrap">
                <div>
                    <img id="hero-img-preview"
                         src="{{ $section->image_url ?? '' }}"
                         class="h-28 w-52 object-cover border border-gray-200 rounded-lg bg-gray-100 {{ $section->image_url ? '' : 'hidden' }}"
                         alt="Hero background">
                    <div id="hero-img-placeholder"
                         class="h-28 w-52 flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 text-gray-400 text-xs gap-1 {{ $section->image_url ? 'hidden' : '' }}">
                        <x-heroicon-o-photo class="w-8 h-8 text-gray-300"/>
                        No image
                    </div>
                </div>
                <div class="flex flex-col gap-2.5">
                    <label class="inline-flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-md cursor-pointer bg-white hover:bg-gray-50 text-sm text-gray-700 w-fit">
                        <x-heroicon-o-arrow-up-tray class="w-4 h-4 text-gray-500 shrink-0"/>
                        Choose image
                        <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only"
                               onchange="hpPreviewImage(this, 'hero-img-preview', 'hero-img-placeholder')">
                    </label>
                    <p class="text-xs text-gray-400">Recommended 1920×800 · JPG/PNG/WebP · max 4 MB</p>
                    @if($section->image_url)
                    <button type="submit"
                            form="hero-remove-img-form"
                            onclick="return confirm('Remove the hero background image?')"
                            class="text-xs text-red-500 hover:text-red-700 flex items-center gap-1">
                        <x-heroicon-o-trash class="w-3.5 h-3.5 shrink-0"/> Remove image
                    </button>
                    @endif
                </div>
            </div>
            @error('image') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Heading --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Heading / Title
                <span class="text-gray-400 font-normal text-xs ml-1">— press Enter to add a line break</span>
            </label>
            {!! html()->textarea('title', old('title', $section->title ?? ''))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm shadow-sm focus:ring-2 focus:outline-none resize-none')
                ->rows(2)
                ->attributes(['placeholder' => "THE RIGHT EQUIPMENT.\nTHE RIGHT SUPPORT."]) !!}
            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Subtitle --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Subtitle / Description
                <span class="text-gray-400 font-normal text-xs ml-1">— press Enter to add a line break</span>
            </label>
            {!! html()->textarea('subtitle', old('subtitle', $section->subtitle ?? ''))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm shadow-sm focus:ring-2 focus:outline-none resize-none')
                ->rows(2)
                ->attributes(['placeholder' => "Local team. Quality equipment.\nReady when you are."]) !!}
            @error('subtitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Overlay Settings --}}
        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50/50">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Overlay Settings</h4>
            <div class="space-y-4">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="content[overlay_enabled]" value="0">
                    <input type="checkbox" name="content[overlay_enabled]" value="1"
                           {{ $overlayEnabled ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Enable dark overlay on background image</span>
                </label>
                <div x-data="{ val: {{ $overlayOpacity }} }">
                    <label class="block text-xs font-medium text-gray-600 mb-2">
                        Overlay Opacity — <span x-text="val + '%'" class="font-mono text-blue-600"></span>
                    </label>
                    <input type="range" name="content[overlay_opacity]"
                           :value="val" x-model="val"
                           min="0" max="100"
                           class="w-full h-1.5 accent-blue-600 cursor-pointer">
                </div>
            </div>
        </div>

        {{-- CTA Button --}}
        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50/50">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">CTA Button</h4>
            <div class="space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="content[button_enabled]" value="0">
                    <input type="checkbox" name="content[button_enabled]" value="1"
                           {{ $buttonEnabled ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Show button on hero</span>
                </label>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Button Text</label>
                    {!! html()->text('button_text', old('button_text', $section->button_text ?? ''))
                        ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:outline-none')
                        ->attributes(['placeholder' => 'e.g. Browse Equipment']) !!}
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Button URL</label>
                    {!! html()->text('button_url', old('button_url', $section->button_url ?? ''))
                        ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:outline-none')
                        ->attributes(['placeholder' => '/equipment-rentals']) !!}
                </div>
            </div>
        </div>

    </div>

</form>
@endif
