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
                            <a href="javascript:void(0);" id="openDatePicker" >
                                <img src="{{ asset('storage/front/images/calendar_icon.png') }}" alt="Calendar"
                                    class="w-[52px]">
                            </a>
                            <input type="text" name="schedule_start_date" id="scheduleStartDateInput"
                                data-format="{{ config('app.date.js_date_format') }}"
                                data-min-date="{{ now()->format('Y-m-d') }}" class="sr-only" readonly
                                placeholder="Select Start Date" />

                            <!-- Optional: error message -->
                            <div id="dateError" class="text-red-600 text-sm hidden mt-1"></div>

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
                    </div>

                    @if ($productDetail->in_store_pickup == 'Yes')
                        <div class="space-y-2 mt-4">
                            <label class="inline-flex items-center space-x-2">
                                <input type="radio" name="service_method" value="In Store Pickup" class="form-radio" />
                                <span>In Store Pickup / Return</span>
                            </label>
                        </div>
                    @endif
                    @if ($productDetail->delivery_and_pickup == 'Yes')
                        <div class="space-y-2">
                            <label class="inline-flex items-center space-x-2">
                                <input type="radio" name="service_method" value="Delivery" class="form-radio" />
                                <span>Delivery - Full Turnkey Service</span>
                            </label>
                        </div>
                        <div id="deliveryDiv" class="mt-4 mb-5 hidden">
                            <div class="">
                                <span>Up To: </span>
                                @if ($productDetail->standard_delivery_fee !== null)
                                    <label class="inline-flex items-center space-x-2">
                                        <input type="radio" name="distance_type" value="Standard" class="sr-only peer"
                                            checked onChange="toggleDeliveryOption(this)" />
                                        <div
                                            class="w-[16px] h-[16px] rounded-full border-2 border-yellow-400 peer-checked:border-yellow-500 peer-checked:bg-yellow-500 flex justify-center items-center">
                                            <div class="w-2 h-2 rounded-full border-white bg-white z-1"></div>
                                        </div>
                                        <span>{{ $standardDeliveryRange . ' ' . $distanceUnit }} </span>
                                    </label>
                                @endif

                                @if ($productDetail->extended_delivery_fee !== null)
                                    <label class="inline-flex items-center space-x-2">
                                        <input type="radio" name="distance_type" value="Standard" class="sr-only peer"
                                            onChange="toggleDeliveryOption(this)" />
                                        <div
                                            class="w-[16px] h-[16px] rounded-full border-2 border-yellow-400 peer-checked:border-yellow-500 peer-checked:bg-yellow-500 flex justify-center items-center">
                                            <div class="w-2 h-2 rounded-full border-white bg-white z-1"></div>
                                        </div>
                                        <span>{{ $extendedDeliveryRange . ' ' . $distanceUnit }} </span>
                                    </label>
                                @endif
                                <label class="inline-flex items-center space-x-2">
                                    <input type="radio" name="distance_type" value="Custom" class="sr-only peer"
                                        onChange="toggleDeliveryOption(this)" />
                                    <div
                                        class="w-[16px] h-[16px] rounded-full border-2 border-yellow-400 peer-checked:border-yellow-500 peer-checked:bg-yellow-500 flex justify-center items-center">
                                        <div class="w-2 h-2 rounded-full border-white bg-white z-1"></div>
                                    </div>
                                    <span>Custom</span>
                                </label>


                                <div id="dropdown_ext_delivery_fee" class="hidden w-full mt-5 mb-5">
                                    <label class="block font-medium mb-1">Choose Delivery Type
                                        ({{ $extendedDeliveryRange . ' ' . $distanceUnit }}):</label>
                                    <select id="deliveryOption_ext_delivery_fee"
                                        class="block w-100 px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none appearance-none">
                                        <option value="">Select Delivery Options</option>
                                        <option data-id="1" value="Delivery + Pickup">Delivery + Pick Up (To/From
                                            My Job Site)
                                            [{{ App\Helpers\CustomHelper::formatCurrency($productDetail->extended_delivery_fee * 2) }}]
                                        </option>
                                        <option data-id="2" value="Delivery + Return">Delivery but I'll Return to Store
                                            [{{ App\Helpers\CustomHelper::formatCurrency($productDetail->extended_delivery_fee) }}]
                                        </option>
                                        <option data-id="3" value="Pickup + Return">I'll Pick Up In-Store but Need
                                            Return
                                            Service Pick Up
                                            [{{ App\Helpers\CustomHelper::formatCurrency($productDetail->extended_delivery_fee) }}]
                                        </option>
                                    </select>
                                </div>

                                <!-- Custom Option Text -->
                                <div id="customServiceOption" class="hidden w-[80%] border-[5px] border-sky-400 p-2 mt-2">
                                    <h4 class="text-center font-bold">Please Call / Text for Custom Solutions</h4>
                                    <h5 class="text-center text-red font-bold py-2">(615) 815-6734</h5>
                                    <p class="italic">Please reserve the item now, using the closest delivery range, then
                                        call / text afterwards for a custom delivery solution. </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="w-full mt-5 mb-5 hidden">
                        <select id="storeLocation" name="store_id"
                            class="block w-100 px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none appearance-none">
                            <option disabled selected value="">Select Store Location</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}">
                                    {{ $store->address }}, {{ $store->city }}, {{ $store->state->abbreviation }} ,
                                    {{ $store->zip_code ?? '' }}
                                </option>
                            @endforeach
                        </select>
                        <div id="dateErrorAddress" class="text-red-600 text-sm mt-2 hidden ">
                            Please select a Store Location.
                        </div>
                    </div>

                    <div class="inline-grid">
                        <h4 class="font-semibold text-2xl mb-1">Options</h4>
                        @if ($productDetail->rental_prepaid_fuel !== null)
                            <!-- Prepaid Fuel -->
                            <label class="inline-flex items-center space-x-2 mt-1 w-fit">
                                <input type="checkbox" class="form-checkbox h-4 w-4" checked id="fuelCheckbox"
                                    data-charged="1 Time Max" data-keep="Keep Option"
                                    data-discard="No Thanks, I'll Take The Risk" data-id="fuelCheckbox"
                                    data-title="Keep Prepaid Fuel"
                                    data-message="I understand that the machine is delivered full of fuel and I’m responsible for returning it full of fuel. If returned without a full tank, I will be charged $8/gallon. If I take the 'Pre-Paid Fuel' option, I can just walk away from this obligation."
                                    onclick="handleCheckboxClick(this)" />
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
                                    data-keep="Keep Option" data-discard="No Thanks, I'll Take The Risk"
                                    data-charged="1 Time Max" data-id="cleanCheckbox" data-title="Keep Prepaid Cleaning"
                                    data-message="I understand I’ll be responsible for bringing the equipment back clean or be charged. This does not cover 'Extreme' cleaning, only standard."
                                    onclick="handleCheckboxClick(this)" />
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
                                    data-charged="1 Time Max" data-keep="Keep Option"
                                    data-discard="No Thanks, I'll Take The Risk" data-id="fuelGallonsCheckbox"
                                    data-title="Keep Prepaid Fuel Gallons"
                                    data-message="This includes a set number of fuel gallons in your rental package."
                                    onclick="handleCheckboxClick(this)" />
                                <span>Fuel Gallons <span class="font-medium">+
                                        {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_fuel_gallons) }}</span></span>
                            </label>
                        @endif

                        @if ($productDetail->rental_def_gallons !== null)
                            <!-- DEF Gallons -->
                            <label class="inline-flex items-center space-x-2 mt-1 w-fit">
                                <input type="checkbox" class="form-checkbox h-4 w-4" checked id="defGallonsCheckbox"
                                    data-charged="1 Time Max" data-keep="Keep Option"
                                    data-discard="No Thanks, I'll Take The Risk" data-id="defGallonsCheckbox"
                                    data-title="Keep Prepaid DEF Gallons"
                                    data-message="This includes a set number of DEF gallons in your rental package."
                                    onclick="handleCheckboxClick(this)" />
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
                                    data-charged="1 Time Max" data-id="damageCheckbox" data-keep="Keep Option"
                                    data-discard="No Thanks, I'll Take The Risk" data-title="Keep Damage Waiver"
                                    data-message="By removing the Damage Waiver, you agree to take full financial responsibility for any damage or repairs, or to provide business insurance."
                                    onclick="handleCheckboxClick(this)" />

                                @switch($productType)
                                    @case('daily')
                                        <span>Damage Waiver <span class="font-medium">+
                                                {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_damage_waiver_daily) }}
                                            </span></span>
                                    @break

                                    @case('weekend')
                                        <span>Damage Waiver <span class="font-medium">+
                                                {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_damage_waiver_weekend) }}
                                            </span></span>
                                    @break

                                    @case('weekly')
                                        <span>Damage Waiver <span class="font-medium">+
                                                {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_damage_waiver_weekly) }}
                                            </span></span>
                                    @break

                                    @case('monthly')
                                        <span>Damage Waiver <span class="font-medium">+
                                                {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_damage_waiver_monthly) }}
                                            </span></span>
                                    @break

                                    @default
                                        <span>-</span>
                                @endswitch
                            </label>
                        @endif


                        @foreach ($productDetail->options as $option)
                            @if ($option->items->isNotEmpty())
                                @foreach ($option->items as $item)
                                    <label class="inline-flex items-center space-x-2 mt-1 w-fit">
                                        <input type="checkbox" class="form-checkbox h-4 w-4"
                                            @if ($item->value == 'Checked') checked="checked" @endif
                                            id="options_{{ $item->unique_id }}" data-id="{{ $item->unique_id }}"
                                            data-keep="{{ $item->accept_label }}"
                                            data-discard="{{ $item->decline_label }}"
                                            data-message="{{ $item->comment }}" data-unique_id="{{ $item->unique_id }}"
                                            @if ($item->comment !== null) onclick="handleCheckboxClick(this)" @endif
                                            data-charged="{{ $item->charged ?? null }}" />
                                        <span>{{ $item->label }} <span class="font-medium">+
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

                                            </span></span>
                                    </label>
                                @endforeach
                            @endif
                        @endforeach

                    </div>
                    <div id="rentalbtn">
                        <button type="button" id="addToRentalBtn"
                            class="border-0 bg-yellow-400  font-medium flex px-6 py-3 mt-4 items-center leading-4 rounded-lg text-[14px] hover:bg-yellow-300  transition-all duration-500 ease-in-out"><i
                                class="fa-solid fa-bag-shopping text-[20px] mr-3"></i> Add to Rental
                            <img src="{{ asset('storage/front/images/loading.gif') }}" alt="loading"
                                class="loader-gif" /></button>
                    </div>
                    <!-- </form> -->
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
    <div id="modalBackdropClean" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
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
        const STANDARD_DELIVERY_FEE = {{ $productDetail->standard_delivery_fee ?? 0 }};
        const EXTENDED_DELIVERY_FEE = {{ $productDetail->extended_delivery_fee ?? 0 }};

        document.addEventListener('DOMContentLoaded', function() {
            const scheduleStartDateInput = document.getElementById('scheduleStartDateInput');
            const openDatePicker = document.getElementById('openDatePicker');
            const selectedDateText = document.getElementById('selectedDateText');
            if (!scheduleStartDateInput || !openDatePicker || !selectedDateText) return;

            // *** Ensure the button does NOT have focus ***
            openDatePicker.blur();

            // Init Air Datepicker (no manual show() needed!)
            const picker = new window.AirDatepicker(scheduleStartDateInput, {
                locale: window.airDatepickerLocaleEn,
                timepicker: false,
                dateFormat: scheduleStartDateInput.dataset.format || 'yyyy-MM-dd',
                minDate: scheduleStartDateInput.dataset.minDate ? new Date(scheduleStartDateInput.dataset
                    .minDate) : false,
                autoClose: false,
                onSelect({
                    date,
                    formattedDate
                }) {
                    if (date) {
                        selectedDateText.textContent = formattedDate;
                        scheduleStartDateInput.value = formattedDate;
                    } else {
                        selectedDateText.textContent = 'Select Start Date';
                        scheduleStartDateInput.value = '';
                    }
                }
            });

            openDatePicker.addEventListener('click', function(e) {
                e.preventDefault();
                picker.show();
            });


            // Update label if value exists on load
            if (scheduleStartDateInput.value) {
                selectedDateText.textContent = scheduleStartDateInput.value;
            }
        });

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

        // Store checkbox being interacted with
        let currentCheckbox = null;

        // Show modal if unchecking; populate with dynamic data attributes
        window.handleCheckboxClick = function(checkbox) {
            if (!checkbox.checked) {
                checkbox.checked = true;
                currentCheckbox = checkbox;

                const message = checkbox.dataset.message;
                const keep_title = checkbox.dataset.keep;
                const discard_title = checkbox.dataset.discard;

                $("#modalMessage").html(message);
                $("#modalRejectBtn").text(discard_title || "No Thanks, I'll Take The Risk");
                $("#modalAcceptBtn").text(keep_title || "Keep Option");

                document.getElementById("modalBackdropClean").classList.remove("hidden");
            }
        };

        // Close modal, keep option checked
        window.cancelUncheck = function() {
            if (currentCheckbox) {
                currentCheckbox.checked = true;
                document.getElementById("modalBackdropClean").classList.add("hidden");
                currentCheckbox = null;
            }
        };

        // Close modal, allow unchecking
        window.confirmUncheckModal = function() {
            if (currentCheckbox) {
                currentCheckbox.checked = false;
                document.getElementById("modalBackdropClean").classList.add("hidden");
                currentCheckbox = null;
            }
        };

        // Modal button events
        document.getElementById("modalAcceptBtn").onclick = cancelUncheck;
        document.getElementById("modalRejectBtn").onclick = confirmUncheckModal;
        document.getElementById("modalBackdropClean").addEventListener("click", function(e) {
            if (e.target === this) {
                cancelUncheck();
            }
        });
    </script>

    <!-- ===========================================
                                                            Section: Delivery/Pickup Option UI Toggling
                                                            Purpose: Switches between in-store and delivery forms, reveals relevant UI.
                                                            ========================================== -->
    <script>
        window.toggleDeliveryOption = function toggleDeliveryOption(radio) {
            const inStoreDiv = document.getElementById("inStoreDiv");
            const deliveryDiv = document.getElementById("deliveryDiv");
            const customdis = document.getElementById("customdis");
            const distance = document.getElementById("distance");
            const address = document.getElementById("address");
            const rentalbtn = document.getElementById("rentalbtn");

            const dropdown_std = document.getElementById("dropdown_std_delivery_fee");
            const dropdown_ext = document.getElementById("dropdown_ext_delivery_fee");

            if (radio.name === "option") {
                if (radio.value === "in-store") {
                    inStoreDiv.classList.remove("hidden");
                    deliveryDiv.classList.add("hidden");
                    address.classList.remove("hidden");
                    distance?.classList.remove("hidden");
                    rentalbtn?.classList.remove("hidden");
                    customdis.classList.add("hidden");
                } else if (radio.value === "delivery") {
                    inStoreDiv.classList.add("hidden");
                    deliveryDiv.classList.remove("hidden");
                    address.classList.add("hidden");
                    rentalbtn?.classList.remove("hidden");
                    const deliveryOption = document.querySelector('input[name="delivery-option"]:checked');
                    if (deliveryOption) {
                        toggleDeliveryOption(deliveryOption);
                    }
                }
            }

            if (radio.name === "delivery-option") {
                dropdown_std?.classList.add("hidden");
                dropdown_ext?.classList.add("hidden");
                customdis.classList.add("hidden");

                if (radio.value === "dis1") {
                    dropdown_std?.classList.remove("hidden");
                } else if (radio.value === "dis2") {
                    dropdown_ext?.classList.remove("hidden");
                } else if (radio.value === "discustom") {
                    customdis.classList.remove("hidden");
                    rentalbtn?.classList.add("hidden");
                }
                address.classList.add("hidden");
            }
        };

        // Handle dropdown change for delivery types (show/hide address)
        document.addEventListener('DOMContentLoaded', function() {
            const stdSelect = document.getElementById('deliveryOption_std_delivery_fee');
            const extSelect = document.getElementById('deliveryOption_ext_delivery_fee');
            const address = document.getElementById("address");
            const rentalbtn = document.getElementById("rentalbtn");

            function handleDeliveryTypeChange(event) {
                const selected = event.target.options[event.target.selectedIndex];
                const dataId = selected?.getAttribute("data-id");

                if (["Delivery", "Pickup"].includes(dataId)) {
                    address.classList.remove("hidden");
                } else {
                    address.classList.add("hidden");
                }
                rentalbtn?.classList.remove("hidden");
            }

            stdSelect?.addEventListener('change', handleDeliveryTypeChange);
            extSelect?.addEventListener('change', handleDeliveryTypeChange);
        });

        // Initialize delivery form on load
        window.onload = () => {
            // const mainSelected = document.querySelector('input[name="option"]:checked");
            //     if (mainSelected) {
            //         toggleDeliveryOption(mainSelected);
            //     }
        };
    </script>

    <!-- ===========================================
                                                                Section: Delivery Fee Calculation
                                                                Purpose: Update delivery fee and delivery_pickup JSON in hidden fields
                                                                Dependencies: jQuery
                                                        ========================================== -->
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            if (typeof window.jQuery !== 'undefined') {
                $(function() {
                    $('input[name="delivery-option"]').on('change', calculateDeliveryFee);
                    $('#deliveryOption_std_delivery_fee, #deliveryOption_ext_delivery_fee').on('change',
                        calculateDeliveryFee);

                    let distance_type = null;
                    let distance_range = null;

                    function calculateDeliveryFee() {
                        let deliveryFee = 0;
                        let delivery_pickup = [];
                        let deliveryDataId = null;

                        const deliveryOption = $('input[name="delivery-option"]:checked').val();

                        if (deliveryOption === 'dis1') {
                            const $selected = $('#deliveryOption_std_delivery_fee');
                            if ($selected.val()) {
                                deliveryFee = parseFloat($selected.val()) * STANDARD_DELIVERY_FEE;
                                const label = $selected.find('option:selected').data('id');
                                deliveryDataId = label;
                                distance_type = 'standard_delivery_fee';
                                distance_range = @json($standardDeliveryRange);
                            }
                        } else if (deliveryOption === 'dis2') {
                            const $selected = $('#deliveryOption_ext_delivery_fee');
                            if ($selected.val()) {
                                deliveryFee = parseFloat($selected.val()) * EXTENDED_DELIVERY_FEE;
                                const label = $selected.find('option:selected').data('id');
                                deliveryDataId = label;
                                distance_type = 'extended_delivery_fee';
                                distance_range = @json($extendedDeliveryRange);
                            }
                        }

                        if (deliveryDataId === 'Delivery+Pickup') {
                            delivery_pickup.push({
                                name: 'Delivery'
                            }, {
                                name: 'Pickup'
                            });
                        } else if (deliveryDataId) {
                            delivery_pickup.push({
                                name: deliveryDataId
                            });
                        }

                        $('#inputDeliveryFee').val(deliveryFee);
                        $('#inputDeliveryPickup').val(JSON.stringify(delivery_pickup));
                    }
                });
            }
        });
    </script>

    <!-- ===========================================
                                                                Section: Add To Rental Cart
                                                                Purpose: Main cart processing logic (validation, collect all options, store in localStorage)
                                                                Dependencies: jQuery
                                                        ========================================== -->
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            if (typeof window.jQuery !== 'undefined') {
                $(function() {
                    // Prevent native form submit (if present)
                    $('#rentalForm').on('submit', function(e) {
                        e.preventDefault();
                    });

                    // "Add to Rental" Button Click Logic
                    $('#addToRentalBtn').on('click', function() {
                        const $btn = $(this);
                        const $loader = $btn.find('.loader-gif');

                        const scheduleDate = $('#selectedDateText').text().trim();
                        if (scheduleDate === 'Select Start Date') {
                            $('#dateError').removeClass('hidden');
                            return;
                        } else {
                            $('#dateError').addClass('hidden');
                        }

                        try {
                            $loader.css('display', 'inline-block');
                            $btn.prop('disabled', true);

                            const storeLocation = $('#storeLocation').val();
                            const qty = parseInt($('#qty').val()) || 1;
                            const name = $('.productname').text().trim();
                            const image = $('.product-image').attr('src') || '';
                            const basePriceText = $('.bg-yellow-400 h4').text().trim().split('/')[0]
                                .trim();
                            const numericPrice = parseFloat(basePriceText.replace(/[^0-9.]/g, ''));

                            const service_method = $('input[name="option"]:checked').val();
                            let service_option = null;
                            if (service_method === 'delivery') {
                                const selectedStd = $('#deliveryOption_std_delivery_fee').val();
                                const selectedExt = $('#deliveryOption_ext_delivery_fee').val();

                                if (selectedStd) {
                                    service_option = $(
                                            '#deliveryOption_std_delivery_fee option:selected')
                                        .data('id');
                                } else if (selectedExt) {
                                    service_option = $(
                                            '#deliveryOption_ext_delivery_fee option:selected')
                                        .data('id');
                                }
                            }

                            const addons = [];
                            $('input[type="checkbox"]:checked').each(function() {
                                const label = $(this).closest('label');
                                const spans = label.find('span');
                                const addonName = spans[0]?.childNodes[0]?.nodeValue
                                    .trim() || '';
                                const price = parseFloat(spans[1]?.innerText.replace(
                                    /[^0-9.]/g, '')) || 0;
                                const charged = $(this).data('charged') || null;
                                const unique_id = $(this).data('unique_id') || null;
                                if (addonName || unique_id) {
                                    addons.push({
                                        name: addonName,
                                        price,
                                        charged,
                                        unique_id
                                    });
                                }
                            });

                            const delivery_pickup = [];
                            const rawPickup = $('#inputDeliveryPickup').val();
                            if (rawPickup) {
                                try {
                                    delivery_pickup.push(...JSON.parse(rawPickup));
                                } catch (e) {
                                    console.warn('Invalid pickup JSON', rawPickup);
                                }
                            }

                            const deliveryFee = parseFloat($('#inputDeliveryFee').val()) || 0;

                            let addonTotal = 0;
                            addons.forEach(addon => {
                                const price = parseFloat(addon.price) || 0;
                                const charged = (addon.charged || '').toLowerCase();
                                addonTotal += (charged === 'unlimited' ? price * qty :
                                    price);
                            });

                            const total_price = (numericPrice * qty) + addonTotal + deliveryFee;
                            const taxrate = @json($taxRate);
                            const tax_amount = +(total_price * taxrate);
                            const total_price_with_tax = +(total_price + tax_amount).toFixed(2);

                            const item = {
                                product_id: @json($productDetail->id),
                                product_unique_id: @json($productDetail->unique_id),
                                varient_type: @json($productDetail->product_type),
                                product_price_type: null,
                                product_sku: @json($productDetail->sku),
                                product_barcode: @json($productDetail->barcode),
                                name,
                                image,
                                product_type: @json($productType),
                                qty,
                                base_price: numericPrice,
                                product_slug: @json($productDetail->slug),
                                addons,
                                sechdule_start_date: scheduleDate,
                                schedule_end_date: null,
                                delivery_fee: deliveryFee,
                                store_id: storeLocation,
                                service_option: service_option,
                                service_method: service_method,
                                distance_type: distance_type,
                                distance_range: distance_range,
                                tax_rate: taxrate,
                                total_price: total_price,
                                tax_amount: tax_amount,
                                total_price_with_tax: total_price_with_tax,
                                delivery_pickup
                            };

                            const cart = JSON.parse(localStorage.getItem('rental_cart')) || [];
                            const existingIndex = cart.findIndex(i => i.product_id == item
                                .product_id);

                            if (existingIndex !== -1) {
                                cart[existingIndex].qty += item.qty;
                                cart[existingIndex] = {
                                    ...cart[existingIndex],
                                    ...item
                                };
                            } else {
                                cart.push(item);
                            }

                            localStorage.setItem('rental_cart', JSON.stringify(cart));

                            setTimeout(() => {
                                $loader.css('display', 'none');
                                $btn.prop('disabled', false);
                                document.querySelector(".toggleCart")?.click();
                            }, 1500);
                        } catch (error) {
                            console.error("Cart processing error:", error);
                            $loader.css('display', 'none');
                            $btn.prop('disabled', false);
                        }
                    });
                });
            }
        });
    </script>
@endpush
