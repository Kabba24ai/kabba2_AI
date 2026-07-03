{{-- ============================================================
     Contact Us — Contact Strip
     Reuses the same visual pattern as the home page contact strip
     but reads data from the Website Pages builder (not HomePageService).
============================================================ --}}

@php
$phoneCard  = $items->firstWhere('item_key', 'phone_card');
$storeCard1 = $items->firstWhere('item_key', 'store_1');
$storeCard2 = $items->firstWhere('item_key', 'store_2');

// Only show active items
$phoneCard  = ($phoneCard  && $phoneCard->status  === 'Active') ? $phoneCard  : null;
$storeCard1 = ($storeCard1 && $storeCard1->status === 'Active') ? $storeCard1 : null;
$storeCard2 = ($storeCard2 && $storeCard2->status === 'Active') ? $storeCard2 : null;

// Pull live store data (first 2 active stores)
$store1 = ($storeCard1 && $stores->isNotEmpty())       ? $stores->get(0) : null;
$store2 = ($storeCard2 && $stores->count() >= 2)       ? $stores->get(1) : null;

$colCount = (int)(bool)$phoneCard + (int)(bool)$store1 + (int)(bool)$store2;
$lgCols = ['1' => 'lg:grid-cols-1', '2' => 'lg:grid-cols-2', '3' => 'lg:grid-cols-3'][$colCount] ?? 'lg:grid-cols-3';
$mdCols = $colCount <= 1 ? '' : 'md:grid-cols-2';
@endphp

@if($section?->status === 'Active' && $colCount > 0)
<section class="relative z-20 py-8">
    <div class="container mx-auto px-4 md:px-6 lg:px-8">
        @if($section?->title || $section?->subtitle)
        <div class="text-center uppercase text-[#171636] mb-6">
            @if($section?->subtitle)
            <p class="text-sm font-semibold tracking-wider">{{ $section->subtitle }}</p>
            @endif
            @if($section?->title)
            <h2 class="mt-1 text-lg md:text-2xl font-semibold tracking-wide leading-none">{{ $section->title }}</h2>
            @endif
        </div>
        @endif

        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="grid grid-cols-1 {{ $mdCols }} {{ $lgCols }} bg-gray-100 gap-px">

                {{-- Phone Card --}}
                @if($phoneCard)
                <div class="bg-white px-6 py-7 lg:px-8 lg:py-8 flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">
                    <div class="shrink-0 mt-0.5 w-11 h-11 rounded-full bg-[#1F1D4E] flex items-center justify-center">
                        <x-heroicon-s-phone class="w-5 h-5 text-white"/>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-900 uppercase tracking-widest leading-none">{{ $phoneCard->title ?? 'Main Sales Line' }}</p>
                        @if($phoneCard->subtitle)
                        <a href="tel:{{ preg_replace('/[^+\d]/', '', $phoneCard->subtitle) }}"
                           class="mt-2 block text-lg font-bold text-yellow-500 leading-tight hover:text-yellow-600 transition-colors">
                            {{ $phoneCard->subtitle }}
                        </a>
                        @endif
                        @if($phoneCard->description)
                        <p class="mt-1.5 text-xs text-gray-500 leading-relaxed">{{ $phoneCard->description }}</p>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Store 1 --}}
                @if($store1)
                <div class="bg-white px-6 py-7 lg:px-8 lg:py-8 flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">
                    <div class="shrink-0 mt-0.5 w-11 h-11 rounded-full bg-[#1F1D4E] flex items-center justify-center">
                        <x-heroicon-s-map-pin class="w-5 h-5 text-white"/>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-900 uppercase tracking-widest leading-none">{{ $storeCard1->title ?: $store1->store_name }}</p>
                        @if($store1->phone)
                        <a href="tel:{{ preg_replace('/[^+\d]/', '', $store1->phone) }}"
                           class="mt-2 block text-lg font-bold text-gray-900 leading-tight hover:text-yellow-500 transition-colors">
                            {{ $store1->phone }}
                        </a>
                        @endif
                        <p class="mt-1.5 text-xs text-gray-500 leading-relaxed">
                            {{ $store1->address }}<br>
                            {{ $store1->city }}{{ $store1->state?->name ? ', ' . $store1->state->name : '' }} {{ $store1->zip_code }}
                        </p>
                    </div>
                </div>
                @endif

                {{-- Store 2 --}}
                @if($store2)
                <div class="bg-white px-6 py-7 lg:px-8 lg:py-8 flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">
                    <div class="shrink-0 mt-0.5 w-11 h-11 rounded-full bg-[#1F1D4E] flex items-center justify-center">
                        <x-heroicon-s-map-pin class="w-5 h-5 text-white"/>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-900 uppercase tracking-widest leading-none">{{ $storeCard2->title ?: $store2->store_name }}</p>
                        @if($store2->phone)
                        <a href="tel:{{ preg_replace('/[^+\d]/', '', $store2->phone) }}"
                           class="mt-2 block text-lg font-bold text-gray-900 leading-tight hover:text-yellow-500 transition-colors">
                            {{ $store2->phone }}
                        </a>
                        @endif
                        <p class="mt-1.5 text-xs text-gray-500 leading-relaxed">
                            {{ $store2->address }}<br>
                            {{ $store2->city }}{{ $store2->state?->name ? ', ' . $store2->state->name : '' }} {{ $store2->zip_code }}
                        </p>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</section>
@endif
