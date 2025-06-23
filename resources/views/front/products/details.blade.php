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
                            class="bg-yellow-400 px-[20px] py-4 max-w-full mt-4 lg:mt-0 text-[14px] font-medium items-center inline-flex gap-3 relative border-2 border-[#fff]">
                            <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
                                <a href="index.php" class="opacity-25">Home</a>
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
                                                class="mx-auto h-full w-full object-contain group-hover:scale-[1.045] transition-all duration-500 ease-in-out" />
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
                    </div>
                    <div class="space-y-2 mt-4">
                        <label class="inline-flex items-center space-x-2">
                            <input type="radio" name="option" value="in-store" class="form-radio" checked
                                onchange="toggleDeliveryOption(this)" />
                            <span>In Store Pickup / Return</span>
                        </label>

                    </div>
                    <div class="space-y-2">
                        <label class="inline-flex items-center space-x-2">
                            <input type="radio" name="option" value="delivery" class="form-radio"
                                onchange="toggleDeliveryOption(this)" />
                            <span>Delivery - Full Turnkey Service</span>
                        </label>
                    </div>
                    <div id="inStoreDiv" class="mt-4">
                    </div>

                    <div id="deliveryDiv" class="mt-4 mb-5 hidden">
                        <div class="">
                            <span>Up To: </span>
                            <label class="inline-flex items-center space-x-2">
                                <input type="radio" name="delivery-option" value="dis1" class="sr-only peer" checked
                                    onchange="toggleDeliveryOption(this)" />
                                <div
                                    class="w-[16px] h-[16px] rounded-full border-2 border-yellow-400 peer-checked:border-yellow-500 peer-checked:bg-yellow-500 flex justify-center items-center">
                                    <div class="w-2 h-2 rounded-full border-white bg-white z-1"></div>
                                </div>
                                <span>15 mi</span>
                            </label>
                            <label class="inline-flex items-center space-x-2">
                                <input type="radio" name="delivery-option" value="dis2" class="sr-only peer"
                                    onchange="toggleDeliveryOption(this)" />
                                <div
                                    class="w-[16px] h-[16px] rounded-full border-2 border-yellow-400 peer-checked:border-yellow-500 peer-checked:bg-yellow-500 flex justify-center items-center">
                                    <div class="w-2 h-2 rounded-full border-white bg-white z-1"></div>
                                </div>
                                <span>30 mi</span>
                            </label>
                            <label class="inline-flex items-center space-x-2">
                                <input type="radio" name="delivery-option" value="discustom" class="sr-only peer"
                                    onchange="toggleDeliveryOption(this)" />
                                <div
                                    class="w-[16px] h-[16px] rounded-full border-2 border-yellow-400 peer-checked:border-yellow-500 peer-checked:bg-yellow-500 flex justify-center items-center">
                                    <div class="w-2 h-2 rounded-full border-white bg-white z-1"></div>
                                </div>
                                <span>Custom</span>
                            </label>
                            <div id="customdis" class="hidden w-[80%] border-[5px] border-[#0391B4] p-2 mt-2">
                                <h4 class="text-center font-bold">Please Call / Text for Custom Solutions</h4>
                                <h5 class="text-center text-[#c40000] font-bold py-2">(615) 815-6734</h5>
                                <p class="italic">Please reserve the item now, using the closest delivery range, then
                                    call / text afterwards for a custom delivery solution. </p>
                            </div>
                        </div>
                    </div>

                    <div id="address" class="w-full mt-5 mb-5 hidden">
                        <select id="category" name="category"
                            class="block w-50 px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none appearance-none">
                            <option disabled selected>Select Delivery Options</option>
                            <option>10296 High 46 , Bon Aqua ,TN ,37025</option>
                            <option>10296 High 46 , Bon Aqua ,TN ,37025</option>
                            <option>4385 SR-48, Charlotte , TN ,37036</option>
                        </select>
                    </div>


                    <div class="flex flex-col">
                        <h4 class="font-semibold text-[16px] mb-1">Options</h4>
                        <label class="inline-flex items-center space-x-2 mt-1">
                            <input type="checkbox" class="form-checkbox h-4 w-4" checked id="fuelCheckbox"
                                onclick="confirmUncheck(this, 'modalBackdropFuel')" />
                            <span class="">Prepaid Fuel <span class="font-medium">+
                                    {{ $productDetail->rental_prepaid_fuel }} </span> </span>
                        </label>
                        <label class="inline-flex items-center space-x-2 mt-1">
                            <input type="checkbox" class="form-checkbox h-4 w-4" checked id="cleanCheckbox"
                                onclick="confirmUncheck(this, 'modalBackdropClean')" />
                            <span class="">Prepaid Cleaning <span class="font-medium">+
                                    {{ $productDetail->rental_prepaid_cleaning }}</span></span>
                        </label>
                        <label class="inline-flex items-center space-x-2 mt-1">
                            <input type="checkbox" class="form-checkbox h-4 w-4" checked id="toothedCheckbox"
                                onclick="confirmUncheck(this, 'modalBackdropToothed')" />
                            <span class="">Toothed Bucket </span>
                        </label>
                        <label class="inline-flex items-center space-x-2 mt-1">
                            <input type="checkbox" class="form-checkbox h-4 w-4" />
                            <span class="">Smooth Bucket *Subject to Availability </span>
                        </label>
                        <label class="inline-flex items-center space-x-2 mt-1">
                            <input type="checkbox" class="form-checkbox h-4 w-4" />
                            <span class="">Forks <span class="font-medium">+ 0.00</span></span>
                        </label>
                        <label class="inline-flex items-center space-x-2 mt-1">
                            <input type="checkbox" class="form-checkbox h-4 w-4" checked id="damageCheckbox"
                                onclick="confirmUncheck(this, 'modalBackdropDamage')" />
                            <span class="">Damage Waiver <span class="font-medium">+
                                    {{ $productDetail->rental_damage_waiver_daily }}</span></span>
                        </label>
                    </div>



                    <div id="rentalbtn">
                        <button type="button"
                            class="toggleCart border-0 bg-yellow-400 text-[14px] px-6 h-[55px] font-medium mt-4 hover:bg-yellow-300  transition-all duration-500 ease-in-out"><i
                                class="fa-solid fa-bag-shopping text-[20px] mr-3"></i> Add to Rental</button>
                    </div>
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
    <!-- fule Modal -->
    <div id="modalBackdropFuel" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full md:max-w-lg p-6 relative max-w-[90%]">
            <p class="text-gray-700 mb-4">I understand that the machine is delivered full of fuel and I’m responsible
                for returning it full of fuel. If returned without a full tank, I realize I will be responsible for the
                added fuel charges of $8/gallon. If I take the “Pre-Paid Fuel” option, I can just walk away from this
                obligation and I’m covered!</p>
            <div class="text-right flex flex-col md:flex-row whitespace-nowrap justify-center gap-3">
                <button onclick="confirmUncheckModal('fuelCheckbox', 'modalBackdropFuel')"
                    class="px-4 py-2 bg-[#cc3333] text-white rounded hover:bg-[#ca2626] order-2 md:order-1">No Thanks,
                    I'll Take The Risk</button>
                <button onclick="cancelUncheck('fuelCheckbox', 'modalBackdropFuel')"
                    class="px-4 py-2 bg-[#007300] rounded hover:bg-[#006000] mr-2 text-[#fff] order-1 md:order-2">Keep
                    Prepaid Fule</button>

            </div>
        </div>
    </div>
    <!-- clean Modal -->
    <div id="modalBackdropClean"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full md:max-w-lg p-6 relative max-w-[90%]">
            <p class="text-gray-700 mb-4">I understand I’ll be responsible for bringing the equipment back at the same,
                or better, cleanliness level as when I receive the equipment or I’ll be charged the rates specified in
                the rental agreement. **Does not cover “Extreme” cleaning, only standard cleaning.</p>
            <div class="text-right flex flex-col md:flex-row whitespace-nowrap justify-center gap-3">
                <button onclick="confirmUncheckModal('cleanCheckbox', 'modalBackdropClean')"
                    class="px-4 py-2 bg-[#cc3333] text-white rounded hover:bg-[#ca2626] order-2 md:order-1">No Thanks,
                    I'll Take The Risk</button>
                <button onclick="cancelUncheck('cleanCheckbox', 'modalBackdropClean')"
                    class="px-4 py-2 bg-[#007300] rounded hover:bg-[#006000] mr-2 text-[#fff] order-1 md:order-2">Keep
                    Prepaid Cleaning</button>

            </div>
        </div>
    </div>
    <!-- Toothed Bucket  Modal -->
    <div id="modalBackdropToothed"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full md:max-w-lg p-6 relative max-w-[90%]">
            <p class="text-gray-700 mb-4">I understand that by not accepting the standard damage waiver, I will be
                responsible for all repairs necessary while using this equipment and have Business Insurance that covers
                this type of rental equipment or adequate financial capability to cover all expenses... including full
                replacement if needed.</p>
            <div class="text-right flex flex-col md:flex-row whitespace-nowrap justify-center gap-3">
                <button onclick="confirmUncheckModal('toothedCheckbox', 'modalBackdropToothed')"
                    class="px-4 py-2 bg-[#cc3333] text-white rounded hover:bg-[#ca2626] order-2 md:order-1">No Thanks,
                    I'll Take The Risk</button>
                <button onclick="cancelUncheck('toothedCheckbox', 'modalBackdropToothed')"
                    class="px-4 py-2 bg-[#007300] rounded hover:bg-[#006000] mr-2 text-[#fff] order-1 md:order-2">Keep
                    Toothed Bucket</button>

            </div>
        </div>
    </div>
    <!-- Damage Waiver  Modal -->
    <div id="modalBackdropDamage"
        class="fixed inset-0 bg-white bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full md:max-w-lg p-6 relative max-w-[90%]">
            <p class="text-gray-700 mb-4">I understand that by not accepting the standard damage waiver, I will be
                responsible for all repairs necessary while using this equipment and have Business Insurance that covers
                this type of rental equipment or adequate financial capability to cover all expenses... including full
                replacement if needed.</p>
            <div class="text-right flex flex-col md:flex-row whitespace-nowrap justify-center gap-3">
                <button onclick="confirmUncheckModal('damageCheckbox', 'modalBackdropDamage')"
                    class="px-4 py-2 bg-[#cc3333] text-white rounded hover:bg-[#ca2626] order-2 md:order-1">No Thanks,
                    I'll Take The Risk</button>
                <button onclick="cancelUncheck('damageCheckbox', 'modalBackdropDamage')"
                    class="px-4 py-2 bg-[#007300] rounded hover:bg-[#006000] mr-2 text-[#fff] order-1 md:order-2">Keep
                    Damage Waiver</button>

            </div>
        </div>
    </div>
@endsection
