{{-- ============================================================
     Contact Us — Locations Section
     2-column grid, location badge, address, call/text buttons,
     info bar, hours of operation, Google Maps embed.

     Store DETAILS are owned by Store Management. The Contact Page
     Builder saves only the DISPLAY ORDER (store_card items with
     content.store_id + display_order on this section):
       • saved order maps to the grid left→right, top→bottom
       • stores not yet ordered append to the end automatically
       • inactive/deleted stores are omitted and the grid compacts
       • with no saved order, the Primary store defaults to first
============================================================ --}}

@php
use App\Models\Stores\Store;

if (!function_exists('contactUsFormatTime')) {
    function contactUsFormatTime($time): ?string {
        return $time ? \Carbon\Carbon::parse($time)->format('g:i A') : null;
    }
}

$daysOrder = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

// Active stores, with everything the cards render
$activeStores = Store::with(['hoursOfOperation', 'state'])
    ->active()
    ->orderByAdmin()
    ->get();
$byId = $activeStores->keyBy('id');

// Saved contact-page display order (independent of Store Management)
$savedIds = ($items ?? collect())
    ->where('item_key', 'store_card')
    ->where('status', 'Active')
    ->sortBy('display_order')
    ->map(fn ($i) => (int) data_get($i->content, 'store_id'))
    ->filter()
    ->values();

if ($savedIds->isEmpty()) {
    // No saved order yet: Primary store defaults to Position 1
    $displayStores = $activeStores->values()
        ->sortByDesc(fn ($s) => $s->is_primary === 'Yes')
        ->values();
} else {
    // Saved order first (missing/inactive stores compact away),
    // then any new stores append to the end
    $displayStores = $savedIds
        ->map(fn ($id) => $byId->get($id))
        ->filter()
        ->concat($activeStores->reject(fn ($s) => $savedIds->contains($s->id)))
        ->values();
}

$badgePalette = ['#1F1D4E', '#B45309', '#065F46', '#1E40AF', '#6B21A8'];
@endphp

@if($section?->status !== 'Inactive' && $displayStores->isNotEmpty())
<section id="contact-locations" class="pt-4 pb-2 md:pt-6 lg:pt-8">
    <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-4 md:px-0">

        @if($section?->title)
            @if($section->subtitle)
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest text-center mb-3">{{ $section->subtitle }}</p>
            @endif
            <div class="flex items-center justify-center gap-3 sm:gap-4 md:gap-6 mb-6 sm:mb-8 md:mb-10">
                <div class="flex-1 max-w-[40px] sm:max-w-[80px] md:max-w-[100px] lg:max-w-[120px] h-px bg-yellow-400"></div>
                <h2 class="text-lg sm:text-xl md:text-2xl lg:text-[32px] font-semibold uppercase tracking-wide leading-tight text-center whitespace-nowrap">
                    {{ $section->title }}
                </h2>
                <div class="flex-1 max-w-[40px] sm:max-w-[80px] md:max-w-[100px] lg:max-w-[120px] h-px bg-yellow-400"></div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            @foreach($displayStores as $store)
            @php $badgeColor = $badgePalette[$loop->index % count($badgePalette)]; @endphp
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden flex flex-col"
                 data-store-card="{{ $store->id }}">
                <div class="px-8 pt-8">
                    <div class="flex items-center gap-3 mb-5">
                        <span class="inline-block px-3 py-1 rounded text-xs font-bold tracking-widest uppercase text-white"
                              style="background:{{ $badgeColor }}">
                            {{ $store->store_name }}
                        </span>
                    </div>
                    <div class="flex items-start gap-3 mb-5 text-sm md:text-base">
                        <span class="mt-1 text-blue-500 shrink-0">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        <div class="flex-1">
                            <p class="font-medium">{{ $store->address }}</p>
                            <p class="text-slate-600">{{ $store->city }}{{ $store->state?->name ? ', '.$store->state->name : '' }} {{ $store->zip_code }}</p>
                        </div>
                        @if($store->phone)
                        <a href="sms:{{ preg_replace('/[^+\d]/', '', $store->phone) }}?body=Hello, I'm interested in your rental services."
                           class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                            </svg>
                            Click to Text
                        </a>
                        @endif
                    </div>
                    @if($store->details)
                    <div class="mb-4 border-l-4 border-blue-600 bg-gray-50 px-5 py-3 text-sm text-slate-700">
                        {{ $store->details }}
                    </div>
                    @endif
                </div>
                @if($store->hoursOfOperation && $store->hoursOfOperation->count() > 0)
                <div class="px-8 pt-4 pb-5">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex-1 h-px bg-gray-200"></div>
                        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-widest whitespace-nowrap">Hours of Operation</h3>
                        <div class="flex-1 h-px bg-gray-200"></div>
                    </div>
                    <div class="space-y-1.5">
                        @foreach($daysOrder as $dayName)
                        @php $day = $store->hoursOfOperation->firstWhere('day_name', $dayName); @endphp
                        <div class="flex items-center text-sm">
                            <span class="font-bold w-12 text-gray-700">{{ substr($dayName, 0, 3) }}</span>
                            @if(!$day || $day->is_closed)
                                <span class="text-gray-500">Closed</span>
                            @else
                                <span class="text-gray-700">{{ contactUsFormatTime($day->start_time) }} &ndash; {{ contactUsFormatTime($day->end_time) }}</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @if($store->latitude && $store->longitude)
                <div class="mt-auto bg-[#e3e3e3] w-full h-[280px]">
                    <iframe class="w-full h-full"
                            src="https://maps.google.com/?q={{ $store->latitude }},{{ $store->longitude }}&output=embed"
                            style="border:0;" allowfullscreen="" loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                @endif
            </div>
            @endforeach
        </div>

    </div>
</section>
@endif
