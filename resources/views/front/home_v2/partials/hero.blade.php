{{-- ============================================================
     Section: Hero
     Image renders at its natural proportions (w-full h-auto).
     The section has pt-16 so the banner starts below the fixed
     navbar rather than sliding behind it.
============================================================ --}}

@php
    $hero          = $hp->hero;
    $titleAlign    = match($hero->titlePosition)    { 'center' => 'center', 'right' => 'right', default => 'left' };
    $subtitleAlign = match($hero->subtitlePosition) { 'center' => 'center', 'right' => 'right', default => 'left' };
@endphp

<section id="home-v2-hero" class="relative pt-[4.2rem] lg:pt-16 bg-gray-900">

    {{-- Wrapper — height driven by the image itself (no aspect-ratio constraint) --}}
    <div class="relative w-full">

        {{-- Full-width image, height scales proportionally — no cropping --}}
        @if($hero->imageUrl)
        <img
            src="{{ $hero->imageUrl }}"
            alt="Equipment rental background"
            class="w-full h-auto block"
            aria-hidden="true"
        >
        @else
        {{-- Placeholder maintains ≈ 1920:450 ratio when no image is set --}}
        <div class="w-full bg-gray-800" style="padding-bottom: 23.44%;"></div>
        @endif

        {{-- Dark overlay --}}
        @if($hero->overlayEnabled)
        <div class="absolute inset-0 bg-black"
             style="opacity: {{ number_format($hero->overlayOpacity / 100, 2) }}"></div>
        @endif

        {{-- Text content, vertically centred over the banner --}}
        <div class="absolute inset-0 flex items-center">
            <div class="container mx-auto px-5 sm:px-6 lg:px-8 w-full">

                <div style="text-align: {{ $titleAlign }};">
                    <h1 class="text-[22px] leading-tight sm:text-[28px] md:text-[36px] lg:text-[38px] font-semibold uppercase"
                        style="color: {{ $hero->titleColor }};">
                        {!! nl2br(e($hero->title)) !!}
                    </h1>
                </div>

                <div class="mt-2" style="text-align: {{ $subtitleAlign }};">
                    <p class="text-sm sm:text-base leading-relaxed"
                       style="color: {{ $hero->subtitleColor }};">
                        {!! nl2br(e($hero->subtitle)) !!}
                    </p>
                </div>

            </div>
        </div>

    </div>

</section>
