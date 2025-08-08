@extends('admin.layouts.app')

@section('title', 'view')

@section('content')

    @include('flash::message')

    <div class="border-b pb-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <!-- Left: Title and Subtitle -->
            <div class="flex items-start sm:items-center gap-3">
                <div class="text-blue-600">
                    <x-heroicon-o-shield-check class="w-6 h-6" />
                </div>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Role Management</h1>
                    <p class="text-sm text-gray-600">Manage roles and permissions</p>
                </div>
            </div>

            <!-- Right: Actions -->
            <!-- <div class="flex flex-wrap items-center justify-end gap-2">
                <a href="https://admin.kabba.local/roles" class="text-sm font-medium text-gray-600 hover:text-gray-800">Back to Employees</a>
                <button class="px-4 py-2 bg-purple-600 text-white rounded-md text-sm font-medium hover:bg-purple-700 transition">
                    Package Demo
                </button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium flex items-center gap-1 hover:bg-blue-700 transition">
                    <x-heroicon-o-plus class="w-4 h-4 text-white" />
                    Add Role
                </button>
            </div> -->
            <div class="flex flex-col sm:flex-row flex-wrap items-center justify-end gap-2">
                <!-- Back link -->
                <a href="https://admin.kabba.local/roles"
                    class="text-sm font-medium text-gray-600 hover:text-gray-800">
                    Back to Employees
                </a>
                <!-- Package Demo Button -->
                <button
                    class="w-full sm:w-auto px-4 py-2 bg-purple-600 text-white rounded-md text-sm font-medium hover:bg-purple-700 transition">
                    Package Demo
                </button>
                <!-- Add Role Button -->
                <a href="{{ route('admin.roles.create.role') }}"
                    class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium flex items-center justify-center gap-1 hover:bg-blue-700 transition">
                    <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Add Role
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
        <!-- Role Card: Administrator -->
        <div class="bg-white rounded-lg shadow-sm p-5">
            <div class="max-w-xl mx-auto mb-6">
                <!-- Role Header Row -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-red-100 rounded-full">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3l8.485 4.243A2 2 0 0121 8.944V13a9 9 0 01-18 0V8.944a2 2 0 01.515-1.701L12 3z" />
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Administrator</h3>
                            <div class="flex items-center text-xs text-gray-500 gap-1 mt-0.5">
                                <x-heroicon-o-users class="w-3 h-3 text-gray-500" />
                                <span>1 user •</span>
                                <span>4 permissions</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Edit/Delete Icons -->
                    <div class="flex gap-2 text-gray-500">
                        <a href="#" title="Edit">
                            <x-heroicon-o-pencil-square class="w-4 h-4 text-gray-600" />
                        </a>
                        <a href="#" title="Delete">
                            <x-heroicon-o-trash class="w-4 h-4 text-gray-600" />
                        </a>
                    </div>
                </div>
            </div>

            <p class="text-sm text-gray-700 mb-4">Full system access and user management</p>
            <span class="inline-block text-xs px-3 py-1 rounded-full bg-red-100 text-red-700 font-medium">
                Administrator
            </span>
        </div>

        <!-- Role Card: Manager -->
        <div class="bg-white rounded-lg shadow-sm p-5">
            <div class="max-w-xl mx-auto mb-6">
                <!-- Role Header Row -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-orange-100 rounded-full">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3l8.485 4.243A2 2 0 0121 8.944V13a9 9 0 01-18 0V8.944a2 2 0 01.515-1.701L12 3z" />
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Manager</h3>
                            <div class="flex items-center text-xs text-gray-500 gap-1 mt-0.5">
                                <x-heroicon-o-users class="w-3 h-3 text-gray-500" />
                                <span>1 user •</span>
                                <span>3 permissions</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Edit/Delete Icons -->
                    <div class="flex gap-2 text-gray-500">
                        <a href="#" title="Edit">
                            <x-heroicon-o-pencil-square class="w-4 h-4 text-gray-600" />
                        </a>
                        <a href="#" title="Delete">
                            <x-heroicon-o-trash class="w-4 h-4 text-gray-600" />
                        </a>
                    </div>
                </div>
            </div>
            <p class="text-sm text-gray-700 mb-4">Team management and reporting access</p>
            <span class="inline-block text-xs px-3 py-1 rounded-full bg-orange-100 text-orange-700 font-medium">
                Manager
            </span>
        </div>

        <!-- Role Card: Supervisor -->
        <div class="bg-white rounded-lg shadow-sm p-5">
            <div class="max-w-xl mx-auto mb-6">
                <!-- Role Header Row -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-yellow-100 rounded-full">
                            <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3l8.485 4.243A2 2 0 0121 8.944V13a9 9 0 01-18 0V8.944a2 2 0 01.515-1.701L12 3z" />
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Supervisor</h3>
                            <div class="flex items-center text-xs text-gray-500 gap-1 mt-0.5">
                                <x-heroicon-o-users class="w-3 h-3 text-gray-500" />
                                <span>1 user •</span>
                                <span>3 permissions</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Edit/Delete Icons -->
                    <div class="flex gap-2 text-gray-500">
                        <a href="#" title="Edit">
                            <x-heroicon-o-pencil-square class="w-4 h-4 text-gray-600" />
                        </a>
                        <a href="#" title="Delete">
                            <x-heroicon-o-trash class="w-4 h-4 text-gray-600" />
                        </a>
                    </div>
                </div>
            </div>
            <p class="text-sm text-gray-700 mb-4">Limited management and oversight capabilities</p>
            <span class="inline-block text-xs px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 font-medium">
                Supervisor
            </span>
        </div>
    </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
        <!-- Role Card: Employee -->
        <div class="bg-white rounded-lg shadow-sm p-5">
            <div class="max-w-xl mx-auto mb-6">
                <!-- Role Header Row -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-100 rounded-full">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3l8.485 4.243A2 2 0 0121 8.944V13a9 9 0 01-18 0V8.944a2 2 0 01.515-1.701L12 3z" />
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Employee</h3>
                            <div class="flex items-center text-xs text-gray-500 gap-1 mt-0.5">
                                <x-heroicon-o-users class="w-3 h-3 text-gray-500" />
                                <span>1 user •</span>
                                <span>2 permissions</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Edit/Delete Icons -->
                    <div class="flex gap-2 text-gray-500">
                        <a href="#" title="Edit">
                            <x-heroicon-o-pencil-square class="w-4 h-4 text-gray-600" />
                        </a>
                        <a href="#" title="Delete">
                            <x-heroicon-o-trash class="w-4 h-4 text-gray-600" />
                        </a>
                    </div>
                </div>
            </div>

            <p class="text-sm text-gray-700 mb-4">Standard employee access to basic features</p>
            <span class="inline-block text-xs px-3 py-1 rounded-full bg-green-100 text-green-700 font-medium">
                Employee
            </span>
        </div>

        <!-- Role Card: Contractor -->
        <div class="bg-white rounded-lg shadow-sm p-5">
            <div class="max-w-xl mx-auto mb-6">
                <!-- Role Header Row -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 rounded-full">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3l8.485 4.243A2 2 0 0121 8.944V13a9 9 0 01-18 0V8.944a2 2 0 01.515-1.701L12 3z" />
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Contractor</h3>
                            <div class="flex items-center text-xs text-gray-500 gap-1 mt-0.5">
                                <x-heroicon-o-users class="w-3 h-3 text-gray-500" />
                                <span>1 user •</span>
                                <span>1 permissions</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Edit/Delete Icons -->
                    <div class="flex gap-2 text-gray-500">
                        <a href="#" title="Edit">
                            <x-heroicon-o-pencil-square class="w-4 h-4 text-gray-600" />
                        </a>
                        <a href="#" title="Delete">
                            <x-heroicon-o-trash class="w-4 h-4 text-gray-600" />
                        </a>
                    </div>
                </div>
            </div>
            <p class="text-sm text-gray-700 mb-4">Limited access for contract workers</p>
            <span class="inline-block text-xs px-3 py-1 rounded-full bg-blue-100 text-blue-700 font-medium">
                Contractor
            </span>
        </div>

        <!-- Role Card: HR Specialist -->
        <div class="bg-white rounded-lg shadow-sm p-5">
            <div class="max-w-xl mx-auto mb-6">
                <!-- Role Header Row -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-100 rounded-full">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3l8.485 4.243A2 2 0 0121 8.944V13a9 9 0 01-18 0V8.944a2 2 0 01.515-1.701L12 3z" />
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">HR Specialist</h3>
                            <div class="flex items-center text-xs text-gray-500 gap-1 mt-0.5">
                                <x-heroicon-o-users class="w-3 h-3 text-gray-500" />
                                <span>1 user •</span>
                                <span>3 permissions</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Edit/Delete Icons -->
                    <div class="flex gap-2 text-gray-500">
                        <a href="#" title="Edit">
                            <x-heroicon-o-pencil-square class="w-4 h-4 text-gray-600" />
                        </a>
                        <a href="#" title="Delete">
                            <x-heroicon-o-trash class="w-4 h-4 text-gray-600" />
                        </a>
                    </div>
                </div>
            </div>
            <p class="text-sm text-gray-700 mb-4">Human resources and employee data access</p>
            <span class="inline-block text-xs px-3 py-1 rounded-full bg-purple-100 text-purple-600 font-medium">
                HR Specialist
            </span>
        </div>
    </div>
    

@endsection


@push('js')



@endpush