@extends('admin.layouts.app')

@section('title', 'view')

@section('content')

    @include('flash::message')


    <div class="bg-gray-50 px-4 py-4 border-b border-gray-200">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Left Section -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <!-- Back to Employees -->
                <a href="https://admin.kabba.local/roles" class="flex items-center text-gray-600 hover:text-gray-800 transition">
                    <svg class="w-5 h-5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"></path>
                    </svg>                
                    <span class="text-sm font-medium">Back to Employees</span>
                </a>

                <!-- Divider -->
                <div class="hidden sm:block h-6 border-l border-gray-300"></div>

                <!-- Customer Info -->
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"> Employee Details</h1>
                    <p class="text-sm text-gray-500">View employee information and settings</p>
                </div>
            </div>

            <!-- Right Section: Buttons -->
            <div class="flex flex-wrap gap-2">
                <form action="" method="POST" class="inline">
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                        Edit Employee
                    </button>
                </form>
            </div>
        </div>
    </div>


    <div class=" bg-white rounded-lg shadow border p-6 flex flex-col sm:flex-row  gap-4 mt-6">
    
        <!-- Profile Icon -->
        <div class="flex-shrink-0">
            <div class="w-16 h-16 bg-blue-100 text-blue-600 flex items-center justify-center rounded-full">
                <!-- Heroicon: User -->
                <x-heroicon-o-user class="w-8 h-8 text-blue-600" />
            </div>
        </div>

        <!-- Info -->
        <div class="flex-1">
            <h2 class="text-lg font-semibold text-gray-900">Michael Brown</h2>

            <div class="flex flex-wrap gap-4 text-sm text-gray-600 mt-1">
                <div class="flex items-center gap-1">
                    <x-heroicon-o-envelope class="w-4 h-4 text-gray-900" />
                    michael.brown@company.com
                </div>
                <div class="flex items-center gap-1">
                    <x-heroicon-o-phone class="w-4 h-4 text-gray-900" />
                    (555) 789-0123
                </div>
                <div class="flex items-center gap-1">
                    <x-heroicon-o-map-pin class="w-4 h-4 text-gray-900" />
                    Chicago, IL
                </div>
            </div>

            <!-- Tags -->
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Salary</span>
                <span class="px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">HR Specialist</span>
            </div>
        </div>
    </div>
    

    <div class=" grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 ">
        <!-- Personal Information -->
        <div class="bg-white p-5 rounded-lg border shadow">
            <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
                <x-heroicon-o-user class="w-5 h-5 text-blue-600" />
                Personal Information
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 text-sm text-gray-700">
                <div>
                    <p class="text-xs text-gray-500 font-medium">Name</p>
                    <p class="text-sm text-gray-900">Michael Brown</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Email Address</p>
                    <p class="text-sm text-gray-900">michael.brown@company.com</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Mobile Phone</p>
                    <p class="text-sm text-gray-900">Not provided</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Phone Number</p>
                    <p class="text-sm text-gray-900">(555) 789-0123</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs text-gray-500 font-medium">Address</p>
                    <p class="text-sm text-gray-900">
                        789 Oak Street<br />
                        Chicago, IL 60601<br />
                        USA
                    </p>
                </div>
            </div>
        </div>

        <!-- Role Assignment -->
        <div class="bg-white p-5 rounded-lg border shadow">
            <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
                <x-heroicon-o-shield-check class="w-5 h-5 text-blue-600" />
                Role Assignment
            </h2>

            <div class="bg-gray-50 border rounded-md px-4 py-3 flex items-start gap-3">
                <div class="w-3 h-3 rounded-full bg-purple-600 mt-1"></div>
                <div>
                    <p class="font-medium text-sm text-gray-900">HR Specialist</p>
                    <p class="text-sm text-gray-600">Human resources and employee data access</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
        <!-- Employment Information Card -->
        <div class="bg-white p-5 rounded-lg border shadow">
            <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-4">
                <x-heroicon-o-briefcase class="w-5 h-5 text-blue-600" />
                Employment Information
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 text-sm text-gray-700">
                <div>
                    <p class="text-xs text-gray-500 font-medium">Start Date</p>
                    <p class="text-sm text-gray-900">November 15, 2022</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">End Date</p>
                    <p class="text-sm text-gray-900">Not provided</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Status</p>
                    <p class="text-sm text-gray-900">Active</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Pay Type</p>
                    <p class="text-sm text-gray-900">Salary</p>
                </div>
            </div>
        </div>

        <!-- Time Clock Settings Card -->
        <div class="bg-white p-5 rounded-lg border shadow">
            <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-4">
                <x-heroicon-o-clock class="w-5 h-5 text-blue-600" />
                Time Clock Settings
            </h2>

            <div class="grid grid-cols-1 text-sm text-gray-700 gap-y-4">
                <!-- Clock Code -->
                <div>
                    <p class="text-xs text-gray-500 font-medium">Clock Code</p>
                    <p class="text-sm text-gray-900">34567</p>
                </div>
                <!-- Limit Times side-by-side -->
                <div class="flex flex-col sm:flex-row sm:items-start sm:gap-6 ">
                    <!-- Limit Start Time -->
                    <div class="flex-1">
                        <p class="text-xs text-gray-500 font-medium">Limit Start Time</p>
                        <p class="text-sm text-gray-900">No</p>
                    </div>

                    <!-- Limit End Time -->
                    <div class="flex-1 mt-4 md:mt-0 lg-mt-0">
                        <p class="text-xs text-gray-500 font-medium">Limit End Time</p>
                        <p class="text-sm text-gray-900">No</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
        <!-- Emergency Contact 1 -->
        <div class="bg-white p-6 rounded-lg border shadow">
            <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-4">
                <x-heroicon-o-phone class="w-5 h-5 text-blue-600" /> 
                Emergency Contact 1
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 text-sm text-gray-700">
                <div>
                    <p class="text-xs text-gray-500 font-medium">Name</p>
                    <p class="text-sm text-gray-900">Emma Brown</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Email</p>
                    <p class="text-sm text-gray-900">emma.brown@email.com</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Mobile Phone</p>
                    <p class="text-sm text-gray-900">(555) 890-1234</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Phone Number</p>
                    <p class="text-sm text-gray-900">(555) 098-7654</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs text-gray-500 font-medium">Address</p>
                    <p class="text-sm text-gray-900">
                        321 Birch Avenue<br />
                        Chicago, IL 60602<br />
                        USA
                    </p>
                </div>
            </div>
        </div>

        <!-- Emergency Contact 2 -->
        <div class="bg-white p-6 rounded-lg border shadow">
            <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-4">
                <x-heroicon-o-phone class="w-5 h-5 text-blue-600" /> 
                Emergency Contact 2
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 text-sm text-gray-700">
                <div>
                    <p class="text-xs text-gray-500 font-medium">Name</p>
                    <p class="text-sm text-gray-900">David Wilson</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Email</p>
                    <p class="text-sm text-gray-900">david.wilson@email.com</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Mobile Phone</p>
                    <p class="text-sm text-gray-900">(555) 901-2345</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Phone Number</p>
                    <p class="text-sm text-gray-900">(555) 987-6543</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs text-gray-500 font-medium">Address</p>
                    <p class="text-sm text-gray-900">
                        654 Spruce Street<br />
                        Evanston, IL 60201<br />
                        USA
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection


@push('js')



@endpush