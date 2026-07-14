{{-- ============================================================
     Section: Hero
     Image renders at its natural proportions (w-full h-auto) up to a
     450px maximum height. 1920×450 is the MAXIMUM supported canvas,
     not a mandatory rendered size: a shorter image (e.g. 1920×400)
     displays at its natural proportional height with no blank space;
     a proportionally taller image is capped at 450px and center-
     cropped via object-fit: cover — never stretched or distorted.
     Inline style is used (not a Tailwind class) because compiled CSS
     is committed and deploys do not run an asset build.
     The section has pt-16 so the banner starts below the fixed
     navbar rather than sliding behind it.
============================================================ --}}

@php
    $hero          = $hp->hero;
    $titleAlign    = match($hero->titlePosition)    { 'center' => 'center', 'right' => 'right', default => 'left' };
    $subtitleAlign = match($hero->subtitlePosition) { 'center' => 'center', 'right' => 'right', default => 'left' };

    /* Text-readability panel. Strength maps to background opacity only (layout
       is unchanged). Dark panel → white text; light panel → dark text. The
       translucent background — not the blur or the shadow — is the primary
       contrast mechanism, so it degrades gracefully where backdrop-filter is
       unsupported. Kept inline (no new classes) because compiled CSS is
       committed and deploys run no asset build. */
    $panelOn       = (bool) $hero->textBgEnabled;
    $panelLight    = $hero->textBgStyle === 'light';
    $panelOpacity  = ['light' => 0.28, 'medium' => 0.42, 'strong' => 0.60][$hero->textBgStrength] ?? 0.42;
    $panelTextColor= $panelLight ? '#111827' : '#ffffff';
    $titleColor    = $panelOn ? $panelTextColor : $hero->titleColor;
    $subtitleColor = $panelOn ? $panelTextColor : $hero->subtitleColor;
    $textShadow    = $panelLight ? '0 1px 2px rgba(255,255,255,.35)' : '0 1px 2px rgba(0,0,0,.45)';
    $panelStyle    = $panelOn
        ? 'display:inline-block; max-width:min(92%,520px); padding:16px 20px; border-radius:10px;'
          . ' background:' . ($panelLight ? "rgba(255,255,255,$panelOpacity)" : "rgba(0,0,0,$panelOpacity)") . ';'
          . ' color:' . $panelTextColor . ';'
          . ' backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px);'
        : '';
@endphp

<section id="home-hero" class="relative pt-[4.2rem] lg:pt-16 bg-gray-900">

    {{-- Wrapper — height driven by the image itself (no aspect-ratio constraint) --}}
    <div class="relative w-full">

        {{-- Full-width image, proportional height, hard-capped at 450px --}}
        @if($hero->imageUrl)
        <img
            src="{{ $hero->imageUrl }}"
            alt="Equipment rental background"
            class="w-full h-auto block"
            style="max-height: 450px; object-fit: cover;"
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

        {{-- Text content, vertically centred over the banner. The panel wraps
             only the heading + supporting text and hugs them (inline-block +
             max-width); it aligns to the heading's chosen side. --}}
        <div class="absolute inset-0 flex items-center">
            <div class="container mx-auto px-5 sm:px-6 lg:px-8 w-full">
                <div style="text-align: {{ $titleAlign }};">

                    <div class="hero-text-panel" style="{{ $panelStyle }}">
                        <div style="text-align: {{ $titleAlign }};">
                            <h1 class="text-[22px] leading-tight sm:text-[28px] md:text-[36px] lg:text-[38px] font-semibold uppercase"
                                style="color: {{ $titleColor }}; text-shadow: {{ $textShadow }};">
                                {!! nl2br(e($hero->title)) !!}
                            </h1>
                        </div>

                        <div class="mt-2" style="text-align: {{ $subtitleAlign }};">
                            <p class="text-sm sm:text-base leading-relaxed"
                               style="color: {{ $subtitleColor }}; text-shadow: {{ $textShadow }};">
                                {!! nl2br(e($hero->subtitle)) !!}
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

</section>
