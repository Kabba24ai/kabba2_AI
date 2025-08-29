@extends('admin.layouts.app')

@section('title', 'Customers')

@push('css')
@endpush

@section('content')

    @include('flash::message')

     <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div class="flex items-center gap-2">
            <svg class=" w-7 h-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Equipment Inventory </h3>
        </div>
        
        <a href="#" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center gap-2">
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"></path>
            </svg>  Reload
        </a>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center mb-6">

        {{-- Search Input --}}
        <div class="relative w-full sm:w-48">
            <input type="text" name="search" placeholder="Search equipment, ID, or customer" value=""
                class="w-full h-10 rounded-md border border-gray-300 bg-white px-4 pr-10 text-sm text-gray-900 shadow-sm " />
            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
            </div>
        </div>

        {{-- Select Category --}}
        <div class="w-full sm:w-48">
            <select name="category"
                class=" w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm">
                <option value="">All Category</option>
                <option value="">Boom Lifts</option>
                <option value="">Brush Cutting</option>                
            </select>
        </div>

        {{-- Select Price --}}
        <div class="w-full sm:w-48">
            <select name="price"
                class="w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm">
                <option value="">All Status</option>
                <option value="in-stock">In Stock</option>
                <option value="buy-now">Buy Now</option>
                <option value="out-of-stock">Out of Stock</option>
                <option value="dni">DNI</option>
            </select>
        </div>

        {{-- Type Dropdown --}}
        <div class="w-full sm:w-48">
            <select name="type"
                class="w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm">
                <option value="">All Stores</option>
                <option value="">Option one</option>
                <option value="">Option two</option>
            </select>
        </div>

    </div>


    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 shadow-sm mb-6">
        <div class="flex flex-wrap items-center gap-6">
            <!-- Equipment Status -->
            <div class="flex items-center gap-2 bg-white rounded-lg px-4 py-2 border shadow-sm">
                <svg class=" w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>               
                <span class="font-medium">Equipment Status</span>
                <label class="flex items-center gap-1 ml-2">
                    <input type="checkbox" value="Available" name="schedule_type[]" class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" checked="">
                    Available
                </label>
                <label class="flex items-center gap-1">
                    <input type="checkbox" value="Rented" name="schedule_type[]" class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" checked="">
                    Rented
                </label>
            </div>

            <!-- Issues & Maintenance -->
            <div class="flex items-center gap-2 bg-white rounded-lg px-4 py-2 border shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wrench w-5 h-5 text-orange-500"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                <span class="font-medium">Issues & Maintenance </span>
                <label class="flex items-center gap-1 ml-2">
                    <input type="checkbox" name="transport_mode[]" value="MaintHold" class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" checked="">
                    Maint. Hold
                </label>
                <label class="flex items-center gap-1">
                    <input type="checkbox" name="transport_mode[]" value="Damaged" class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" checked="">
                    Damaged
                </label>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg shadow border border-gray-200 bg-white">
        <!-- Desktop / large screens -->
        <div class="md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-100 text-gray-600 ">
                    <tr>
                    <th class="px-4 py-3 text-left font-semibold">Category</th>
                    <th class="px-4 py-3 text-left font-semibold">Equipment Name</th>
                    <th class="px-4 py-3 text-left font-semibold">Equip. ID</th>
                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                    <th class="px-4 py-3 text-left font-semibold">Tech / Mgt.</th>
                    <th class="px-4 py-3 text-left font-semibold">Location</th>
                    <th class="px-4 py-3 text-left font-semibold">Delivery Date</th>
                    <th class="px-4 py-3 text-left font-semibold">Return Date</th>
                    <th class="px-4 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-900">
                    <!-- ROW 1 -->
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 font-semibold  text-gray-700">Generators</td>
                        <td class="px-4 py-4 break-words">Cat 9 Ton Generator w/Cab</td>
                        <td class="px-4 py-4">
                            <a href="#" class="text-blue-600 hover:underline">CAT-9T-CAB-002</a>
                        </td>
                        <td class="px-4 py-4">
                            <div class="inline-flex items-center gap-1.5 whitespace-nowrap">
                                <!-- alert icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle w-3.5 h-3.5 text-red-600"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Damaged</span>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="font-medium">Tom Wilson</div>
                            <div class="text-xs text-gray-500">2024-05-18 · 03:15 PM</div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="inline-flex items-center gap-1">
                                <svg class="w-3 h-3 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"></path>
                                </svg>
                            Bon Aqua Shop
                            </div>
                        </td>
                        <td class="px-4 py-4">-</td>
                        <td class="px-4 py-4">-</td>
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-end gap-3">
                                <button class="p-1.5 rounded text-green-600" aria-label="Edit">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"></path>
                                    </svg>
                                </button>
                                <button class="p-1.5 rounded text-red-600" aria-label="Delete">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- ROW 2 -->
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 font-semibold  text-gray-700">Chippers</td>
                        <td class="px-4 py-4 break-words">Bandit 12&quot; Wood Chipper</td>
                        <td class="px-4 py-4">
                            <a href="#" class="text-blue-600 hover:underline">BAN-CH-12-003</a>
                        </td>
                        <td class="px-4 py-4">
                            <div class="inline-flex items-center gap-1.5 whitespace-nowrap">
                                <!-- alert icon -->
                                 <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 1 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/>
                                </svg>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">  Maint. Hold</span>
                            </div>

                        </td>
                        <td class="px-4 py-4">
                            <div class="font-medium">Mike Johnson</div>
                            <div class="text-xs text-gray-500">2024-05-15 · 11:45 AM</div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="inline-flex items-center gap-1">
                                <svg class="w-3 h-3 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"></path>
                                </svg>
                                </span>Charlotte Shop
                            </div>
                        </td>
                        <td class="px-4 py-4">-</td>
                        <td class="px-4 py-4">-</td>
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-end gap-3">
                                 <button class="p-1.5 rounded text-green-600" aria-label="Edit">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"></path>
                                    </svg>
                                </button>
                                <button class="p-1.5 rounded text-red-600" aria-label="Delete">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- ROW 3 (Rented example) -->
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 font-semibold text-gray-700">Excavators</td>
                        <td class="px-4 py-4 break-words">Takeuchi TL12 Skid Steer</td>
                        <td class="px-4 py-4">
                            <a href="#" class="text-blue-600 hover:underline">TAK-SS-5</a>
                        </td>
                        <td class="px-4 py-4">
                            <div class="inline-flex items-center gap-1.5 whitespace-nowrap">
                                <!-- alert icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-8 0v2"/><circle cx="12" cy="7" r="4"/>
                                </svg>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Rented</span>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="font-medium">Mike Johnson</div>
                            <div class="text-xs text-gray-500">2024-05-07 · 09:15 AM</div>
                        </td>
                        <td class="px-4 py-4">
                            <a href="#" class="text-blue-600 hover:underline">Jerry Verner</a>
                        </td>
                        <td class="px-4 py-4">
                            <div class="inline-flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M8 2v4M16 2v4M3 10h18M5 22h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
                            </svg>
                            May 07
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="inline-flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M8 2v4M16 2v4M3 10h18M5 22h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
                            </svg>
                            May 14
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-end gap-3">
                                <button class="p-1.5 rounded text-green-600" aria-label="Edit">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"></path>
                                    </svg>
                                </button>
                                <button class="p-1.5 rounded text-red-600" aria-label="Delete">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>


    <!-- <div id="customer-table-wrapper">
        <div class="overflow-x-auto rounded-lg shadow border border-gray-200 bg-white dark:bg-gray-900">
            <div id="customer-loader" class="hidden"></div>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                    <tr>
                    <th class="cus-width-20 px-4 py-3 text-left font-semibold">Category</th>
                    <th class="cus-width-20 px-4 py-3 text-left font-semibold">Equipment Name</th>
                    <th class="cus-width-15 px-4 py-3 text-left font-semibold">Equip. ID</th>
                    <th class="cus-width-15 px-4 py-3 text-left font-semibold">Status</th>
                    <th class="cus-width-10 px-4 py-3 text-left font-semibold">Tech/Mgt.</th>
                    <th class="cus-width-10 px-4 py-3 text-left font-semibold">Location</th>
                    <th class="cus-width-10 px-4 py-3 text-left font-semibold">Delivery Date</th>
                    <th class="cus-width-10 px-4 py-3 text-left font-semibold">Return Date</th>
                    <th class="cus-width-10 px-4 py-3 text-left font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-900 dark:text-gray-100">
                    <tr id="customer-row-CUS-SELP-J1MC">
                    <td class="px-4 py-3">
                        <input type="checkbox" class="customer-checkbox" value="CUS-SELP-J1MC">
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium">nipa soni</div>
                    </td>
                    <td class="px-4 py-3">
                        ABCRentals
                        <a>
                        </a>
                    </td>
                    <td class="px-4 py-3  whitespace-nowrap">
                        <div class="flex items-center gap-1">
                            <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                            </svg>
                            <span> (908) 922-2323
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-3 inline-flex whitespace-nowrap">
                        <span class="items-center gap-2 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 px-2 py-0.5 text-xs font-medium">
                        Good Standing
                        </span>
                        <span class="items-center gap-2 px-2 py-1">
                            <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"></path>
                            </svg>
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-block rounded-full bg-blue-100 text-blue-600 px-2 py-0.5 text-xs font-medium">
                        3 orders</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        ₹15,372.20
                    </td>
                    <td class="px-4 py-3 space-x-2 whitespace-nowrap">
                        <a href="https://admin.kabba.local/crm/customers/CUS-SELP-J1MC/view">
                            <button class="text-blue-600 hover:text-blue-800" title="View">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                                </svg>
                            </button>
                        </a>
                        <form action="https://admin.kabba.local/crm/customers/CUS-SELP-J1MC" method="POST" class="inline delete-customer-form" data-customer-name="nipa soni">
                            <input type="hidden" name="_token" value="LjRrqHLx01kaIJxylqFoUUZfp1H3OqNO5MLprDO9" autocomplete="off">                            <input type="hidden" name="_method" value="DELETE">                            
                            <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                                </svg>
                            </button>
                        </form>
                    </td>
                    </tr>
                
                </tbody>
            </table>
        </div>
        <div class="mt-6">
            <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
                <div class="flex justify-between flex-1 sm:hidden">
                    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md dark:text-gray-600 dark:bg-gray-800 dark:border-gray-600">
                    « Previous
                    </span>
                    <a href="https://admin.kabba.local/crm/customers?page=2" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-gray-700 dark:active:text-gray-300">
                    Next »
                    </a>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                    <p class="text-sm text-gray-700 leading-5 dark:text-gray-400">
                        Showing
                        <span class="font-medium">1</span>
                        to
                        <span class="font-medium">10</span>
                        of
                        <span class="font-medium">13</span>
                        results
                    </p>
                    </div>
                    <div>
                    <span class="relative z-0 inline-flex rtl:flex-row-reverse shadow-sm rounded-md">
                        <span aria-disabled="true" aria-label="&amp;laquo; Previous">
                            <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-l-md leading-5 dark:bg-gray-800 dark:border-gray-600" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </span>
                        </span>
                        <span aria-current="page">
                        <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 dark:bg-gray-800 dark:border-gray-600">1</span>
                        </span>
                        <a href="https://admin.kabba.local/crm/customers?page=2" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-500 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:text-gray-300 dark:active:bg-gray-700 dark:focus:border-blue-800" aria-label="Go to page 2">
                        2
                        </a>
                        <a href="https://admin.kabba.local/crm/customers?page=2" rel="next" class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md leading-5 hover:text-gray-400 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:active:bg-gray-700 dark:focus:border-blue-800" aria-label="Next &amp;raquo;">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </a>
                    </span>
                    </div>
                </div>
            </nav>
        </div>
    </div> -->
    



@endsection

@push('js')


@endpush