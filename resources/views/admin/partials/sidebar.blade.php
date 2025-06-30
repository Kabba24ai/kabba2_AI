<aside :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'"
    class="sidebar fixed top-0 left-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-auto border-r border-gray-200 bg-white px-5 transition-all duration-300 lg:static lg:translate-x-0 dark:border-gray-800 dark:bg-black"
    @click.outside="sidebarToggle = false">
    <!-- SIDEBAR HEADER -->
    <div :class="sidebarToggle ? 'justify-center' : 'justify-between'"
        class="sidebar-header flex items-center gap-2 pt-8 pb-7">
        <a href="index.html">
            <span class="logo" :class="sidebarToggle ? 'hidden' : ''">
                <img class="dark:hidden w-10" src="{{ asset('storage/admin/images/logo/rent-n-king-logo-outro.png') }}"
                    alt="Logo" />
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

                @php
                    $ecommerceActive = Route::is([
                        'admin.product-management.products.*',
                        'admin.product-management.categories.*',
                        'admin.product-management.options.*',

                    ]);

                    $ordersActive = Route::is([
                        'admin.order-management.orders.*',
                        'admin.order-management.schedules.*',
                    ]);

                @endphp

                <ul class="mb-6 flex flex-col gap-4">
                    <!-- Dashboard -->
                    <li>
                        <a href="{{ route('admin.dashboard.index') }}"
                            class="menu-item group flex items-center gap-3 {{ Route::is('admin.dashboard.index') ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <span
                                class="w-6 h-6 flex items-center justify-center {{ Route::is('admin.dashboard.index') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}">
                                <x-heroicon-o-squares-2x2 class="w-7 h-7" />
                            </span>
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">Dashboard</span>
                        </a>
                    </li>

                    <!-- Ecommerce -->
                    <li x-data="{ open: {{ $ecommerceActive ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="open = !open"
                            class="menu-item group flex items-center gap-3 {{ $ecommerceActive ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <x-heroicon-o-building-storefront class="w-6 h-6 " />
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">Ecommerce</span>
                            <span class="menu-item-arrow"
                                :class="[open ? 'menu-item-arrow-inactive' : 'menu-item-arrow-active', sidebarToggle ?
                                    'lg:hidden' : ''
                                ]">

                                <!-- Chevron Left (when open) -->
                                <template x-if="!open">
                                    <x-heroicon-o-chevron-left class="w-5 h-5" />
                                </template>

                                <!-- Chevron Down (when closed) -->
                                <template x-if="open">
                                    <x-heroicon-o-chevron-down class="w-5 h-5" />
                                </template>
                            </span>

                        </a>

                        <div x-show="open" x-transition>
                            <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                <li>
                                    <a href="{{ route('admin.product-management.products.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.product-management.products.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-circle-stack class="h-5 w-5" /> Products
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.product-management.options.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.product-management.options.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-cube class="h-5 w-5" /> Product Options
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.product-management.categories.create') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.product-management.categories.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-inbox-stack class="h-5 w-5" /> Product Categories
                                    </a>
                                </li>

                              

                            </ul>
                        </div>
                    </li>

                    <!-- Orders -->
                    <li x-data="{ open: {{ $ordersActive ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="open = !open"
                            class="menu-item group flex items-center gap-3 {{ $ordersActive ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <x-heroicon-o-clipboard-document-list class="w-6 h-6" />
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">Orders</span>
                            <span class="menu-item-arrow"
                                :class="[open ? 'menu-item-arrow-inactive' : 'menu-item-arrow-active', sidebarToggle ?
                                    'lg:hidden' : ''
                                ]">

                                <!-- Chevron Left (when open) -->
                                <template x-if="!open">
                                    <x-heroicon-o-chevron-left class="w-5 h-5" />
                                </template>

                                <!-- Chevron Down (when closed) -->
                                <template x-if="open">
                                    <x-heroicon-o-chevron-down class="w-5 h-5" />
                                </template>
                            </span>

                        </a>

                        <div x-show="open" x-transition>
                            <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                <li>
                                    <a href="{{ route('admin.order-management.orders.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.order-management.orders.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-shopping-cart class="h-5 w-5" /> Orders
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.order-management.schedules.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.order-management.schedules.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-calendar class="h-5 w-5" /> Schedules
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    
                    @php
                    
                    $maintenanceActive = Route::is([
                        'admin.maintenance-management.equipments.*',
                        'admin.maintenance-management.parts.*',
                        'admin.maintenance-management.suppliers.*',
                    ]);


                    $crmActive = Route::is([
                        'admin.crm.customer_portal.*',
                    ]);
                      
                    @endphp
                    
                    <!-- Maintenance -->
                    <li x-data="{ open: {{ $maintenanceActive ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="open = !open"
                            class="menu-item group flex items-center gap-3 {{ $maintenanceActive ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <x-heroicon-o-cog-6-tooth  class="w-6 h-6" />
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">Maintenance</span>
                            <span class="menu-item-arrow"
                                :class="[open ? 'menu-item-arrow-inactive' : 'menu-item-arrow-active', sidebarToggle ?
                                    'lg:hidden' : ''
                                ]">

                                <!-- Chevron Left (when open) -->
                                <template x-if="!open">
                                    <x-heroicon-o-chevron-left class="w-5 h-5" />
                                </template>

                                <!-- Chevron Down (when closed) -->
                                <template x-if="open">
                                    <x-heroicon-o-chevron-down class="w-5 h-5" />
                                </template>
                            </span>

                        </a>

                        <div x-show="open" x-transition>
                            <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                <li>
                                    <a href="{{ route('admin.maintenance-management.equipments.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.maintenance-management.equipments.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-circle-stack class="h-5 w-5" /> Equipments
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.maintenance-management.parts.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.maintenance-management.parts.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-circle-stack class="h-5 w-5" /> Parts List
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.maintenance-management.suppliers.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.maintenance-management.suppliers.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-circle-stack class="h-5 w-5" /> Suppliers
                                    </a>
                                </li>
                               
                            </ul>
                        </div>
                    </li>


                    
                    <!-- crm -->
                    <li x-data="{ open: {{ $crmActive ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="open = !open"
                            class="menu-item group flex items-center gap-3 {{ $crmActive ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <x-heroicon-o-cog-6-tooth  class="w-6 h-6" />
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">CRM</span>
                            <span class="menu-item-arrow"
                                :class="[open ? 'menu-item-arrow-inactive' : 'menu-item-arrow-active', sidebarToggle ?
                                    'lg:hidden' : ''
                                ]">

                                <!-- Chevron Left (when open) -->
                                <template x-if="!open">
                                    <x-heroicon-o-chevron-left class="w-5 h-5" />
                                </template>

                                <!-- Chevron Down (when closed) -->
                                <template x-if="open">
                                    <x-heroicon-o-chevron-down class="w-5 h-5" />
                                </template>
                            </span>

                        </a>

                        <div x-show="open" x-transition>
                            <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                <li>
                                    <a href="{{ route('admin.crm.customer_portal.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.crm.customer_portal.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-inbox-stack class="h-5 w-5" /> Customer Portal
                                    </a>
                                </li>
                               
                            </ul>
                        </div>
                    </li>

                    @php
                    $settingsActive = Route::is([
                        'admin.terms-and-conditions.*',
                        'admin.configurations.*',
                    ]);

                    @endphp
                    <!-- Settings -->
                    <li x-data="{ open: {{ $settingsActive ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="open = !open"
                            class="menu-item group flex items-center gap-3 {{ $settingsActive ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <x-heroicon-o-cog-6-tooth  class="w-6 h-6" />
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">Settings</span>
                            <span class="menu-item-arrow"
                                :class="[open ? 'menu-item-arrow-inactive' : 'menu-item-arrow-active', sidebarToggle ?
                                    'lg:hidden' : ''
                                ]">

                                <!-- Chevron Left (when open) -->
                                <template x-if="!open">
                                    <x-heroicon-o-chevron-left class="w-5 h-5" />
                                </template>

                                <!-- Chevron Down (when closed) -->
                                <template x-if="open">
                                    <x-heroicon-o-chevron-down class="w-5 h-5" />
                                </template>
                            </span>

                        </a>

                        <div x-show="open" x-transition>
                            <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                <li>
                                    <a href="{{ route('admin.terms-and-conditions.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.terms-and-conditions.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-information-circle class="h-5 w-5" /> Terms & Conditions
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('admin.configurations.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.configurations.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-cog-8-tooth class="h-5 w-5" /> Settings
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>

            </div>
        </nav>
        <!-- Sidebar Menu -->
    </div>
</aside>
