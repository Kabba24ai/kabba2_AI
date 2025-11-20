@extends('admin.layouts.app')

@section('title', 'Equipment Inventory')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div class="flex items-center gap-2">
            <svg class=" w-7 h-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Equipment Inventory </h3>
        </div>
        <a href="#" id="reloadBtn"
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center gap-2">
            <svg id="reloadIcon" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0
                     3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1
                     13.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            Reload
        </a>
    </div>

    <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm mb-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center mb-6">
            <div class="relative w-full sm:w-48">
                <input type="text" name="search" placeholder="Search equipment, ID, or customer" value="" class="w-full h-10 rounded-md border border-gray-300 bg-white px-4 pr-10 text-sm text-gray-900 shadow-sm ">
                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
            </div>

            <div class="w-full sm:w-48">
                <select name="category" class=" w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm">
                    <option value="">All Product Categories</option>
                    <option value="1">  Accessories</option>
                    <option value="2">Attachments</option>
                    <option value="3">Boom Lifts</option>
                    <option value="4">Brush Cutting</option>
                    <option value="5">Bull Dozers</option>
                    <option value="6">Concrete</option>
                </select>
            </div>

            <div class="w-full sm:w-48">
                <select name="store" class="w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm">
                    <option value="">All Stores</option>
                    <option value="1">Bon Aqua</option>
                    <option value="2">Charlotte</option>
                </select>
            </div>
        </div>
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <div class="flex flex-wrap items-center gap-6">
                <div class="flex items-center gap-2 bg-white rounded-lg px-4 py-2 border shadow-sm flex-wrap md:flex-nowrap">
                    <svg class=" w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span class="font-medium">Equipment Status</span>
                    <label class="flex items-center gap-1 ml-2">
                        <input type="checkbox" value="Available" name="equipment_status[]" class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" checked="">
                        Available
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="checkbox" value="Rented" name="equipment_status[]" class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" checked="">
                        Rented
                    </label>
                </div>

                <div class="flex items-center gap-2 bg-white rounded-lg px-4 py-2 border shadow-sm flex-wrap md:flex-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wrench w-5 h-5 text-orange-500">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z">
                        </path>
                    </svg>
                    <span class="font-medium">Issues &amp; Maintenance </span>
                    <label class="flex items-center gap-1 ml-2">
                        <input type="checkbox" name="equipment_status[]" value="Maintenance" class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" checked="">
                        Maint. Hold
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="checkbox" name="equipment_status[]" value="Damaged" class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" checked="">
                        Damaged
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-x-auto mb-6">
        <table class="min-w-full border-collapse text-sm">
            <thead class="font-semibold bg-gray-100 text-gray-600 ">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Category</th>
                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Equipment Name</th>
                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Equip. ID</th>
                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Status</th>
                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Location</th>

                    <!-- Calendar headers -->
                    <th class="px-4 py-3 text-left font-semibold">Nov18</th>
                    <th class="px-4 py-3 text-left font-semibold">Nov19</th>
                    <th class="px-4 py-3 text-left font-semibold">Nov20</th>
                    <th class="px-4 py-3 text-left font-semibold">Nov21</th>
                    <th class="px-4 py-3 text-left font-semibold">Nov22</th>
                    <th class="px-4 py-3 text-left font-semibold">Nov24</th>
                    <th class="px-4 py-3 text-left font-semibold">Nov25</th>
                    <th class="px-4 py-3 text-left font-semibold">Nov26</th>
                    <th class="px-4 py-3 text-left font-semibold">Nov27</th>
                    <th class="px-4 py-3 text-left font-semibold">Nov28</th>
                    <th class="px-4 py-3 text-left font-semibold">Nov29</th>
                    <th class="px-4 py-3 text-left font-semibold">Nov30</th>
                    <th class="px-4 py-3 text-left font-semibold">Dec01</th>

                    <th class="px-4 py-3 text-left font-semibold">Actions</th>
                </tr>
            </thead>

            <tbody class="text-sm  whitespace-nowrap">
            
                <!-- ROW EXAMPLE -->
                <tr class="border-b border-gray-200">
                    <td class="px-4 py-4 font-semibold text-gray-700">Boom Lifts</td>
                    <td class="px-4 py-4">40' Towable</td>
                    <td class="px-4 py-4">
                        <a href="#" class="text-blue-600 uppercase">NIF-BL-2</a>
                    </td>

                    <!-- STATUS BADGE -->
                    <td class="px-4 py-4">
                        <div class="inline-flex items-center gap-1.5 whitespace-nowrap">
                            <svg class="w-4 h-4 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"></path>
                            </svg>
                            <span class="px-2 py-1 rounded-md text-xs font-medium bg-red-100 text-red-600 ">Damaged</span>
                        </div>
                    </td>

                    <td class="px-4 py-4">Bon Aqua</td>

                    <!-- Calendar blocks -->
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-red-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-red-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-red-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-red-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-red-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-red-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-red-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-red-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-blue-100 rounded text-xs flex items-center justify-center text-gray-500">0052</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-blue-100 rounded text-xs flex items-center justify-center text-gray-500">0062</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-blue-100 rounded text-xs flex items-center justify-center text-gray-500">0065</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-blue-100 rounded text-xs flex items-center justify-center text-gray-500">0052</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-blue-100 rounded text-xs flex items-center justify-center text-gray-500">0052</div></td>

                    <td class="px-4 py-4">
                        <button class="text-green-500 hover:text-green-700 text-xl">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125">
                                </path>
                            </svg>
                        </button>
                    </td>
                </tr>

                <tr class="border-b border-gray-200">
                    <td class="px-4 py-4 font-semibold text-gray-700">Boom Lifts</td>
                    <td class="px-4 py-4">40' Towable</td>
                    <td class="px-4 py-4">
                        <a href="#" class="text-blue-600 uppercase">NIF-BL-1</a>
                    </td>

                    <!-- STATUS BADGE -->
                    <td class="px-4 py-4">
                        <div class="inline-flex items-center gap-1.5 whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 1 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"></path>
                            </svg>
                            <span class="px-2 py-1 rounded-md text-xs font-medium bg-yellow-100 text-yellow-800">Maint. Hold</span>
                        </div>
                    </td>

                    <td class="px-4 py-4">Bon Aqua</td>

                    <!-- Calendar blocks -->
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-yellow-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-yellow-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-yellow-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-yellow-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-yellow-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-blue-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-blue-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-blue-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-yellow-100 rounded text-xs flex items-center justify-center text-gray-500">0052</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-yellow-100 rounded text-xs flex items-center justify-center text-gray-500">0062</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-yellow-100 rounded text-xs flex items-center justify-center text-gray-500">0065</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-yellow-100 rounded text-xs flex items-center justify-center text-gray-500">0052</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-yellow-100 rounded text-xs flex items-center justify-center text-gray-500">0052</div></td>

                    <td class="px-4 py-4">
                        <button class="text-green-500 hover:text-green-700 text-xl">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125">
                                </path>
                            </svg>
                        </button>
                    </td>
                </tr>

                <tr class="border-b border-gray-200">
                    <td class="px-4 py-4 font-semibold text-gray-700">Boom Lifts</td>
                    <td class="px-4 py-4">56' - 4X4 - Outriggers</td>
                    <td class="px-4 py-4">
                        <a href="#" class="text-blue-600 uppercase">NIF-BL-3</a>
                    </td>

                    <!-- STATUS BADGE -->
                    <td class="px-4 py-4">
                        <div class="inline-flex items-center gap-1.5 whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-8 0v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span class="px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700">Rented</span>
                        </div>
                    </td>

                    <td class="px-4 py-4">Bon Aqua</td>

                    <!-- Calendar blocks -->
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-blue-100 rounded text-xs flex items-center justify-center text-gray-500">0012</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-blue-100 rounded text-xs flex items-center justify-center text-gray-500">0062</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-blue-100 rounded text-xs flex items-center justify-center text-gray-500">0052</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-blue-100 rounded text-xs flex items-center justify-center text-gray-500">0092</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-blue-100 rounded text-xs flex items-center justify-center text-gray-500">0082</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-blue-100 rounded text-xs flex items-center justify-center text-gray-500">0072</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-blue-100 rounded text-xs flex items-center justify-center text-gray-500">0062</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-green-100 rounded text-xs flex items-center justify-center text-gray-500">0042</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-green-100 rounded text-xs flex items-center justify-center text-gray-500">0052</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-green-100 rounded text-xs flex items-center justify-center text-gray-500">0062</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-green-100 rounded text-xs flex items-center justify-center text-gray-500">0065</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-green-100 rounded text-xs flex items-center justify-center text-gray-500">0052</div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-green-100 rounded text-xs flex items-center justify-center text-gray-500">0052</div></td>

                    <td class="px-4 py-4">
                        <button class="text-green-500 hover:text-green-700 text-xl">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125">
                                </path>
                            </svg>
                        </button>
                    </td>
                </tr>

                <tr class="border-b border-gray-200">
                    <td class="px-4 py-4 font-semibold text-gray-700">Boom Lifts</td>
                    <td class="px-4 py-4">56' - 4X4 - No Outriggers</td>
                    <td class="px-4 py-4">
                        <a href="#" class="text-blue-600 uppercase">NIF-BL-3</a>
                    </td>

                    <!-- STATUS BADGE -->
                    <td class="px-4 py-4">
                        <div class="inline-flex items-center gap-1.5 whitespace-nowrap">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="w-3.5 h-3.5 text-green-600">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M8 12.5l2.5 2.5 5-5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span class="px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700">Available</span>
                        </div>
                    </td>

                    <td class="px-4 py-4">Bon Aqua</td>

                    <!-- Calendar blocks -->
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-green-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-green-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-green-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-green-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-green-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-green-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-green-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-green-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-green-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-yellow-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-yellow-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-yellow-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>
                    <td class="px-4 py-4"><div class="w-auto h-4 bg-yellow-100 rounded text-xs flex items-center justify-center text-gray-500"></div></td>

                    <td class="px-4 py-4">
                        <button class="text-green-500 hover:text-green-700 text-xl">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125">
                                </path>
                            </svg>
                        </button>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>

    <div class="bg-white shadow-sm rounded-lg  w-full mb-6">
        <h2 class="text-lg font-semibold p-5">Unassigned Orders</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse text-sm">
                <thead class="font-semibold bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Product</th>
                        <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Order</th>
                        <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Customer</th>
                        <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Company</th>
                        <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Delivery Address</th>
                        <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Phone</th>
                        <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Equipment</th>
                        <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Location</th>
                        <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Delivery Date</th>
                        <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Return Date</th>
                        <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Payment</th>
                        <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Actions</th>
                    </tr>
                </thead>

                <tbody class="text-sm">
                    <tr class="border-b border-gray-200">
                        <td class="px-4 py-3 whitespace-nowrap">Skid Steer – XL 111 Hp</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <a href="#" class="text-brand-500 underline font-bold">#0001</a>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">Gary Jezoski</td>
                        <td class="px-4 py-3 whitespace-nowrap">Development 360, Inc</td>
                        <td class="px-4 py-3 whitespace-nowrap">509 Center Ave, Dickson, Tennessee, 37055</td>
                        <td class="px-4 py-3 whitespace-nowrap">(615) 969-3989</td>
                        <td class="px-4 py-3">
                            <a href="#" class="text-blue-600 hover:underline">Assign</a>
                        </td>
                        <td class="px-4 py-3 text-gray-400">—</td>

                        <!-- Delivery Date -->
                        <td class="px-4 py-3 space-y-1 whitespace-nowrap">
                            <div class="flex items-center gap-1 text-gray-700">
                                <svg class="w-4 h-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z"></path>
                                </svg>
                                Nov 10, 25
                            </div>
                            <div class="text-xs text-gray-400">07:57 AM</div>
                        </td>

                        <!-- Return Date -->
                        <td class="px-4 py-3 space-y-1 whitespace-nowrap">
                            <div class="flex items-center gap-1 text-gray-700">
                                <svg class="w-4 h-4 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z"></path>
                                </svg>
                                Oct 08, 25
                            </div>
                            <div class="text-xs text-gray-400">07:57 AM</div>
                        </td>

                        <!-- Payment -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold bg-yellow-100 text-yellow-700 rounded-md">
                                Pending
                            </span>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3 text-gray-500 cursor-pointer">
                            <a href="#" class="text-sky-600 hover:text-sky-800" title="View" target="_blank">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                                </svg>                           
                            </a>                        
                        </td>
                    </tr>

                    <tr class="border-b border-gray-200">
                        <td class="px-4 py-3 whitespace-nowrap">Skid Steer - Forestry System</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <a href="#" class="text-brand-500 underline font-bold">#0006</a>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">Joe blow</td>
                        <td class="px-4 py-3 whitespace-nowrap">Development 360, Inc</td>
                        <td class="px-4 py-3 whitespace-nowrap">509 Center Ave, Dickson, Tennessee, 37055</td>
                        <td class="px-4 py-3 whitespace-nowrap">(615) 815-6734</td>
                        <td class="px-4 py-3">
                            <a href="#" class="text-blue-600 hover:underline">Assign</a>
                        </td>
                        <td class="px-4 py-3 text-gray-400">—</td>

                        <!-- Delivery Date -->
                        <td class="px-4 py-3 space-y-1 whitespace-nowrap">
                            <div class="flex items-center gap-1 text-gray-700">
                                 <svg class="w-4 h-4 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z"></path>
                                </svg>
                                Nov 11, 25
                            </div>
                            <div class="text-xs text-gray-400">07:57 AM</div>
                        </td>

                        <!-- Return Date -->
                        <td class="px-4 py-3 space-y-1 whitespace-nowrap">
                            <div class="flex items-center gap-1 text-gray-700">
                                <svg class="w-4 h-4 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z"></path>
                                </svg>
                                Oct 12, 25
                            </div>
                            <div class="text-xs text-gray-400">07:57 AM</div>
                        </td>

                        <!-- Payment -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold bg-green-100 text-green-700 rounded-md">
                                paid
                            </span>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3 text-gray-500 cursor-pointer">
                            <a href="#" class="text-sky-600 hover:text-sky-800" title="View" target="_blank">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                                </svg>                           
                            </a>                        
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>



@endsection

@push('js')

@endpush