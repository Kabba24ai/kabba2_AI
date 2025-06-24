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
                    <form id="rentalForm" method="POST" action="{{ route('front.orders.cart.add') }}">
                    @csrf

                    <input type="hidden" name="product_id" value="{{ $productDetail->id }}">
                    <input type="hidden" name="name" id="inputName">
                    <input type="hidden" name="image" id="inputImage">
                    <input type="hidden" name="rental_type" value="{{ $productType }}">
                    <input type="hidden" name="qty" id="inputQty">
                    <input type="hidden" name="base_price" id="inputBasePrice">
                    <input type="hidden" name="addons" id="inputAddons">
                    <input type="hidden" name="schedule_date" id="inputScheduleDate">
                    <input type="hidden" name="delivery_fee" id="inputDeliveryFee">
                    <div
                        class="bg-[#f9fafc] mb-4 flex flex-col lg:flex-row justify-between items-center p-2 lg:p-0 lg:pl-4 md:pl-4">
                        <div>
                            <h3 class="font-bold text-[18px] lg:text-[22px]">{{ $productDetail->product_name }}</h3>
                            <p
                                class="before:content-['('] before:text-[#6f6f87] after:content-[')'] after:text-[#6f6f87] text-[#188753] text-[14px]">
                                In Stock</p>
                        </div>
                        <div
                            class="bg-yellow-400 mt-3 lg:mt-0 lg:text-[32px] text-[24px] h-[50px] lg:h-[70px] px-6 flex items-center font-medium">
                            <h4>
                                @switch($productType)
                                    @case('daily')
                                        {{ CustomHelper::formatCurrency($productDetail->rental_daily) }}
                                        <span class="text-[16px] font-normal">/ day</span>
                                    @break

                                    @case('weekend')
                                        {{ CustomHelper::formatCurrency($productDetail->rental_weekend) }}
                                        <span class="text-[16px] font-normal">/ week</span>
                                    @break

                                    @case('weekly')
                                        {{ CustomHelper::formatCurrency($productDetail->rental_weekly) }}
                                        <span class="text-[16px] font-normal">/ week</span>
                                    @break

                                    @case('monthly')
                                        {{ CustomHelper::formatCurrency($productDetail->rental_monthly) }}
                                        <span class="text-[16px] font-normal">/ month</span>
                                    @break
                                    @default
                                        <span>-</span>
                                @endswitch
                            </h4>
                        </div>

                    </div>
                    <div id="distance" class="mt-5 mb-5 ">
                        <div class="flex items-center space-x-2">

                            <div class="relative">
                                <input type="text" id="dateInput" name="date" readonly class="hidden" />
                                <div class="flex items-center">
                                    <button id="openDatePicker" type="button" class="px-4 py-2">
                                        <img src="{{ asset('storage/front/images/calendar_icon.png') }}" alt=""
                                            class="w-[52px]">
                                    </button>
                                    <div id="selectedDateText">Select Start Date</div>
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
                        <div id="dateError" class="text-red-600 text-sm mt-2 hidden">Please select a start date.</div>
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
                                <span>15 mi</span>
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
                                <span>30 mi</span>
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
                                <!-- For 15 mi -->
                                <div id="dropdown15" class="hidden my-2">
                                    <label class="block font-medium mb-1">Choose Delivery Type (15 mi):</label>
                                    <select id="deliveryOption15" class="w-full border p-2 rounded" >
                                        <option value="">Select Delivery Options</option>
                                        <option value="2">Delivery + Pick Up (To/From My Job Site) [{{ CustomHelper::formatCurrency($productDetail->standard_delivery_fee * 2) }}]</option>
                                        <option value="1">Delivery but I'll Return to Store [{{ CustomHelper::formatCurrency($productDetail->standard_delivery_fee) }}]</option>
                                        <option value="1">I'll Pick Up In-Store but Need Return Service Pick Up [{{ CustomHelper::formatCurrency($productDetail->standard_delivery_fee) }}]</option>
                                    </select>
                                </div>
                            @endif

                            @if ($productDetail->extended_delivery_fee !== null)
                                <!-- For 30 mi -->
                                <div id="dropdown30" class="hidden my-2">
                                    <label class="block font-medium mb-1">Choose Delivery Type (30 mi):</label>
                                    <select id="deliveryOption30" class="w-full border p-2 rounded" >
                                        <option value="">Select Delivery Options</option>
                                        <option value="2">Delivery + Pick Up (To/From My Job Site) [{{ CustomHelper::formatCurrency($productDetail->extended_delivery_fee * 2) }}]</option>
                                        <option value="1">Delivery but I'll Return to Store [{{ CustomHelper::formatCurrency($productDetail->extended_delivery_fee) }}]</option>
                                        <option value="1">I'll Pick Up In-Store but Need Return Service Pick Up [{{ CustomHelper::formatCurrency($productDetail->extended_delivery_fee) }}]</option>
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
                        <select id="category" name="category"
                            class="block w-100 px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none appearance-none">
                            <option disabled selected>Select Store Location</option>
                            <option>10296 High 46 , Bon Aqua ,TN ,37025</option>
                            <option>10296 High 46 , Bon Aqua ,TN ,37025</option>
                            <option>4385 SR-48, Charlotte , TN ,37036</option>
                        </select>
                    </div>


                    <div class="flex flex-col">
                    	
                        
                        <h4 class="font-semibold text-[16px] mb-1">Options</h4>
                        @foreach ($productDetail->options as $option)
                       	@if($option->items->isNotEmpty())
                        @foreach ($option->items as $item)
                            
                        	<label class="inline-flex items-center space-x-2 mt-1">
                            <input type="checkbox" class="form-checkbox h-4 w-4" @if($item->value=="Checked") checked="checked" @endif
                                id="options_{{$item->unique_id}}"
                                data-id="{{$item->unique_id}}"
                                data-keep="{{$item->accept_label}}"
                                data-discard="{{$item->discard_label}}"
                                data-message="{{$item->comment}}"
                                onclick="handleCheckboxClick(this)" />
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
                                
                                {{ CustomHelper::formatCurrency($price) }}
                            </span></span>
                        </label>
                        @endforeach
						@endif
                       
                        @endforeach   
                        <!-- Prepaid Fuel -->
                        
                        
                    </div>



                    <div id="rentalbtn">
                        <button type="button" 
                        id="addToRentalBtn"
                            class="border-0 bg-yellow-400  font-medium flex px-6 py-3 mt-4 items-center leading-4 rounded-lg text-[14px] hover:bg-yellow-300  transition-all duration-500 ease-in-out"><i
                                class="fa-solid fa-bag-shopping text-[20px] mr-3"></i> Add to Rental</button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </section>
    <!-- Amazing Additions -->
    <section class="pb-[60px]">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <h2 class="md:text-[28px] font-bold text-left mb-10  ">Amazing Additions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-y-0 gap-x-8 lg:gap-8">
                <div class="pt-[30px] rounded-lg text-center">
                    <a href="javascript:void(0)" class="text-[#231E41] group">
                        
                        <div class="lg:h-80 w-full relative overflow-hidden">
                            <img src="{{ $productDetail->image_url }}" alt="boom-lift-v2"
                                class="mx-auto h-full lg:w-full w-[90%] object-contain opacity-100 group-hover:scale-[1.05] transition-all duration-500 ease-in-out" />
                            <div
                                class="bg-[#000] opacity-0 top-0 group-hover:opacity-25 absolute h-full w-full transition-all duration-500 ease-in-out">
                            </div>
                        </div>
                        <div>
                            <h3 class="font-bold text-[16px] hover:text-black text-left px-2 py-3 bg-white mb-4">
                                {{ $productDetail->product_name }}- Daily</h3>
                            <h4 class="text-[24px] mt-2 font-medium text-black text-left">
                                {{ $productDetail->rental_daily }}</h4>
                            <button type="button"
                                class="border-0 bg-yellow-500 text-black font-medium mt-4 px-6 py-1 leading-[50px] text-[14px] hover:bg-yellow-400 transition-all duration-500 ease-in-out">
                                <i class="fa-solid fa-bag-shopping text-[20px] mr-3"></i> Add to Rental
                            </button>
                        </div>
                    </a>
                </div>
                <div class="pt-[30px] rounded-lg text-center">
                    <a href="javascript:void(0)" class=" group">
                        <div class="lg:h-80 w-full relative overflow-hidden">
                            <img src="{{ $productDetail->image_url }}" alt="boom-lift-v2"
                                class="mx-auto h-full lg:w-full w-[90%] object-contain opacity-100 group-hover:scale-[1.05] transition-all duration-500 ease-in-out" />
                            <div
                                class="bg-[#000] opacity-0 top-0 group-hover:opacity-25 absolute h-full w-full transition-all duration-500 ease-in-out">
                            </div>
                        </div>
                        <div>
                            <h3 class="font-bold text-[16px] hover:text-black text-left px-2 py-3 bg-white mb-4">
                                {{ $productDetail->product_name }} - Weekend</h3>
                            <h4 class="text-[24px] mt-2 font-medium text-black text-left">
                                {{ $productDetail->rental_weekend }}</h4>
                            <button type="button"
                                class="border-0 bg-yellow-500 text-black font-medium mt-4 px-6 py-1 leading-[50px] text-[14px] hover:bg-yellow-400 transition-all duration-500 ease-in-out">
                                <i class="fa-solid fa-bag-shopping text-[20px] mr-3"></i> Add to Rental
                            </button>
                        </div>
                    </a>
                </div>
                <div class="pt-[30px] rounded-lg text-center">
                    <a href="javascript:void(0)" class=" group">
                        <div class="lg:h-80 w-full overflow-hidden relative">
                            <img src="{{ $productDetail->image_url }}" alt="boom-lift-v2"
                                class="mx-auto h-full lg:w-full w-[90%] object-contain opacity-100 group-hover:scale-[1.05] transition-all duration-500 ease-in-out" />
                            <div
                                class="bg-[#000] opacity-0 top-0 group-hover:opacity-25 absolute h-full w-full transition-all duration-500 ease-in-out">
                            </div>
                        </div>
                        <div>
                            <h3 class="font-bold text-[16px] hover:text-black text-left px-2 py-3 bg-white mb-4">
                                {{ $productDetail->product_name }}- Weekly</h3>
                            <h4 class="text-[24px] mt-2 font-medium text-black text-left">
                                {{ $productDetail->rental_weekly }}</h4>
                            <button type="button"
                                class="border-0 bg-yellow-500 text-black font-medium mt-4 px-6 py-1 leading-[50px] text-[14px] hover:bg-yellow-400 transition-all duration-500 ease-in-out">
                                <i class="fa-solid fa-bag-shopping text-[20px] mr-3"></i> Add to Rental
                            </button>
                        </div>
                    </a>
                </div>
                <div class="pt-[30px] rounded-lg text-center">
                    <a href="javascript:void(0)" class=" group">
                        <div class="lg:h-80 w-full relative overflow-hidden">
                            <img src="{{ $productDetail->image_url }}" alt="boom-lift-v2"
                                class="mx-auto h-full lg:w-full w-[90%] object-contain opacity-100 group-hover:scale-[1.05] transition-all duration-500 ease-in-out" />
                            <div
                                class="bg-[#000] opacity-0 top-0 group-hover:opacity-25 absolute h-full w-full transition-all duration-500 ease-in-out ">
                            </div>
                        </div>
                        <div>
                            <h3 class="font-bold text-[16px] hover:text-black text-left px-2 py-3 bg-white mb-4">
                                {{ $productDetail->product_name }} - Monthly</h3>
                            <h4 class="text-[24px] mt-2 font-medium text-black text-left">
                                {{ $productDetail->rental_monthly }}</h4>
                            <button type="button"
                                class="border-0 bg-yellow-500 text-black font-medium mt-4 px-6 py-1 leading-[50px] text-[14px] hover:bg-yellow-400 transition-all duration-500 ease-in-out">
                                <i class="fa-solid fa-bag-shopping text-[20px] mr-3"></i> Add to Rental
                            </button>
                        </div>
                    </a>
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

	<!-- clean Modal -->
        <div id="modalBackdropClean"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                dayEl.className =
                    "py-1 rounded hover:bg-yellow-500 hover:text-white focus:outline-none";

                const today = new Date();
                if (
                    day === today.getDate() &&
                    month === today.getMonth() &&
                    year === today.getFullYear()
                ) {
                    dayEl.classList.add("bg-yellow-100");
                }

                dayEl.addEventListener("click", () => {
                    const formatted = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
                    selectedText.textContent = formatted;
                    dateInput.value = formatted; // update hidden input
                    datePicker.classList.add("hidden");
                });

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
				$("#modalMessage").html(message);
				
				
               $("modalRejectBtn").text(discard_title || "No Thanks, I'll Take The Risk");
			   $("modalAcceptBtn").text(keep_title);

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

        // product details radio hide/show
        window.toggleDeliveryOption = function toggleDeliveryOption(radio) {
            const inStoreDiv = document.getElementById("inStoreDiv");
            const deliveryDiv = document.getElementById("deliveryDiv");
            const customdis = document.getElementById("customdis");
            const distance = document.getElementById("distance");
            const address = document.getElementById("address");
            const rentalbtn = document.getElementById("rentalbtn");

            if (radio.name === "option") {
                if (radio.value === "in-store") {
                    // Show for in-store
                    inStoreDiv.classList.remove("hidden");
                    address.classList.remove("hidden");
                    distance.classList.remove("hidden");
                    rentalbtn.classList.remove("hidden");

                    // Hide delivery-related
                    deliveryDiv.classList.add("hidden");
                    customdis.classList.add("hidden");
                } else if (radio.value === "delivery") {
                    // Show delivery section
                    deliveryDiv.classList.remove("hidden");
                    inStoreDiv.classList.add("hidden");

                    // Hide address for delivery initially
                    address.classList.add("hidden");
                    customdis.classList.add("hidden");

                    // Check which delivery-option is selected
                    const deliveryOption = document.querySelector(
                        'input[name="delivery-option"]:checked',
                    );
                    if (deliveryOption) {
                        toggleDeliveryOption(deliveryOption); // Trigger sub-option logic
                    }
                }
            }

            if (radio.name === "delivery-option") {
                // hide both dropdowns first
                dropdown15?.classList.add("hidden");
                dropdown30?.classList.add("hidden");
                customdis.classList.add("hidden");


                if (radio.value === "dis1") {
                    dropdown15?.classList.remove("hidden");
                    rentalbtn.classList.remove("hidden");
                customdis.classList.add("hidden");
                  

                } else if (radio.value === "dis2") {
                    dropdown30?.classList.remove("hidden");
                    rentalbtn.classList.remove("hidden");
                    customdis.classList.add("hidden");


                } else if (radio.value === "discustom") {
                    customdis.classList.remove("hidden");
                    rentalbtn.classList.add("hidden");

                }
            }

        };

        window.onload = () => {
            const mainSelected = document.querySelector('input[name="option"]:checked');
            if (mainSelected) {
                toggleDeliveryOption(mainSelected);
            }
        };
                
   </script>



<script>
    const STANDARD_DELIVERY_FEE = {{ $productDetail->standard_delivery_fee ?? 0 }};
    const EXTENDED_DELIVERY_FEE = {{ $productDetail->extended_delivery_fee ?? 0 }};
</script>


    <script>


// Block form submission from Enter key or other sources
$('#rentalForm').on('submit', function (e) {
    e.preventDefault(); // always block native submit
});


 $('#addToRentalBtn').on('click', function () {
     
        const scheduleDate = $('#selectedDateText').text().trim();
    if (scheduleDate === 'Select Start Date') {
        $('#dateError').removeClass('hidden');
        return;
    } else {
        $('#dateError').addClass('hidden');
    }


        const qty = parseInt(document.querySelector('#qty').value) || 1;

        console.log("qty :- ", qty);

        const name = document.querySelector('h3').innerText.trim();
        console.log("name :- ", name);
        

        const image = document.querySelector('.product-image')?.src || '';

        console.log("image :- ", image);


        const basePriceText = document.querySelector('.bg-yellow-400 h4')?.innerText?.trim() || '';

        console.log("Base price text:", basePriceText);

        const rawPrice = basePriceText.split('/')[0].trim(); // "$1,234.00"
        console.log("rawPrice price text:", rawPrice);


const numericPrice = parseFloat(
    rawPrice.replace(/[^0-9.]/g, '') // remove $ and commas
);
console.log("Numeric price:", numericPrice); // 1234


        
        console.log("basePrice :- ", numericPrice);


        const addons = [];
        document.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
            const label = cb.closest('label');
            if (label) {
                const spans = label.querySelectorAll('span');
                let addonName = spans[0]?.innerText.trim() || '';
                let price = spans[1] ? parseFloat(spans[1].innerText.replace(/[^0-9.]/g, '')) : 0;
                addons.push({ name: addonName, price });
            }
        });



        console.log("addons :- ", addons);

// Determine delivery fee if not already set via onchange
let deliveryFee = parseFloat($('#inputDeliveryFee').val()) || 0;

// Auto-pick from active dropdown if missing
if (deliveryFee === 0) {
    const deliveryOption = document.querySelector('input[name="delivery-option"]:checked')?.value;
    
    if (deliveryOption === 'dis1') {
        const selected15 = document.getElementById('deliveryOption15');
        if (selected15?.value) {
            deliveryFee = parseFloat(selected15.value) * STANDARD_DELIVERY_FEE;
        }
    } else if (deliveryOption === 'dis2') {
        const selected30 = document.getElementById('deliveryOption30');
        if (selected30?.value) {
            deliveryFee = parseFloat(selected30.value) * EXTENDED_DELIVERY_FEE;
        }
    }

    $('#inputDeliveryFee').val(deliveryFee);
    console.log("✅ Calculated deliveryFee:", deliveryFee);
}



        // Fill hidden fields
        $('#inputName').val(name);
        $('#inputImage').val(image);
        $('#inputQty').val(qty);
        $('#inputBasePrice').val(numericPrice);
        $('#inputScheduleDate').val(scheduleDate);
        $('#inputAddons').val(JSON.stringify(addons));

        // Submit with AJAX
        $.ajax({
            url: $('#rentalForm').attr('action'),
            method: 'POST',
            xhrFields: {
                withCredentials: true // ⬅️ important if you're using subdomains/local.dev
            },
            data: $('#rentalForm').serialize(),
            success: function (data) {
                if (data.success) {
                    console.log('✅ Cart updated:', data);
                   
                        const toggleBtn = document.querySelector('.toggleCart');
                        if (toggleBtn) toggleBtn.click();
                   
                } else {
                    console.error('❌ Cart update failed:', data);
                }
            },
            error: function (xhr) {
                console.error('❌ AJAX Error:', xhr.responseText);
                alert('Something went wrong!');
            }
        });
    });
    </script>



@endpush
