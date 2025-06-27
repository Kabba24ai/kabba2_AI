@extends('front.layouts.app')

@section('title', $title)

@section('content')
    <!-- Page Title Section -->
    <section
        class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-[#fff] md:border-r-[30px] md:border-r-[#fff] bg-[#f9fafc]">
        <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-[30px] md:px-0">
            <div id="breadcrumbs" class="pt-[100px] pb-[20px] ">
                <div class="w-full">
                    <div class="flex flex-col lg:flex-row justify-between items-center ">
                        <h1 class="text-[28px] md:text-[34px] lg:text-[40px] tracking-[-2px] leading-[110%] font-bold">
                            {{ $productDetail->product_name }}</h1>
                        <ul
                            class="border-yellow-400 px-[20px] py-4 max-w-full mt-4 lg:mt-0 text-[14px] font-medium items-center inline-flex gap-3 relative border-2 border-[#fff]">
                            <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
                                <a href="{{ route('front.home.index') }}" class="opacity-25">Home</a>
                            </li>
                            @foreach ($productDetail->categories as $category)
                                <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
                                    <a href="{{ route('front.categories.index', $category->slug) }}"
                                        class="opacity-25">{{ $category->title }}</a>
                                </li>
                            @endforeach

                            <li>
                                <a href="javascript:void(0)"
                                    class="cursor-not-allowed">{{ $productDetail->product_name }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- prooduct details -->
    <section class="pb-[60px] pt-[60px]">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-8">
                <div class="rounded-lg text-center">
                    <div class="border-[1px] relative">
                        <div style="--swiper-navigation-color: #fff; --swiper-pagination-color: #fff"
                            class="swiper mainSwiper">
                            <div class="swiper-wrapper">

                                @foreach ($productDetail->mediaChildren as $mediaChildren)
                                    <div class="swiper-slide">
                                        <div class="lg:h-full w-full group relative overflow-hidden">
                                            <img src="{{ $mediaChildren->media->url }}" alt="boom-lift-v2"
                                                class="product-image mx-auto h-full w-full object-contain group-hover:scale-[1.045] transition-all duration-500 ease-in-out" />
                                        </div>
                                    </div>
                                @endforeach

                            </div>
                        </div>
                        <div class="bg-yellow-400 py-1 px-2 absolute z-10 top-3 left-3 text-[14px]">
                            {{ $productDetail->product_name }}
                        </div>
                    </div>
                    <div thumbsSlider="" class="swiper thumbSwiper">
                        <div class="swiper-wrapper">
                            @foreach ($productDetail->mediaChildren as $Children)
                                <div class="swiper-slide">
                                    <div class="h-[50px] float-left relative group overflow-hidden">
                                        <img src="{{ $Children->media->url }}"
                                            alt="{{ $Children->media->original_file_name }}"
                                            class="mx-auto h-full w-full object-contain {{ !$loop->first ? 'group-hover:scale-[1.045] transition-all duration-500 ease-in-out' : 'group-hover:scale-[1.045] transition-all duration-500 ease-in-out' }}" />
                                    </div>
                                </div>
                            @endforeach

                        </div>
                    </div>
                </div>
                <div>
             
                    <input type="hidden" name="delivery_fee" id="inputDeliveryFee">
                    <input type="hidden" name="delivery_pickup" id="inputDeliveryPickup">

                    <div
                        class="bg-[#f9fafc] mb-4 flex flex-col lg:flex-row justify-between items-center p-2 lg:p-0  md:pl-4">
                        <div>
                            <h3 class="font-bold text-[18px] lg:text-[22px] productname">{{ $productDetail->product_name }}</h3>
                            <p
                                class="before:content-['('] before:text-[#6f6f87] after:content-[')'] after:text-[#6f6f87] text-[#188753] text-[14px]">
                                In Stock</p>
                        </div>
                        <div
                            class="bg-yellow-400 mt-3 lg:mt-0 lg:text-[32px] text-[24px] h-[50px] lg:h-[70px] px-6 flex items-center font-medium">
                            <h4>
                                @switch($productType)
                                    @case('daily')
                                        {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_daily) }}
                                        <span class="text-[16px] font-normal">/ day</span>
                                    @break

                                    @case('weekend')
                                        {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_weekend) }}
                                        <span class="text-[16px] font-normal">/ week</span>
                                    @break

                                    @case('weekly')
                                        {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_weekly) }}
                                        <span class="text-[16px] font-normal">/ week</span>
                                    @break

                                    @case('monthly')
                                        {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_monthly) }}
                                        <span class="text-[16px] font-normal">/ month</span>
                                    @break
                                    @default
                                        <span>-</span>
                                @endswitch
                            </h4>
                        </div>

                    
                    </div>

                    <div>
                    <p> {!! $productDetail->short_description !!} </p>    
                </div>

                    <div id="distance" class="mt-5 mb-5 ">
                        <div class="flex items-center space-x-2">

                            <div class="relative">
                                <input type="text" id="dateInput" name="date" readonly class="hidden" />
                                <div class="flex items-center">
                                    <button id="openDatePicker" type="button" class="pr-2">
                                        <img src="{{ asset('storage/front/images/calendar_icon.png') }}" alt=""
                                            class="w-[52px]">
                                    </button>
                                    <div id="selectedDateText" class="mr-4">Select Start Date</div>
                                </div>

                                <div id="datePicker"
                                    class="absolute top-full -left-28 lg:left-0 mt-2 max-[380px]:w-[280px] w-[300px] bg-white border border-gray-300 rounded-md shadow-lg p-4 hidden z-10">
                                    <div class="flex justify-between items-center mb-4">
                                        <button id="prevMonth" class="p-1 rounded hover:bg-gray-200">&lt;</button>
                                        <div id="monthYear" class="font-semibold"></div>
                                        <button id="nextMonth" class="p-1 rounded hover:bg-gray-200">&gt;</button>
                                    </div>
                                    <div
                                        class="grid grid-cols-7 gap-1 text-center text-xs font-semibold text-gray-500 mb-2">
                                        <div>Sun</div>
                                        <div>Mon</div>
                                        <div>Tue</div>
                                        <div>Wed</div>
                                        <div>Thu</div>
                                        <div>Fri</div>
                                        <div>Sat</div>
                                    </div>
                                    <div id="daysGrid" class="grid grid-cols-7 gap-1 text-center"></div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button type="button"
                                    class="border-0 bg-yellow-400 rounded-full w-[30px] h-[30px] text-xl font-bold hover:bg-yellow-300  transition-all duration-500 ease-in-out"
                                    onclick="changeQty(-1)">−</button>

                                <input type="number" id="qty" name="qty" value="1" min="1"
                                    max="1000" class="w-16 text-center border border-gray-300 rounded" />

                                <button type="button"
                                    class="border-0 bg-yellow-400 rounded-full w-[30px] h-[30px] text-xl font-bold hover:bg-yellow-300  transition-all duration-500 ease-in-out"
                                    onclick="changeQty(1)">+</button>
                            </div>
                        </div>
                        <div id="dateError" class="text-red-600 text-sm hidden">Please select a start date.</div>
                    </div>
                    @if($productDetail->in_store_pickup=='Yes')
                    <div class="space-y-2 mt-4">
                        <label class="inline-flex items-center space-x-2">
                            <input type="radio" name="option" value="in-store" class="form-radio" checked
                                onchange="toggleDeliveryOption(this)" />
                            <span>In Store Pickup / Return</span>
                        </label>

                    </div>
                    @endif
                    @if($productDetail->delivery_and_pickup=='Yes')
                    <div class="space-y-2">
                        <label class="inline-flex items-center space-x-2">
                            <input type="radio" name="option" value="delivery" class="form-radio"
                                onchange="toggleDeliveryOption(this)" />
                            <span>Delivery - Full Turnkey Service</span>
                        </label>
                    </div>
                   @endif
                   @if($productDetail->in_store_pickup=='Yes')
                    <div id="inStoreDiv" class="mt-4">
                    </div>
                   @endif
					@if($productDetail->delivery_and_pickup=='Yes')
                    <div id="deliveryDiv" class="mt-4 mb-5 hidden">
                        <div class="">
                            <span>Up To: </span>
                            @if ($productDetail->standard_delivery_fee !== null)
                            <label class="inline-flex items-center space-x-2">
                                <input type="radio" name="delivery-option" value="dis1" class="sr-only peer" checked
                                    onchange="toggleDeliveryOption(this)" />
                                <div
                                    class="w-[16px] h-[16px] rounded-full border-2 border-yellow-400 peer-checked:border-yellow-500 peer-checked:bg-yellow-500 flex justify-center items-center">
                                    <div class="w-2 h-2 rounded-full border-white bg-white z-1"></div>
                                </div>
                                <span>{{$standard_delivery_range->setting_value }} mi</span>
                            </label>
                            @endif

                            @if ($productDetail->extended_delivery_fee !== null)
                            <label class="inline-flex items-center space-x-2">
                                <input type="radio" name="delivery-option" value="dis2" class="sr-only peer"
                                    onchange="toggleDeliveryOption(this)" />
                                <div
                                    class="w-[16px] h-[16px] rounded-full border-2 border-yellow-400 peer-checked:border-yellow-500 peer-checked:bg-yellow-500 flex justify-center items-center">
                                    <div class="w-2 h-2 rounded-full border-white bg-white z-1"></div>
                                </div>
                                <span>{{$extended_delivery_range->setting_value }} mi</span>
                            </label>
                            @endif
                            <label class="inline-flex items-center space-x-2">
                                <input type="radio" name="delivery-option" value="discustom" class="sr-only peer"
                                    onchange="toggleDeliveryOption(this)" />
                                <div
                                    class="w-[16px] h-[16px] rounded-full border-2 border-yellow-400 peer-checked:border-yellow-500 peer-checked:bg-yellow-500 flex justify-center items-center">
                                    <div class="w-2 h-2 rounded-full border-white bg-white z-1"></div>
                                </div>
                                <span>Custom</span>
                            </label>

                             @if ($productDetail->standard_delivery_fee !== null)
                                <!-- For standard_delivery_range mi -->
                                <div id="dropdown_std_delivery_fee" class="hidden  w-full mt-5 mb-5 ">
                                    <label class="block font-medium mb-1">Choose Delivery Type ({{$standard_delivery_range->setting_value }} mi):</label>
                                    <select id="deliveryOption_std_delivery_fee" class="block w-100 px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none appearance-none" >
                                        <option value="">Select Delivery Options</option>
                                        <option data-id="Delivery+Pickup" value="2">Delivery + Pick Up (To/From My Job Site) [{{ App\Helpers\CustomHelper::formatCurrency($productDetail->standard_delivery_fee * 2) }}]</option>
                                        <option data-id="Delivery" value="1">Delivery but I'll Return to Store [{{ App\Helpers\CustomHelper::formatCurrency($productDetail->standard_delivery_fee) }}]</option>
                                        <option data-id="Pickup" value="1">I'll Pick Up In-Store but Need Return Service Pick Up [{{ App\Helpers\CustomHelper::formatCurrency($productDetail->standard_delivery_fee) }}]</option>
                                    </select>
                                </div>
                            @endif

                            @if ($productDetail->extended_delivery_fee !== null)
                                <!-- For extended_delivery_fee mi -->
                                <div id="dropdown_ext_delivery_fee" class="hidden w-full mt-5 mb-5">
                                    <label class="block font-medium mb-1">Choose Delivery Type ({{$extended_delivery_range->setting_value }} mi):</label>
                                    <select id="deliveryOption_ext_delivery_fee" class="block w-100 px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none appearance-none" >
                                        <option value="">Select Delivery Options</option>
                                        <option data-id="Delivery+Pickup" value="2">Delivery + Pick Up (To/From My Job Site) [{{ App\Helpers\CustomHelper::formatCurrency($productDetail->extended_delivery_fee * 2) }}]</option>
                                        <option data-id="Delivery" value="1">Delivery but I'll Return to Store [{{ App\Helpers\CustomHelper::formatCurrency($productDetail->extended_delivery_fee) }}]</option>
                                        <option data-id="Pickup" value="1">I'll Pick Up In-Store but Need Return Service Pick Up [{{ App\Helpers\CustomHelper::formatCurrency($productDetail->extended_delivery_fee) }}]</option>
                                    </select>
                                </div>
                            @endif

                            <!-- Custom Option Text -->
                            <div id="customdis" class="hidden w-[80%] border-[5px] border-[#0391B4] p-2 mt-2">
                                <h4 class="text-center font-bold">Please Call / Text for Custom Solutions</h4>
                                <h5 class="text-center text-[#c40000] font-bold py-2">(615) 815-6734</h5>
                                <p class="italic">Please reserve the item now, using the closest delivery range, then
                                    call / text afterwards for a custom delivery solution. </p>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div id="address" class="w-full mt-5 mb-5 hidden">
                        <select id="storeLocation" name="store_location" 
                            class="block w-100 px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none appearance-none">
                            <option disabled selected value="">Select Store Location</option>
                            @foreach($stores as $store)
                                    <option value="{{ $store->id }}">
                                        {{ $store->address }}, {{ $store->city }}, {{ $store->state->abbreviation }} , {{ $store->zip_code ?? '' }}
                                    </option>
                                @endforeach
                        </select>
                    <div id="dateErroraddress" class="text-red-600 text-sm mt-2 hidden ">Please select a Store Location.</div>

                    </div>
                    <div class="inline-grid">

                        <h4 class="font-semibold text-[16px] mb-1">Options</h4>


                        @if($productDetail->rental_prepaid_fuel !== null)
                         <!-- Prepaid Fuel -->
                         <label class="inline-flex items-center space-x-2 mt-1 w-fit">
                            <input type="checkbox" class="form-checkbox h-4 w-4" checked
                                id="fuelCheckbox"
                                 data-charged="1 Time Max"
                                  data-keep="Keep Option"
                                data-discard="No Thanks, I'll Take The Risk"
                                data-id="fuelCheckbox"
                                data-title="Keep Prepaid Fuel"
                                data-message="I understand that the machine is delivered full of fuel and I’m responsible for returning it full of fuel. If returned without a full tank, I will be charged $8/gallon. If I take the 'Pre-Paid Fuel' option, I can just walk away from this obligation."
                                onclick="handleCheckboxClick(this)" />
                            <span>Prepaid Fuel <span class="font-medium">+ {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_prepaid_fuel) }} </span></span>
                        </label>
						@endif

                      

                        @if($productDetail->rental_prepaid_cleaning !== null)
                            <!-- Prepaid Cleaning -->
                            <label class="inline-flex items-center space-x-2 mt-1 w-fit">
                                <input type="checkbox" class="form-checkbox h-4 w-4" checked
                                    id="cleanCheckbox"
                                     data-keep="Keep Option"
                                data-discard="No Thanks, I'll Take The Risk"
                                     data-charged="1 Time Max"
                                    data-id="cleanCheckbox"
                                    data-title="Keep Prepaid Cleaning"
                                    data-message="I understand I’ll be responsible for bringing the equipment back clean or be charged. This does not cover 'Extreme' cleaning, only standard."
                                    onclick="handleCheckboxClick(this)" />
                                <span>Prepaid Cleaning <span class="font-medium">+ {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_prepaid_cleaning) }} </span></span>
                            </label>
						@endif

                        @if($productDetail->rental_fuel_gallons !== null)
                            <!-- Prepaid Fuel Gallons -->
                            <label class="inline-flex items-center space-x-2 mt-1 w-fit">
                                <input type="checkbox" class="form-checkbox h-4 w-4" checked
                                    id="fuelGallonsCheckbox"
                                    data-charged="1 Time Max"
                                      data-keep="Keep Option"
                                data-discard="No Thanks, I'll Take The Risk"
                                    data-id="fuelGallonsCheckbox"
                                    data-title="Keep Prepaid Fuel Gallons"
                                    data-message="This includes a set number of fuel gallons in your rental package."
                                    onclick="handleCheckboxClick(this)" />
                                <span>Fuel Gallons <span class="font-medium">+ {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_fuel_gallons) }}</span></span>
                            </label>
                        @endif

                        @if($productDetail->rental_def_gallons !== null)
                            <!-- DEF Gallons -->
                            <label class="inline-flex items-center space-x-2 mt-1 w-fit">
                                <input type="checkbox" class="form-checkbox h-4 w-4" checked
                                    id="defGallonsCheckbox"
                                    data-charged="1 Time Max"
                                      data-keep="Keep Option"
                                data-discard="No Thanks, I'll Take The Risk"
                                    data-id="defGallonsCheckbox"
                                    data-title="Keep Prepaid DEF Gallons"
                                    data-message="This includes a set number of DEF gallons in your rental package."
                                    onclick="handleCheckboxClick(this)" />
                                <span>DEF Gallons <span class="font-medium">+ {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_def_gallons) }}</span></span>
                            </label>
                        @endif
                        
                        @if(
                                $productDetail->rental_damage_waiver_daily !== null ||
                                $productDetail->rental_damage_waiver_weekend !== null ||
                                $productDetail->rental_damage_waiver_weekly !== null ||
                                $productDetail->rental_damage_waiver_monthly !== null
                            )
                        <!-- Damage Waiver -->
                        <label class="inline-flex items-center space-x-2 mt-1 w-fit" >
                            <input type="checkbox" class="form-checkbox h-4 w-4" checked
                                id="damageCheckbox"
                                 data-charged="1 Time Max"
                                data-id="damageCheckbox"
                                data-keep="Keep Option"
                                data-discard="No Thanks, I'll Take The Risk"

                                data-title="Keep Damage Waiver"
                                data-message="By removing the Damage Waiver, you agree to take full financial responsibility for any damage or repairs, or to provide business insurance."
                                onclick="handleCheckboxClick(this)" />

                                @switch($productType)
                                    @case('daily')
                                    <span>Damage Waiver <span class="font-medium">+  {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_damage_waiver_daily) }} </span></span>
                                    @break

                                    @case('weekend')
                                    <span>Damage Waiver <span class="font-medium">+  {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_damage_waiver_weekend) }} </span></span>
                                    @break

                                    @case('weekly')
                                    <span>Damage Waiver <span class="font-medium">+  {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_damage_waiver_weekly) }} </span></span>
                                    @break

                                    @case('monthly')
                                    <span>Damage Waiver <span class="font-medium">+  {{ App\Helpers\CustomHelper::formatCurrency($productDetail->rental_damage_waiver_monthly) }} </span></span>
                                    @break
                                    @default
                                        <span>-</span>
                                @endswitch
                        </label>
                        @endif


                        @foreach ($productDetail->options as $option)
                       	@if($option->items->isNotEmpty())
                        @foreach ($option->items as $item)

                        <label class="inline-flex items-center space-x-2 mt-1 w-fit">
                            <input type="checkbox" class="form-checkbox h-4 w-4" @if($item->value=="Checked") checked="checked" @endif
                                id="options_{{$item->unique_id}}"
                                data-id="{{$item->unique_id}}"
                                data-keep="{{$item->accept_label}}"
                                data-discard="{{$item->decline_label}}"
                                data-message="{{$item->comment}}"
                                data-unique_id="{{$item->unique_id}}"
                                @if($item->comment !== null)  onclick="handleCheckboxClick(this)" @endif
                               
                                data-charged="{{ $item->charged ?? null }}"
                                />
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

                                @if(!empty($item->charged) && $item->charged !== null)
                                <small style="display: none;" class="text-gray-500">({{ $item->charged }})</small>
                                 @endif

                            </span></span>
                        </label>
                        @endforeach
						@endif

                        @endforeach
                        
                    </div>
                    <div id="rentalbtn">
                        <button type="button"
                        id="addToRentalBtn"
                            class="border-0 bg-yellow-400  font-medium flex px-6 py-3 mt-4 items-center leading-4 rounded-lg text-[14px] hover:bg-yellow-300  transition-all duration-500 ease-in-out"><i
                                class="fa-solid fa-bag-shopping text-[20px] mr-3"></i> Add to Rental
                                <img src="{{ asset('storage/front/images/loading.gif') }}" alt="loading" class="loader-gif" /></button>
                    </div>
                <!-- </form> -->
                </div>
            </div>
        </div>
    </section>

    <section class="pb-[60px]">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <div class="pb-[60px]">
                <a href="#" class="bg-yellow-400 px-6 border-0 text-[14px] py-4">Details</a>
            </div>
            <div class="flex flex-col gap-y-4 text-[#6f6f87]">
                {!! $productDetail->description !!}
            </div>
        </div>
    </section>


 <!-- Amazing Additions -->
   <section class="pb-[60px]">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <h2 class="md:text-[28px] font-bold text-left mb-10  text-purple">Amazing Additions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-y-0 gap-x-8 lg:gap-8">

            @foreach ($productDetail->relatedProducts as $relatedProducts)
                <div class="pt-[30px] rounded-lg text-center">
                    <div
                        class="text-purple group">
                        <div class="lg:h-80 w-full relative overflow-hidden">
                            <img src="{{ $relatedProducts->media->url }}" alt="boom-lift-v2"
                                class="mx-auto h-full lg:w-full w-[90%] object-contain opacity-100 group-hover:scale-[1.05] transition-all duration-500 ease-in-out" />
                            <div class="bg-black opacity-0 top-0 group-hover:opacity-25 absolute h-full w-full transition-all duration-500 ease-in-out"></div>
                        </div>
                        <div>
                            <h3 class="font-bold text-[16px] hover:text-black text-left px-2 py-3 bg-[#f2f3f5] mb-4">{{ $relatedProducts->product_name }}</h3>
                           
                            <div class="grid grid-cols-2 gap-x-2 gap-y-2">
                                <a href="{{ route('front.products.details', ['slug' => $relatedProducts->slug, 'productType' => 'daily']) }}"
                                    class="border-0 bg-yellow-500  font-medium flex flex-col px-2 py-1 leading-4 rounded-lg text-[14px] hover:bg-yellow-400  transition-all duration-500 ease-in-out">
                                    Daily
                                        <span> {{ App\Helpers\CustomHelper::formatCurrency($relatedProducts->rental_daily) }} </span>
                        </a>
                                <a href="{{ route('front.products.details', ['slug' => $relatedProducts->slug, 'productType' => 'weekend']) }}"
                                    class="border-0 bg-yellow-500  font-medium flex flex-col px-2 py-1 leading-4 rounded-lg text-[14px] hover:bg-yellow-400  transition-all duration-500 ease-in-out">
                                    Weekend Spcl.
                                    <span>{{ App\Helpers\CustomHelper::formatCurrency($relatedProducts->rental_weekend) }}</span>
                        </a>
                                <a href="{{ route('front.products.details', ['slug' => $relatedProducts->slug, 'productType' => 'weekly']) }}"
                                    class="border-0 bg-yellow-300  font-medium flex flex-col px-2 py-1 leading-4 rounded-lg text-[14px] hover:bg-yellow-400  transition-all duration-500 ease-in-out">
                                    Weekly
                                    <span>{{ App\Helpers\CustomHelper::formatCurrency($relatedProducts->rental_weekly) }}</span>
                        </a>
                                <a href="{{ route('front.products.details', ['slug' => $relatedProducts->slug, 'productType' => 'monthly']) }}"
                                    class="border-0 bg-yellow-300  font-medium flex flex-col px-2 py-1 leading-4 rounded-lg text-[14px] hover:bg-yellow-400  transition-all duration-500 ease-in-out">
                                    Monthly
                                    <span>{{ App\Helpers\CustomHelper::formatCurrency($relatedProducts->rental_monthly) }}</span>
                        </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
                
            </div>
        </div>
    </section>

   
	<!-- clean Modal -->
        <div id="modalBackdropClean"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg shadow-lg w-full md:max-w-lg p-6 relative max-w-[90%]">
                <p id="modalMessage" class="text-gray-700 mb-4">Dynamic message here</p>
                <div class="text-right flex flex-col md:flex-row whitespace-nowrap justify-center gap-3">
                    <button id="modalRejectBtn"
                        class="px-4 py-2 bg-[#cc3333] text-white rounded hover:bg-[#ca2626] order-2 md:order-1">
                        No Thanks, I'll Take The Risk
                    </button>
                    <button id="modalAcceptBtn"
                        class="px-4 py-2 bg-[#007300] text-white rounded hover:bg-[#006000] order-1 md:order-2">
                        Keep Option
                    </button>

                </div>
            </div>
        </div>

@endsection
@push('js')





<script>
    window.appDateFormat = "{{ config('app.date.date_format') }}";
</script>
   <script>
    // product detail img
    document.addEventListener("DOMContentLoaded", function () {
        var product = new Swiper(".thumbSwiper", {
            loop: true,
            spaceBetween: 10,
            slidesPerView: 4,
            freeMode: true,
            watchSlidesProgress: true,
        });

        var swiper2 = new Swiper(".mainSwiper", {
            loop: true,
            spaceBetween: 10,
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            thumbs: {
                swiper: product,
            },
        });
    });

    // ui-datrpicker
    const openBtn = document.getElementById("openDatePicker");
    const selectedText = document.getElementById("selectedDateText");
    const datePicker = document.getElementById("datePicker");
    const monthYear = document.getElementById("monthYear");
    const daysGrid = document.getElementById("daysGrid");
    const prevMonthBtn = document.getElementById("prevMonth");
    const nextMonthBtn = document.getElementById("nextMonth");
    const dateInput = document.getElementById("dateInput");

        // Only initialize if all required elements exist
        if (
            openBtn &&
            selectedText &&
            datePicker &&
            monthYear &&
            daysGrid &&
            prevMonthBtn &&
            nextMonthBtn &&
            dateInput
        ) {
        let currentDate = new Date();

        openBtn.addEventListener("click", () => {
            datePicker.classList.toggle("hidden");
            if (!datePicker.classList.contains("hidden")) {
                renderCalendar(currentDate);
            }
        });

        document.addEventListener("click", (e) => {
            if (
                !datePicker.contains(e.target) &&
                e.target !== openBtn &&
                !openBtn.contains(e.target)
            ) {
                datePicker.classList.add("hidden");
            }
        });

        prevMonthBtn.addEventListener("click", () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar(currentDate);
        });

        nextMonthBtn.addEventListener("click", () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar(currentDate);
        });
        window.renderCalendar = function renderCalendar(date) {
            const year = date.getFullYear();
            const month = date.getMonth();

            monthYear.textContent = date.toLocaleString("default", {
                month: "long",
                year: "numeric",
            });
            daysGrid.innerHTML = "";

            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDay = firstDay.getDay();

            for (let i = 0; i < startDay; i++) {
                daysGrid.innerHTML += "<div></div>";
            }

            for (let day = 1; day <= lastDay.getDate(); day++) {
            const dayEl = document.createElement("button");
            dayEl.textContent = day;

            const thisDate = new Date(year, month, day);
            const today = new Date();
            today.setHours(0, 0, 0, 0); 

            if (thisDate < today) {
                dayEl.className = "py-1 rounded text-gray-400 cursor-not-allowed";
                dayEl.disabled = true;
            } else {
                dayEl.className =
                    "py-1 rounded hover:bg-yellow-500 hover:text-white focus:outline-none";
                if (
                    day === today.getDate() &&
                    month === today.getMonth() &&
                    year === today.getFullYear()
                ) {
                    dayEl.classList.add("bg-yellow-100");
                }

                dayEl.addEventListener("click", () => {
                    const jsFormat = window.appDateFormat || "d/m/Y"; // fallback

                    const dayStr = String(day).padStart(2, "0");
                    const monthStr = String(month + 1).padStart(2, "0");
                    const yearStr = String(year);

                    // Build the date string based on the format
                    let formatted;
                    switch (jsFormat) {
                        case 'd/m/Y':
                            formatted = `${dayStr}/${monthStr}/${yearStr}`;
                            break;
                        case 'm/d/Y':
                            formatted = `${monthStr}/${dayStr}/${yearStr}`;
                            break;
                        case 'Y-m-d':
                            formatted = `${yearStr}-${monthStr}-${dayStr}`;
                            break;
                        default:
                            formatted = `${dayStr}/${monthStr}/${yearStr}`; // default fallback
                    }

                    selectedText.textContent = formatted;
                    dateInput.value = formatted;
                    datePicker.classList.add("hidden");
                });
            }
            daysGrid.appendChild(dayEl);
        }
        };
        } else {
            console.log("Date picker elements not found on this page");
        }

    // qty pluse/minus
        window.changeQty = function changeQty(delta) {
            const input = document.getElementById("qty");
            let current = parseInt(input.value) || 1;
            let newValue = current + delta;
            if (newValue < parseInt(input.min)) newValue = parseInt(input.min);
            if (newValue > parseInt(input.max)) newValue = parseInt(input.max);
            input.value = newValue;
        };

        // Global variable to store the checkbox being interacted with
        let currentCheckbox = null;

        /**
        * Called when a checkbox is clicked.
        * If user tries to uncheck it, prevent unchecking immediately,
        * and show the confirmation modal with dynamic content.
        */

	    window.handleCheckboxClick = function (checkbox) {
            if (!checkbox.checked) {
                checkbox.checked = true;

                currentCheckbox = checkbox;

                // Get dynamic message and button label
                const message = checkbox.dataset.message;
                const keep_title = checkbox.dataset.keep;
				const discard_title = checkbox.dataset.discard;



                 // Update modal content
				$("#modalMessage").html(message);
                $("#modalRejectBtn").text(discard_title || "No Thanks, I'll Take The Risk");
                $("#modalAcceptBtn").text(keep_title || "Keep Option");

                document.getElementById("modalBackdropClean").classList.remove("hidden");
            }
        };

        /**
        * Called when user clicks "Keep Option" button or clicks outside the modal.
        * Closes the modal and keeps the checkbox checked.
        */
        window.cancelUncheck = function () {
            if (currentCheckbox) {
                currentCheckbox.checked = true;
                document.getElementById("modalBackdropClean").classList.add("hidden");
                currentCheckbox = null;
            }
        };

        /**
        * Called when user clicks "No Thanks, I'll Take The Risk" button.
        * Unchecks the checkbox and closes the modal.
        */
        window.confirmUncheckModal = function () {
            if (currentCheckbox) {
                currentCheckbox.checked = false;
                document.getElementById("modalBackdropClean").classList.add("hidden");
                currentCheckbox = null;
            }
        };

        // Assign event listeners to modal buttons
        // "Keep Option" button (green)
        document.getElementById("modalAcceptBtn").onclick = cancelUncheck;

        // "No Thanks" button (red)
        document.getElementById("modalRejectBtn").onclick = confirmUncheckModal;

        /**
        * If user clicks anywhere outside the modal content,
        * treat it as cancel and close the modal.
        */
        document.getElementById("modalBackdropClean").addEventListener("click", function (e) {
            if (e.target === this) {
                cancelUncheck();
            }
        });

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

                        address.classList.remove("hidden"); //  Always show for In-Store
                        distance?.classList.remove("hidden");
                        rentalbtn?.classList.remove("hidden");

                        customdis.classList.add("hidden");
                    } else if (radio.value === "delivery") {
                        inStoreDiv.classList.add("hidden");
                        deliveryDiv.classList.remove("hidden");

                        address.classList.add("hidden"); // initially hide for delivery
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

                    // Reset address visibility
                    address.classList.add("hidden");
                }
            };

            // Add this to handle dropdown change logic
            document.addEventListener('DOMContentLoaded', function () {
                const stdSelect = document.getElementById('deliveryOption_std_delivery_fee');
                const extSelect = document.getElementById('deliveryOption_ext_delivery_fee');
                const address = document.getElementById("address");
                const rentalbtn = document.getElementById("rentalbtn");

                function handleDeliveryTypeChange(event) {
                    const selected = event.target.options[event.target.selectedIndex];
                    const dataId = selected?.getAttribute("data-id");

                    // Show address only for these types
                    if (["Delivery", "Pickup"].includes(dataId)) {
                        address.classList.remove("hidden");
                    } else {
                        address.classList.add("hidden");
                    }

                    // Always show rental button for valid selection
                    rentalbtn?.classList.remove("hidden");
                }

                stdSelect?.addEventListener('change', handleDeliveryTypeChange);
                extSelect?.addEventListener('change', handleDeliveryTypeChange);
            });


        window.onload = () => {
            const mainSelected = document.querySelector('input[name="option"]:checked');
            if (mainSelected) {
                toggleDeliveryOption(mainSelected);
            }
        };
   </script>
    <script>
        // Defer jQuery usage until DOM is ready AND Vite scripts are loaded
        window.addEventListener('DOMContentLoaded', function () {
            if (typeof window.jQuery !== 'undefined') {
                $(function () {
                    $('#rentalForm').on('submit', function (e) {
                        e.preventDefault();
                    });

                    $('#addToRentalBtn').on('click', function () {
                     
                    });
                });
            } else {
                alert('jQuery is NOT available in inline script');
            }
        });
    </script>

<script>
    const STANDARD_DELIVERY_FEE = {{ $productDetail->standard_delivery_fee ?? 0 }};
    const EXTENDED_DELIVERY_FEE = {{ $productDetail->extended_delivery_fee ?? 0 }};
</script>
        
<script>
window.addEventListener('DOMContentLoaded', function () {
    if (typeof window.jQuery !== 'undefined') {
        $(function () {
            // Prevent native form submit
            $('#rentalForm').on('submit', function (e) {
                e.preventDefault();
            });

            // Watch delivery selection changes
            $('input[name="delivery-option"]').on('change', calculateDeliveryFee);
            $('#deliveryOption_std_delivery_fee, #deliveryOption_ext_delivery_fee').on('change', calculateDeliveryFee);

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

                        distance_type = 'standard_delivery_fee' ;
                        distance_range =  @json($standard_delivery_range->setting_value) ;

                    }
                } else if (deliveryOption === 'dis2') {
                    const $selected = $('#deliveryOption_ext_delivery_fee');
                    if ($selected.val()) {
                        deliveryFee = parseFloat($selected.val()) * EXTENDED_DELIVERY_FEE;
                        const label = $selected.find('option:selected').data('id');
                        deliveryDataId = label;

                        distance_type = 'extended_delivery_fee' ;
                        distance_range =  @json($extended_delivery_range->setting_value) ;

                    }
                }

                if (deliveryDataId === 'Delivery+Pickup') {
                    delivery_pickup.push({ name: 'Delivery' }, { name: 'Pickup' });
                } else if (deliveryDataId) {
                    delivery_pickup.push({ name: deliveryDataId });
                }

                $('#inputDeliveryFee').val(deliveryFee);
                $('#inputDeliveryPickup').val(JSON.stringify(delivery_pickup));
              
            }

            $('#addToRentalBtn').on('click', function () {
                const $btn = $(this);
                const $loader = $btn.find('.loader-gif'); // use exact class

                try {
                    // --- Validate first ---
                    const scheduleDate = $('#selectedDateText').text().trim();
                    if (scheduleDate === 'Select Start Date') {
                        $('#dateError').removeClass('hidden');
                        return;
                    } else {
                        $('#dateError').addClass('hidden');
                    }

                    
                    $loader.css('display', 'inline-block');
                    $btn.prop('disabled', true);

                    const storeLocation = $('#storeLocation').val();
                    const qty = parseInt($('#qty').val()) || 1;
                    const name = $('.productname').text().trim();
                    const image = $('.product-image').attr('src') || '';
                    const basePriceText = $('.bg-yellow-400 h4').text().trim().split('/')[0].trim();
                    const numericPrice = parseFloat(basePriceText.replace(/[^0-9.]/g, ''));

                    const service_method = $('input[name="option"]:checked').val();
                    let service_option = null;

                    if (service_method === 'delivery') {
                        const selectedStd = $('#deliveryOption_std_delivery_fee').val();
                        const selectedExt = $('#deliveryOption_ext_delivery_fee').val();

                        if (selectedStd) {
                            service_option = $('#deliveryOption_std_delivery_fee option:selected').data('id');
                        } else if (selectedExt) {
                            service_option = $('#deliveryOption_ext_delivery_fee option:selected').data('id');
                        }
                    }

                    const addons = [];
                    $('input[type="checkbox"]:checked').each(function () {
                        const label = $(this).closest('label');
                        const spans = label.find('span');
                        const addonName = spans[0]?.childNodes[0]?.nodeValue.trim() || '';
                        const price = parseFloat(spans[1]?.innerText.replace(/[^0-9.]/g, '')) || 0;
                        const charged = $(this).data('charged') || null;
                        const unique_id = $(this).data('unique_id') || null;

                        if (addonName || unique_id) {
                            addons.push({ name: addonName, price, charged, unique_id });
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
                        delivery_pickup
                    };

                    const cart = JSON.parse(localStorage.getItem('rental_cart')) || [];
                    const existingIndex = cart.findIndex(i => i.product_id == item.product_id);

                    if (existingIndex !== -1) {
                        cart[existingIndex].qty += item.qty;
                        cart[existingIndex] = { ...cart[existingIndex], ...item };
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


                /*try {
                    // Show loader and disable button
                    $loader.css('display', 'inline-block');
                    $btn.prop('disabled', true);

                const scheduleDate = $('#selectedDateText').text().trim();
                if (scheduleDate === 'Select Start Date') {
                    $('#dateError').removeClass('hidden');
                    return;
                } else {
                    $('#dateError').addClass('hidden');
                }

                const storeLocation = $('#storeLocation').val();
                const qty = parseInt($('#qty').val()) || 1;
                const name = $('.productname').text().trim();
                const image = $('.product-image').attr('src') || '';
                const basePriceText = $('.bg-yellow-400 h4').text().trim().split('/')[0].trim();
                const numericPrice = parseFloat(basePriceText.replace(/[^0-9.]/g, ''));


                const service_method = $('input[name="option"]:checked').val(); // 'in-store' or 'delivery'
                let service_option = null;

                // Determine delivery option only if method is delivery
                if (service_method === 'delivery') {
                    const selectedStd = $('#deliveryOption_std_delivery_fee').val();
                    const selectedExt = $('#deliveryOption_ext_delivery_fee').val();

                    if (selectedStd) {
                        service_option = $('#deliveryOption_std_delivery_fee option:selected').data('id');
                    } else if (selectedExt) {
                        service_option = $('#deliveryOption_ext_delivery_fee option:selected').data('id');
                    }
                }

                const addons = [];
                    $('input[type="checkbox"]:checked').each(function () {
                        const label = $(this).closest('label');
                        const spans = label.find('span');
                        const addonName = spans[0]?.childNodes[0]?.nodeValue.trim() || '';
                        const price = parseFloat(spans[1]?.innerText.replace(/[^0-9.]/g, '')) || 0;
                        const charged = $(this).data('charged') || null;
                        const unique_id = $(this).data('unique_id') || null;

                        //  Only push if name or unique_id is present (you can modify this logic as needed)
                        if (addonName || unique_id) {
                            addons.push({ name: addonName, price, charged, unique_id });
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
                    schedule_end_date:null ,
                    delivery_fee: deliveryFee,
                    store_id: storeLocation,
                    service_option: service_option,
                    service_method: service_method,
                    distance_type: distance_type ,
                    distance_range:distance_range ,
            
                    delivery_pickup
                };

                const cart = JSON.parse(localStorage.getItem('rental_cart')) || [];

                const existingIndex = cart.findIndex(i => i.product_id == item.product_id);

                // Then update or push
                if (existingIndex !== -1) {
                    cart[existingIndex].qty += item.qty;
                    cart[existingIndex] = { ...cart[existingIndex], ...item };
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
    }*/

            });
        });
    }
});
</script>

@endpush
