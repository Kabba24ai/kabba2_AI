
    <div class="bg-white p-3 md:p-5 rounded-lg shadow-sm border mt-6">
        <!-- Section Title -->
        <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-600" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 3l8.485 4.243A2 2 0 0121 8.944V13a9 9 0 01-18 0V8.944a2 2 0 01.515-1.701L12 3z" />
            </svg>
            Role Information
        </h2>

        <!-- Form Fields -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Role Name -->
            <div class="col-span-1">
                <label class="text-xs text-gray-500 font-medium">Role Name *</label>
                <input type="text" placeholder="e.g., Manager, Supervisor, Employee"
                    class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300" />
            </div>

            <!-- Role Color -->
            <div class="col-span-1">
                <label class="block text-xs text-gray-500 font-medium">Role Color *</label>
                <div class="flex items-center gap-2 mt-2">
                <!-- Color Preview -->
                <div id="colorPreview" class="w-8 h-8 rounded-md border border-gray-300" style="background-color: #3b82f6;"></div>
                    <!-- Dropdown -->
                    <select id="colorSelect"
                            class="flex-1 pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-300">
                        <option value="#3b82f6" selected>Blue</option>
                        <option value="#10b981">Green</option>
                        <option value="#f59e0b">Amber</option>
                        <option value="#ef4444">Red</option>
                        <option value="#8b5cf6">Purple</option>
                        <option value="#6b7280">Gray</option>
                    </select>
                </div>
            </div>

            <!-- Description -->
            <div class="col-span-1 sm:col-span-2">
                <label class="text-xs text-gray-500 font-medium">Description *</label>
                <textarea rows="3" placeholder="Describe the role's responsibilities and scope..."
                        class="w-full pl-2 pr-2 py-2 w-full border rounded-md text-sm border-gray-3003 py-2"></textarea>
            </div>
        </div>
    </div>

    <div class="pointer-events-none opacity-50">
        <div class="bg-white border shadow-sm rounded-lg p-3 md:p-5 mt-6">
            <!-- Section Header -->
            <h2 class="text-base font-semibold text-gray-800 flex items-center mb-4 gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-600" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 3l8.485 4.243A2 2 0 0121 8.944V13a9 9 0 01-18 0V8.944a2 2 0 01.515-1.701L12 3z" />
                </svg>
                Permissions <span class="text-gray-400 font-normal">(4 selected) </span> <span class="text-grey-900">(This module is static. We will work in future.)</span>
            </h2>

            <div class="bg-white border shadow-sm rounded-lg p-5 mt-6">
                <!-- Group Header -->
                <div class="bg-blue-50 text-blue-700 font-medium px-3 py-2 rounded mb-4 flex items-center gap-2">
                    <x-heroicon-o-users class="w-4 h-4" />
                    User Management <span class="text-sm font-normal">(5 permissions)</span>
                </div>

                <!-- Permission Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- Permission Card -->
                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">View Users</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">read</span>
                            </div>
                            <p class="text-sm text-gray-500">View employee profiles and basic information</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">Create Users</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">write</span>
                            </div>
                            <p class="text-sm text-gray-500">Add new employees to the system</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">Edit Users</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">write</span>
                            </div>
                            <p class="text-sm text-gray-500">Modify employee information and settings</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">Delete Users</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700">admin</span>
                            </div>
                            <p class="text-sm text-gray-500">Remove employees from the system</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">Manage User Roles</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700">admin</span>
                            </div>
                            <p class="text-sm text-gray-500">Assign and modify user roles and permissions</p>
                        </div>
                    </label>

                </div>
            </div>

            <div class="bg-white border shadow-sm rounded-lg p-5 mt-6">
                <!-- Group Header -->
                <div class="bg-green-50 text-green-700 font-medium px-3 py-2 rounded mb-4 flex items-center gap-2">
                    <x-heroicon-o-document-text class="w-4 h-4" />
                    Reporting <span class="text-sm font-normal">(4 permissions)</span>
                </div>

                <!-- Permission Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- Permission Card -->
                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">View Reports</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">read</span>
                            </div>
                            <p class="text-sm text-gray-500">Access and view system reports</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800"> Create Reports</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">write</span>
                            </div>
                            <p class="text-sm text-gray-500">Generate custom reports and analytics</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">Export Reports</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">write</span>
                            </div>
                            <p class="text-sm text-gray-500">Export reports to various formats (PDF, Excel, etc.)</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800"> Schedule Reports</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700">admin</span>
                            </div>
                            <p class="text-sm text-gray-500">Set up automated report generation and delivery</p>
                        </div>
                    </label>

                </div>
            </div>

            <div class="bg-white border shadow-sm rounded-lg p-5 mt-6">
                <!-- Group Header -->
                <div class="bg-red-50 text-red-700 font-medium px-3 py-2 rounded mb-4 flex items-center gap-2">
                    <x-heroicon-o-cog class="w-4 h-4" />
                    System <span class="text-sm font-normal">(4 permissions)</span>
                </div>

                <!-- Permission Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Permission Card -->
                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">System Settings</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700">admin</span>
                            </div>
                            <p class="text-sm text-gray-500">Access and modify system configuration</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">System Backup</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700">admin</span>
                            </div>
                            <p class="text-sm text-gray-500">Create and manage system backups</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">View System Logs</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">read</span>
                            </div>
                            <p class="text-sm text-gray-500">Access system logs and audit trails</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">System Maintenance</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700">admin</span>
                            </div>
                            <p class="text-sm text-gray-500">Perform system maintenance tasks</p>
                        </div>
                    </label>

                </div>
            </div>

            <div class="bg-white border shadow-sm rounded-lg p-5 mt-6">
                <!-- Group Header -->
                <div class="bg-purple-50 text-purple-700 font-medium px-3 py-2 rounded mb-4 flex items-center gap-2">
                    <x-heroicon-o-clock class="w-4 h-4" />
                    Time clock <span class="text-sm font-normal">(4 permissions)</span>
                </div>

                <!-- Permission Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Permission Card -->
                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">View Time Records</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">read</span>
                            </div>
                            <p class="text-sm text-gray-500">View employee time clock records</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">Edit Time Records</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">write</span>
                            </div>
                            <p class="text-sm text-gray-500">Modify employee time clock entries</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">Approve Timesheets</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">write</span>
                            </div>
                            <p class="text-sm text-gray-500">Approve or reject employee timesheets</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800"> Time Clock Settings</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700">admin</span>
                            </div>
                            <p class="text-sm text-gray-500">Configure time clock rules and policies</p>
                        </div>
                    </label>

                </div>
            </div>

            <div class="bg-white border shadow-sm rounded-lg p-5 mt-6">
                <!-- Group Header -->
                <div class="bg-yellow-50 text-yellow-700 font-medium px-3 py-2 rounded mb-4 flex items-center gap-2">
                    <x-heroicon-o-currency-dollar class="w-4 h-4" />
                    Payroll <span class="text-sm font-normal">(4 permissions)</span>
                </div>

                <!-- Permission Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Permission Card -->
                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">View Payroll</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">read</span>
                            </div>
                            <p class="text-sm text-gray-500">Access payroll information and records</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">Process Payrolls</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">write</span>
                            </div>
                            <p class="text-sm text-gray-500">Run payroll calculations and processing</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">Approve Payroll</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700">admin</span>
                            </div>
                            <p class="text-sm text-gray-500">Final approval for payroll processing</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">Payroll Reports </p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">read</span>
                            </div>
                            <p class="text-sm text-gray-500">Generate and access payroll reports</p>
                        </div>
                    </label>

                </div>
            </div>

            <div class="bg-white border shadow-sm rounded-lg p-5 mt-6">
                <!-- Group Header -->
                <div class="bg-blue-50 text-blue-700 font-medium px-3 py-2 rounded mb-4 flex items-center gap-2">
                    <x-heroicon-o-user class="w-4 h-4" />
                    Hr <span class="text-sm font-normal">(4 permissions)</span>
                </div>

                <!-- Permission Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Permission Card -->
                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">HR Documents </p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">write</span>
                            </div>
                            <p class="text-sm text-gray-500">Access and manage employee documents</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800">Manage Benefits</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">write</span>
                            </div>
                            <p class="text-sm text-gray-500">Administer employee benefits and enrollment</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800"> Performance Reviews </p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">write</span>
                            </div>
                            <p class="text-sm text-gray-500">Conduct and manage employee performance reviews</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 border rounded-md p-4 hover:shadow-sm cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400">
                        <input type="checkbox" class="mt-1 text-blue-600" disabled />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-sm text-gray-800"> HR Compliance</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700">admin</span>
                            </div>
                            <p class="text-sm text-gray-500">Manage HR compliance and regulatory requirements</p>
                        </div>
                    </label>

                </div>
            </div>
        </div>

    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mt-6">
        <div class="flex justify-end space-x-3">
            <!-- Cancel Button -->
            <button type="button" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                Cancel
            </button>

            <!-- Add Employee Button -->
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                <!-- Heroicon: User Plus -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h11l3 3v13a2 2 0 01-2 2z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-8H7v8m0-16V5h4v4H7z"></path>
                </svg>
                Create Role
            </button>
        </div>
    </div>