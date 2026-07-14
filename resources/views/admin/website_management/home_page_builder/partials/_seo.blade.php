{{-- ── SEO Settings ──────────────────────────────────────────────────── --}}
@php
    $routePrefix = $routePrefix ?? 'admin.website-management.home-builder';

    // OG fields are OPTIONAL — blank fields inherit the Meta values at
    // render time (SeoResolver). Never copy Meta text into the OG inputs:
    // keeping them blank lets future Meta edits flow through automatically.
    $isContact       = str_contains($routePrefix, 'contact');
    $ogImageFallback = $isContact
        ? "the website's default social-sharing image"
        : 'the homepage hero image';
@endphp
<form method="POST"
      action="{{ route($routePrefix . '.update') }}"
      enctype="multipart/form-data" data-parsley-validate>
    @csrf

    <div class="flex items-center justify-between mb-5">
        <div>
            <h3 class="text-base font-semibold text-gray-900">SEO Settings</h3>
            <p class="text-xs text-gray-500 mt-0.5">Meta tags, Open Graph, and canonical URL for {{ $isContact ? 'the Contact Us page' : 'the home page' }}. OG fields are optional — blank fields automatically use the Meta values.</p>
        </div>
        <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium shadow-sm">
            Save SEO Settings
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- Meta Title --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Meta Title <span class="text-gray-400 font-normal">(max 160 chars)</span>
            </label>
            {!! html()->text('meta_title', old('meta_title', $page?->meta_title ?? ''))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                ->attributes(['placeholder' => 'Rent n King – Equipment Rentals', 'maxlength' => '160']) !!}
            @error('meta_title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Meta Keywords --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
            {!! html()->text('meta_keywords', old('meta_keywords', $page?->meta_keywords ?? ''))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                ->attributes(['placeholder' => 'equipment rental, heavy machinery, Tennessee']) !!}
            @error('meta_keywords') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Meta Description --}}
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Meta Description <span class="text-gray-400 font-normal">(max 320 chars)</span>
            </label>
            {!! html()->textarea('meta_description', old('meta_description', $page?->meta_description ?? ''))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                ->rows(3)
                ->attributes(['placeholder' => 'Rent n King provides quality equipment rentals...', 'maxlength' => '320']) !!}
            @error('meta_description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- OG Title --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                OG Title
                <span class="text-gray-400 font-normal text-xs ml-1">Optional. When blank, the Meta Title is used.</span>
            </label>
            {!! html()->text('og_title', old('og_title', $page?->og_title ?? ''))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                ->attributes(['placeholder' => 'Leave blank to use the Meta Title', 'maxlength' => '160']) !!}
            @if(blank(old('og_title', $page?->og_title ?? '')) && filled($page?->meta_title))
                <p class="mt-1 text-xs text-gray-500">Using Meta Title: <span class="font-medium">{{ $page->meta_title }}</span></p>
            @endif
            @error('og_title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Canonical URL --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Canonical URL</label>
            {!! html()->text('canonical_url', old('canonical_url', $page?->canonical_url ?? ''))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                ->attributes(['placeholder' => 'https://rentnking.com']) !!}
            @error('canonical_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- OG Description --}}
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
                OG Description
                <span class="text-gray-400 font-normal text-xs ml-1">Optional. When blank, the Meta Description is used.</span>
            </label>
            {!! html()->textarea('og_description', old('og_description', $page?->og_description ?? ''))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                ->rows(2)
                ->attributes(['placeholder' => 'Leave blank to use the Meta Description', 'maxlength' => '320']) !!}
            @if(blank(old('og_description', $page?->og_description ?? '')) && filled($page?->meta_description))
                <p class="mt-1 text-xs text-gray-500">Using Meta Description: <span class="font-medium">{{ \Illuminate\Support\Str::limit($page->meta_description, 110) }}</span></p>
            @endif
            @error('og_description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- OG Image --}}
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
                OG Image <span class="text-gray-400 font-normal">(recommended 1200×630)</span>
                <span class="text-gray-400 font-normal text-xs ml-1">Optional. When blank, the default page or website image is used.</span>
            </label>
            <div class="flex items-center gap-4 flex-wrap">
                @php $ogMedia = $page?->og_image ? \App\Models\Global\Media::find($page->og_image) : null; @endphp
                <div>
                    <img id="seo-og-preview"
                         src="{{ $ogMedia?->url ?? '' }}"
                         class="h-16 w-32 object-cover border rounded-md bg-gray-50 {{ $ogMedia?->url ? '' : 'hidden' }}"
                         alt="OG image preview">
                    <div id="seo-og-placeholder"
                         class="h-16 w-32 flex items-center justify-center border rounded-md bg-gray-100 text-gray-400 text-xs {{ $ogMedia?->url ? 'hidden' : '' }}">
                        No image
                    </div>
                </div>
                <label class="inline-flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-md cursor-pointer bg-white hover:bg-gray-50 text-sm text-gray-700 w-fit">
                    Choose OG image
                    <input type="file" name="og_image" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only"
                           onchange="hpPreviewImage(this, 'seo-og-preview', 'seo-og-placeholder')">
                </label>
            </div>
            @if(!$ogMedia)
                <p class="mt-1 text-xs text-gray-500">No separate OG image selected. Using {{ $ogImageFallback }}.</p>
            @endif
            @error('og_image') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

    </div>

</form>
