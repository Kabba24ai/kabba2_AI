<nav
    class="text-dark border-b border-b-neutral-800 fixed w-full mt-0 z-[9999] bg-neutral-800 top-0 bg-opacity-100 ">
    <div class="container !px-0">
        <div class="max-w-7xl mx-auto py-1 flex items-center  px-4">
            <a href="#" class="1/6">
                <img src="{{ asset('storage/front/images/logo.png') }}" alt="" class="h-full md:w-23 w-40">
            </a>
            <div class="flex space-x-4 5/6 w-full items-center justify-end text-white">
                <ul class="lg:flex hidden">
                    <li class=" px-5 {{ Route::is('front.home.index') ? 'active' : '' }}  group-[.active]:font-bold group">
                        <a href="{{ route('front.home.index') }}"
                            class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out group-[.active]:font-bold">Home</a>
                    </li>
                    <li class="px-5 group ">
                        <a href="javascript:void(0)"
                            class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out">Equipment
                            Rentals</a>
                        <ul
                            class=" absolute min-w-32 pt-6 pb-5 top-18 right-4 border translate-y-2 bg-neutral-800/90 border-neutral-800/90  opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 ease-in-out z-10 grid grid-flow-col grid-rows-[repeat(4,_auto)] gap-y-4 gap-x-2 before:content-[''] before:h-7 before:absolute before:top-[-1.75rem] before:w-full ">

                            @foreach ($frontCategoryTree as $category)
                                <li
                                    class="{{ $category->childCategories->isNotEmpty() ? 'group/item relative' : 'flex w-full whitespace-nowrap' }}">
                                    <a href="{{ route('front.categories.index', $category->slug) }}"
                                        class="hover:text-yellow-400 px-5 text-sm transition-all duration-300 ease-in-out">
                                        {{ $category->title }}
                                    </a>
                                    @if ($category->childCategories->isNotEmpty())
                                        <ul
                                            class="absolute w-auto min-w-32 pt-4 pb-0 top-3 border translate-y-2 bg-black/70 opacity-0 invisible group-hover/item:opacity-100 group-hover/item:visible transition-all duration-300 ease-in-out z-10 grid grid-flow-col grid-rows-[repeat(4,_auto)] gap-4">
                                            @foreach ($category->childCategories as $child)
                                                <li class="flex w-full whitespace-nowrap">
                                                    <a href="{{ route('front.categories.sub-category', ['slug' => $category->slug, 'childCategorySlug' => $child->slug]) }}"
                                                        class="hover:text-yellow-400 px-5 text-sm transition-all duration-300 ease-in-out">
                                                        {{ $child->title }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach

                        </ul>
                    </li>
                    <li class=" px-5 group">
                        <a href="javascript:void(0)"
                            class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out group-[.active]:font-bold">How
                            It Works</a>
                    </li>
                    <li class=" px-5 {{ Route::is('front.faqs.index') ? 'active' : '' }} group-[.active]:font-bold group">
                        <a href="{{ route('front.faqs.index') }}"
                            class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out group-[.active]:font-bold">Faq</a>
                    </li>
                    <li class=" px-5 {{ Route::is('front.contact-us.index') ? 'active' : '' }} group-[.active]:font-bold group">
                        <a href="{{ route('front.contact-us.index') }}"
                            class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out group-[.active]:font-bold ">Contact
                            Us</a>
                    </li>

                    <!-- <li class=" px-5">
                        <a href="{{ route('front.auth.login.index') }}"
                            class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out">Log
                            In</a>
                    </li>
                     -->

                     <li class="px-5 relative">
                        <!-- @if(auth('customer')->check())
                            @php
                                $user = auth('customer')->user();
                                $initial = strtoupper(substr($user->full_name, 0, 1));
                            @endphp

                            <a href="{{ route('front.customer.dashboard.index') }}" class="flex items-center gap-2 group">
                                <div class="w-8 h-8 rounded-full bg-yellow-400 text-white font-bold flex items-center justify-center text-sm shadow">
                                    {{ $initial }}
                                </div>
                                <span class="text-sm font-medium group-hover:text-yellow-400 transition-all duration-300">
                                    {{ $user->full_name }}
                                </span>
                            </a>
                        @else
                            <a href="{{ route('front.auth.login.index') }}"
                            class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out">
                                Log In
                            </a>
                        @endif -->
                        @if(auth('customer')->check())
                            @php
                                $user = auth('customer')->user();
                                $initial = strtoupper(substr($user->full_name, 0, 1));
                            @endphp

                        <!-- Profile trigger -->
                        <a href="javascript:void(0)" class="flex items-center gap-2 group" onclick="toggleDropdown()">
                            <div class="w-8 h-8 rounded-full bg-yellow-400 text-white font-bold flex items-center justify-center text-sm shadow">
                           {{ $initial }}
                            </div>
                            <!-- <span class="text-sm font-medium group-hover:text-yellow-400 transition-all duration-300">
                            Nipa Patel
                            </span> -->
                        </a>

                        <!-- Dropdown -->
                        <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white border rounded shadow-md z-10">
                            <a href="{{ route('front.customer.dashboard.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-900 hover:bg-gray-100">
                                <!-- Account Icon -->
                                <x-heroicon-o-user class="w-4 h-4 mr-2 text-gray-900" />
                                Account
                            </a>
                            <form method="POST" action="{{ route('front.auth.logout.index') }}">
                                @csrf
                                <button type="submit" class="flex items-center gap-2 px-4 py-2 text-gray-900 text-sm w-full text-left hover:bg-gray-100">
                                    <!-- Logout Icon -->
                                    <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 mr-2 text-gray-900" />
                                    Logout
                                </button>
                            </form>
                        </div>

                         @else
                            <a href="{{ route('front.auth.login.index') }}"
                            class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out">
                                Log In
                            </a>
                        @endif 
                    </li>
                </ul>
            </div>
            <div>
                <div class="flex items-center">
                    @if(auth('customer')->check())
                        @php
                            $user = auth('customer')->user();
                            $initial = strtoupper(substr($user->full_name, 0, 1));
                        @endphp

                    <!-- Profile trigger -->
                    <a href="javascript:void(0)" class="flex items-center gap-2 group mobile-d-none" onclick="toggleDropdownmobile()">
                        <div class="w-8 h-8 rounded-full bg-yellow-400 text-white font-bold flex items-center justify-center text-sm shadow">
                        {{ $initial }}
                        </div>
                        <!-- <span class="text-sm font-medium group-hover:text-yellow-400 transition-all duration-300">
                        Nipa Patel
                        </span> -->
                    </a>

                    <!-- Dropdown -->
                    <div id="userDropdownmobile" class="hidden absolute right-[115px] top-[55px] sm:right-[80px] sm:top-[55px] md:right-[130px] md:top-[55px] mt-2 w-48 bg-white border rounded shadow-md z-10">
                        <a href="{{ route('front.customer.dashboard.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-900 hover:bg-gray-100">
                            <!-- Account Icon -->
                            <x-heroicon-o-user class="w-4 h-4 mr-2 text-gray-900" />
                            Account
                        </a>
                        <form method="POST" action="{{ route('front.auth.logout.index') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-2 px-4 py-2 text-gray-900 text-sm w-full text-left hover:bg-gray-100">
                                <!-- Logout Icon -->
                                <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 mr-2 text-gray-900" />
                                Logout
                            </button>
                        </form>
                    </div>

                        @else
                        <a href="{{ route('front.auth.login.index') }}" 
                        class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out mobile-d-none">
                           <x-heroicon-o-arrow-right-on-rectangle class="w-6 h-6 mr-2 text-yellow-400 " />
                        </a>
                    @endif 
                    <a href="javascript:void(0)" class="toggleCart px-3 py-2 rounded-full relative text-center me-5">
                        <i class="fa-solid fa-bag-shopping text-2xl text-white"></i>
                        <span id="cart-count"
                            class="bg-yellow-400 w-5 h-5 rounded-full absolute -top-1 -right-0 text-xs font-bold flex items-center justify-center">
                            0
                        </span>
                    </a>
                    <a href="javascript:void(0)" id="toggleHeader"
                        class="w-5 h-14 pt-6 flex justify-center me-4 block lg:hidden">
                        <span class="relative"></span>
                    </a>
                </div>
            </div>
        </div>

        <!-- mobile menu -->
        <div id="headerOffCanvas"
            class="offcanvas-header-body left-0 md:left-auto md:ml-4 md:right-auto lg:hidden opacity-0 absolute md:max-w-2xl top-16 sm:top-17 bottom-0 lg:max-w-md w-full max-h-0 bg-neutral-800/90 text-white shadow-lg z-[9999] transform translate-y-full">
            <div class="relative w-full h-full overflow-y-auto ">
                <div class="py-4 md:px-4 h-[calc(100vh-80px)] overflow-y-auto">
                    <ul class="lg:hidden block text-center">
                        <li class=" py-3 {{ Route::is('front.home.index') ? 'active' : '' }} group-[.active]:font-bold group">
                            <a href="{{ route('front.home.index') }}"
                                class="hover:text-yellow-400 px-5 text-sm group-[.active]:font-bold transition-all duration-300 ease-in-out">Home</a>
                        </li>


                        <li class=" py-3 group relative">
                            <a href="javascript:void(0)" id="menuToggleBtn"
                                class="hover:text-yellow-400 px-5 text-sm transition-all duration-300 ease-in-out">Equipment
                                Rentals</a>
                            <ul id="dropdownMenu"
                                class="bg-neutral-800 text-center  transition-all duration-300 ease-in-out z-10
                                 grid gap-y-4">

                                <li class="py-3 group relative">
                                    <!-- <a href="javascript:void(0)"
                                        class="hover:text-yellow-400 px-5 text-sm transition-all duration-300 ease-in-out">Equipment
                                        Rentals</a> -->
                                    <ul
                                        class="bg-neutral-800 text-center transition-all duration-300 ease-in-out z-10 grid gap-y-4">
                                        @foreach ($frontCategoryTree as $category)
                                            <li
                                                class="{{ $category->childCategories->isNotEmpty() ? 'group/item relative' : 'flex w-full whitespace-nowrap' }}">
                                                <a href="{{ route('front.categories.index', $category->slug) }}"
                                                    class="hover:text-yellow-400 w-full px-5 text-sm transition-all duration-300 ease-in-out">
                                                    {{ $category->title }}
                                                </a>
                                                @if ($category->childCategories->isNotEmpty())
                                                    <ul
                                                        class=" w-auto !pb-0 border translate-y-2 bg-black/60 transition-all duration-300 ease-in-out z-10 grid grid-flow-col grid-rows-[repeat(4,_auto)] ">
                                                        @foreach ($category->childCategories as $child)
                                                            <li class="flex w-full whitespace-nowrap">
                                                                <a href="{{ route('front.categories.sub-category', ['slug' => $category->slug, 'childCategorySlug' => $child->slug]) }}"
                                                                    class="hover:text-yellow-400 w-full p-2 text-sm transition-all duration-300 ease-in-out">
                                                                    {{ $child->title }}
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>

                            </ul>
                        </li>

                       
                        <li class=" py-3 {{ Route::is('front.faqs.index') ? 'active' : '' }} group-[.active]:font-bold group">
                            <a href="{{ route('front.faqs.index') }}"
                                class="hover:text-yellow-400 px-5text-sm transition-all duration-300 ease-in-out group-[.active]:font-bold ">Faqs</a>
                        </li>

                         <li class=" py-3 {{ Route::is('front.contact-us.index') ? 'active' : '' }} group-[.active]:font-bold group">
                            <a href="{{ route('front.contact-us.index') }}"
                                class="hover:text-yellow-400 px-5 text-sm transition-all duration-300 ease-in-out group-[.active]:font-bold">Contact</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- mobile menu end -->

        <!--  Off canvas Cart -->
        <div id="cartOffCanvas"
            class="offcanvas-cart-body absolute top-17 right-0 overflow-hidden md:top-17 max-w-0 lg:top-20 opacity-0 md:max-w-md w-0 h-screen bg-white/90  shadow-lg z-[9999] transform translate-x-full transition-all duration-300 ease-in-out">
            <div class="relative w-full h-[calc(100vh-80px)]">
                <div class="px-9 pt-15 pb-15 overflow-y-scroll h-full w-full">
                    <div id="cartData" data-fetch-cart-url="{{ route('front.cart.index') }}">
                        <p class="text-center py-10 text-gray-600">Your cart is empty.</p>

                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>


<script>
  function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('hidden');
  }

  // Close the dropdown when clicking outside
  document.addEventListener('click', function (e) {
    const dropdown = document.getElementById('userDropdown');
    const trigger = dropdown.previousElementSibling;
    if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
      dropdown.classList.add('hidden');
    }
  });
</script>

<script>
  function toggleDropdownmobile() {
    const dropdown = document.getElementById('userDropdownmobile');
    dropdown.classList.toggle('hidden');
  }

  // Close the dropdown when clicking outside
  document.addEventListener('click', function (e) {
    const dropdown = document.getElementById('userDropdownmobile');
    const trigger = dropdown.previousElementSibling;
    if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
      dropdown.classList.add('hidden');
    }
  });
</script>