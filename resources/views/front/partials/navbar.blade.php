@if (session('impersonated_by_admin') && auth('customer')->check())
    <div class="fixed top-0 left-0 right-0 z-[10000] bg-yellow-500 text-black px-4 py-2">
        <div class="container mx-auto flex items-center justify-between">
            <div class="text-sm font-medium">
                Logged in as
                <span class="font-bold text-white">
                    {{ auth('customer')->user()->full_name ?? 'Customer' }}
                </span>
                by
                <span class="font-bold text-blue-600">
                    [{{ session('impersonator.name', 'Admin') }}]
                </span>
            </div>

            <form method="POST" action="{{ route('front.auth.logout.index') }}">
                @csrf
                <button type="submit"
                    class="text-sm px-3 py-1 rounded border border-black hover:bg-black hover:text-white transition">
                    Logout
                </button>
            </form>
        </div>
    </div>

    {{-- Spacer so your fixed main nav doesn’t overlap this banner --}}
    <div class="h-10"></div>
@endif

<nav class="text-dark border-b border-b-neutral-800 fixed w-full mt-0 z-[9999] bg-neutral-800 bg-opacity-100 no-print">
    <div class="container !px-0">
        <div class="max-w-7xl mx-auto py-1.5 flex items-center px-4">
            <a href="{{ route('front.home.index') }}" class="1/6">
                <img src="{{ asset('storage/front/images/logo.png') }}" alt="" class="h-full md:w-23 w-40">
            </a>
            <div class="flex space-x-4 5/6 w-full items-center justify-right text-white ml-5"">
                <ul class=" lg:flex hidden flex items-center gap-6">
                    <li class="{{ Route::is('front.home.index') ? 'active' : '' }} group-[.active]:font-bold group"><a
                            href="{{ route('front.home.index') }}"
                            class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out group-[.active]:font-bold">Home</a>
                    </li>

                    <!-- Equipment Rentals (mega menu trigger) -->
                    <li class="relative menu-wrap ">
                        <a href="#"
                            class="flex items-center hover:text-yellow-400 text-sm transition-all duration-300 equipment-ren ease-in-out group-[.active]:font-bold   ">
                            Equipment Rentals
                        </a>

                        <!-- MEGA PANEL (5 equal columns) -->
                        <div class="mega hidden w-[100%] absolute left-0 right-0 submenu-top shadow-xl">
                            <div class="max-w-5xl w-screen px-6 py-6 grid grid-cols-4 gap-4 bg-white">

                                @foreach ($frontCategoryTree->chunk(ceil($frontCategoryTree->count() / 4)) as $categoryColumn)
                                    <!-- Column 1 -->
                                    <ul class="mega-col space-y-2 text-gray-700">
                                        @foreach ($categoryColumn as $category)
                                            <li
                                                class="{{ $category->childCategories->isNotEmpty() ? 'relative mb-0 group/item' : 'mb-0' }} py-1">
                                                <a href="{{ route('front.categories.index', $category->slug) }}"
                                                    class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out text-gray-700 leading-7">
                                                    {{ $category->title }}
                                                </a>

                                                {{-- Flyout for child categories --}}
                                                @if ($category->childCategories->isNotEmpty())
                                                    <div
                                                        class="flyout hidden absolute left-0 px-4 py-4 top-6 w-72 shadow-lg border border-gray-300 bg-white rounded-md z-20">
                                                        @foreach ($category->childCategories as $child)
                                                            <a href="{{ route('front.categories.sub-category', ['slug' => $category->slug, 'childCategorySlug' => $child->slug]) }}"
                                                                class="block px-2 py-1 hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out text-gray-700">
                                                                {{ $child->title }}
                                                            </a>

                                                            {{-- If child has grandchildren --}}
                                                            @if ($child->childCategories->isNotEmpty())
                                                                <div
                                                                    class="absolute top-0 left-full ml-2 w-max min-w-56 pt-3 pb-3
                                                        border border-gray-300 bg-white shadow-lg rounded-md
                                                        opacity-0 invisible transition-all duration-200 ease-in-out
                                                        group-hover/item:opacity-100 group-hover/item:visible
                                                        z-[9999] flex flex-col gap-y-1">
                                                                    @foreach ($child->childCategories as $grandChild)
                                                                        <a href="{{ route('front.categories.sub-sub-category', [
                                                                            'slug' => $category->slug,
                                                                            'childCategorySlug' => $child->slug,
                                                                            'grandChildSlug' => $grandChild->slug,
                                                                        ]) }}"
                                                                            class="block px-4 py-1 text-sm text-gray-700 hover:text-yellow-400 whitespace-nowrap">
                                                                            {{ $grandChild->title }}
                                                                        </a>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endforeach
                            </div>
                        </div>
                    </li>

                    <!-- <li class=""><a href="#" class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out group-[.active]:font-bold">How It Works</a></li> -->

                    <li class=" {{ Route::is('front.faqs.index') ? 'active' : '' }} group-[.active]:font-bold group"><a
                            href="{{ route('front.faqs.index') }}"
                            class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out group-[.active]:font-bold">Faq</a>
                    </li>

                    <li
                        class=" {{ Route::is('front.contact-us.index') ? 'active' : '' }} group-[.active]:font-bold group">
                        <a href="{{ route('front.contact-us.index') }}"
                            class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out group-[.active]:font-bold">Contact
                            Us</a>
                    </li>
                    <li
                        class="group-[.active]:font-bold group">
                        <a href="https://opportunities.kabba.ai/" target="_blank"
                            class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out group-[.active]:font-bold">Employment Opportunities </a>
                    </li>


                </ul>
            </div>
            <div>
                <div class="flex items-center">
                    <div class="relative">

                        @if (auth('customer')->check())
                            @php
                                $user = auth('customer')->user();
                                $initial = strtoupper(substr($user->full_name, 0, 1));
                            @endphp

                            <!-- Profile trigger -->
                            <a href="javascript:void(0)" id="userDropdownTrigger" class="flex items-center gap-2 group"
                                onclick="toggleDropdown(event)">
                                <div
                                    class="w-8 h-8 rounded-full bg-yellow-400 text-white font-bold flex items-center justify-center text-sm shadow">
                                    {{ $initial }}
                                </div>
                                <!-- <span class="text-sm font-medium group-hover:text-yellow-400 transition-all duration-300">
                            Nipa Patel
                            </span> -->
                            </a>

                            <!-- Dropdown -->
                            <div class="relative">
                                <div id="userDropdown"
                                    class="hidden absolute right-0 mt-2 w-48 bg-white border rounded shadow-md z-10">
                                    <a href="{{ route('front.customer.dashboard.index') }}"
                                        class="flex items-center gap-2 px-4 py-2 text-sm text-gray-900 hover:bg-gray-100">
                                        <!-- Account Icon -->
                                        <x-heroicon-o-user class="w-4 h-4 mr-2 text-gray-900" />
                                        Account
                                    </a>
                                    <form method="POST" action="{{ route('front.auth.logout.index') }}">
                                        @csrf
                                        <button type="submit"
                                            class="flex items-center gap-2 px-4 py-2 text-gray-900 text-sm w-full text-left hover:bg-gray-100">
                                            <!-- Logout Icon -->
                                            <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 mr-2 text-gray-900" />
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('front.auth.login.index') }}"
                                class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out text-white whitespace-nowrap">
                                Log In
                            </a>
                        @endif
                    </div>

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
                        <li
                            class=" py-3 {{ Route::is('front.home.index') ? 'active' : '' }} group-[.active]:font-bold group">
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

                        <li
                            class=" py-3 {{ Route::is('front.faqs.index') ? 'active' : '' }} group-[.active]:font-bold group">
                            <a href="{{ route('front.faqs.index') }}"
                                class="hover:text-yellow-400 px-5text-sm transition-all duration-300 ease-in-out group-[.active]:font-bold ">Faqs</a>
                        </li>
                        <li
                            class=" py-3 {{ Route::is('front.contact-us.index') ? 'active' : '' }} group-[.active]:font-bold group">
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
            class="-translate-x-full offcanvas-cart-body absolute top-17 right-0 overflow-hidden md:top-17 max-w-0 lg:top-20 opacity-0 md:max-w-md w-0 h-screen bg-white/100  shadow-lg z-[9999] transform translate-x-full transition-all duration-300 ease-in-out">
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

@push('js')
    <script>
        const toggleCartButton = document.querySelector('.toggleCart');
        const cartPanel = document.querySelector('#cartOffCanvas');

        // Check if mobile screen
        function isMobile() {
            return window.innerWidth < 768;
        }

        // Open Cart
        function openCart() {
            cartPanel.classList.remove('translate-x-full');
            cartPanel.classList.add('translate-x-0');

            if (isMobile()) {
                document.body.classList.add('overflow-hidden');
            }
        }

        // Close Cart
        function closeCart() {
            cartPanel.classList.add('translate-x-full');
            cartPanel.classList.remove('translate-x-0');

            if (isMobile()) {
                document.body.classList.remove('overflow-hidden');
            }
        }

        // Click event to open cart
        toggleCartButton?.addEventListener('click', openCart);

        // Close on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCart();
            }
        });

        // Optional: close on outside click (if you add a backdrop)
        // document.querySelector('#cartBackdrop')?.addEventListener('click', closeCart);
    </script>



    <script>
        function toggleDropdown(e) {
            e.stopPropagation(); // prevent document click from firing
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close the dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('userDropdown');
            const trigger = document.getElementById('userDropdownTrigger');

            if (!dropdown || !trigger) return;

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
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('userDropdownmobile');
            if (!dropdown) return; // Exit early if the element doesn't exist

            const trigger = dropdown.previousElementSibling;
            if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
@endpush
