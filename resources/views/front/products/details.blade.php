@extends('front.layouts.app')

@section('title', $title)

@section('content')

    <!-- Page Title Section -->
    <section class="md:mx-[30px] bg-gray-100">
        <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto ">
            <div class="pt-[100px] pb-[20px] ">
                <div class="w-full">
                    <div class="flex flex-col lg:flex-row justify-between items-center ">
                        <h1 class="text-4xl font-bold">
                            {{ $productDetail->product_name }}</h1>
                        <ul
                            class="border-yellow-400 px-[20px] py-4 max-w-full mt-4 lg:mt-0 text-sm font-medium items-center inline-flex gap-3 relative border-2">
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
                                    <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
                                        <a href="{{ route('front.categories.index', $child->slug) }}">
                                            {{ $child->title }}
                                        </a>
                                    </li>
                                @endforeach
                            @endif
                            <li class="tracking-[0] whitespace-nowrap">
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
                            class="absolute top-3 left-3 z-10 bg-yellow-400 text-gray-800 text-xs font-medium rounded px-2 py-1 shadow">
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
                    <div class="bg-gray-100 mb-4 flex flex-col lg:flex-row justify-between items-center pl-2">
                        <div>
                            <h3 class="font-bold text-2xl">{{ $productDetail->product_name }}
                            </h3>
                            <p
                                class="before:content-['('] before:text-gray-500 after:content-[')'] after:text-gray-500 text-green-600 ">
                                In Stock</p>
                        </div>
                        <div class="bg-yellow-400 text-xl h-[50px] lg:h-[70px] px-6 flex items-center font-medium">
                            <h4>
                                @if ($productDetail->product_type == 'Rental')
                                    <span>
                                        <span class="font-bold text-3xl">
                                            {{ App\Helpers\CustomHelper::formatCurrency($productDetail->getRentalPrice($productType)) }}
                                        </span>
                                        @if ($productDetail->isRentalOnSale($productType))
                                            <span class="line-through text-gray-500 text-base ml-1 font-normal italic">
                                                {{ App\Helpers\CustomHelper::formatCurrency($productDetail->getRentalPrice($productType, false)) }}
                                            </span>
                                        @endif
                                        <span class="font-normal">/ {{ $productType }}</span>
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
                        <div class="flex items-center space-x-4">
                            <!-- Calendar icon button -->
                            <a href="javascript:void(0);" id="openDatePicker">
                                <img src="{{ asset('storage/front/images/calendar_icon.png') }}" alt="Calendar"
                                    class="w-[52px]">
                            </a>
                            <input type="text" name="schedule_start_date" id="scheduleStartDateInput"
                                data-format="{{ config('app.date.js_date_format') }}"
                                data-min-date="{{ now()->format('Y-m-d') }}" class="sr-only" readonly
                                placeholder="Select Start Date" />


                            <!-- Show selected date here -->
                            <span id="selectedDateText" class="text-gray-700 text-base">Select Start Date</span>
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
                                        class="block w-100 px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none appearance-none">
                                        <option value="">Select Delivery Options</option>
                                        <option data-id="1" value="Delivery + Pickup">
                                            Delivery + Pick Up (To/From My Job Site)
                                            [<span id="DeliveryPickupPrice"></span>]
                                        </option>
                                        <option data-id="2" value="Delivery + Return">
                                            Delivery but I'll Return to Store
                                            [<span id="DeliveryReturnPrice"></span>]
                                        </option>
                                        <option data-id="3" value="Pickup + Return">
                                            I'll Pick Up In-Store but Need Return Service Pick Up
                                            [<span id="PickupReturnPrice"></span>]
                                        </option>
                                    </select>
                                </div>

                                <!-- Custom Option Text -->
                                <div id="customServiceOption" class="hidden w-[80%] border-[5px] border-sky-400 p-2 mt-2">
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
                                class="block w-100 px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none appearance-none">
                                <option disabled selected value="">Select Store Location</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}">
                                        {{ $store->address }}, {{ $store->city }}, {{ $store->state->abbreviation }} ,
                                        {{ $store->zip_code ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($productDetail->hasOptions())
                        <div id="optionDiv" class="inline-grid">
                            <h4 class="font-semibold text-2xl mb-1">Options</h4>
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

                            @if ($productDetail->rental_fuel_gallons !== null)
                                <!-- Prepaid Fuel Gallons -->
                                <label class="inline-flex items-center space-x-2 mt-1 w-fit">
                                    <input type="checkbox" class="form-checkbox h-4 w-4" checked id="fuelGallonsCheckbox"
                                        data-charged="1 Time Max" data-name="rental_fuel_gallons"
                                        data-keep="{{ $productSettings['fuel_gallons_approve_label'] }}"
                                        data-discard="{{ $productSettings['fuel_gallons_decline_label'] }}"
                                        data-message="{{ $productSettings['fuel_gallons_info'] }}" />
                                    <span>Fuel Gallons <span class="font-medium">+
                                            {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_fuel_gallons) }}</span></span>
                                </label>
                            @endif

                            @if ($productDetail->rental_def_gallons !== null)
                                <!-- DEF Gallons -->
                                <label class="inline-flex items-center space-x-2 mt-1 w-fit">
                                    <input type="checkbox" class="form-checkbox h-4 w-4" checked id="defGallonsCheckbox"
                                        data-charged="1 Time Max" data-name="rental_def_gallons"
                                        data-keep="{{ $productSettings['def_gallons_approve_label'] }}"
                                        data-discard="{{ $productSettings['def_gallons_decline_label'] }}"
                                        data-message="{{ $productSettings['def_gallons_info'] }}" />
                                    <span>DEF Gallons <span class="font-medium">+
                                            {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_def_gallons) }}</span></span>
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
                                        $damageWaiverPrice = match ($productType) {
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
                                                        $price = match ($productType) {
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
                            <div class="lg:h-80 w-full group relative">
                                <img src="{{ $relatedProduct->image_url }}" alt="{{ $relatedProduct->product_name }}"
                                    class="mx-auto h-full lg:w-full w-[90%] object-contain opacity-100 group-hover:opacity-0 transition-opacity duration-1000 ease-in-out" />
                            </div>

                            <div>
                                <h3 class="mt-4 font-bold text-black md:text-xl p-2 bg-gray-100 mb-4">
                                    {{ $relatedProduct->product_name }}
                                </h3>
                                @if ($relatedProduct->product_type == 'Rental')
                                    <div class="grid grid-cols-2 gap-2">
                                        <!-- Repeat this button block for each rental period -->
                                        @foreach (['daily', 'weekend', 'weekly', 'monthly'] as $type)
                                            <a href="{{ route('front.products.details', ['categorySlug' => $category->slug, 'slug' => $relatedProduct->slug, 'productType' => $type]) }}"
                                                class="relative border-0 bg-yellow-400 text-black font-medium flex flex-col items-center px-2 rounded-xl hover:bg-yellow-500 overflow-hidden min-h-[40]">
                                                @if ($relatedProduct->isRentalOnSale($type))
                                                    <span
                                                        class="absolute left-[-25px] top-2 w-[100px] h-6 bg-white text-yellow-400 font-bold text-xs flex items-center justify-center"
                                                        style="transform: rotate(-50deg); z-index: 20; box-shadow: 0 4px 6px rgba(0,0,0,0.10); letter-spacing: 0.5px;">
                                                        Sale
                                                    </span>
                                                @endif
                                                <span class="z-30">{{ ucfirst($type) }}</span>
                                                <span>{{ App\Helpers\CustomHelper::formatCurrency($relatedProduct->getRentalPrice($type)) }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <!-- Retail Product (centered button, no inline styles) -->
                                    <div class="grid grid-cols-2 gap-2">
                                        <a href="{{ route('front.products.details', ['categorySlug' => $category->slug, 'slug' => $relatedProduct->slug, 'productType' => 'retail']) }}"
                                            class="relative border-0 bg-yellow-400 text-black font-medium flex flex-col items-center px-2 rounded-xl hover:bg-yellow-500 overflow-hidden min-h-[40]">
                                            @if ($relatedProduct->isRetailOnSale())
                                                <span
                                                    class="absolute left-[-25px] top-2 w-[100px] h-6 bg-white text-yellow-400 font-bold text-xs flex items-center justify-center"
                                                    style="transform: rotate(-50deg); z-index: 20; box-shadow: 0 4px 6px rgba(0,0,0,0.10); letter-spacing: 0.5px;">
                                                    Sale
                                                </span>
                                            @endif
                                            <span class="z-30">Retail</span>
                                            <span>{{ App\Helpers\CustomHelper::formatCurrency($relatedProduct->getRetailPrice()) }}</span>
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
        // <!-- ===========================================
        //         Section: Set Delivery Fee Constants
        //         Purpose: JS-side variables for delivery fee calculations
        // ========================================== -->

        document.addEventListener('DOMContentLoaded', function() {
            const STANDARD_DELIVERY_FEE = {{ $productDetail->standard_delivery_fee ?? 0 }};
            const EXTENDED_DELIVERY_FEE = {{ $productDetail->extended_delivery_fee ?? 0 }};
            const FORMATTED_STANDARD_DELIVERY_FEE =
                "{{ App\Helpers\CustomHelper::formatCurrency($productDetail->standard_delivery_fee ?? 0) }}";
            const FORMATTED_EXTENDED_DELIVERY_FEE =
                "{{ App\Helpers\CustomHelper::formatCurrency($productDetail->extended_delivery_fee ?? 0) }}";
            const FORMATTED_STANDARD_DELIVERY_FEE_X2 =
                "{{ App\Helpers\CustomHelper::formatCurrency(($productDetail->standard_delivery_fee ?? 0) * 2) }}";
            const FORMATTED_EXTENDED_DELIVERY_FEE_X2 =
                "{{ App\Helpers\CustomHelper::formatCurrency(($productDetail->extended_delivery_fee ?? 0) * 2) }}";


            function updateDeliveryPrices(type) {
                const dropdown = document.getElementById('deliveryOptionsDropdown');
                const customBox = document.getElementById('customServiceOption');
                const title = document.getElementById('distanceTypeTitle');

                // Clear price spans
                ['DeliveryPickupPrice', 'DeliveryReturnPrice', 'PickupReturnPrice'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.innerText = '';
                });

                let radio = document.querySelector('input[name="distance_type"]:checked');
                if (radio && radio.dataset.text) {
                    title.textContent = `(${radio.dataset.text})`;
                } else {
                    title.textContent = '';
                }

                if (type === 'Standard' || type === 'Extended') {
                    dropdown.classList.remove('hidden');
                    customBox.classList.add('hidden');

                    if (type === 'Standard') {
                        document.getElementById('DeliveryPickupPrice').innerText =
                            FORMATTED_STANDARD_DELIVERY_FEE_X2;
                        document.getElementById('DeliveryReturnPrice').innerText = FORMATTED_STANDARD_DELIVERY_FEE;
                        document.getElementById('PickupReturnPrice').innerText = FORMATTED_STANDARD_DELIVERY_FEE;
                    } else {
                        document.getElementById('DeliveryPickupPrice').innerText =
                            FORMATTED_EXTENDED_DELIVERY_FEE_X2;
                        document.getElementById('DeliveryReturnPrice').innerText = FORMATTED_EXTENDED_DELIVERY_FEE;
                        document.getElementById('PickupReturnPrice').innerText = FORMATTED_EXTENDED_DELIVERY_FEE;
                    }
                } else {
                    dropdown.classList.add('hidden');
                    customBox.classList.remove('hidden');
                }
            }

            function toggleServiceMethod(selected) {
                const deliveryDiv = document.getElementById('deliveryDiv');
                const storeDiv = document.getElementById('storeDiv');
                if (selected === 'Delivery') {
                    if (deliveryDiv) deliveryDiv.classList.remove('hidden');
                    if (storeDiv) storeDiv.classList.add('hidden');
                    // Show delivery options for default checked distance type
                    let checkedRadio = document.querySelector('input[name="distance_type"]:checked');
                    updateDeliveryPrices(checkedRadio ? checkedRadio.value : 'Standard');
                } else if (selected === 'In Store Pickup') {
                    if (deliveryDiv) deliveryDiv.classList.add('hidden');
                    if (storeDiv) storeDiv.classList.remove('hidden');
                }
            }

            // Initial state for service_method
            let checkedService = document.querySelector('input[name="service_method"]:checked');
            toggleServiceMethod(checkedService ? checkedService.value : '');

            // Listen for service_method changes
            document.body.addEventListener('change', function(event) {
                if (event.target.name === 'service_method') {
                    toggleServiceMethod(event.target.value);
                }
                if (event.target.name === 'distance_type') {
                    updateDeliveryPrices(event.target.value);
                }
            });

            // Listen for delivery option changes
            const deliveryOptionSelect = document.getElementById('deliveryOptionSelect');
            if (deliveryOptionSelect) {
                deliveryOptionSelect.addEventListener('change', function(event) {
                    const value = event.target.value;
                    const storeDiv = document.getElementById('storeDiv');

                    // Show storeDiv for "Delivery + Return" and "Pickup + Return" (the 2nd and 3rd options)
                    if (value === "Delivery + Return" || value === "Pickup + Return") {
                        storeDiv.classList.remove('hidden');
                    } else {
                        storeDiv.classList.add('hidden');
                    }
                });
            }

            const scheduleStartDateInput = document.getElementById('scheduleStartDateInput');
            const openDatePicker = document.getElementById('openDatePicker');
            const selectedDateText = document.getElementById('selectedDateText');
            if (!scheduleStartDateInput || !openDatePicker || !selectedDateText) return;

            // *** Ensure the button does NOT have focus ***
            openDatePicker.blur();

            // Helper to track last input type and key
            let lastInputType = null;
            let lastKeyPressed = null;

            document.addEventListener('keydown', function(e) {
                lastInputType = 'keyboard';
                lastKeyPressed = e.key;
            });

            document.addEventListener('mousedown', function() {
                lastInputType = 'mouse';
                lastKeyPressed = null;
            });

            // Init Air Datepicker (no manual show() needed!)
            const picker = new window.AirDatepicker(scheduleStartDateInput, {
                locale: window.airDatepickerLocaleEn,
                timepicker: false,
                dateFormat: scheduleStartDateInput.dataset.format || 'yyyy-MM-dd',
                minDate: scheduleStartDateInput.dataset.minDate ? new Date(scheduleStartDateInput.dataset
                    .minDate) : false,
                autoClose: false,
                keyboardNav: true,
                onSelect({
                    date,
                    formattedDate,
                    datepicker
                }) {
                    if (date) {
                        selectedDateText.textContent = formattedDate;
                        scheduleStartDateInput.value = formattedDate;

                        // Autoclose if selected by mouse, or Enter key via keyboard
                        if (lastInputType === 'mouse' || lastKeyPressed === 'Enter') {
                            datepicker.hide();
                        }

                        lastInputType = null;
                        lastKeyPressed = null;
                    } else {
                        selectedDateText.textContent = 'Select Start Date';
                        scheduleStartDateInput.value = '';
                    }
                }
            });

            openDatePicker.addEventListener('click', function(e) {
                e.preventDefault();
                picker.show();
                document.getElementById('dateError').textContent = "";
            });


            // Update label if value exists on load
            if (scheduleStartDateInput.value) {
                selectedDateText.textContent = scheduleStartDateInput.value;
            }

            // <!-- ===========================================
            //     Section: Quantity Increment/Decrement
            //     Purpose: Handles + and - button logic for qty input
            //     ========================================== -->

            window.changeQty = function changeQty(delta) {
                const input = document.getElementById("qty");
                let current = parseInt(input.value) || 1;
                let newValue = current + delta;
                if (newValue < parseInt(input.min)) newValue = parseInt(input.min);
                if (newValue > parseInt(input.max)) newValue = parseInt(input.max);
                input.value = newValue;
            };


            // <!-- ===========================================
            //  Section: Option/Addon Checkbox Modal Confirmation
            //  Purpose: Prevents accidental unchecking of important add-ons,
            //  displays a confirmation modal with dynamic messaging.
            // ========================================== -->

            const optionGrid = document.getElementById('optionDiv');
            const modal = document.getElementById("modalBackdropClean");
            const modalMessage = document.getElementById("modalMessage");
            const modalRejectBtn = document.getElementById("modalRejectBtn");
            const modalAcceptBtn = document.getElementById("modalAcceptBtn");
            let currentCheckbox = null;

            if (!optionGrid) return;

            // Handle all option checkboxes with event delegation
            optionGrid.addEventListener("click", function(e) {
                const checkbox = e.target.closest('input[type="checkbox"].form-checkbox');
                if (!checkbox) return;

                // Only if trying to uncheck
                if (!checkbox.checked) {
                    const message = (checkbox.dataset.message || "").trim();

                    // If no message, allow normal uncheck
                    if (!message) return;

                    // Prevent the uncheck
                    e.preventDefault();
                    checkbox.checked = true;

                    // Set modal data
                    modalMessage.innerHTML = message;
                    modalRejectBtn.textContent = checkbox.dataset.discard ||
                        "No Thanks, I'll Take The Risk";
                    modalAcceptBtn.textContent = checkbox.dataset.keep || "Keep Option";
                    currentCheckbox = checkbox;

                    modal.classList.remove("hidden");
                }
            });

            // Modal handlers (use a single function to DRY up close logic)
            function closeModal() {
                modal.classList.add("hidden");
                currentCheckbox = null;
            }

            modalAcceptBtn.addEventListener("click", closeModal);

            modalRejectBtn.addEventListener("click", function() {
                if (currentCheckbox) currentCheckbox.checked = false;
                closeModal();
            });

            // Close modal on backdrop click or ESC key
            modal.addEventListener("click", function(e) {
                if (e.target === modal) closeModal();
            });
            document.addEventListener("keydown", function(e) {
                if (e.key === "Escape") closeModal();
            });

            // add to cart functionality
            const addToCartBtn = document.getElementById('addToCart');
            if (!addToCartBtn) return;

            addToCartBtn.addEventListener('click', function() {
                const loader = addToCartBtn.querySelector('.loader-gif');
                if (loader) loader.classList.remove('hidden');
                addToCartBtn.disabled = true;
                addToCartBtn.classList.add('opacity-60', 'cursor-not-allowed');

                // 1. Gather input (no price/totals)
                const qty = parseInt(document.getElementById('qty').value) || 1;
                const scheduleDate = document.getElementById('scheduleStartDateInput').value;
                // Show error message inline
                const dateError = document.getElementById('dateError');

                // --------- ADD THIS CHECK ----------
                if (!scheduleDate) {
                    if (loader) loader.classList.add('hidden');
                    addToCartBtn.disabled = false;
                    addToCartBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                    if (dateError) {
                        dateError.textContent = 'Please select a date before adding to cart.';
                        dateError.classList.remove('hidden');
                    }
                    //document.getElementById('scheduleStartDateInput').focus();
                    return; // Stop the rest of the function
                }
                // -----------------------------------

                const serviceMethod = document.querySelector('input[name="service_method"]:checked')
                    ?.value || '';
                let distanceType = '';
                if (serviceMethod === 'Delivery') {
                    distanceType = document.querySelector('input[name="distance_type"]:checked')?.value ||
                        '';
                }
                const serviceOption = document.getElementById('deliveryOptionSelect')?.value || '';
                const storeId = document.getElementById('storeSelect')?.value || '';
                let distanceRange = '';
                let unit = "{{ $productSettings['distance_unit'] ?? '' }}";
                if (distanceType === 'Standard') {
                    distanceRange = "{{ $productSettings['standard_delivery_range'] ?? '' }} " + unit;
                } else if (distanceType === 'Extended') {
                    distanceRange = "{{ $productSettings['extended_delivery_range'] ?? '' }} " + unit;
                }

                const productOptionItems = [];
                const productRentalItems = [];

                document.querySelectorAll('#optionDiv input[type="checkbox"].form-checkbox:checked')
                    .forEach(checkbox => {
                        if (checkbox.dataset.unique_id) {
                            // Real option from the database
                            productOptionItems.push({
                                unique_id: checkbox.dataset.unique_id,
                                // Optionally add more data as needed
                            });
                        } else {
                            // UI-only flags (not real options)
                            productRentalItems.push(checkbox.dataset.name);
                        }
                    });

                // Send minimal info only
                const payload = {
                    product_unique_id: "{{ $productDetail->unique_id ?? '' }}",
                    product_type: "{{ $productDetail->product_type ?? '' }}",
                    product_variant: "{{ $productType }}",
                    quantity: qty,
                    schedule_start_date: scheduleDate,
                    service_method: serviceMethod,
                    distance_type: distanceType,
                    distance_range: distanceRange,
                    service_option: serviceOption,
                    store_id: storeId,
                    product_option_items: productOptionItems,
                    product_rental_items: productRentalItems
                };

                // 2. Send to server (update with your route!)
                fetch('{{ route('front.cart.save') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(async response => {
                        if (!response.ok) {
                            // Try to parse error JSON if available
                            const errorData = await response.json().catch(() => null);
                            throw errorData || {
                                message: 'An unknown error occurred.'
                            };
                        }
                        return response.json();
                    })
                    .then(resp => {
                        if (resp.success && resp.cart_items) {
                            resp.cart_items.cart_data.forEach(item => CartStorage.addOrUpdateItem(item));

                            document.querySelectorAll('.toggleCart span').forEach(el =>
                                el.textContent = CartStorage.getTotalQuantity()
                            );
                            document.querySelector('.toggleCart')?.click();
                        } else {
                            notyf.error(resp.message || 'Failed to add to cart.');
                        }
                    })
                    .catch(err => {
                        if (err && err.errors) {
                            Object.values(err.errors).forEach(messages => {
                                messages.forEach(msg => showStickyError(msg));
                            });
                        } else {
                            showStickyError(err.message ||
                                'Could not connect to server. Please try again.');
                        }
                    })
                    .finally(() => {
                        if (loader) loader.classList.add('hidden');
                        addToCartBtn.disabled = false;
                        addToCartBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                    });

            });

        });
    </script>
@endpush
