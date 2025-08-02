@extends('front.layouts.app')

@section('title', $title)

@section('content')

    <!-- Page Title Section -->
    <section class="md:mx-[30px] bg-gray-100">
        <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto ">
            <div class="pt-[100px] pb-[20px] ">
                <div class="w-full">
                    <div class="flex flex-col lg:flex-row justify-between items-center ">
                        <h1 class="text-3xl lg:text-4xl font-bold">
                            {{ $productDetail->product_name }}</h1>
                        <ul
                            class="border-yellow-400 px-5 py-4 mt-4 lg:mt-0 text-sm font-medium border-2 max-w-full flex flex-wrap items-center gap-3">
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
                                        <a href="{{ route('front.categories.sub-category', ['slug' => $parentCategory->slug, 'childCategorySlug' => $child->slug]) }}">
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
    <!-- product details -->
    <section class="pb-[60px] pt-[60px]">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-8">

                @php
                    $galleryImages = [$productDetail->image_url];
                    foreach ($productDetail->mediaChildren as $child) {
                        $galleryImages[] = $child->media->url;
                    }
                @endphp

                <div id="product-gallery-wrapper" class="w-full mx-auto">
                    <div
                        class="relative w-full mx-auto aspect-square border border-gray-200 rounded-lg overflow-hidden bg-white group flex items-center justify-center">
                        <!-- Title -->
                        <div
                            class="absolute top-3 left-3 z-10 h-5 bg-yellow-400 text-gray-800 text-xs font-medium rounded px-2 py-1 shadow flex items-center justify-center">
                            {{ $productDetail->product_name ?? 'Product' }}
                        </div>
                        <!-- Main image (with lightbox, initially first image) -->
                        <a href="{{ $galleryImages[0] }}" id="main-image-link">
                            <img src="{{ $galleryImages[0] }}" alt="Product" id="main-image"
                                class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105" />
                        </a>

                        <!-- Zoom icon -->
                        <button type="button" id="zoom-btn"
                            class="absolute bottom-3 right-3 bg-white/80 hover:bg-white rounded-full shadow p-2 transition opacity-0 group-hover:opacity-100 focus:opacity-100">
                            <x-heroicon-o-magnifying-glass class="w-6 h-6 text-gray-700" />
                        </button>
                    </div>
                    <!-- Thumbnails -->
                    <div class="flex gap-2 mt-3 px-1">
                        @foreach ($galleryImages as $imgIndex => $imgUrl)
                            <div class="h-12 w-12 rounded-lg border border-gray-200 overflow-hidden bg-white cursor-pointer flex items-center justify-center {{ $imgIndex === 0 ? 'ring-2 ring-yellow-400' : '' }}"
                                data-thumb-index="{{ $imgIndex }}">
                                <img src="{{ $imgUrl }}" alt="Thumbnail" class="w-full h-full object-contain" />
                                <a href="{{ $imgUrl }}" data-gallery="product-gallery" class="hidden">
                                    <span class="sr-only">View larger image of product thumbnail {{ $imgIndex + 1 }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="bg-gray-100 mb-4 flex flex-col lg:flex-row justify-between items-center pl-2 header-price">
                        <div>
                            <h3 class="font-bold text-xl lg:text-2xl">{{ $productDetail->product_name }}
                            </h3>
                            <p
                                class="before:content-['('] before:text-gray-500 after:content-[')'] after:text-gray-500 text-green-600 ">
                                In Stock</p>
                        </div>
                        <div class="bg-yellow-400 text-xl h-[auto] md:h[auto] lg:h-[70px] px-6 flex items-center font-medium price-tag-box header-price">
                            <h4>
                                @if ($productDetail->product_type == 'Rental')
                                    <span>
                                        <span class="font-bold text-xl lg:text-3xl">
                                            {{ App\Helpers\CustomHelper::formatCurrency($productDetail->getRentalPrice($productVariant)) }}
                                        </span>
                                        @if ($productDetail->isRentalOnSale($productVariant))
                                            <span class="line-through text-gray-500 text-base ml-1 font-normal italic">
                                                {{ App\Helpers\CustomHelper::formatCurrency($productDetail->getRentalPrice($productVariant, false)) }}
                                            </span>
                                        @endif
                                        <span class="font-normal">/ {{ $productVariant }}</span>
                                    </span>
                                @else
                                    <span>
                                        <span class="font-bold text-3xl">
                                            {{ App\Helpers\CustomHelper::formatCurrency($productDetail->getRetailPrice()) }}
                                        </span>
                                        @if ($productDetail->isRetailOnSale())
                                            <span class="line-through text-gray-500 text-base ml-1 font-normal italic">
                                                {{ App\Helpers\CustomHelper::formatCurrency($productDetail->getRetailPrice(false)) }}
                                            </span>
                                        @endif
                                    </span>
                                @endif
                            </h4>
                        </div>
                    </div>

                    <div>
                        <strong> Short Description :- </strong>
                        <p> {!! $productDetail->short_description !!} </p>
                    </div>

                    <div class="my-5">
                        <div class="flex items-center">
                            <!-- Calendar icon button -->
                            <a href="javascript:void(0);" id="openDatePicker" class="flex items-center space-x-2 mr-3">
                                <x-heroicon-o-calendar-days class="w-6 h-6 md:w-[52px] h-[52px] text-yellow-400" />
                                <span id="selectedDateText" class="text-sm md:text-base text-gray-700">Select Start Date</span>
                            </a>

                            <!-- Right side: date display + qty controls, spaced out -->
                            <div class="flex items-center space-x-3 md:space-x-2 lg:space-x-4">

                                <!-- Quantity controls unchanged -->
                                <button type="button"
                                    class="border-0 bg-yellow-400 rounded-full w-[30px] h-[30px] text-xl font-bold hover:bg-yellow-300 transition-all duration-500 ease-in-out"
                                    onclick="changeQty(-1)">−</button>

                                <input type="number" id="qty" name="qty" value="1" min="1" max="1000"
                                    class="w-16 text-center border border-gray-300 rounded" />

                                <button type="button"
                                    class="border-0 bg-yellow-400 rounded-full w-[30px] h-[30px] text-xl font-bold hover:bg-yellow-300 transition-all duration-500 ease-in-out"
                                    onclick="changeQty(1)">+</button>
                            </div>

                            <!-- Hidden input stays outside for accessibility/JS -->
                            <input type="text" name="delivery_date" id="scheduleStartDateInput"
                                data-format="{{ config('app.date.js_date_format') }}"
                                data-min-date="{{ now()->format(config('app.date.db_date_format')) }}"
                                class="sr-only" readonly placeholder="Select Start Date" />
                        </div>
                        <!-- Optional: error message -->
                        <div id="dateError" class="text-red-600 text-sm hidden mt-1"></div>
                    </div>

                    <div class="my-5">
                        @php
                            $showInStore = $productDetail->in_store_pickup === 'Yes';
                            $showDelivery = $productDetail->delivery_and_pickup === 'Yes';

                            // Figure out which service method should be checked by default
                            $checkedOption = $showInStore
                                ? 'In Store Pickup'
                                : ($showDelivery
                                    ? 'Delivery'
                                    : 'In Store Pickup');

                            $hasStandard = $productDetail->standard_delivery_fee !== null;
                            $hasExtended = $productDetail->extended_delivery_fee !== null;

                            // Determine default checked value
                            $checkedDistanceType = $hasStandard ? 'Standard' : ($hasExtended ? 'Extended' : 'Custom');
                        @endphp
                        @if ($showInStore)
                            <div class="space-y-2">
                                <label class="inline-flex items-center space-x-2">
                                    <input type="radio" name="service_method" value="In Store Pickup" class="form-radio"
                                        {{ $checkedOption === 'In Store Pickup' ? 'checked' : '' }} />
                                    <span>In Store Pickup / Return</span>
                                </label>
                            </div>
                        @endif
                        @if ($showDelivery)
                            <div class="space-y-2">
                                <label class="inline-flex items-center space-x-2">
                                    <input type="radio" name="service_method" value="Delivery" class="form-radio"
                                        {{ $checkedOption === 'Delivery' ? 'checked' : '' }} />
                                    <span>Delivery - Full Turnkey Service</span>
                                </label>
                            </div>
                            <div id="deliveryDiv" class="mt-4 mb-5 {{ $checkedOption === 'Delivery' ? '' : 'hidden' }}">
                                <div>
                                    <span>Up To: </span>
                                    @if ($hasStandard)
                                        <label class="inline-flex items-center space-x-2">
                                            <input type="radio" name="distance_type" value="Standard"
                                                class="sr-only peer"
                                                data-text="{{ $productSettings['standard_delivery_range'] . ' ' . $productSettings['distance_unit'] }}"
                                                {{ $checkedDistanceType === 'Standard' ? 'checked' : '' }} />
                                            <div
                                                class="w-[16px] h-[16px] rounded-full border-2 border-yellow-400 peer-checked:border-yellow-500 peer-checked:bg-yellow-500 flex justify-center items-center">
                                                <div class="w-2 h-2 rounded-full border-white bg-white z-1"></div>
                                            </div>
                                            <span>{{ $productSettings['standard_delivery_range'] . ' ' . $productSettings['distance_unit'] }}</span>
                                        </label>
                                    @endif

                                    @if ($hasExtended)
                                        <label class="inline-flex items-center space-x-2">
                                            <input type="radio" name="distance_type" value="Extended"
                                                class="sr-only peer"
                                                data-text="{{ $productSettings['extended_delivery_range'] . ' ' . $productSettings['distance_unit'] }}"
                                                {{ $checkedDistanceType === 'Extended' ? 'checked' : '' }} />
                                            <div
                                                class="w-[16px] h-[16px] rounded-full border-2 border-yellow-400 peer-checked:border-yellow-500 peer-checked:bg-yellow-500 flex justify-center items-center">
                                                <div class="w-2 h-2 rounded-full border-white bg-white z-1"></div>
                                            </div>
                                            <span>{{ $productSettings['extended_delivery_range'] . ' ' . $productSettings['distance_unit'] }}</span>
                                        </label>
                                    @endif
                                    <label class="inline-flex items-center space-x-2">
                                        <input type="radio" name="distance_type" value="Custom" class="sr-only peer"
                                            {{ $checkedDistanceType === 'Custom' ? 'checked' : '' }} />
                                        <div
                                            class="w-[16px] h-[16px] rounded-full border-2 border-yellow-400 peer-checked:border-yellow-500 peer-checked:bg-yellow-500 flex justify-center items-center">
                                            <div class="w-2 h-2 rounded-full border-white bg-white z-1"></div>
                                        </div>
                                        <span>Custom</span>
                                    </label>
                                </div>

                                <div id="deliveryOptionsDropdown" class="hidden w-full mt-5 mb-5">
                                    <label for="deliveryOptionSelect" class="block font-medium mb-1">
                                        Choose Delivery Type
                                        <span id="distanceTypeTitle"></span>
                                    </label>
                                    <select id="deliveryOptionSelect"
                                        class="block w-full md:w-8/12 lg:w-5/12  px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none appearance-none">
                                        <option value="">Select Delivery Options</option>
                                        <option data-id="1" value="Delivery + Pickup">
                                            Delivery + Pick Up (To/From My Job Site)
                                            [<span id="DeliveryPickupPrice"></span>]
                                        </option>
                                        <option data-id="2" value="Delivery Only">
                                            Delivery but I'll Return to Store
                                            [<span id="DeliveryReturnPrice"></span>]
                                        </option>
                                        <option data-id="3" value="Return Only">
                                            I'll Pick Up In-Store but Need Return Service Pick Up
                                            [<span id="PickupReturnPrice"></span>]
                                        </option>
                                    </select>
                                </div>

                                <!-- Custom Option Text -->
                                <div id="customServiceOption" class="hidden w-[100%] md:w-[100%] lg:w-[80%] border-[5px] border-sky-400 p-2 mt-2">
                                    <h4 class="text-center font-bold">Please Call / Text for Custom Solutions</h4>
                                    <h5 class="text-center text-red font-bold py-2">(615) 815-6734</h5>
                                    <p class="italic">Please reserve the item now, using the closest delivery range,
                                        then
                                        call / text afterwards for a custom delivery solution. </p>
                                </div>
                            </div>
                        @endif
                        <div id="storeDiv" class="hidden w-full mt-5 mb-5">
                            <label for="storeSelect" class="block font-medium mb-1">
                                Choose Store Location
                            </label>
                            <select id="storeSelect"
                                class="block w-full md:w-8/12 lg:w-5/12 px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none appearance-none">
                                <option disabled selected value="">Select Store Location</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}">
                                        {{ $store->store_name}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($productDetail->hasOptions())
                        <div id="optionDiv" class="inline-grid">
                            <h4 class="font-semibold text-xl lg:text-2xl mb-1">Options</h4>
                            @if ($productDetail->rental_prepaid_fuel !== null)
                                <!-- Prepaid Fuel -->
                                <label class="inline-flex items-center space-x-2 mt-1 w-fit">
                                    <input type="checkbox" class="form-checkbox h-4 w-4" checked id="fuelCheckbox"
                                        data-charged="1 Time Max" data-name="rental_prepaid_fuel"
                                        data-keep="{{ $productSettings['prepaid_fuel_approve_label'] }}"
                                        data-discard="{{ $productSettings['prepaid_fuel_decline_label'] }}"
                                        data-message="{{ $productSettings['prepaid_fuel_info'] }}" />
                                    <span>Prepaid Fuel
                                        <span class="font-medium">
                                            +
                                            {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_prepaid_fuel) }}
                                        </span>
                                    </span>
                                </label>
                            @endif

                            @if ($productDetail->rental_prepaid_cleaning !== null)
                                <!-- Prepaid Cleaning -->
                                <label class="inline-flex items-center space-x-2 mt-1 w-fit">
                                    <input type="checkbox" class="form-checkbox h-4 w-4" checked id="cleanCheckbox"
                                        data-charged="1 Time Max" data-name="rental_prepaid_cleaning"
                                        data-keep="{{ $productSettings['prepaid_cleaning_approve_label'] }}"
                                        data-discard="{{ $productSettings['prepaid_cleaning_decline_label'] }}"
                                        data-message="{{ $productSettings['prepaid_cleaning_info'] }}" />
                                    <span>Prepaid Cleaning
                                        <span class="font-medium">+
                                            {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_prepaid_cleaning) }}
                                        </span>
                                    </span>
                                </label>
                            @endif

                            @if (
                                $productDetail->rental_damage_waiver_daily !== null ||
                                    $productDetail->rental_damage_waiver_weekend !== null ||
                                    $productDetail->rental_damage_waiver_weekly !== null ||
                                    $productDetail->rental_damage_waiver_monthly !== null)
                                <!-- Damage Waiver -->
                                <label class="inline-flex items-center space-x-2 mt-1 w-fit">
                                    <input type="checkbox" class="form-checkbox h-4 w-4" checked id="damageCheckbox"
                                        data-charged="1 Time Max" data-name="rental_damage_waiver"
                                        data-keep="{{ $productSettings['damage_waiver_approve_label'] }}"
                                        data-discard="{{ $productSettings['damage_waiver_decline_label'] }}"
                                        data-message="{{ $productSettings['damage_waiver_info'] }}" />
                                    @php
                                        $damageWaiverPrice = match ($productVariant) {
                                            'daily' => App\Helpers\CustomHelper::formatCurrency(
                                                $productDetail->rental_damage_waiver_daily,
                                            ),
                                            'weekend' => App\Helpers\CustomHelper::formatCurrency(
                                                $productDetail->rental_damage_waiver_weekend,
                                            ),
                                            'weekly' => App\Helpers\CustomHelper::formatCurrency(
                                                $productDetail->rental_damage_waiver_weekly,
                                            ),
                                            'monthly' => App\Helpers\CustomHelper::formatCurrency(
                                                $productDetail->rental_damage_waiver_monthly,
                                            ),
                                            default => '-',
                                        };
                                    @endphp

                                    <span>Damage Waiver
                                        <span class="font-medium">
                                            + {{ $damageWaiverPrice }}
                                        </span>
                                    </span>
                                </label>
                            @endif

                            @foreach ($productDetail->options as $option)
                                @if ($option->items->isNotEmpty())
                                    @foreach ($option->items as $item)
                                        <label class="inline-flex items-center space-x-2 mt-1 w-fit">
                                            <input type="checkbox" class="form-checkbox h-4 w-4"
                                                {{ $item->value == 'Checked' ? 'checked' : '' }}
                                                id="options_{{ $item->unique_id }}"
                                                data-keep="{{ $item->accept_label }}"
                                                data-discard="{{ $item->decline_label }}"
                                                data-message="{{ $item->comment }}"
                                                data-unique_id="{{ $item->unique_id }}"
                                                data-charged="{{ $item->charged ?? null }}" />
                                            <span>{{ $item->label }}
                                                <span class="font-medium">+
                                                    @php
                                                        $price = match ($productVariant) {
                                                            'daily' => $item->daily,
                                                            'weekend' => $item->weekend,
                                                            'weekly' => $item->weekly,
                                                            'monthly' => $item->monthly,
                                                            default => $item->retail_price,
                                                        };
                                                    @endphp
                                                    {{ App\Helpers\CustomHelper::formatCurrency($price) }}

                                                    @if (!empty($item->charged) && $item->charged !== null)
                                                        <small style="display: none;"
                                                            class="text-gray-500">({{ $item->charged }})</small>
                                                    @endif
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach
                                @endif
                            @endforeach
                        </div>
                    @endif
                    <div>
                        <button type="button" id="addToCart"
                            class="border-0 bg-yellow-400  font-medium flex px-6 py-3 mt-4 items-center leading-4 rounded-lg text-[14px] hover:bg-yellow-300  transition-all duration-500 ease-in-out"><i
                                class="fa-solid fa-bag-shopping text-[20px] mr-3"></i> Add to Cart
                            <img src="{{ asset('storage/front/images/loading.gif') }}" alt="loading"
                                class="loader-gif ml-5 h-5 hidden" /></button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-16">
        <div class="container mx-auto max-w-screen-2xl px-4 md:px-6">
            <div class="pb-10">
                <a href="javascript:void(0)"
                    class="bg-yellow-400 px-6 py-3 text-sm font-medium rounded-lg shadow hover:bg-yellow-300 transition-colors border-0">
                    Details
                </a>
            </div>
            <div class="flex flex-col gap-4 text-gray-700 leading-relaxed">
                {!! $productDetail->description !!}
            </div>
        </div>
    </section>



    <!-- Amazing Additions -->
    <section class="pb-[60px]">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <h2 class="md:text-[28px] font-bold text-left mb-10  text-purple">Amazing Additions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-y-0 gap-x-8 lg:gap-8">

                @foreach ($productDetail->relatedProducts as $relatedProduct)
                    <div class="py-[20px] lg:py-[30px] rounded-lg text-center">
                        <div class="transition-all duration-300 ease-in-out hover:text-black">
                            <div
                                class="lg:h-80 w-full flex items-center justify-center relative rounded-lg overflow-hidden group transition-all duration-500">
                                <div
                                    class="w-full h-full flex items-center justify-center bg-white transition-all duration-500 rounded-lg">
                                    <img src="{{ $relatedProduct->image_url }}" alt="{{ $relatedProduct->product_name }}"
                                        class="w-[90%] h-full object-contain transition-all duration-500 ease-in-out group-hover:scale-105 "
                                        loading="lazy" />
                                </div>
                            </div>

                            <div>
                                <h3 class="mt-4 font-bold text-black md:text-xl p-2 bg-gray-100 mb-4">
                                    {{ $relatedProduct->product_name }}
                                </h3>
                                @if ($relatedProduct->product_type == 'Rental')
                                    <div class="grid grid-cols-2 gap-2">
                                        <!-- Repeat this button block for each rental period -->
                                        @foreach (['daily', 'weekend', 'weekly', 'monthly'] as $type)
                                            <a href="{{ route('front.products.details', ['slug' => $relatedProduct->slug, 'productVariant' => $type]) }}"
                                                class="relative border-0 bg-yellow-400 text-black font-medium flex flex-col items-center px-2 rounded-xl hover:bg-yellow-500 overflow-hidden min-h-[40]">
                                                {{-- @if ($relatedProduct->isRentalOnSale($type))
                                                    <span
                                                        class="absolute left-[-25px] top-2 w-[100px] h-6 bg-white text-yellow-400 font-bold text-xs flex items-center justify-center"
                                                        style="transform: rotate(-50deg); z-index: 20; box-shadow: 0 4px 6px rgba(0,0,0,0.10); letter-spacing: 0.5px;">
                                                        Sale
                                                    </span>
                                                @endif --}}
                                                <span class="z-30">
                                                    {{ ucfirst($type) }}
                                                    @if ($relatedProduct->isRentalOnSale($type))
                                                        - <span class="font-bold bg-white text-yellow-400 text-xs me-2 px-2.5 py-0.5 rounded-full dark:bg-white dark:text-yellow-600">Sale</span>
                                                    @endif
                                                </span>
                                                <span>
                                                    <span class="font-bold text-black">
                                                        {{ App\Helpers\CustomHelper::formatCurrency($relatedProduct->getRentalPrice($type)) }}
                                                    </span>
                                                    @if ($relatedProduct->isRentalOnSale($type))
                                                        <span
                                                            class="line-through text-gray-500 text-base ml-1 font-normal italic">
                                                            {{ App\Helpers\CustomHelper::formatCurrency($relatedProduct->getRentalPrice($type, false)) }}
                                                        </span>
                                                    @endif
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <!-- Retail Product (centered button, no inline styles) -->
                                    <div class="grid grid-cols-2 gap-2">
                                        <a href="{{ route('front.products.details', ['slug' => $relatedProduct->slug, 'productVariant' => 'retail']) }}"
                                            class="relative border-0 bg-yellow-400 text-black font-medium flex flex-col items-center px-2 rounded-xl hover:bg-yellow-500 overflow-hidden min-h-[40]">
                                            {{-- @if ($relatedProduct->isRetailOnSale())
                                                <span
                                                    class="absolute left-[-25px] top-2 w-[100px] h-6 bg-white text-yellow-400 font-bold text-xs flex items-center justify-center"
                                                    style="transform: rotate(-50deg); z-index: 20; box-shadow: 0 4px 6px rgba(0,0,0,0.10); letter-spacing: 0.5px;">
                                                    Sale
                                                </span>
                                            @endif --}}
                                            <span class="z-30">
                                                {{ ucfirst('retail') }}
                                                @if ($relatedProduct->isRetailOnSale())
                                                    - <span class="font-bold bg-white text-yellow-400 text-xs me-2 px-2.5 py-0.5 rounded-full dark:bg-white dark:text-yellow-600">Sale</span>
                                                @endif
                                            </span>
                                            <span>
                                                <span class="font-bold text-lg text-black">
                                                    {{ App\Helpers\CustomHelper::formatCurrency($relatedProduct->getRetailPrice(false)) }}
                                                </span>
                                                @if ($relatedProduct->isRetailOnSale())
                                                    <span
                                                        class="line-through text-gray-500 text-base ml-1 font-normal italic">
                                                        {{ App\Helpers\CustomHelper::formatCurrency($relatedProduct->getRetailPrice(false)) }}
                                                    </span>
                                                @endif
                                            </span>
                                        </a>
                                    </div>
                                @endif
                                <h6 class="text-[12px] mt-2 text-black text-center ">Select an Option to Learn More or
                                    Reserve Today</h6>
                            </div>

                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </section>


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
@endsection

@push('js')
    <script>
        window.productPageContext = {
            formattedStandardFeeX2: "{{ App\Helpers\CustomHelper::formatCurrency(($productDetail->standard_delivery_fee ?? 0) * 2) }}",
            formattedStandardFee: "{{ App\Helpers\CustomHelper::formatCurrency($productDetail->standard_delivery_fee ?? 0) }}",
            formattedExtendedFeeX2: "{{ App\Helpers\CustomHelper::formatCurrency(($productDetail->extended_delivery_fee ?? 0) * 2) }}",
            formattedExtendedFee: "{{ App\Helpers\CustomHelper::formatCurrency($productDetail->extended_delivery_fee ?? 0) }}",
            distanceUnit: "{{ $productSettings['distance_unit'] ?? '' }}",
            standardDeliveryRange: "{{ $productSettings['standard_delivery_range'] ?? '' }}",
            extendedDeliveryRange: "{{ $productSettings['extended_delivery_range'] ?? '' }}",
            productUniqueId: "{{ $productDetail->unique_id ?? '' }}",
            productType: "{{ $productDetail->product_type ?? '' }}",
            productVariant: "{{ $productVariant }}",
            cartSaveUrl: "{{ route('front.cart.save') }}",
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        };
    </script>
    @vite('resources/front/assets/js/products/product-details-page.js')
@endpush
