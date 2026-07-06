{{-- ============================================================
     Contact Us — Hero Banner
     Exact same structure as home v2 hero (pt-16, w-full h-auto
     img in document flow, absolute overlay + text on top).
============================================================ --}}

@php
use App\Models\Global\Media;
$cfg          = $section?->content ?? [];
$hasOverlay   = !empty($cfg['overlay_enabled']);
$opacity      = (int)($cfg['overlay_opacity'] ?? 55);

$bgImageUrl = null;
if ($section?->image) {
    $media = Media::find($section->image);
    $bgImageUrl = $media?->url;
}

$titleAlign    = match($cfg['title_position']    ?? 'left') { 'center' => 'center', 'right' => 'right', default => 'left' };
$subtitleAlign = match($cfg['subtitle_position'] ?? 'left') { 'center' => 'center', 'right' => 'right', default => 'left' };
$titleColor    = $cfg['title_color']    ?? '#ffffff';
$subtitleColor = $cfg['subtitle_color'] ?? '#ffffff';

$descColor    = !empty($cfg['description_color'])    ? $cfg['description_color']    : null;
$descPosition = !empty($cfg['description_position']) ? $cfg['description_position'] : 'left';
@endphp

<section class="relative pt-16 bg-gray-900">

    <div class="relative w-full">

        {{-- Background image in document flow --}}
        @if($bgImageUrl)
        <img src="{{ e($bgImageUrl) }}"
             alt="Contact Us background"
             class="w-full h-auto block"
             aria-hidden="true">
        @else
        <div class="w-full bg-gray-800" style="padding-bottom: 23.44%;"></div>
        @endif

        {{-- Dark overlay --}}
        @if($hasOverlay)
        <div class="absolute inset-0 bg-black"
             style="opacity: {{ number_format($opacity / 100, 2) }};"></div>
        @endif

        {{-- Text content --}}
        <div class="absolute inset-0 flex items-center">
            <div class="container mx-auto px-5 sm:px-6 lg:px-8 w-full">

                <div style="text-align: {{ $titleAlign }};">
                    <h1 class="text-[22px] leading-tight sm:text-[28px] md:text-[36px] lg:text-[38px] font-semibold uppercase"
                        style="color: {{ $titleColor }};">
                        {!! nl2br(e($section?->title ?? 'CONTACT US')) !!}
                    </h1>
                </div>

                @if($section?->subtitle)
                <div class="mt-2" style="text-align: {{ $subtitleAlign }};">
                    <p class="text-sm sm:text-base leading-relaxed"
                       style="color: {{ $subtitleColor }};">
                        {!! nl2br(e($section->subtitle)) !!}
                    </p>
                </div>
                @endif

                @if(!empty($cfg['description']))
                @php
                    $descStyle = 'text-align:' . $descPosition . ';' . ($descColor ? ' color:' . $descColor . ';' : '');
                @endphp
                <div class="mt-2" style="text-align: {{ $descPosition }};">
                    <p class="text-sm sm:text-base leading-relaxed {{ $descColor ? '' : 'text-white/80' }}"
                       style="{{ $descStyle }}">
                        {{ $cfg['description'] }}
                    </p>
                </div>
                @endif

            </div>
        </div>

    </div>

</section>
