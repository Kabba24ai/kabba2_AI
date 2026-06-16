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

    <!-- High Demand Alert Modal -->
    <div id="modalHighDemandAlert" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[99999] hidden">
        <div
            class="bg-white rounded-lg shadow-lg w-full md:max-w-3xl p-6 relative max-w-[95%] flex flex-col md:flex-row items-center gap-6">
            <div class="w-full md:w-1/3 flex justify-center">
                <img src="{{ asset('storage/front/images/lady-image.webp') }}" alt="Customer Service Representative"
                    class="max-h-64 object-contain">
            </div>

            <div class="flex-1 text-center md:text-left">
                <!-- Header -->
                <h2 class="text-red-600 font-bold text-2xl mb-4">High Demand Alert!</h2>

                <!-- Message -->
                <p class="text-gray-700 mb-4">
                    This item is currently experiencing a spike in demand, please complete the reservation and then
                    <span class="font-semibold italic">call Customer Service</span> to confirm the schedule details:
                </p>

                @if (!empty($brandingSettings['site_phone']))
                    <!-- Phone Number -->
                    <p class="text-black text-lg mb-6">
                        <a href="tel:{{ $brandingSettings['site_phone'] }}"
                            class="font-bold hover:underline focus:underline">
                            {{ $brandingSettings['site_phone'] }}
                        </a>
                    </p>
                @endif

                <!-- Buttons -->
                <div class="flex flex-col sm:flex-row justify-center md:justify-end gap-3">
                    <button id="continueReservationBtn"
                        class="px-5 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                        Continue Reservation
                    </button>
                </div>
            </div>


        </div>
    </div>

    <div id="customServiceOption" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[99999] hidden">
        <div
            class="bg-white rounded-lg shadow-lg w-full md:max-w-3xl p-6 relative max-w-[95%] flex flex-col md:flex-row items-center gap-6">
            <div class="w-full md:w-1/3 flex justify-center">
                <img src="{{ asset('storage/front/images/delivery-range.webp') }}" alt="Customer Service Representative"
                    class="max-h-64 object-contain">
            </div>

            <div class="flex-1 text-center md:text-left">
                <!-- Header -->
                <h2 class="text-red-600 font-bold text-2xl mb-4">Custom Range</h2>

                <!-- Message -->
                <p class="text-gray-700 mb-4">
                    Your reservation will be completed using the Extended Range Delivery and we will call you to make the
                    final arrangements based upon your delivery requirements.
                </p>

                <!-- Phone Number -->
                @if (!empty($brandingSettings['site_phone']))
                    <p class="text-black text-lg mb-6">
                        <a href="tel:{{ $brandingSettings['site_phone'] }}"
                            class="font-bold hover:underline focus:underline">
                            {{ $brandingSettings['site_phone'] }}
                        </a>
                    </p>
                @endif

                <!-- Buttons -->
                <div class="flex flex-col sm:flex-row justify-center md:justify-end gap-3">
                    <button id="continueReservationBtnCustom"
                        class="px-5 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                        Continue Reservation
                    </button>
                </div>
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
