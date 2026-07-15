@extends('front.layouts.app')

@section('title', $title)

@push('meta')
    @if (!empty($productDetail->seo_title))
        <meta name="title" content="{{ $productDetail->seo_title }}">
        <meta property="og:title" content="{{ $productDetail->seo_title }}">
        <meta name="twitter:title" content="{{ $productDetail->seo_title }}">
    @endif
    @if (!empty($productDetail->seo_description))
        <meta name="description" content="{{ $productDetail->seo_description }}">
        <meta property="og:description" content="{{ $productDetail->seo_description }}">
        <meta name="twitter:description" content="{{ $productDetail->seo_description }}">
    @endif
@endpush

@push('schema')
    @include('front.partials.ai-page-metadata', [
        'pageType'  => 'product',
        'pageModel' => $productDetail,
    ])
@endpush

@section('content')

    <!-- Page Title Section -->
    <section class="md:mx-[30px] bg-gray-100" >
        <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto ">
            <div class="pt-[100px] pb-[20px] ">
                <div class="w-full">
                    <div class="flex flex-col lg:flex-row justify-between items-center ">
                        <h1 class="text-3xl lg:text-4xl font-bold">
                            {{ $productDetail->product_name }}</h1>
                        <ul class="px-5 py-4 mt-4 lg:mt-0 text-sm font-medium max-w-full flex flex-wrap items-center gap-3">
                            <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
                                <a href="{{ route('front.home.index') }}" class="opacity-25">Home</a>
                            </li>
                            @if ($parentCategory)
                                <!-- Parent Category -->
                                <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
                                    <a href="{{ route('front.categories.index', $parentCategory->slug) }}">
                                        {{ $parentCategory->title }}
                                    </a>
                                </li>
                                <!-- Only display child categories that are linked to the product -->
                                @foreach ($childCategories as $child)
                                    <li class="tracking-[0] after:content-['/'] after:pl-[5px]">
                                        <a
                                            href="{{ route('front.categories.sub-category', ['slug' => $parentCategory->slug, 'childCategorySlug' => $child->slug]) }}">
                                            {{ $child->title }}
                                        </a>
                                    </li>
                                @endforeach
                            @endif
                            <li class="tracking-[0] ">
                                <a href="javascript:void(0)"
                                    class="cursor-not-allowed">{{ $productDetail->product_name }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div id="productDetails">
        <!-- Skeleton loader shown while AJAX loads product details -->
        <div id="productDetailsSkeleton">
            <!-- Main product section skeleton -->
            <section class="pb-[10px] pt-[60px]">
                <div
                    class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                        <!-- Gallery skeleton -->
                        <div class="w-full mx-auto">
                            <div
                                class="relative w-full aspect-square border border-gray-200 rounded-lg overflow-hidden bg-gray-200 animate-pulse">
                            </div>
                            <div class="flex gap-2 mt-3 px-1">
                                @for ($i = 0; $i < 4; $i++)
                                    <div class="h-12 w-12 rounded-lg bg-gray-200 animate-pulse"></div>
                                @endfor
                            </div>
                        </div>
                        <!-- Product info skeleton -->
                        <div class="flex flex-col gap-4">
                            <!-- Price header bar -->
                            <div class="bg-gray-200 animate-pulse rounded h-[70px] w-full"></div>
                            <!-- Title lines -->
                            <div class="h-5 bg-gray-200 animate-pulse rounded w-3/4"></div>
                            <div class="h-4 bg-gray-200 animate-pulse rounded w-1/2"></div>
                            <!-- Date picker block -->
                            <div class="h-24 bg-gray-200 animate-pulse rounded w-full"></div>
                            <!-- Options block -->
                            <div class="h-16 bg-gray-200 animate-pulse rounded w-full"></div>
                            <!-- Button -->
                            <div class="h-12 bg-gray-200 animate-pulse rounded w-full"></div>
                            <!-- Extra lines -->
                            <div class="h-4 bg-gray-200 animate-pulse rounded w-5/6"></div>
                            <div class="h-4 bg-gray-200 animate-pulse rounded w-2/3"></div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Description / tabs skeleton -->
            <section class="py-8">
                <div
                    class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
                    <div class="flex gap-4 mb-6">
                        <div class="h-8 w-28 bg-gray-200 animate-pulse rounded"></div>
                        <div class="h-8 w-28 bg-gray-200 animate-pulse rounded"></div>
                        <div class="h-8 w-28 bg-gray-200 animate-pulse rounded"></div>
                    </div>
                    <div class="space-y-3">
                        <div class="h-4 bg-gray-200 animate-pulse rounded w-full"></div>
                        <div class="h-4 bg-gray-200 animate-pulse rounded w-5/6"></div>
                        <div class="h-4 bg-gray-200 animate-pulse rounded w-4/6"></div>
                        <div class="h-4 bg-gray-200 animate-pulse rounded w-full"></div>
                        <div class="h-4 bg-gray-200 animate-pulse rounded w-3/4"></div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- clean Modal -->
    <div id="modalBackdropClean" class="fixed inset-0 bg-black/50 flex items-center justify-center z-99999 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full md:max-w-lg p-6 relative max-w-[90%]">
            <p id="modalMessage" class="text-gray-700 mb-4">Dynamic message here</p>
            <div class="text-right flex flex-col md:flex-row whitespace-nowrap justify-center gap-3">
                <button id="modalRejectBtn"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 order-2 md:order-1">
                    No Thanks, I'll Take The Risk
                </button>
                <button id="modalAcceptBtn"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 order-1 md:order-2">
                    Keep Option
                </button>
            </div>
        </div>
    </div>

    <!-- High Demand Alert Modal — content managed in Website Mgt → High Demand Alert -->
    @php $hdAlert = app(\App\Services\Website\HighDemandAlertService::class)->config(); @endphp
    <div id="modalHighDemandAlert" role="dialog" aria-modal="true" aria-labelledby="hdAlertTitle"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-[99999] hidden">
        <div
            class="bg-white rounded-lg shadow-lg w-full md:max-w-3xl p-6 relative max-w-[95%] flex flex-col md:flex-row items-center gap-6">
            <div class="w-full md:w-1/3 flex justify-center">
                <img src="{{ $hdAlert->imageUrl }}" alt="Customer Service Representative"
                    class="max-h-64 object-contain">
            </div>

            <div class="flex-1 text-center md:text-left">
                <!-- Header -->
                <h2 id="hdAlertTitle" class="text-red-600 font-bold text-2xl mb-4">{{ $hdAlert->title }}</h2>

                <!-- Message (purified on save — limited emphasis tags only; line breaks kept) -->
                <p class="text-gray-700 mb-4">{!! nl2br($hdAlert->message) !!}</p>

                @if (!empty($hdAlert->phone))
                    <!-- Phone Number -->
                    <p class="text-black text-lg mb-6">
                        <a href="tel:{{ $hdAlert->phone }}"
                            class="font-bold hover:underline focus:underline">
                            {{ $hdAlert->phone }}
                        </a>
                    </p>
                @endif

                <!-- Buttons -->
                <div class="flex flex-col sm:flex-row justify-center md:justify-end gap-3">
                    <button id="continueReservationBtn"
                        class="px-5 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                        {{ $hdAlert->buttonText }}
                    </button>
                </div>
            </div>


        </div>
    </div>

    {{-- Custom Delivery selector — replaces the old informational Custom Range popup.
         Tier identifiers (custom_1..4) are internal only; customers see distances. --}}
    @php
        $customDeliveryTiers = \App\Helpers\DeliveryTierHelper::availableCustomTiersForProduct($productDetail, $productSettings);
    @endphp
    <div id="customDeliveryModal" role="dialog" aria-modal="true" aria-labelledby="customDeliveryTitle"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-[99999] hidden">
        <div class="bg-white rounded-lg shadow-lg w-full md:max-w-xl p-6 relative max-w-[95%] max-h-[90vh] overflow-y-auto">
            <h2 id="customDeliveryTitle" class="font-bold text-2xl mb-4">Custom Delivery</h2>

            <!-- Distance range section -->
            <h3 class="font-semibold text-lg mb-2">Select Distance Range</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6" role="radiogroup" aria-label="Select Distance Range">
                @foreach ($customDeliveryTiers as $customDeliveryTier)
                    <label
                        class="flex items-center gap-2 border-2 border-gray-200 rounded-lg px-4 py-3 cursor-pointer transition-colors has-[:checked]:border-yellow-500 has-[:checked]:bg-yellow-50 hover:border-yellow-400">
                        <input type="radio" name="custom_delivery_tier" class="sr-only peer"
                            value="{{ $customDeliveryTier['tier'] }}"
                            data-distance="{{ $customDeliveryTier['distance'] }}"
                            data-unit="{{ $customDeliveryTier['unit'] }}"
                            data-rate="{{ $customDeliveryTier['one_way_rate'] }}" />
                        <span
                            class="w-[16px] h-[16px] shrink-0 rounded-full border-2 border-yellow-400 peer-checked:border-yellow-500 peer-checked:bg-yellow-500 flex justify-center items-center">
                            <span class="w-2 h-2 rounded-full bg-white"></span>
                        </span>
                        <span class="text-gray-800">Up to {{ $customDeliveryTier['distance'] }} {{ $customDeliveryTier['unit'] }}</span>
                    </label>
                @endforeach
            </div>

            <!-- Delivery service section -->
            <h3 class="font-semibold text-lg mb-2">Select Delivery Service</h3>
            <div class="flex flex-col gap-2 mb-6" role="radiogroup" aria-label="Select Delivery Service">
                @foreach ([
                    'Delivery + Pickup' => 'Delivery + Return Pickup',
                    'Delivery Only' => 'Delivery Only',
                    'Return Only' => 'Return Pickup Only',
                ] as $serviceValue => $serviceLabel)
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="custom_delivery_service" class="sr-only peer"
                            value="{{ $serviceValue }}" {{ $serviceValue === 'Delivery + Pickup' ? 'checked' : '' }} />
                        <span
                            class="w-[16px] h-[16px] shrink-0 rounded-full border-2 border-yellow-400 peer-checked:border-yellow-500 peer-checked:bg-yellow-500 flex justify-center items-center">
                            <span class="w-2 h-2 rounded-full bg-white"></span>
                        </span>
                        <span class="text-gray-800">{{ $serviceLabel }}</span>
                    </label>
                @endforeach
            </div>

            <!-- Price summary (live) -->
            <div id="customDeliveryPriceSummary" aria-live="polite"
                class="mb-6 text-sm font-medium text-gray-700 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3">
                Choose a distance range to see pricing.
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row justify-end gap-3">
                <button type="button" id="customDeliveryCancelBtn"
                    class="px-5 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-100 order-2 sm:order-1">
                    Cancel
                </button>
                <button type="button" id="customDeliveryContinueBtn" disabled
                    class="px-5 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed order-1 sm:order-2">
                    Continue with Reservation
                </button>
            </div>
        </div>
    </div>


@endsection

@push('js')
    <script>
        window.productPageContext = {
            formattedStandardFeeX2: "{{ App\Helpers\CustomHelper::formatCurrency(($productDetail->standard_delivery_fee ?? 0) * 2) }}",
            formattedStandardFee: "{{ App\Helpers\CustomHelper::formatCurrency($productDetail->standard_delivery_fee ?? 0) }}",
            formattedExtendedFeeX2: "{{ App\Helpers\CustomHelper::formatCurrency(($productDetail->extended_delivery_fee ?? 0) * 2) }}",
            formattedExtendedFee: "{{ App\Helpers\CustomHelper::formatCurrency($productDetail->extended_delivery_fee ?? 0) }}",
            formattedZeroFee: "{{ App\Helpers\CustomHelper::formatCurrency(0) }}",
            distanceUnit: "{{ $productSettings['distance_unit'] ?? '' }}",
            standardDeliveryRange: "{{ $productSettings['standard_delivery_range'] ?? '' }}",
            extendedDeliveryRange: "{{ $productSettings['extended_delivery_range'] ?? '' }}",
            productUniqueId: "{{ $productDetail->unique_id ?? '' }}",
            productType: "{{ $productDetail->product_type ?? '' }}",
            productVariant: "{{ $productVariant }}",
            cartSaveUrl: "{{ route('front.cart.save') }}",
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            hasHighDemandAlert: "{{ $productDetail->has_high_demand_alert ?? false }}",
            // Available Custom tiers (identifiers are internal; customers see distances)
            customTiers: @json($customDeliveryTiers ?? []),
            customDelivery: { confirmed: null },
        };

        document.addEventListener('DOMContentLoaded', function() {

            loadProductDetailsPage();

            function loadProductDetailsPage() {

                let cart = window.CartStorage.getCart();

                apiFetch(
                        '{{ route('front.products.details', ['slug' => $productDetail->slug, 'productVariant' => $productVariant]) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                            },
                            body: JSON.stringify({
                                kabba_cart: cart
                            })
                        })
                    .then(response => {
                        if (response.html) {
                            window.productPageContext.parentRentalLock = response.parent_rental_lock || null;
                            document.getElementById('productDetails').innerHTML = response.html;
                            document.dispatchEvent(new CustomEvent('product:details-loaded'));
                        } else {
                            console.error('Failed to load product details:');
                        }
                    })
            }
        });
    </script>
    @vite('resources/front/assets/js/products/product-details-page.js')
@endpush
