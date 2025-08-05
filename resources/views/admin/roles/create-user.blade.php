@extends('admin.layouts.app')

@section('title', 'Roles')

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
                    <h1 class="text-2xl font-bold text-gray-900"> Add New Employee</h1>
                    <p class="text-sm text-gray-500">Create a new employee profile</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <x-heroicon-o-user class="w-5 h-5 text-blue-600" />
            Personal Information
        </h2>

        <form class="space-y-6">
            <!-- Row 1 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="firstName" class="text-xs text-gray-500 font-medium">First Name *</label>
                    <input type="text" id="firstName" placeholder="John"
                            class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
                </div>
                <div>
                    <label for="middleName" class="text-xs text-gray-500 font-medium">Middle Name</label>
                    <input type="text" id="middleName" placeholder="Michael"
                            class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
                </div>
                <div>
                    <label for="lastName" class="text-xs text-gray-500 font-medium">Last Name *</label>
                    <input type="text" id="lastName" placeholder="Smith" class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
                </div>
            </div>

            <!-- Row 2 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" id="email" placeholder="john@company.com"
                        class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
                </div>
                <div>
                    <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1">Mobile Phone</label>
                    <input type="tel" id="mobile" placeholder="(555) 123-4567"
                            class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                    <input type="tel" id="phone" placeholder="(555) 123-4567"  class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <x-heroicon-o-magnifying-glass class="w-5 h-5 text-blue-600" />
            Address Information
        </h2>

        <form class="space-y-6">
            <!-- Street Address -->
            <div class="mb-4">
                <label for="street" class="text-xs text-gray-500 font-medium">Street Address *</label>
                <input type="text" id="street" placeholder="123 Main Street"
                    class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
            </div>

            <!-- Grid for City, State, Zip Code, Country -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="city" class="text-xs text-gray-500 font-medium">City *</label>
                    <input type="text" id="city" placeholder="New York"
                            class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
                </div>
                <div>
                    <label for="state" class="text-xs text-gray-500 font-medium">State *</label>
                    <select id="state"
                            class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
                        <option value="">Select State</option>
                        <option value="NY">New York</option>
                        <option value="CA">California</option>
                        <option value="TX">Texas</option>
                    </select>
                </div>
                <div>
                    <label for="zip" class="text-xs text-gray-500 font-medium">Zip Code</label>
                    <input type="text" id="zip" placeholder="10001"
                        class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
                </div>
                <div>
                    <label for="country" class="text-xs text-gray-500 font-medium">Country *</label>
                    <input type="text" id="country" placeholder="USA"
                        class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <x-heroicon-o-briefcase class="w-5 h-5 text-blue-600" />
            Employment Information
        </h2>

        <form class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Start Date -->
            <div>
                <label for="startDate" class="text-xs text-gray-500 font-medium">Start Date *</label>
                <input
                    class="w-full pl-2 pr-2 py-2  border rounded-md  text-sm bg-white datepicker border-gray-300"
                    value=""
                    type="text"
                    name="tax_document_valid_until"
                    id="tax_document_valid_until"
                    placeholder="MM-DD-YYYY"
                    autocomplete="off"
                /> 
                <!-- <input type="date" id="startDate" class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300"> -->
            </div>

            <!-- End Date -->
            <div>
                <label for="endDate" class="text-xs text-gray-500 font-medium">End Date</label>
                <input
                    class="w-full pl-2 pr-2 py-2  border rounded-md  text-sm bg-white datepicker border-gray-300"
                    value=""
                    type="text"
                    name="tax_document_valid_until"
                    id="tax_document_valid_until"
                    placeholder="MM-DD-YYYY"
                    autocomplete="off"
                /> 
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="text-xs text-gray-500 font-medium">Status</label>
                <select id="status" class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
                    <option>Active</option>
                    <option>Inactive</option>
                </select>
            </div>

            <!-- Pay Type -->
            <div>
                <label for="payType" class="text-xs text-gray-500 font-medium">Pay Type</label>
                <select id="payType" class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
                    <option>Hourly</option>
                    <option>Salary</option>
                </select>
            </div>
        </form>
    </div>


    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <x-heroicon-o-clock class="w-5 h-5 text-blue-600" />
            Time Clock Settings
        </h2>

        <form class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Clock Code -->
            <div class="flex flex-col">
                <label for="clockCode" class="text-xs text-gray-500 font-medium">Clock Code *</label>
                <input type="text" id="clockCode" placeholder="12345"
                    class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="limitStart" class="rounded border-gray-300 focus:ring-blue-500">
                <label for="limitStart" class="flex items-center text-xs text-gray-500 font-medium cursor-pointer">
                    Limit End Time
                    <x-heroicon-o-question-mark-circle class="w-4 h-4 text-gray-400 ml-1" />
                </label>
            </div>

           <div class="flex items-center gap-2">
                <input type="checkbox" id="limitEnd" class="rounded border-gray-300 focus:ring-blue-500">
                <label for="limitEnd" class="flex items-center text-xs text-gray-500 font-medium cursor-pointer">
                    Limit End Time
                    <x-heroicon-o-question-mark-circle class="w-4 h-4 text-gray-400 ml-1" />
                </label>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <x-heroicon-o-phone class="w-5 h-5 text-blue-600" /> 
            Emergency Contact 1
        </h2>

        <form class="space-y-6">
            <!-- Name Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="text-xs text-gray-500 font-medium">First Name</label>
                    <input type="text" placeholder="Jane"
                            class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium">Middle Name</label>
                    <input type="text" placeholder="Marie"
                            class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium">Last Name</label>
                    <input type="text" placeholder="Smith"
                            class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                </div>
            </div>

            <!-- Contact Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="text-xs text-gray-500 font-medium">Email Address</label>
                    <input type="email" placeholder="jane.smith@email.com"
                            class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium">Mobile Phone</label>
                    <input type="tel" placeholder="(555) 987-6543"
                            class="w-full  pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium">Phone Number</label>
                    <input type="tel" placeholder="(555) 987-6543"
                            class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                </div>
            </div>

            <!-- Address -->
            <div class="mb-4">
                <label class="text-xs text-gray-500 font-medium">Street Address</label>
                <input type="text" placeholder="456 Oak Avenue"
                    class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
            </div>

            <!-- City, State, Zip, Country -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="text-xs text-gray-500 font-medium">City</label>
                    <input type="text" placeholder="New York"
                            class="w-full  pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium">State</label>
                    <select class="w-full  pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
                        <option>Select State</option>
                        <option>New York</option>
                        <option>California</option>
                        <option>Texas</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium">Zip Code</label>
                    <input type="text" placeholder="10001"
                            class="w-full  pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                    </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium">Country</label>
                    <input type="text" placeholder="USA"
                        class="w-full  pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <x-heroicon-o-phone class="w-5 h-5 text-blue-600" /> 
            Emergency Contact 2
        </h2>

        <form class="space-y-6">
            <!-- Name Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="text-xs text-gray-500 font-medium">First Name</label>
                    <input type="text" placeholder="Robert"
                            class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium">Middle Name</label>
                    <input type="text" placeholder="James"
                            class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium">Last Name</label>
                    <input type="text" placeholder="Johnson"
                            class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                </div>
            </div>

            <!-- Contact Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="text-xs text-gray-500 font-medium">Email Address</label>
                    <input type="email" placeholder="robert.johnson@email.com"
                            class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium">Mobile Phone</label>
                    <input type="tel" placeholder="(555) 456-7890"
                            class="w-full  pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium">Phone Number</label>
                    <input type="tel" placeholder="(555) 456-7890"
                            class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                </div>
            </div>

            <!-- Address -->
            <div class="mb-4">
                <label class="text-xs text-gray-500 font-medium">Street Address</label>
                <input type="text" placeholder="789 Pine Street"
                    class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
            </div>

            <!-- City, State, Zip, Country -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="text-xs text-gray-500 font-medium">City</label>
                    <input type="text" placeholder="Brooklyn"
                            class="w-full  pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium">State</label>
                    <select class="w-full  pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
                        <option>Select State</option>
                        <option>New York</option>
                        <option>California</option>
                        <option>Texas</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium">Zip Code</label>
                    <input type="text" placeholder="11201"
                            class="w-full  pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                    </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium">Country</label>
                    <input type="text" placeholder="USA"
                        class="w-full  pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <x-heroicon-o-shield-check class="w-5 h-5 text-blue-600" />
            Role Assignment
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Card Checkbox -->
            <label class="flex items-start gap-2 p-4 border-1 rounded-lg transition-all cursor-pointer
                            hover:shadow-sm
                            has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400
                            border-gray-300 bg-white">
                <input type="checkbox" class="mt-1 text-blue-600" checked/>
                <div>
                    <p class="font-semibold text-sm text-gray-800">Administrator</p>
                    <p class="text-sm text-gray-500">Full system access and user management</p>
                </div>
            </label>

            <label class="flex items-start gap-2 p-4 border-1 rounded-lg transition-all cursor-pointer
                            hover:shadow-sm
                            has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400
                            border-gray-300 bg-white">
                <input type="checkbox" class="mt-1 text-blue-600" />
                <div>
                    <p class="font-semibold text-sm text-gray-800">Manager</p>
                    <p class="text-sm text-gray-500">Team management and reporting access</p>
                </div>
            </label>

            <label class="flex items-start gap-2 p-4 border-1 rounded-lg transition-all cursor-pointer
                            hover:shadow-sm
                            has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400
                            border-gray-300 bg-white">
                <input type="checkbox" class="mt-1 text-blue-600"  />
                <div>
                    <p class="font-semibold text-sm text-gray-800">Supervisor</p>
                    <p class="text-sm text-gray-500">Limited management and oversight capabilities</p>
                </div>
            </label>

            <label class="flex items-start gap-2 p-4 border-1 rounded-lg transition-all cursor-pointer
                            hover:shadow-sm
                            has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400
                            border-gray-300 bg-white">
                <input type="checkbox" class="mt-1 text-blue-600" />
                <div>
                    <p class="font-semibold text-sm text-gray-800">Employee</p>
                    <p class="text-sm text-gray-500">Standard employee access to basic features</p>
                </div>
            </label>

            <label class="flex items-start gap-2 p-4 border-1 rounded-lg transition-all cursor-pointer
                            hover:shadow-sm
                            has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400
                            border-gray-300 bg-white">
                <input type="checkbox" class="mt-1 text-blue-600" />
                <div>
                    <p class="font-semibold text-sm text-gray-800">Contractor</p>
                    <p class="text-sm text-gray-500">Limited access for contract workers</p>
                </div>
            </label>

            <label class="flex items-start gap-2 p-4 border-1 rounded-lg transition-all cursor-pointer
                            hover:shadow-sm
                            has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400
                            border-gray-300 bg-white">
                <input type="checkbox" class="mt-1 text-blue-600" />
                <div>
                    <p class="font-semibold text-sm text-gray-800">HR Specialist</p>
                    <p class="text-sm text-gray-500">Human resources and employee data access</p>
                </div>
            </label>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <div class="flex justify-end space-x-3">
            <!-- Cancel Button -->
            <button type="button"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                Cancel
            </button>

            <!-- Add Employee Button -->
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                <!-- Heroicon: User Plus -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white mr-2" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h11l3 3v13a2 2 0 01-2 2z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-8H7v8m0-16V5h4v4H7z" />
                </svg>
                Add Employee
            </button>
        </div>
    </div>

@endsection


@push('js')

<script>
    window.APP_DATE_FORMAT = @json(config('app.aire_datepicker_format', 'MM/dd/yyyy'));
</script>


@endpush