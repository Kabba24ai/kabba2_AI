@extends('admin.layouts.app')

@section('title', 'Roles')

@section('content')

    @include('flash::message')

    <div class="bg-white px-4 py-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-user class="w-6 h-6 text-blue-600" />
                    <div>
                        <h2 class="text-base sm:text-lg font-semibold text-gray-900">User Account Settings</h2>
                        <p class="text-sm text-gray-600">Manage employee information and settings</p>
                    </div>
                </div>
            </div>

            <!-- Right: Action Buttons -->
            <div class="flex flex-wrap justify-right items-center gap-2">
                <button class="bg-gray-700 text-white px-4 py-2 rounded flex items-center gap-2 text-sm font-medium">
                    <x-heroicon-o-shield-check class="w-5 h-5" />
                    Manage Roles
                </button>
                <a href="{{route('admin.roles.create.user')}}" class="bg-blue-600 text-white px-4 py-2 rounded flex items-center gap-2 text-sm font-medium">
                    <x-heroicon-o-plus class="w-5 h-5" />
                    Add Employee
                </a>
            </div>
        </div>
    </div>


    <div class="container mt-6">
        <div class="bg-white border border-gray-200 rounded-md p-4 w-full">
            <div class="flex justify-left">
                <ul class="flex flex-wrap gap-2 text-sm sm:text-base font-medium">
                    <li id="filter__Roles" data-category="all" class="cursor-pointer text-sm px-4 py-2 bg-blue-600 text-gray-700  rounded ">All Roles</li>
                    <li id="filter__Administrator" data-category="administrator" class="cursor-pointer text-sm px-4 py-2 bg-red-100 text-gray-700 rounded">Administrator</li>
                    <li id="filter__Manager" data-category="manager" class="cursor-pointer text-sm px-4 py-2 bg-orange-100 text-gray-700 rounded">Manager</li>
                    <li id="filter__Supervisor" data-category="supervisor" class="cursor-pointer text-sm px-4 py-2 bg-yellow-100 text-gray-700 rounded">Supervisor</li>
                    <li id="filter__Employee" data-category="employee" class="cursor-pointer text-sm px-4 py-2 bg-green-100 text-gray-700 rounded">Employee</li>
                    <li id="filter__Contractor" data-category="contractor" class="cursor-pointer text-sm px-4 py-2 bg-indigo-100 text-gray-700 rounded">Contractor</li>
                    <li id="filter__Specialist" data-category="specialist" class="cursor-pointer text-sm px-4 py-2 bg-purple-100 text-gray-700 rounded">HR Specialist</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="bg-white border rounded-md p-4 w-full mt-6">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full">
            
            <!-- Search Field -->
            <div class="flex items-center w-full sm:w-full border rounded-md px-3 py-2 text-sm shadow-sm border-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1 0 5.64 5.64a7.5 7.5 0 0 0 10.61 10.61z" />
                </svg>
                <input
                    type="text"
                    placeholder="Search employees by name or email..."
                    class="w-full outline-none text-sm bg-transparent text-gray-700 placeholder-gray-400"
                />
            </div>

            <!-- Status Dropdown -->
          <div class="w-full sm:w-48 relative">
            <label for="status" class="sr-only">Status</label>
                <div class="flex items-center border border-gray-300 rounded-md shadow-sm px-3 py-2 bg-white text-sm text-gray-700">
                    <!-- Filter Icon -->
                    <x-heroicon-o-funnel class="w-5 h-5 text-gray-500" />
                    <!-- Dropdown -->
                    <select id="status" name="status" class="w-full bg-transparent outline-none text-gray-700 pr-4">
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            
        </div>
    </div>

    
    <div class="container  mt-6">
            <div  id="cardWrapper" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"> -->
            <!-- Card 1 -->
            <div class="tv-filter-item tv-case-study tv-case-study-show" data-category="manager">
                <div class="bg-gray-50">
                    <div class="">
                        <div class="bg-white rounded-lg shadow border p-4">
                            <div class="flex justify-between items-start">
                                <div class="flex gap-3 items-start">
                                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                                        <x-heroicon-o-user class="w-5 h-5" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-base font-semibold text-gray-800">John Michael Smith</h2>
                                        <div class="flex gap-2 mt-1 text-xs">
                                            <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full">active</span>
                                            <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">salary</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.roles.view.user') }}"><x-heroicon-o-eye class="w-4 h-4 text-grey-600" /></a>
                                    <button>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" />
                                        </svg>
                                    </button>
                                    <button><x-heroicon-o-trash class="w-4 h-4 text-gray-600" /></button>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="mt-4 text-sm border-b text-gray-700 space-y-2 mb-4">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-envelope class="w-4 h-4 text-gray-900" />
                                    john.smith@company.com
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                    (555) 987-6543 <span class="text-gray-400 mx-1">-</span> <span class="text-gray-400">(555) 123-4567</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-map-pin class="w-4 h-4 text-gray-900" />
                                    New York, NY
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-briefcase class="w-4 h-4 text-gray-900" />
                                    Clock Code: 12345
                                </div>
                                <div class="flex items-center gap-2 mb-4">
                                <x-heroicon-o-calendar class="w-4 h-4 text-gray-900" />
                                    Started: 1/15/2023
                                </div>
                            </div>

                            <!-- Roles -->
                            <div class="mt-4  flex gap-2 flex-wrap">
                                <span class="bg-red-100 text-red-800 text-xs px-3 py-1 rounded-full">Administrator</span>
                                <span class="bg-orange-100 text-orange-800 text-xs px-3 py-1 rounded-full">Manager</span>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="mt-4 pt-2 border-t text-sm text-gray-700">
                                <p class="font-semibold mb-1">Emergency Contact</p>
                                <p>Jane Smith</p>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                    (555) 876-5432
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    
            <!-- Card 2 -->
            <div class="tv-filter-item tv-case-study" data-category="administrator">
                <div class="bg-gray-50">
                    <div class="">
                        <div class="bg-white rounded-lg shadow border p-4">
                            <div class="flex justify-between items-start">
                                <div class="flex gap-3 items-start">
                                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                                        <x-heroicon-o-user class="w-5 h-5" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-base font-semibold text-gray-800">John Michael Smith</h2>
                                        <div class="flex gap-2 mt-1 text-xs">
                                            <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full">active</span>
                                            <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">salary</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button><x-heroicon-o-eye class="w-4 h-4 text-grey-600" /></button>
                                    <button>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" />
                                        </svg>
                                    </button>
                                    <button><x-heroicon-o-trash class="w-4 h-4 text-gray-600" /></button>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="mt-4 text-sm border-b text-gray-700 space-y-2 mb-4">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-envelope class="w-4 h-4 text-gray-900" />
                                    john.smith@company.com
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                    (555) 987-6543 <span class="text-gray-400 mx-1">-</span> <span class="text-gray-400">(555) 123-4567</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-map-pin class="w-4 h-4 text-gray-900" />
                                    New York, NY
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-briefcase class="w-4 h-4 text-gray-900" />
                                    Clock Code: 12345
                                </div>
                                <div class="flex items-center gap-2 mb-4">
                                <x-heroicon-o-calendar class="w-4 h-4 text-gray-900" />
                                    Started: 1/15/2023
                                </div>
                            </div>

                            <!-- Roles -->
                            <div class="mt-4  flex gap-2 flex-wrap">
                                <span class="bg-red-100 text-red-800 text-xs px-3 py-1 rounded-full">Administrator</span>
                                <span class="bg-orange-100 text-orange-800 text-xs px-3 py-1 rounded-full">Manager</span>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="mt-4 pt-2 border-t text-sm text-gray-700">
                                <p class="font-semibold mb-1">Emergency Contact</p>
                                <p>Jane Smith</p>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                    (555) 876-5432
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    
            <!-- Card 3 -->
            <div class="tv-filter-item tv-case-study" data-category="manager">
                <div class="bg-gray-50">
                    <div class="">
                        <div class="bg-white rounded-lg shadow border p-4">
                            <div class="flex justify-between items-start">
                                <div class="flex gap-3 items-start">
                                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                                        <x-heroicon-o-user class="w-5 h-5" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-base font-semibold text-gray-800">John Michael Smith</h2>
                                        <div class="flex gap-2 mt-1 text-xs">
                                            <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full">active</span>
                                            <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">salary</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button><x-heroicon-o-eye class="w-4 h-4 text-grey-600" /></button>
                                    <button>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" />
                                        </svg>
                                    </button>
                                    <button><x-heroicon-o-trash class="w-4 h-4 text-gray-600" /></button>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="mt-4 text-sm border-b text-gray-700 space-y-2 mb-4">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-envelope class="w-4 h-4 text-gray-900" />
                                    john.smith@company.com
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                    (555) 987-6543 <span class="text-gray-400 mx-1">-</span> <span class="text-gray-400">(555) 123-4567</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-map-pin class="w-4 h-4 text-gray-900" />
                                    New York, NY
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-briefcase class="w-4 h-4 text-gray-900" />
                                    Clock Code: 12345
                                </div>
                                <div class="flex items-center gap-2 mb-4">
                                <x-heroicon-o-calendar class="w-4 h-4 text-gray-900" />
                                    Started: 1/15/2023
                                </div>
                            </div>

                            <!-- Roles -->
                            <div class="mt-4  flex gap-2 flex-wrap">
                                <span class="bg-red-100 text-red-800 text-xs px-3 py-1 rounded-full">Administrator</span>
                                <span class="bg-orange-100 text-orange-800 text-xs px-3 py-1 rounded-full">Manager</span>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="mt-4 pt-2 border-t text-sm text-gray-700">
                                <p class="font-semibold mb-1">Emergency Contact</p>
                                <p>Jane Smith</p>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                    (555) 876-5432
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    
            <!-- Card 4 -->
            <div class="tv-filter-item tv-case-study" data-category="supervisor">
                <div class="bg-gray-50">
                    <div class="">
                        <div class="bg-white rounded-lg shadow border p-4">
                            <div class="flex justify-between items-start">
                                <div class="flex gap-3 items-start">
                                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                                        <x-heroicon-o-user class="w-5 h-5" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-base font-semibold text-gray-800">Sarah Johnson</h2>
                                        <div class="flex gap-2 mt-1 text-xs">
                                            <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full">active</span>
                                            <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">hourly</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button><x-heroicon-o-eye class="w-4 h-4 text-grey-600" /></button>
                                    <button>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" />
                                        </svg>
                                    </button>
                                    <button><x-heroicon-o-trash class="w-4 h-4 text-gray-600" /></button>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="mt-4 text-sm border-b text-gray-700 space-y-2 mb-4">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-envelope class="w-4 h-4 text-gray-900" />
                                    sarah.johnson@company.com
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                    (555) 987-6543 <span class="text-gray-400 mx-1">-</span> <span class="text-gray-400">(555) 123-4567</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-map-pin class="w-4 h-4 text-gray-900" />
                                    Los Angeles, CA
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-briefcase class="w-4 h-4 text-gray-900" />
                                    Clock Code: 23456
                                </div>
                                <div class="flex items-center gap-2 mb-4">
                                <x-heroicon-o-calendar class="w-4 h-4 text-gray-900" />
                                    Started: 3/1/2023
                                </div>
                            </div>

                            <!-- Roles -->
                            <div class="mt-4  flex gap-2 flex-wrap">
                                <span class="bg-yellow-100 text-yellow-800 text-xs px-3 py-1 rounded-full">Supervisor</span>
                                <span class="bg-green-100 text-green-800 text-xs px-3 py-1 rounded-full">Employee</span>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="mt-4 pt-2 border-t text-sm text-gray-700">
                                <p class="font-semibold mb-1">Emergency Contact</p>
                                <p>Mike Johnson</p>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                    (555) 210-9876
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    
            <!-- Card 5 -->
            <div class="tv-filter-item tv-case-study" data-category="employee">
                <div class="bg-gray-50">
                    <div class="">
                        <div class="bg-white rounded-lg shadow border p-4">
                            <div class="flex justify-between items-start">
                                <div class="flex gap-3 items-start">
                                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                                        <x-heroicon-o-user class="w-5 h-5" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-base font-semibold text-gray-800">Sarah Johnson</h2>
                                        <div class="flex gap-2 mt-1 text-xs">
                                            <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full">active</span>
                                            <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">hourly</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button><x-heroicon-o-eye class="w-4 h-4 text-grey-600" /></button>
                                    <button>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" />
                                        </svg>
                                    </button>
                                    <button><x-heroicon-o-trash class="w-4 h-4 text-gray-600" /></button>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="mt-4 text-sm border-b text-gray-700 space-y-2 mb-4">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-envelope class="w-4 h-4 text-gray-900" />
                                    sarah.johnson@company.com
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                    (555) 987-6543 <span class="text-gray-400 mx-1">-</span> <span class="text-gray-400">(555) 123-4567</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-map-pin class="w-4 h-4 text-gray-900" />
                                    Los Angeles, CA
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-briefcase class="w-4 h-4 text-gray-900" />
                                    Clock Code: 23456
                                </div>
                                <div class="flex items-center gap-2 mb-4">
                                <x-heroicon-o-calendar class="w-4 h-4 text-gray-900" />
                                    Started: 3/1/2023
                                </div>
                            </div>

                            <!-- Roles -->
                            <div class="mt-4  flex gap-2 flex-wrap">
                                <span class="bg-yellow-100 text-yellow-800 text-xs px-3 py-1 rounded-full">Supervisor</span>
                                <span class="bg-green-100 text-green-800 text-xs px-3 py-1 rounded-full">Employee</span>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="mt-4 pt-2 border-t text-sm text-gray-700">
                                <p class="font-semibold mb-1">Emergency Contact</p>
                                <p>Mike Johnson</p>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                    (555) 210-9876
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    
            <!-- Card 6 -->
            <div class="tv-filter-item tv-case-study " data-category="contractor">
                <div class="bg-gray-50">
                    <div class="">
                        <div class="bg-white rounded-lg shadow border p-4">
                            <div class="flex justify-between items-start">
                                <div class="flex gap-3 items-start">
                                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                                        <x-heroicon-o-user class="w-5 h-5" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-base font-semibold text-gray-800">Emily Davis</h2>
                                        <div class="flex gap-2 mt-1 text-xs">
                                            <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded-full">inactive</span>
                                            <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">hourly</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button><x-heroicon-o-eye class="w-4 h-4 text-grey-600" /></button>
                                    <button>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" />
                                        </svg>
                                    </button>
                                    <button><x-heroicon-o-trash class="w-4 h-4 text-gray-600" /></button>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="mt-4 text-sm border-b text-gray-700 space-y-2 mb-4">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-envelope class="w-4 h-4 text-gray-900" />
                                    emily.davis@company.com
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                    (555) 654-3210 <span class="text-gray-400 mx-1">-</span> <span class="text-gray-400">(555) 012-3456</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-map-pin class="w-4 h-4 text-gray-900" />
                                    Miami, FL
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-briefcase class="w-4 h-4 text-gray-900" />
                                    Clock Code: 45678
                                </div>
                                <div class="flex items-center gap-2 mb-4">
                                <x-heroicon-o-calendar class="w-4 h-4 text-gray-900" />
                                    Started: 6/1/2023
                                </div>
                            </div>

                            <!-- Roles -->
                            <div class="mt-4  flex gap-2 flex-wrap">
                                <span class="bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full">Contractor</span>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="mt-4 pt-2 border-t text-sm text-gray-700">
                                <p class="font-semibold mb-1">Emergency Contact</p>
                                <p>James Davis</p>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                    (555) 543-2109
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 7 -->
            <div class="tv-filter-item tv-case-study" data-category="specialist">
                <div class="bg-gray-50">
                    <div class="">
                        <div class="bg-white rounded-lg shadow border p-4">
                            <div class="flex justify-between items-start">
                                <div class="flex gap-3 items-start">
                                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                                        <x-heroicon-o-user class="w-5 h-5" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-base font-semibold text-gray-800">Michael Brown</h2>
                                        <div class="flex gap-2 mt-1 text-xs">
                                            <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full">active</span>
                                            <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">salary</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button><x-heroicon-o-eye class="w-4 h-4 text-grey-600" /></button>
                                    <button>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" />
                                        </svg>
                                    </button>
                                    <button><x-heroicon-o-trash class="w-4 h-4 text-gray-600" /></button>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="mt-4 text-sm border-b text-gray-700 space-y-2 mb-4">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-envelope class="w-4 h-4 text-gray-900" />
                                    michael.brown@company.com
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                    (555) 789-0123 
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-map-pin class="w-4 h-4 text-gray-900" />
                                    Chicago, IL
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-briefcase class="w-4 h-4 text-gray-900" />
                                    Clock Code: 34567
                                </div>
                                <div class="flex items-center gap-2 mb-4">
                                <x-heroicon-o-calendar class="w-4 h-4 text-gray-900" />
                                    Started: 11/15/2022
                                </div>
                            </div>

                            <!-- Roles -->
                            <div class="mt-4  flex gap-2 flex-wrap">
                                <span class="bg-purple-100 text-purple-800 text-xs px-3 py-1 rounded-full">HR Specialist</span>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="mt-4 pt-2 border-t text-sm text-gray-700">
                                <p class="font-semibold mb-1">Emergency Contact</p>
                                <p>Emma Brown</p>
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                                    (555) 098-7654
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

 


@endsection


<script>
  document.addEventListener('DOMContentLoaded', function () {
    const filterButtons = document.querySelectorAll('ul li[id^="filter__"]');
    const cards = document.querySelectorAll('.tv-filter-item');

    const roleColors = {
      administrator: ['bg-red-100'],
      manager: ['bg-orange-100'],
      supervisor: ['bg-yellow-100'],
      employee: ['bg-green-100'],
      contractor: ['bg-indigo-100'],
      specialist: ['bg-purple-100'],
      all: ['bg-gray-100']
    };

    const activeColors = {
      administrator: ['bg-red-600', 'text-white'],
      manager: ['bg-orange-600', 'text-white'],
      supervisor: ['bg-yellow-600', 'text-white'],
      employee: ['bg-green-600', 'text-white'],
      contractor: ['bg-indigo-600', 'text-white'],
      specialist: ['bg-purple-600', 'text-white'],
      all: ['bg-blue-600', 'text-white']
    };

    filterButtons.forEach(button => {
      button.addEventListener('click', () => {
        const selectedCategory = button.getAttribute('data-category') || 'all';

        // Reset all tabs
        filterButtons.forEach(btn => {
          const cat = btn.getAttribute('data-category') || 'all';

          btn.classList.remove(
            'bg-blue-600', 'bg-red-600', 'bg-orange-600', 'bg-yellow-600',
            'bg-green-600', 'bg-indigo-600', 'bg-purple-600', 'text-white',
            'bg-red-100', 'bg-orange-100', 'bg-yellow-100', 'bg-green-100',
            'bg-indigo-100', 'bg-purple-100', 'bg-gray-100',
            'text-gray-600'
          );

          // Inactive state: light bg + text-gray-600
          btn.classList.add(...(roleColors[cat] || roleColors['all']));
          btn.classList.add('text-gray-600');
        });

        // Active state: override with dark bg + white text
        button.classList.remove(...(roleColors[selectedCategory] || []));
        button.classList.remove('text-gray-600');
        button.classList.add(...(activeColors[selectedCategory] || activeColors['all']));

        // Filter items
        cards.forEach(card => {
          const cardCategory = card.getAttribute('data-category');
          if (selectedCategory === 'all' || selectedCategory === cardCategory) {
            card.classList.remove('hidden');
          } else {
            card.classList.add('hidden');
          }
        });
      });
    });

    // Trigger default
    document.getElementById('filter__Roles')?.click();
  });
</script>



