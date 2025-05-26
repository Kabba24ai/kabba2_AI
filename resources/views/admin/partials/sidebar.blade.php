<aside :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'"
    class="sidebar fixed top-0 left-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-auto border-r border-gray-200 bg-white px-5 transition-all duration-300 lg:static lg:translate-x-0 dark:border-gray-800 dark:bg-black"
    @click.outside="sidebarToggle = false">
    <!-- SIDEBAR HEADER -->
    <div :class="sidebarToggle ? 'justify-center' : 'justify-between'"
        class="sidebar-header flex items-center gap-2 pt-8 pb-7">
        <a href="index.html">
            <span class="logo" :class="sidebarToggle ? 'hidden' : ''">
                <img class="dark:hidden w-10"
                    src="{{ asset('storage/admin/images/logo/rent-n-king-logo-outro.png') }}" alt="Logo" />
                <img class="hidden dark:block w-10"
                    src="{{ asset('storage/admin/images/logo/rent-n-king-logo-outro.png') }}" alt="Logo" />
            </span>

            <img class="logo-icon w-10" :class="sidebarToggle ? 'lg:block' : 'hidden'"
                src="{{ asset('storage/admin/images/logo/rent-n-king-logo-outro.png') }}" alt="Logo" />
        </a>
    </div>
    <!-- SIDEBAR HEADER -->

    <div class="no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear">
        <!-- Sidebar Menu -->
        <nav x-data="{ selected: $persist('Dashboard') }">
            <div>
                <h3 class="mb-4 text-xs leading-[20px] text-gray-400 uppercase">
                    <span class="menu-group-title" :class="sidebarToggle ? 'lg:hidden' : ''">
                        Modules
                    </span>

                    <svg :class="sidebarToggle ? 'lg:block hidden' : 'hidden'"
                        class="menu-group-icon mx-auto fill-current" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                            fill="" />
                    </svg>
                </h3>

                <ul class="mb-6 flex flex-col gap-4">
                    <!-- Menu Item Dashboard -->
                    <li x-data>
                        <a href="{{ route('admin.dashboard.index') }}"
                            @click="selected = (selected === 'Dashboard' ? '' : 'Dashboard')"
                            class="menu-item group flex items-center gap-3"
                            :class="(selected === 'Dashboard' && page === 'Dashboard') ? 'menu-item-active' :
                            'menu-item-inactive'">
                            {{-- Icon container --}}
                            <span class="w-6 h-6 flex items-center justify-center"
                                :class="(selected === 'Dashboard' && page === 'Dashboard') ? 'menu-item-icon-active' :
                                'menu-item-icon-inactive'">
                                <x-heroicon-o-squares-2x2 class="w-7 h-7" />
                            </span>

                            {{-- Text --}}
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                Dashboard
                            </span>
                        </a>
                    </li>

                    <!-- Menu Item: Ecommerce -->
                    <li>
                        <a href="#" @click.prevent="selected = (selected === 'Ecommerce' ? '' : 'Ecommerce')"
                            class="menu-item group"
                            :class="(selected === 'Ecommerce') || (page === 'productList' || page === 'productCategory') ?
                            'menu-item-active' : 'menu-item-inactive'">

                            <!-- Heroicon: Shopping Bag -->
                            <x-heroicon-o-shopping-bag :class="(selected === 'Ecommerce') || (page === 'productList' || page === 'productCategory') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'" class="w-6 h-6" />

                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                Ecommerce
                            </span>

                            <!-- Heroicon: Chevron Down -->
                            <span class="menu-item-arrow"
                                :class="[(selected === 'Ecommerce') ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive',
                                    sidebarToggle ? 'lg:hidden' : ''
                                ]">
                                <x-heroicon-o-chevron-down class="w-5 h-5" />
                            </span>
                        </a>

                        <!-- Dropdown Menu Start -->
                        <div class="translate transform overflow-hidden"
                            :class="(selected === 'Ecommerce') ? 'block' : 'hidden'">
                            <ul :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">

                                {{-- <!-- Product List -->
                                <li>
                                    <a href="product-list.html" class="menu-dropdown-item group"
                                        :class="page === 'productList' ? 'menu-dropdown-item-active' :
                                            'menu-dropdown-item-inactive'">
                                        Product List
                                    </a>
                                </li> --}}

                                <!-- Product Categories -->
                                <li>
                                    <a href="{{ route('admin.product-management.categories.index') }}" class="menu-dropdown-item group"
                                        :class="page === 'productCategory' ? 'menu-dropdown-item-active' :
                                            'menu-dropdown-item-inactive'">

                                        <!-- Circle Icon -->
                                        <span class="w-4 h-4 rounded-full border border-gray-400 group-hover:border-brand-500 dark:border-gray-500 dark:group-hover:border-brand-400"></span>
                                        <!-- Label -->
                                        <span >Product Categories</span>
                                    </a>
                                </li>


                            </ul>
                        </div>
                        <!-- Dropdown Menu End -->
                    </li>


                </ul>
            </div>


        </nav>
        <!-- Sidebar Menu -->
    </div>
</aside>
