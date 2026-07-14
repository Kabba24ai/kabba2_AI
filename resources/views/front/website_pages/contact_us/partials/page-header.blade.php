{{-- ============================================================
     Contact Us — Page Header
     Simple title band. The hero image belongs to the homepage
     only — interior pages use this standard header treatment.
     Data: the page's `hero` section row (title / subtitle /
     content.description text preserved from the old hero).
============================================================ --}}

<section id="contact-page-header" class="pt-[4.2rem] lg:pt-16 bg-[#171636]">
    <div class="container mx-auto px-5 sm:px-6 lg:px-8 py-10 md:py-14">

        <h1 class="text-[22px] leading-tight sm:text-[28px] md:text-[34px] font-semibold uppercase text-white">
            {!! nl2br(e($section?->title ?? 'CONTACT US')) !!}
        </h1>

        @if($section?->subtitle)
        <p class="mt-2 text-sm sm:text-base leading-relaxed text-white/80">
            {!! nl2br(e($section->subtitle)) !!}
        </p>
        @endif

        @if(!empty($section?->content['description']))
        <p class="mt-2 text-sm sm:text-base leading-relaxed text-white/70">
            {!! nl2br(e($section->content['description'])) !!}
        </p>
        @endif

    </div>
</section>
