@php $routePrefix = $routePrefix ?? 'admin.website-management.home-builder'; @endphp
@php
    $heroContent      = $section?->content ?? [];
    // Merge old() input over DB values so errors repopulate the form
    $oldContent       = old('content', $heroContent);
    $overlayEnabled   = (bool)(int)($oldContent['overlay_enabled'] ?? $heroContent['overlay_enabled'] ?? true);
    $overlayOpacity   = (int)       ($oldContent['overlay_opacity'] ?? $heroContent['overlay_opacity'] ?? 65);
    $titlePosition    = $oldContent['title_position']    ?? $heroContent['title_position']    ?? 'left';
    $subtitlePosition = $oldContent['subtitle_position'] ?? $heroContent['subtitle_position'] ?? 'left';
    $titleColor       = $oldContent['title_color']       ?? $heroContent['title_color']       ?? '#ffffff';
    $subtitleColor    = $oldContent['subtitle_color']    ?? $heroContent['subtitle_color']     ?? '#ffffff';
    $descValue        = old('content.description',          $heroContent['description']          ?? '');
    $descPosition     = $oldContent['description_position'] ?? $heroContent['description_position'] ?? 'left';
    $descColor        = $oldContent['description_color']    ?? $heroContent['description_color']    ?? '#ffffff';
    $isActive         = ($section?->status ?? 'Active') === 'Active';
@endphp

@if(!$section)
    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-sm text-yellow-800">
        Hero section not found. Run the seeder first.
    </div>
@else
{{-- Separate form for image removal — must NOT be nested inside the main form --}}
<form id="hero-remove-img-form" method="POST"
      action="{{ route($routePrefix . '.section.remove-image', $section->unique_id) }}">
    @csrf @method('DELETE')
</form>

<form method="POST"
      action="{{ route($routePrefix . '.section.update', $section->unique_id) }}"
      enctype="multipart/form-data"
      data-track-changes
      x-data="{
          active:           @js($isActive),
          imgSrc:           @js($section->image_url ?? ''),
          mediaId:          '',
          overlayEnabled:   @js($overlayEnabled),
          overlayOpacity:   @js($overlayOpacity),
          titleText:        @js(old('title', $section->title ?? '')),
          subtitleText:     @js(old('subtitle', $section->subtitle ?? '')),
          titlePosition:    @js($titlePosition),
          subtitlePosition: @js($subtitlePosition),
          titleColor:          @js($titleColor),
          subtitleColor:       @js($subtitleColor),
          descriptionText:     @js($descValue),
          descriptionPosition: @js($descPosition),
          descriptionColor:    @js($descColor),
      }">
    @csrf

    {{-- Header row --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Hero Section</h3>
            <p class="text-xs text-gray-500 mt-0.5">Background image, heading, and overlay settings.</p>
        </div>
        <div class="flex items-center gap-3">
            <input type="hidden" name="status"
                   value="{{ ($section->status ?? 'Active') === 'Active' ? 'Active' : 'Inactive' }}"
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
                Save Hero
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ── Background Image ──────────────────────────────────── --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Background Image</label>
            <input type="hidden" name="media_id" :value="mediaId">
            <div class="flex items-start gap-5">
                <div>
                    <img :src="imgSrc"
                         x-show="imgSrc"
                         class="h-28 w-52 object-cover border border-gray-200 rounded-lg bg-gray-100"
                         alt="Hero background">
                    <div x-show="!imgSrc"
                         class="h-28 w-52 flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 text-gray-400 text-xs gap-1">
                        <x-heroicon-o-photo class="w-8 h-8 text-gray-300"/>
                        No image
                    </div>
                </div>
                <div class="flex flex-col gap-2.5">
                    <button type="button"
                            @click="window.MediaPicker.open(m => { mediaId = m.id; imgSrc = m.url; })"
                            class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium w-fit transition-colors">
                        <x-heroicon-o-photo class="w-4 h-4 shrink-0"/>
                        Choose from Library
                    </button>
                    <label class="inline-flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-md cursor-pointer bg-white hover:bg-gray-50 text-sm text-gray-700 w-fit">
                        <x-heroicon-o-arrow-up-tray class="w-4 h-4 text-gray-500 shrink-0"/>
                        Upload New
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/svg+xml" class="sr-only"
                               @change="mediaId = ''; imgSrc = URL.createObjectURL($event.target.files[0])">
                    </label>
                    <p class="text-xs text-gray-400">Recommended 1920×450 · JPG/PNG/WebP/SVG · max 4 MB</p>
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
            @error('image')    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('media_id') <p class="mt-1   text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- ── Overlay Settings ─────────────────────────────────────── --}}
        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50/50 self-start">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Overlay Settings</h4>
            <div class="space-y-4">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="content[overlay_enabled]" value="0">
                    <input type="checkbox" name="content[overlay_enabled]" value="1"
                           x-model="overlayEnabled"
                           class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Enable dark overlay on background image</span>
                </label>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">
                        Overlay Opacity — <span x-text="overlayOpacity + '%'" class="font-mono text-blue-600"></span>
                    </label>
                    <input type="range" name="content[overlay_opacity]"
                           x-model="overlayOpacity"
                           min="0" max="100"
                           class="w-full h-1.5 accent-blue-600 cursor-pointer">
                    @error('content.overlay_opacity')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ── Heading / Title ──────────────────────────────────────── --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Heading / Title
                <span class="text-gray-400 font-normal text-xs ml-1">— press Enter to add a line break</span>
            </label>
            <textarea name="title" rows="2"
                      x-model="titleText"
                      placeholder="THE RIGHT EQUIPMENT.&#10;THE RIGHT SUPPORT."
                      class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm shadow-sm focus:ring-2 focus:outline-none resize-none"
            ></textarea>
            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <div class="grid grid-cols-2 gap-3 mt-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Position</label>
                    <select name="content[title_position]"
                            x-model="titlePosition"
                            class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
                        <option value="left">Left</option>
                        <option value="center">Center</option>
                        <option value="right">Right</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Text Color</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="content[title_color]"
                               x-model="titleColor"
                               class="h-9 w-12 border border-gray-200 rounded cursor-pointer p-0.5 bg-white">
                        <span class="text-xs text-gray-500 font-mono" x-text="titleColor"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Subtitle / Description ───────────────────────────────── --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Subtitle / Description
                <span class="text-gray-400 font-normal text-xs ml-1">— press Enter to add a line break</span>
            </label>
            <textarea name="subtitle" rows="2"
                      x-model="subtitleText"
                      placeholder="Local team. Quality equipment.&#10;Ready when you are."
                      class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm shadow-sm focus:ring-2 focus:outline-none resize-none"
            ></textarea>
            @error('subtitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <div class="grid grid-cols-2 gap-3 mt-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Position</label>
                    <select name="content[subtitle_position]"
                            x-model="subtitlePosition"
                            class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
                        <option value="left">Left</option>
                        <option value="center">Center</option>
                        <option value="right">Right</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Text Color</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="content[subtitle_color]"
                               x-model="subtitleColor"
                               class="h-9 w-12 border border-gray-200 rounded cursor-pointer p-0.5 bg-white">
                        <span class="text-xs text-gray-500 font-mono" x-text="subtitleColor"></span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Description (contact page only) ──────────────────────── --}}
    @if($showDescription ?? false)
    <div class="mt-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Description
            <span class="text-gray-400 font-normal text-xs ml-1">— third line of text below the subtitle</span>
        </label>
        <textarea name="content[description]" rows="2"
                  x-model="descriptionText"
                  placeholder="We might be on the phone with others when you call, but we'll always call you back as quickly as possible."
                  class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm shadow-sm focus:ring-2 focus:outline-none resize-none"
        >{{ $descValue }}</textarea>
        @error('content.description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        <div class="grid grid-cols-2 gap-3 mt-3">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Position</label>
                <select name="content[description_position]"
                        x-model="descriptionPosition"
                        class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
                    <option value="left">Left</option>
                    <option value="center">Center</option>
                    <option value="right">Right</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Text Color</label>
                <div class="flex items-center gap-2">
                    <input type="color" name="content[description_color]"
                           x-model="descriptionColor"
                           class="h-9 w-12 border border-gray-200 rounded cursor-pointer p-0.5 bg-white">
                    <span class="text-xs text-gray-500 font-mono" x-text="descriptionColor"></span>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Live Preview ─────────────────────────────────────────────── --}}
    <div class="mt-6 border border-gray-200 rounded-xl overflow-hidden">
        <div class="flex items-center gap-2 px-4 py-2.5 border-b border-gray-100 bg-gray-50">
            <x-heroicon-o-eye class="w-4 h-4 text-gray-400"/>
            <span class="text-xs font-semibold text-gray-600">Live Preview</span>
            <span class="text-xs text-gray-400">— updates as you edit</span>
        </div>

        {{-- Preview viewport --}}
        <div class="relative overflow-hidden bg-gray-900" style="min-height: 200px;">

            {{-- Background image --}}
            <img :src="imgSrc" x-show="imgSrc"
                 class="w-full h-auto block"
                 aria-hidden="true">
            <div x-show="!imgSrc"
                 class="w-full bg-gradient-to-br from-gray-700 to-gray-900"
                 style="height: 220px;"></div>

            {{-- Overlay --}}
            <div x-show="overlayEnabled"
                 class="absolute inset-0 bg-black"
                 :style="{ opacity: overlayOpacity / 100 }"></div>

            {{-- Text content --}}
            <div class="absolute inset-0 flex items-center px-8 py-8">
                <div class="w-full">
                    <div :style="{ textAlign: titlePosition }">
                        <p class="text-lg sm:text-xl font-bold uppercase leading-snug"
                           :style="{ whiteSpace: 'pre-line', color: titleColor }"
                           x-text="titleText || 'Heading / Title…'"></p>
                    </div>
                    <div class="mt-2" :style="{ textAlign: subtitlePosition }">
                        <p class="text-sm leading-relaxed"
                           :style="{ whiteSpace: 'pre-line', color: subtitleColor }"
                           x-text="subtitleText || 'Subtitle / Description…'"></p>
                    </div>
                    @if($showDescription ?? false)
                    <div class="mt-2" x-show="descriptionText" :style="{ textAlign: descriptionPosition }">
                        <p class="text-xs leading-relaxed"
                           :style="{ whiteSpace: 'pre-line', color: descriptionColor }"
                           x-text="descriptionText"></p>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

</form>
@endif
