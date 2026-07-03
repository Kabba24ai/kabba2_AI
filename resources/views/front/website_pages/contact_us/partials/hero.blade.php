{{-- ============================================================
     Contact Us — Hero Banner
     Full-width hero matching the V2 approved design.
     Background image, overlay, large heading, yellow subtitle,
     and a short description from the builder.
============================================================ --}}

@php
use App\Models\Global\Media;
$cfg        = $section?->content ?? [];
$hasOverlay = !empty($cfg['overlay_enabled']);
$opacity    = (int)($cfg['overlay_opacity'] ?? 55);
$minHeight  = $cfg['min_height'] ?? '380px';

$bgImageUrl = null;
if ($section?->image) {
    $media = Media::find($section->image);
    $bgImageUrl = $media?->url;
}
@endphp

<section class="relative w-full overflow-hidden"
         style="min-height:{{ $minHeight }}; {{ $bgImageUrl ? "background-image:url('" . e($bgImageUrl) . "');" : '' }} background-size:cover; background-position:center;">

    {{-- Overlay --}}
    @if($hasOverlay)
    <div class="absolute inset-0" style="background:rgba(0,0,0,{{ number_format($opacity / 100, 2) }});"></div>
    @endif

    {{-- Content --}}
    <div class="relative z-10 flex flex-col justify-center h-full"
         style="min-height:{{ $minHeight }}; padding-top:80px; padding-bottom:60px;">
        <div class="container mx-auto px-4 md:px-6 lg:px-8">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white leading-tight">
                {{ $section?->title ?? 'CONTACT US' }}
            </h1>
            @if($section?->subtitle)
            <p class="mt-3 text-lg md:text-xl font-bold text-yellow-400 uppercase tracking-wide">
                {{ $section->subtitle }}
            </p>
            @endif
            @if(!empty($cfg['description']))
            <p class="mt-3 text-sm md:text-base text-white/80 max-w-lg">
                {{ $cfg['description'] }}
            </p>
            @endif
        </div>
    </div>

</section>
