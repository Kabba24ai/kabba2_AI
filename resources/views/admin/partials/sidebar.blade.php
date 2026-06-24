<aside :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'"
    class="sidebar fixed top-0 left-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-auto border-r border-gray-200 bg-white px-5 transition-all duration-300 lg:static lg:translate-x-0 dark:border-gray-800 dark:bg-black"
    @click.outside="sidebarToggle = false">
    <!-- SIDEBAR HEADER -->
    <div :class="sidebarToggle ? 'justify-center' : 'justify-between'"
        class="sidebar-header flex items-center gap-2 pt-8 pb-7">
        <a href="{{ route('admin.dashboard.index') }}">
            <span class="logo" :class="sidebarToggle ? 'hidden' : ''">
                <img class="dark:hidden w-10" src="{{ $logo }}" alt="Logo" />
                <img class="hidden dark:block w-10" src="{{ $logo }}" alt="Logo" />
            </span>

            <img class="logo-icon w-10" :class="sidebarToggle ? 'lg:block' : 'hidden'" src="{{ $logo }}"
                alt="Logo" />
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
                        'admin.product-management.equipment-assignments.*',
                    ]);

                    $ordersActive = Route::is([
                        'admin.order-management.orders.*',
                        'admin.order-management.schedules.*',
                        'admin.order-management.dispatch.*',
                        'admin.order-management.equipment-inventory.*',
                        'admin.order-management.schedule-assignment.*',
                        'admin.order-management.schedule-conflicts.*',
                        'admin.order-management.inventory-equipment.*',
                    ]);

                    $customerChecklistActive = Route::is([
                        // Add the correct route for customer checklist when ready
                        'admin.checklist-management.customer_checklist.*',
                    ]);

                    $crmActive = Route::is([
                        'admin.crm.customers.*',
                        'admin.crm.billing-summary.*',
                        'admin.crm.funnels.*',
                        'admin.crm.message-management.*',
                        'admin.crm.sales-funnels.*',
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

                    <!-- Tasks -->
                    @if (Route::has('admin.tasks.index'))
                    <li>
                        <a href="{{ route('admin.tasks.index') }}"
                            class="menu-item group flex items-center gap-3 {{ Route::is('admin.tasks.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <span class="w-6 h-6 flex items-center justify-center {{ Route::is('admin.tasks.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}">
                                <x-heroicon-o-check-circle class="w-6 h-6" />
                            </span>
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">Tasks</span>
                        </a>
                    </li>
                    @endif

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
                                        <x-heroicon-o-calendar class="h-5 w-5" /> Schedule
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('admin.order-management.dispatch.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.order-management.dispatch.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-truck class="h-5 w-5" /> Dispatch
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('admin.order-management.schedule-assignment.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.order-management.schedule-assignment.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-calendar-days class="h-5 w-5" />
                                        Schedule Assignment
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('admin.order-management.schedule-conflicts.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.order-management.schedule-conflicts.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                                        Schedule Conflicts
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('admin.order-management.equipment-inventory.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.order-management.equipment-inventory.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="lucide lucide-inventory w-5 h-5">
                                            <rect x="3" y="3" width="7" height="7" rx="1" />
                                            <path d="M3 7h7" />
                                            <rect x="14" y="3" width="7" height="7" rx="1" />
                                            <path d="M14 7h7" />
                                            <rect x="8.5" y="14" width="7" height="7" rx="1" />
                                            <path d="M8.5 18h7" />
                                        </svg> Equipment Inventory
                                    </a>
                                </li>



                                {{-- <li>
                                    <a href="{{ route('admin.order-management.inventory-equipment.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.order-management.inventory-equipment.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="lucide lucide-inventory w-5 h-5">
                                            <rect x="3" y="3" width="7" height="7" rx="1" />
                                            <path d="M3 7h7" />
                                            <rect x="14" y="3" width="7" height="7" rx="1" />
                                            <path d="M14 7h7" />
                                            <rect x="8.5" y="14" width="7" height="7" rx="1" />
                                            <path d="M8.5 18h7" />
                                        </svg> Inventory Equipment
                                    </a>
                                </li> --}}

                            </ul>
                        </div>
                    </li>

                    <!-- products -->
                    <li x-data="{ open: {{ $ecommerceActive ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="open = !open"
                            class="menu-item group flex items-center gap-3 {{ $ecommerceActive ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <x-heroicon-o-building-storefront class="w-6 h-6 " />
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">Products</span>
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

                                {{-- <li>
                                    <a href="{{ route('admin.product-management.equipment-assignments.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.product-management.equipment-assignments.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-wrench-screwdriver class="h-5 w-5" /> Equipment Assignments
                                    </a>
                                </li> --}}
                            </ul>
                        </div>
                    </li>

                    <!-- crm -->
                    <li x-data="{ open: {{ $crmActive ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="open = !open"
                            class="menu-item group flex items-center gap-3 {{ $crmActive ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <x-heroicon-o-inbox class="w-6 h-6" />
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
                                    <a href="{{ route('admin.crm.customers.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.crm.customers.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-users class="w-6 h-6" /> Customers
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.crm.billing-summary.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.crm.billing-summary.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-document-text class="w-5 h-5 mr-1" /> Billing Summary
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.crm.funnels.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.crm.funnels.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-chart-bar class="w-5 h-5 mr-1" /> Default Funnels
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('admin.crm.message-management.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.crm.message-management.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <svg class="w-5 h-5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                            aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155">
                                            </path>
                                        </svg> Message Management
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('admin.crm.sales-funnels.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.crm.sales-funnels.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-funnel class="w-5 h-5 mr-1" /> Sales Funnels
                                    </a>
                                </li>

                            </ul>
                        </div>
                    </li>

                    @php
                        // Add the correct route for reports
                        $reportsActive = Route::is(['admin.reports.*']);
                        // Add the correct route for reports
                    @endphp

                    <!-- reports -->
                    <li x-data="{ open: {{ $reportsActive ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="open = !open"
                            class="menu-item group flex items-center gap-3 {{ $reportsActive ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <x-heroicon-o-chart-bar class="w-6 h-6" /> <!-- better icon for Reports -->

                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">Reports</span>
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
                                    <a href="{{ route('admin.reports.sales-tax.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.reports.sales-tax.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-currency-dollar class="w-6 h-6" />
                                        Sales Tax
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.reports.calls-log.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.reports.calls-log.*') || Route::is('admin.reports.fuel-charge-alerts.*') || Route::is('admin.reports.new-damage-alerts.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-bell-alert class="w-6 h-6" />
                                        Dash Reports
                                    </a>
                                </li>
                                <li x-data="{ openSalesReport: {{ Route::is('admin.reports.sales-reports.*') ? 'true' : 'false' }} }">
                                    <a href="#" @click.prevent="openSalesReport = !openSalesReport"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.reports.sales-reports.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-chart-bar class="w-6 h-6" />
                                        <span class="flex-1">Sales Reports</span>
                                        <template x-if="!openSalesReport">
                                            <x-heroicon-o-chevron-left class="w-4 h-4 ml-auto" />
                                        </template>
                                        <template x-if="openSalesReport">
                                            <x-heroicon-o-chevron-down class="w-4 h-4 ml-auto" />
                                        </template>
                                    </a>
                                    <div x-show="openSalesReport" x-transition>
                                        <ul class="mt-1 flex flex-col gap-1 pl-6">
                                            <li>
                                                <a href="{{ route('admin.reports.sales-reports.pure-sales-summary.index') }}"
                                                    class="menu-dropdown-item group
                                                    {{ Route::is('admin.reports.sales-reports.pure-sales-summary.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                                    <x-heroicon-o-document-chart-bar class="w-5 h-5" />
                                                    Pure Sales Summary
                                                </a>
                                            </li>
                                            <li>
                                                <a href="{{ route('admin.reports.sales-reports.sales-trend.index') }}"
                                                    class="menu-dropdown-item group
                                                    {{ Route::is('admin.reports.sales-reports.sales-trend.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                                    <x-heroicon-o-arrow-trending-up class="w-5 h-5" />
                                                    Sales Trend
                                                </a>
                                            </li>
                                            {{-- Sales By Stores: hidden from nav, route kept --}}

                                            {{-- Product Analytics --}}
                                            <li>
                                                <a href="{{ route('admin.reports.sales-reports.product-performance.index') }}"
                                                    class="menu-dropdown-item group
                                                    {{ Route::is('admin.reports.sales-reports.product-performance.index') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                                    <x-heroicon-o-trophy class="w-5 h-5" />
                                                    Product Analytics
                                                </a>
                                            </li>

                                            {{-- Employee Performance --}}
                                            <li>
                                                <a href="{{ route('admin.reports.sales-reports.employee-performance.index') }}"
                                                    class="menu-dropdown-item group
                                                    {{ Route::is('admin.reports.sales-reports.employee-performance.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                                    <x-heroicon-o-user-group class="w-5 h-5" />
                                                    Employee Performance
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </li>

                            </ul>
                        </div>
                    </li>



                    @php

                        $maintenanceActive = Route::is([
                            'admin.maintenance-management.equipment.*',
                            'admin.maintenance-management.equipment-worksheet*',
                            'admin.maintenance-management.equipment-service.*',
                            'admin.maintenance-management.service-master.*',
                            'admin.maintenance-management.parts.*',
                            'admin.maintenance-management.suppliers.*',
                            'admin.maintenance-management.equipment-ai.*',
                        ]);

                        // Checklist Management
                        $checklistManagementActive = Route::is(['admin.checklist-management.*']);

                        $rentalReadyActive = Route::is([
                            'admin.checklist-management.rental-ready.question_and_categories.*',
                            'admin.checklist-management.rental-ready.templates.*',
                        ]);

                        $rentalReadyquestion = Route::is([
                            'admin.checklist-management.rental-ready.question_and_categories.*',
                        ]);

                        $rentalReadytemplates = Route::is(['admin.checklist-management.rental-ready.templates.*']);

                    @endphp

                    <!-- Maintenance -->
                    <li x-data="{ open: {{ $maintenanceActive ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="open = !open"
                            class="menu-item group flex items-center gap-3 {{ $maintenanceActive ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <x-heroicon-o-cog-6-tooth class="w-6 h-6" />
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
                                    <a href="{{ route('admin.maintenance-management.equipment.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.maintenance-management.equipment.*') && !Route::is('admin.maintenance-management.equipment-service.*') && !Route::is('admin.maintenance-management.equipment-worksheet*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">

                                        <x-heroicon-o-circle-stack class="h-5 w-5" /> Equipment Mgt.
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('admin.maintenance-management.equipment-ai.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.maintenance-management.equipment-ai.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-cpu-chip class="h-5 w-5" /> Equipment Mgt. AI
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('admin.maintenance-management.equipment-worksheet.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.maintenance-management.equipment-worksheet*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">

                                        <x-heroicon-o-table-cells class="h-5 w-5" /> Equipment Worksheet
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('admin.maintenance-management.equipment-service.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.maintenance-management.equipment-service.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">

                                        <x-heroicon-o-wrench class="h-5 w-5" /> Equipment Service
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('admin.maintenance-management.service-master.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.maintenance-management.service-master.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">

                                        <x-heroicon-o-wrench-screwdriver class="h-5 w-5" /> Service Master Admin
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.maintenance-management.parts.index') }}"
                                        class="menu-dropdown-item group
                                {{ Route::is('admin.maintenance-management.parts.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-circle-stack class="h-5 w-5" /> Parts
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

                    <!-- Checklist Mangement -->
                    <li x-data="{ open: {{ $checklistManagementActive ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="open = !open"
                            class="menu-item group flex items-center gap-3 {{ $checklistManagementActive ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <x-heroicon-o-clipboard-document-check class="w-6 h-6" />
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">Checklist
                                Mangement</span>
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
                                    <a href="{{ route('admin.checklist-management.equipment-management.index') }}"
                                        class="menu-dropdown-item group {{ Route::is('admin.checklist-management.equipment-management.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-wrench-screwdriver class="w-5 h-5" /> Rental Ready Mgt.
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.checklist-management.checklist-master.index') }}"
                                        class="menu-dropdown-item group {{ Route::is('admin.checklist-management.checklist-master.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-check-circle class="w-5 h-5" /> Checklist Master
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.checklist-management.rental-ready.index') }}"
                                        class="menu-dropdown-item group {{ Route::is('admin.checklist-management.rental-ready.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-truck class="h-5 w-5" /> Rental Ready Admin
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.checklist-management.customer-admin.index') }}"
                                        class="menu-dropdown-item group {{ Route::is('admin.checklist-management.customer-admin.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-s-user-group class="w-5 h-5" /> Customer Admin
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li x-data="{ open: 'false' }">
                        @php
                            $projectManagerUrl = config('app.domains.project_manager');
                            $ssoUrl = auth()->check()
                                ? \App\Helpers\SsoHelper::generateSsoUrl(auth()->user()->email, $projectManagerUrl)
                                : $projectManagerUrl;
                        @endphp
                        <a href="{{ $ssoUrl }}" target="_blank"
                            class="menu-item group flex items-center gap-3 menu-item-inactive }}">
                            <x-heroicon-o-squares-plus class="w-6 h-6" />

                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">Project
                                Manager</span>
                        </a>

                    </li>

                    @php
                        $kabbaAiAllowedHosts = ['admin.rentnking.com', 'admin.kabba.local', 'admin.kabba.ai'];
                        $showKabbaAiCustomersMenu = in_array(request()->getHost(), $kabbaAiAllowedHosts, true);
                    @endphp
                    @if ($showKabbaAiCustomersMenu)
                        <li x-data="{ open: 'false' }">
                            <a href="{{ route('admin.crm.kabba-ai-customers.index') }}"
                                class="menu-item group flex items-center gap-3 {{ Route::is('admin.crm.kabba-ai-customers.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                                <x-heroicon-o-users class="w-6 h-6" />
                                <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                    Kabba AI (Customers)
                                </span>
                            </a>
                        </li>
                    @endif




                    @php
                        $settingsActive = Route::is([
                            'admin.terms-and-conditions.*',
                            'admin.stores.*',
                            'admin.configurations.*',
                        ]);

                    @endphp

                    @php
                        $platformAdministration = Route::is('admin.hrm.*');

                        $isUsers = Route::is('admin.hrm.users.*') || Route::is('admin.hrm.*');
                        $isRoles = Route::is('admin.roles.manage.role') || Route::is('admin.roles.create.role');

                    @endphp



                    <!-- Platform Administration -->
                    <li x-data="{ open: {{ $platformAdministration ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="open = !open"
                            class="menu-item group flex items-center gap-3 {{ $platformAdministration ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <x-heroicon-o-briefcase class="w-6 h-6" />
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">HRM</span>
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
                                    <a href="{{ route('admin.hrm.users.index') }}"
                                        class="menu-dropdown-item group {{ $isUsers ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-users class="w-5 h-5" /> Users Account Settings

                                    </a>
                                </li>

                                <li>
                                    <a href="{{ config('app.domains.timetrackerpro') }}/?logout=1" target="_blank"
                                        class="menu-dropdown-item group menu-dropdown-item-inactive">
                                        <x-heroicon-o-clock class="w-5 h-5" />
                                        Time Tracker Pro
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('admin.hrm.opportunities.login.token') }}" target="_blank"
                                        class="menu-dropdown-item group menu-dropdown-item-inactive">
                                        <x-heroicon-o-briefcase class="w-5 h-5" />
                                        Employment Opportunities
                                    </a>
                                </li>


                            </ul>
                        </div>
                    </li>



                    <!-- Message Management -->
                    <?php /* <li>
                    <a href="{{ route('admin.message-management.index') }}"
                        class="menu-item group flex items-center gap-3
                            {{ Route::is('admin.message-management.*') ? 'menu-item-active' : 'menu-item-inactive' }}">

                        <span
                            class="w-6 h-6 flex items-center justify-center
                                {{ Route::is('admin.message-management.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}">
                            <x-heroicon-o-chat-bubble-left-right class="w-7 h-7" />
                        </span>

                        <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                            Message Management
                        </span>
                    </a>
                </li> */
                    ?>



                    @php

                        // New logic for Website Management active state
                        $websiteManagementActive =
                            Route::is('admin.website-management.home-page.*') ||
                            Route::is('admin.website-management.faq-page.*') ||
                            Route::is('admin.website-management.contact-us.*') ||
                            Route::is('admin.website-management.footer.*') ||
                            Route::is('admin.website-management.branding.*');
                    @endphp


                    <!-- Website Management -->
                    <li x-data="{ open: {{ $websiteManagementActive ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="open = !open"
                            class="menu-item group flex items-center gap-3 {{ $websiteManagementActive ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <x-heroicon-o-globe-alt class="w-6 h-6" />
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">Website Mgt</span>
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
                                    <a href="{{ route('admin.website-management.faq-page.index') }}"
                                        class="menu-dropdown-item group {{ Route::is('admin.website-management.faq-page.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-question-mark-circle class="w-5 h-5" /> FAQ Page
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.website-management.contact-us.index') }}"
                                        class="menu-dropdown-item group {{ Route::is('admin.website-management.contact-us.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-s-phone class="w-5 h-5" />
                                        Contact Us
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.website-management.footer.index') }}"
                                        class="menu-dropdown-item group {{ Route::is('admin.website-management.footer.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-rectangle-group class="w-5 h-5" /> Footer
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.website-management.branding.index') }}"
                                        class="menu-dropdown-item group {{ Route::is('admin.website-management.branding.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-swatch class="w-5 h-5" /> Branding
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>



                    @php
                        $tutorialsActive = Route::is('admin.tutorials.*');
                    @endphp

                    <!-- Tutorials -->
                    <li x-data="{ open: {{ $tutorialsActive ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="open = !open"
                            class="menu-item group flex items-center gap-3 {{ $tutorialsActive ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <x-heroicon-o-book-open class="w-6 h-6" />
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">Tutorials</span>
                            <span class="menu-item-arrow"
                                :class="[open ? 'menu-item-arrow-inactive' : 'menu-item-arrow-active', sidebarToggle ? 'lg:hidden' : '']">
                                <template x-if="!open">
                                    <x-heroicon-o-chevron-left class="w-5 h-5" />
                                </template>
                                <template x-if="open">
                                    <x-heroicon-o-chevron-down class="w-5 h-5" />
                                </template>
                            </span>
                        </a>
                        <div x-show="open" x-transition>
                            <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9" :class="sidebarToggle ? 'lg:hidden' : ''">
                                {{-- Video Tutorials: placeholder --}}
                                <li>
                                    <span class="menu-dropdown-item group menu-dropdown-item-inactive cursor-not-allowed opacity-50 flex items-center gap-1.5">
                                        <x-heroicon-o-play-circle class="h-5 w-5" /> Video Tutorials
                                        <span class="ml-auto text-[10px] bg-gray-100 text-gray-400 px-1.5 rounded">Soon</span>
                                    </span>
                                </li>
                                {{-- System Logic --}}
                                <li x-data="{ openLogic: {{ Route::is('admin.tutorials.system-logic.*') ? 'true' : 'false' }} }">
                                    <a href="#" @click.prevent="openLogic = !openLogic"
                                        class="menu-dropdown-item group {{ Route::is('admin.tutorials.system-logic.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-document-text class="h-5 w-5" />
                                        <span class="flex-1">System Logic</span>
                                        <template x-if="!openLogic">
                                            <x-heroicon-o-chevron-left class="w-4 h-4 ml-auto" />
                                        </template>
                                        <template x-if="openLogic">
                                            <x-heroicon-o-chevron-down class="w-4 h-4 ml-auto" />
                                        </template>
                                    </a>
                                    <div x-show="openLogic" x-transition>
                                        <ul class="mt-1 flex flex-col gap-1 pl-5">
                                            <li>
                                                <a href="{{ route('admin.tutorials.system-logic.dispatch.index') }}"
                                                    class="menu-dropdown-item group {{ Route::is('admin.tutorials.system-logic.dispatch.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                                    <x-heroicon-o-truck class="h-4 w-4" /> Dispatch
                                                </a>
                                            </li>
                                            {{-- Future modules: placeholder --}}
                                            @foreach (['Schedule', 'Parts', 'CRM', 'Equipment', 'Orders', 'Reports', 'HRM'] as $futureModule)
                                                <li>
                                                    <span class="menu-dropdown-item group menu-dropdown-item-inactive cursor-not-allowed opacity-40 flex items-center gap-1.5 text-xs">
                                                        <x-heroicon-o-ellipsis-horizontal class="h-4 w-4" /> {{ $futureModule }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Settings -->
                    <li x-data="{ open: {{ $settingsActive ? 'true' : 'false' }} }">
                        <a href="#" @click.prevent="open = !open"
                            class="menu-item group flex items-center gap-3 {{ $settingsActive ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <x-heroicon-o-cog-6-tooth class="w-6 h-6" />
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
                                    <a href="{{ route('admin.stores.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.stores.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                        <x-heroicon-o-home class="h-5 w-5" /> Stores
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('admin.configurations.index') }}"
                                        class="menu-dropdown-item group
                                        {{ Route::is('admin.configurations.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }} ">
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
