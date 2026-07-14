{{-- Homepage wrapper for the GLOBAL shared contact strip.
     Content is managed once (Home Page Builder → Contact Strip) and
     rendered by front/partials/contact_strip.blade.php on every page
     that shows it. On the homepage the strip floats over the hero's
     bottom edge, hence overlapHero = true. --}}
@include('front.partials.contact_strip', ['overlapHero' => true])
