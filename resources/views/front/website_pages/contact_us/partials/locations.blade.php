{{-- ============================================================
     Contact Us — Locations Section
     Exactly matches the approved V2 design:
     2-column grid, location badge, address, call/text buttons,
     info bar, hours of operation, Google Maps embed.

     If item.content.store_id is set, hours are pulled live from
     the Store model. Otherwise all data comes from item.content.
============================================================ --}}

@php
use App\Models\Stores\Store;

function contactUsFormatTime($time): ?string {
    return $time ? \Carbon\Carbon::parse($time)->format('g:i A') : null;
}

$activeItems = $items->where('status', 'Active')->sortBy('display_order')->values();

// Pre-load stores by ID for items that reference a store
$storeIds = $activeItems->pluck('content')->map(fn($c) => data_get($c, 'store_id'))->filter()->unique()->values();
$linkedStores = $storeIds->isNotEmpty()
    ? Store::with(['hoursOfOperation', 'state'])
        ->whereIn('id', $storeIds)
        ->get()
        ->keyBy('id')
    : collect();

$daysOrder = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

// If no items are configured, fall back to all active stores passed from controller
$fallbackStores   = isset($stores) ? $stores : collect();
$useStoreFallback = $activeItems->isEmpty() && $fallbackStores->isNotEmpty();

$badgePalette = ['#1F1D4E', '#B45309', '#065F46', '#1E40AF', '#6B21A8'];
@endphp

@if($section?->status !== 'Inactive' && ($activeItems->isNotEmpty() || $useStoreFallback))
<section class="py-10">
    <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-4 md:px-0">

        @if($section?->title)
        <div class="text-center mb-8">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">{{ $section->subtitle }}</p>
            <h2 class="mt-1 text-2xl md:text-3xl font-bold text-gray-900">{{ $section->title }}</h2>
            <div class="mx-auto mt-3 w-12 border-t-2 border-yellow-400"></div>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 pb-[60px]">
            @if($useStoreFallback)
            {{-- No items configured — render active stores directly --}}
            @foreach($fallbackStores as $store)
            @php $badgeColor = $badgePalette[$loop->index % count($badgePalette)]; @endphp
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden flex flex-col">
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
            @else
            @foreach($activeItems as $item)
            @php
                $c       = $item->content ?? [];
                $storeId = data_get($c, 'store_id');
                $linked  = $storeId ? $linkedStores->get($storeId) : null;

                // Use linked store for hours if available, manual fields otherwise
                $hasHours = $linked && $linked->hoursOfOperation && $linked->hoursOfOperation->count() > 0;

                // Map embed: use lat/lng or maps_url override
                $lat      = $c['latitude'] ?? ($linked?->latitude ?? null);
                $lng      = $c['longitude'] ?? ($linked?->longitude ?? null);
                $mapsUrl  = !empty($c['maps_url']) ? $c['maps_url'] : null;

                // Badge / name
                $badgeColor = $c['badge_color'] ?? '#1F1D4E';
                $locName    = $item->title;

                // Address (prefer manual, fall back to linked store)
                $address  = ($c['address']     ?? null) ?: ($linked?->address      ?? '');
                $city     = ($c['city']        ?? null) ?: ($linked?->city         ?? '');
                $stateStr = ($c['state']       ?? null) ?: ($linked?->state?->name ?? '');
                $zip      = ($c['zip']         ?? null) ?: ($linked?->zip_code     ?? '');
                $phone    = ($c['phone']       ?? null) ?: ($linked?->phone        ?? '');
                $details  = ($c['description'] ?? null) ?: ($linked?->details      ?? '');
            @endphp

            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden flex flex-col">
                <div class="px-8 pt-8">

                    {{-- Location badge + name --}}
                    <div class="flex items-center gap-3 mb-5">
                        <span class="inline-block px-3 py-1 rounded text-xs font-bold tracking-widest uppercase text-white"
                              style="background:{{ $badgeColor }}">
                            {{ $locName }}
                        </span>
                    </div>

                    {{-- Address + Click to Text --}}
                    @if($address)
                    <div class="flex items-start gap-3 mb-5 text-sm md:text-base">
                        <span class="mt-1 text-blue-500 shrink-0">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        <div class="text-left flex-1">
                            <p class="font-medium">{{ $address }}</p>
                            <p class="text-slate-600">{{ $city }}{{ $stateStr ? ', '.$stateStr : '' }} {{ $zip }}</p>
                        </div>
                        @if($phone)
                        <a href="sms:{{ preg_replace('/[^+\d]/', '', $phone) }}?body=Hello, I'm interested in your rental services. Could you provide information about availability and pricing?"
                           class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                            </svg>
                            Click to Text
                        </a>
                        @endif
                    </div>
                    @endif

                    {{-- Info bar --}}
                    @if($details)
                    <div class="mb-4 border-l-4 border-blue-600 bg-gray-50 px-5 py-3 text-sm text-slate-700">
                        {{ $details }}
                    </div>
                    @endif
                </div>

                {{-- Hours of Operation --}}
                @if($hasHours)
                <div class="px-8 pt-4 pb-2">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex-1 h-px bg-gray-200"></div>
                        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-widest whitespace-nowrap">Hours of Operation</h3>
                        <div class="flex-1 h-px bg-gray-200"></div>
                    </div>
                    <div class="space-y-1.5">
                        @foreach($daysOrder as $dayName)
                        @php
                            $day   = $linked->hoursOfOperation->firstWhere('day_name', $dayName);
                            $short = substr($dayName, 0, 3);
                        @endphp
                        <div class="flex items-center text-sm">
                            <span class="font-bold w-12 text-gray-700">{{ $short }}</span>
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

                {{-- Map --}}
                @if($lat && $lng || $mapsUrl)
                <div class="mt-auto bg-[#e3e3e3] w-full h-[280px]">
                    @php
                        $embedSrc = $mapsUrl ?: "https://maps.google.com/?q={$lat},{$lng}&output=embed";
                    @endphp
                    <iframe class="w-full h-full"
                            src="{{ $embedSrc }}"
                            style="border:0;" allowfullscreen="" loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
                @endif

            </div>
            @endforeach
            @endif
        </div>

    </div>
</section>
@endif
