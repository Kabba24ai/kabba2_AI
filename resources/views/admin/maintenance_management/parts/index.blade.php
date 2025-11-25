@extends('admin.layouts.app')

@section('title', 'Parts Management')

@section('content')

<div class="min-h-screen bg-gray-50" x-data="{selected: 'parts'}">
    {{-- Header --}}
    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
        <div class="flex items-center space-x-4 ">
            <h2 class="text-xl font-bold text-gray-900">Equipment Management System </h2>

            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <button @click="selected = 'parts'"
                    :class="selected === 'parts' ? ' bg-blue-600 text-white ' : 'bg-gray-100 text-gray-700s'"
                    class="w-full sm:w-auto px-6 py-3 rounded text-md flex items-center gap-2"
                    type="button">
                    <svg class="h-4 w-4 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg> Parts Management
                </button>
                <button @click="selected = 'template'"
                    :class="selected === 'template' ? ' bg-blue-600 text-white ' : 'bg-gray-100 text-gray-700'"
                    class="w-full sm:w-auto px-6 py-3 rounded text-md flex items-center gap-2 "
                    type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 " fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                        <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                        <path d="M10 9H8"></path>
                        <path d="M16 13H8"></path>
                        <path d="M16 17H8"></path>
                    </svg> Template Management
                </button>
            </div>
        </div>
    </div>
    <div>

        {{-- parts --}}
        <div x-show="selected === 'parts'">
            <div class=" mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                <!-- Left Section -->
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <h1 class="text-2xl font-semibold text-gray-900">Parts Management</h1>
                    </div>
                    <p class="text-gray-600">Manage parts inventory across all equipment</p>
                </div>

                <!-- Right Buttons -->
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <!-- Manage Category -->
                    <a href="javascript:void(0)" onclick="openModal('CategoryModalWrapper')"
                        class="flex items-center text-md justify-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-3 text-sm rounded-lg transition-colors w-full sm:w-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z" />
                        </svg>
                        Manage Category
                    </a>
                </div>

            </div>


            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-5 lg:grid-cols-5 xl:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="text-left">
                            <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Total</p>
                            <p class="text-2xl font-bold text-gray-700 mt-1">{{ $parts->count() }}</p>
                        </div>
                        <svg class="h-6 w-6 text-gray-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="text-left">
                            <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">In Stock</p>
                            <p class="text-2xl font-bold text-green-600 mt-1">{{ $parts->where('stock_status', 'in-stock')->count() }}</p>
                        </div>
                        <svg class="h-6 w-6 text-green-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="text-left">
                            <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Buy Now</p>
                            <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $parts->where('stock_status', 'buy-now')->count() }}</p>
                        </div>
                        <svg class="h-6 w-6 text-yellow-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="text-left">
                            <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Out of Stock</p>
                            <p class="text-2xl font-bold text-red-600 mt-1">{{ $parts->where('stock_status', 'out-of-stock')->count() }}</p>
                        </div>
                        <svg class="h-6 w-6 text-red-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="text-left">
                            <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">DNI</p>
                            <p class="text-2xl font-bold text-gray-700 mt-1">{{ $parts->where('stock_status', 'dni')->count() }}</p>
                        </div>
                        <svg class="h-6 w-6 text-gray-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Filter Controls --}}
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 mb-6">
                <form class="flex flex-wrap gap-4 items-end">

                    {{-- Search --}}
                    <div class=" relative w-full sm:w-48">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search parts, equipment, suppliers..."
                            class="pl-9 pr-4 py-3 border border-gray-300 rounded-lg w-full text-sm">
                    </div>

                    {{-- Category Filter --}}
                    <div class="w-full sm:w-48">

                        <!-- Category Filter -->
                        <select id="partCategorys" name="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm text-gray-900">
                            <option value="">All Categories</option>
                        </select>
                    </div>

                    {{-- Category Filter --}}
                    <div class="w-full sm:w-48">

                        <select id="supplierCategory" name="category" class="w-full px-4 py-3  border border-gray-300 rounded-lg text-sm text-gray-900">
                            <option value="">All Equipment</option>
                            <option value="2">Equipment all</option>
                            <option value="6">Financing == Default</option>
                            <option value="3">Supplies</option>
                            <option value="4">tegories</option>
                        </select>
                    </div>

                    <div class="flex gap-2">

                        <a href="#" class="text-sm bg-blue-100 text-blue-700 px-4 py-3 flex gap-2 items-center rounded-lg ">
                            Quick: Supplies
                        </a>

                    </div>


                    <div class="flex gap-2">
                        <a href="#" class="text-sm text-gray-600 bg-white px-4 py-3 flex gap-2 items-center rounded-lg border border-gray-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Clear
                        </a>
                    </div>

                    <div class="flex gap-2">
                        <a href="javascript:void(0)" onclick="openSupplierModal()" class="text-sm text-gray-600 bg-white px-4 py-3 flex gap-2 items-center rounded-lg border border-gray-300">
                            <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            Supplier Search
                        </a>
                    </div>

                    <div class="flex gap-2 w-full sm:w-auto sm:ml-auto">
                        <a href="{{ route('admin.maintenance-management.parts.create') }}" class="flex-shrink-0 inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Parts
                        </a>
                    </div>
                </form>
            </div>

            <!-- table  -->

            <div id="parts-table-wrapper" class="overflow-x-auto max-w-full rounded-2xl shadow border border-gray-200 bg-white">

                @include('admin.maintenance_management.parts.partials._table', ['parts' => $parts])

            </div>
        </div>

        {{-- template --}}
        <div x-show="selected === 'template'">

            <div class="mb-6">
                <div class="flex items-center space-x-3 mb-2">

                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600 " fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                        <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                        <path d="M10 9H8"></path>
                        <path d="M16 13H8"></path>
                        <path d="M16 17H8"></path>
                    </svg>

                    <h1 class="text-2xl font-semibold text-gray-900">Parts List Templates </h1>
                </div>
                <p class="text-gray-600">Create and manage reusable parts lists for different equipment categories</p>
            </div>


            {{-- Filter Controls --}}
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 mb-6">
                <form class="flex flex-wrap items-end justify-between gap-4">

                    {{-- Left Side: Filters --}}
                    <div class="flex flex-wrap gap-4 items-end">
                        {{-- Search --}}
                        <div class="relative w-full sm:w-48">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search template..."
                                class="w-full pl-10 text-sm px-3 py-3 border border-gray-300 rounded-md">
                        </div>

                        {{-- Category Filter --}}
                        <div class="w-full sm:w-48">
                            <select id="supplierCategory" name="category"
                                class="w-full px-3 py-3 border border-gray-300 rounded-md text-sm text-gray-900">
                                <option value="">All Category</option>
                                <option value="2">Equipment all</option>
                                <option value="6">Financing == Default</option>
                                <option value="3">Supplies</option>
                                <option value="4">tegories</option>
                            </select>
                        </div>
                    </div>

                    {{-- Right Side: Action Buttons --}}
                    <div class="flex gap-2 justify-end w-full sm:w-auto">
                        <a href="{{ route('admin.maintenance-management.parts.templates.create') }}"
                            class="text-md text-white bg-blue-600  px-6 py-3 flex gap-2 items-center rounded-md hover:bg-blue-700 transition border border-gray-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create Template
                        </a>


                    </div>

                </form>
            </div>

            <!-- table -->

            <div id="supplier-table-wrapper" class="overflow-x-auto max-w-full rounded-lg shadow border border-gray-200 bg-white dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm" id="suppliers-table-wrapper">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="py-4 px-6 text-left font-semibold">Template Name</th>
                            <th class="py-4 px-6 text-left font-semibold">Category</th>
                            <th class="py-4 px-6 text-left font-semibold">Description</th>
                            <th class="py-4 px-6 text-left font-semibold ">Parts Count</th>
                            <th class="py-4 px-6 text-left font-semibold">Created By</th>
                            <th class="py-4 px-6 text-left font-semibold">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-200">

                        <tr class="hover:bg-gray-50 transition-colors">

                            <td class="py-4 px-6 whitespace-nowrap">
                                <span class="inline-flex items-center py-0.5 rounded-full text-sm font-medium  text-gray-800">
                                    Excavator Standard Maintenance
                                </span>
                                <div class="text-gray-500 text-sm">Modified: 2024-02-10</div>
                            </td>


                            <td class="py-4 px-6 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs  font-medium text-purple-800 bg-purple-200">
                                    Bulldozers
                                </span>
                            </td>

                            <td class="py-4 px-6 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full text-sm font-medium text-gray-800">
                                    John Deere 650K Dozer
                                </span>
                            </td>

                            <td class="py-4 px-6 whitespace-nowrap w-full">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-gray-800 bg-gray-200">
                                    22 Parts
                                </span>
                            </td>


                            <td class="py-4 px-6 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full text-sm font-medium text-gray-800">
                                    Raja Hindustan
                                </span>
                            </td>



                            <td class="py-4 px-6 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.maintenance-management.parts.templates.view') }}" class="text-blue-600 rounded transition-colors" title="View Details">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                                        </svg> </a>
                                    <button class="text-green-600 rounded transition-colors" title="Edit">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"></path>
                                        </svg> </button>
                                    <button class="text-red-600 rounded transition-colors" title="Delete">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                                        </svg> </button>
                                </div>
                            </td>
                        </tr>


                        <tr class="hover:bg-gray-50 transition-colors">

                            <td class="py-4 px-6 whitespace-nowrap">
                                <span class="inline-flex items-center py-0.5 rounded-full text-sm font-medium  text-gray-800">
                                    Generator Basic Service Kit
                                </span>
                                <div class="text-gray-500 text-sm">Modified: 2024-02-05</div>

                            </td>


                            <td class="py-4 px-6 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-purple-800 bg-purple-200">
                                    Generators
                                </span>
                            </td>

                            <td class="py-4 px-6 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full text-sm font-medium text-gray-800">
                                    Essential service parts for generator maintenance
                                </span>
                            </td>

                            <td class="py-4 px-6 whitespace-nowrap w-full">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-gray-800 bg-gray-200">
                                    22 parts
                                </span>
                            </td>


                            <td class="py-4 px-6 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full text-sm font-medium text-gray-800">
                                    Raja sing
                                </span>
                            </td>



                            <td class="py-4 px-6 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <button class="text-blue-600 rounded transition-colors" title="View Details">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                                        </svg> </button>
                                    <button class="text-green-600 rounded transition-colors" title="Edit">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"></path>
                                        </svg> </button>
                                    <button class="text-red-600 rounded transition-colors" title="Delete">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                                        </svg> </button>
                                </div>
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>



        </div>

    </div>
</div>

<div id="supplierModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-7xl flex flex-col max-h-full overflow-hidden border border-gray-200">

            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex items-center space-x-3"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building2 h-8 w-8 text-blue-600">
                        <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                        <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                        <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                        <path d="M10 6h4"></path>
                        <path d="M10 10h4"></path>
                        <path d="M10 14h4"></path>
                        <path d="M10 18h4"></path>
                    </svg>
                    <h2 class="text-2xl font-semibold text-gray-900">Supplier Search</h2>
                </div>
                <button onclick="closeSupplierModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <!-- Scrollable Content -->
            <div class=" overflow-y-auto max-h-[70vh]">
                <div id="supplierModalBody"></div>
            </div>
            <div class="flex justify-end gap-2 pt-4 pb-4 px-4 border-t border-gray-200">
                <button type="button" onclick="closeSupplierModal()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white">Close</button>
            </div>
        </div>
    </div>
</div>


@include('admin.maintenance_management.parts.partials._model_category')

@endsection

@push('js')

<script>
    function openSupplierModal() {
        document.getElementById('supplierModal').classList.remove('hidden');
        renderOptions();
    }

    function closeSupplierModal() {
        document.getElementById('supplierModal').classList.add('hidden');
    }
</script>


<script>
    document.addEventListener('DOMContentLoaded', () => {

        // === Common Modal Functions ===
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.remove('hidden');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('hidden');
        }


        // === Optional: Close when clicking outside modal content ===
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-wrapper')) {
                e.target.classList.add('hidden');
            }
        });

        // === Make globally accessible ===
        window.openModal = openModal;
        window.closeModal = closeModal;
    });
</script>


<script>
    const SUPPLIERS_DATA = [{
            name: "Atlas Copco",
            contact: "Emma Davis",
            phone: "(803) 817-7000",
            email: "emma.davis@atlascopco.com",
            address: "2200 Dagen Blvd, Rock Hill, SC 29730",
            parts: [{
                    name: "Air Filter Element",
                    category: "Compressors",
                    price: 67.25,
                    status: "In Stock",
                    part: "AF-2024-012",
                    id: "CMP-078"
                },
                {
                    name: "Custom Wiring Harness",
                    category: "Compressors",
                    price: 45.00,
                    status: "DNI",
                    part: "CW-2024-904",
                    id: "CMP-079"
                }
            ]
        },
        {
            name: "Caterpillar Inc.",
            contact: "Mike Johnson",
            phone: "(309) 675-1000",
            email: "mike.johnson@cat.com",
            address: "100 N.E. Adams St, Peoria, IL 61629",
            parts: [{
                name: "Hydraulic Filter",
                category: "Excavators",
                price: 45.99,
                status: "In Stock",
                part: "HF-2024-001",
                id: "EXC-045"
            }]
        },
        {
            name: "Industrial Supply Co.",
            contact: "Lisa Anderson",
            phone: "(313) 555-0123",
            email: "lisa.anderson@indsupplyco.com",
            address: "455 Industrial Blvd, Detroit, MI 48226",
            parts: []
        },
        {
            name: "John Deere",
            contact: "Robert Chen",
            phone: "(309) 765-8000",
            email: "robert.chen@deere.com",
            address: "One John Deere Pl, Moline, IL 61265",
            parts: [{
                name: "Oil Pressure Sensor",
                category: "Engines",
                price: 32.99,
                status: "In Stock",
                part: "OP-2024-018",
                id: "ENG-022"
            }]
        }
    ];

    // Keep a flag so we only mount HTML once
    let supplierModalMounted = false;

    // Called by your existing open function
    function renderOptions() {
        mountSupplierModal(); // ensure layout exists
        renderSupplierList(SUPPLIERS_DATA);
        const search = document.getElementById('supplierSearch');
        if (search) {
            search.value = '';
            search.oninput = () => {
                const term = search.value.toLowerCase();
                const filtered = SUPPLIERS_DATA.filter(s =>
                    s.name.toLowerCase().includes(term) ||
                    s.contact.toLowerCase().includes(term)
                );
                renderSupplierList(filtered);
                // reset details if nothing selected
                if (!filtered.length) {
                    document.getElementById('supplierDetails').innerHTML =
                        `<p class="text-gray-400 text-sm text-center mt-12">No suppliers match your search.</p>`;
                }
            };
        }
    }

    // Build the sidebar/details layout once
    function mountSupplierModal() {
        if (supplierModalMounted) return;
        const body = document.getElementById('supplierModalBody');
        if (!body) return;

        body.innerHTML = `
    <div class="bg-white rounded-lg w-full mx-auto">
        <div class="flex flex-col md:flex-row">
            <!-- Sidebar -->
            <div class="w-full md:w-1/3 border-r border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
                        <input id="supplierSearch" type="text" placeholder="Search suppliers..." class="w-full text-sm pl-10 px-3 py-3 border border-gray-300 rounded-lg" value="">
                    </div>
                </div>
                <ul id="supplierList" class="divide-y divide-gray-100 max-h-[40vh] overflow-y-auto"></ul>
            </div>

            <!-- Details -->
            <div id="supplierDetails" class="w-full md:w-2/3 bg-gray-50 min-h-[40vh] overflow-y-auto">
                <p class="text-gray-400 text-lg text-center mt-12">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building2 h-16 w-16 text-gray-300 mx-auto mb-4"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg>
                    Select a supplier to view parts
                </p>
            </div>
        </div>
    </div>
    `;
        supplierModalMounted = true;
    }

    // Render the sidebar list
    function renderSupplierList(list) {
        const ul = document.getElementById('supplierList');
        if (!ul) return;
        ul.innerHTML = '';

        list.forEach(s => {
            const li = document.createElement('li');
            li.className = 'p-3 cursor-pointer hover:bg-blue-50 transition-colors';
            li.innerHTML = `
        <div>
          <p class="font-medium text-gray-900">${s.name}</p>
          <div class="flex items-center text-xs text-gray-600 mb-1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user h-3 w-3 mr-1"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>${s.contact}</div>
          <div class="flex items-center text-xs text-gray-600"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone h-3 w-3 mr-1"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>${s.phone}</div>
        </div>
      `;
            li.addEventListener('click', () => {
                // highlight
                ul.querySelectorAll('li').forEach(el => el.classList.remove('bg-blue-50', 'border-l-4', 'border-blue-600', 'border-b-0'));
                li.classList.add('bg-blue-50', 'border-l-4', 'border-blue-600', 'border-b-0');
                // show details
                showSupplierDetails(s);
            });
            ul.appendChild(li);
        });
    }

    // Fill the right panel
    function showSupplierDetails(s) {
        const wrap = document.getElementById('supplierDetails');
        if (!wrap) return;

        wrap.innerHTML = `
    <div class="p-6 border-b border-gray-200 bg-gray-50">
        <div class="mb-4">
            <h3 class="text-xl font-bold text-gray-900 mb-3">${s.name}</h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm mb-4">
            <div class="flex items-start space-x-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin h-4 w-4 text-gray-500 mt-0.5 flex-shrink-0"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg><div><div class="text-gray-600 font-medium">Address</div><div class="text-gray-900">${s.address}</div></div></div>
            <div class="flex items-start space-x-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone h-4 w-4 text-gray-500 mt-0.5 flex-shrink-0"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg><div><div class="text-gray-600 font-medium">Phone</div><div class="text-gray-900">${s.phone}</div></div></div>
            <div class="flex items-start space-x-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user h-4 w-4 text-gray-500 mt-0.5 flex-shrink-0"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg><div><div class="text-gray-600 font-medium">Contact</div><div class="text-gray-900">${s.contact}</div></div></div>
            <div class="flex items-start space-x-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail h-4 w-4 text-gray-500 mt-0.5 flex-shrink-0"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg><div><div class="text-gray-600 font-medium">Email</div><div class="text-gray-900">${s.email}</div></div></div>
        </div>
    </div>

    <h4 class="font-medium text-gray-800 p-4 border-b border-gray-200 bg-white">Parts from ${s.name} (${s.parts.length})</h4>
      <div class="p-4">
        ${
          s.parts.length
            ? s.parts.map(p => `
                <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 hover:shadow-sm transition-all mb-3">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <h5 class="font-semibold text-sm text-gray-900">${p.name}</h5>
                            <p class="text-sm text-gray-600">CAT 320D Excavator <span class="text-gray-400">•</span> ${p.category}</p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border text-green-700 bg-green-100 border-green-200">In Stock</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <div class="space-y-1">
                            <div class="text-gray-600">
                                <span class="font-medium">Part #:</span>
                                <span class="font-mono">${p.part}</span>
                            </div>
                            <div class="text-gray-600">
                                <span class="font-medium">Equipment ID:</span>
                                <span class="font-mono">${p.id}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-green-600">$${p.price.toFixed(2)}</div>
                            <div class="text-xs text-gray-500 ">Stock: 15 / Min: 10</div>
                        </div>
                    </div>
                </div>
              `).join('')
            : `<p class="text-gray-400 text-sm">No parts found for this supplier.</p>`
        }
      </div>
    `;
    }

    // Your open/close remain the same, just ensure open calls renderOptions()
    function openSupplierModal() {
        document.getElementById('supplierModal').classList.remove('hidden');
        renderOptions(); // mount + render content
    }

    function closeSupplierModal() {
        document.getElementById('supplierModal').classList.add('hidden');
    }
</script>

<!-- search filter  -->

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let searchInput = document.querySelector('input[name="search"]');
        let categorySelect = document.querySelector('select[name="category"]');
        let equipmentSelect = document.querySelector('select[name="equipment_id"]');
        let wrapper = document.querySelector('#parts-table-wrapper');
        let loader = document.querySelector('#parts-loader');
        let timeout = null;

        function fetchParts() {
            const params = new URLSearchParams();

            if (searchInput.value.length >= 2 || searchInput.value.length === 0)
                params.append('search', searchInput.value);

            if (categorySelect && categorySelect.value)
                params.append('category', categorySelect.value);

            if (equipmentSelect && equipmentSelect.value)
                params.append('equipment_id', equipmentSelect.value);

            loader?.classList.remove('hidden');
            wrapper?.classList.add('opacity-50', 'pointer-events-none');

            fetch("{{ route('admin.maintenance-management.parts.index') }}?" + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {

                    console.log('data:- ');
                    console.log(data);
                    console.log('data:- ');


                    wrapper.innerHTML = data.html;
                    // document.querySelector('#parts-total-count')?.textContent = data.total;
                })
                .catch(err => {
                    console.error(err);
                    wrapper.innerHTML = '<div class="text-red-500 p-4">Error loading parts.</div>';
                })
                .finally(() => {
                    loader?.classList.add('hidden');
                    wrapper?.classList.remove('opacity-50', 'pointer-events-none');
                });
        }

        // Delayed input search
        searchInput?.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(fetchParts, 400);
        });

        // Immediate filter changes
        categorySelect?.addEventListener('change', fetchParts);
        equipmentSelect?.addEventListener('change', fetchParts);
    });
</script>






@endpush