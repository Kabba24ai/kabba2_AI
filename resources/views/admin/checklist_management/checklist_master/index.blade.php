@extends('admin.layouts.app')

@section('title', 'checklist master')

@push('css')

@endpush

@section('content')

    @include('flash::message')

    <div class="bg-white">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            
            <!-- Left: Title and Subtitle -->
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">Checklist Systems</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Independent checklist systems that can be assigned to multiple equipment items
                </p>
            </div>

            <!-- Right: Action Button -->
            <div>
                <a href="{{ route('admin.checklist_management.checklist-master.create') }}"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                    <!-- Plus Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Create New Checklist System
                </a>
            </div>

        </div>
    </div>


<!-- Filters -->
<div class="bg-white border border-gray-200 rounded-md p-4 w-full mt-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
            <label class="text-sm font-medium text-gray-700 mb-1 block">Search Systems</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                    </svg>
                </div>
                <input type="text" placeholder="Search checklist systems..." class="w-full border pl-10 pr-3 py-2 rounded-md text-sm" />
            </div>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700 mb-1 block">Filter by Category</label>
            <select id="categoryFilter" class="w-full border px-3 py-2 rounded-md text-sm">
                <option value="">All Categories</option>
                <option value="Heavy Equipment">Heavy Equipment</option>
                <option value="Compact Equipment">Compact Equipment</option>
                <option value="Power Equipment">Power Equipment</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700 mb-1 block">Results</label>
            <div class="border rounded-md px-3 py-2 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/>
                </svg>  
                5 of 5 systems
            </div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="bg-white border border-gray-200 rounded-md overflow-x-auto mt-6">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-100 text-gray-600">
            <tr>
                <th class="px-4 py-3 text-left font-semibold">Checklist System Name</th>
                <th class="px-4 py-3 text-left font-semibold">Category</th> 
                <th class="px-4 py-3 text-left font-semibold">Rental Ready</th> 
                <th class="px-4 py-3 text-left font-semibold">Customer Checklist</th> 
                <th class="px-4 py-3 text-left font-semibold">Actions</th> 
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-900">
            <tr data-status="Heavy Equipment"> 
                <td class="px-4 py-3 font-semibold text-gray-900">Standard Heavy Equipment System</td>
                <td class="px-4 py-3"><span class="bg-gray-100 px-2 py-1 rounded-full text-xs font-medium">Heavy Equipment</span></td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-4 h-4 text-blue-600" />
                        <div class="text-left">
                            <div class="text-sm text-gray-900">12 questions</div>
                            <button class="text-xs text-blue-600 hover:text-blue-800 hover:underline transition-colors" title="Go to this Rental Ready Template">Heavy Equipment Standard</button>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-4 h-4 text-purple-600" />
                        <div class="text-left">
                            <div class="text-sm text-gray-900">3 questions</div>
                            <button class="text-xs text-purple-600 hover:text-purple-800 hover:underline transition-colors" title="Go to this Rental Ready Template">Heavy Equipment Customer Checklist</button>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 flex gap-2">
                    <a href="#" class="text-blue-600">
                        <x-heroicon-o-pencil class="w-4 h-4" />
                    </a>
                    <a href="#" class="text-red-600">
                        <x-heroicon-o-trash class="w-4 h-4" />
                    </a>
                </td>
            </tr>

            <tr data-status="Compact Equipment">
                <td class="px-4 py-3 font-semibold text-gray-900">Compact Equipment System</td>
                <td class="px-4 py-3"><span class="bg-gray-100 px-2 py-1 rounded-full text-xs font-medium">Compact Equipment </span></td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-4 h-4 text-blue-600" />
                        <div class="text-left">
                            <div class="text-sm text-gray-900">10 questions</div>
                            <button class="text-xs text-blue-600 hover:text-blue-800 hover:underline transition-colors" title="Go to this Rental Ready Template">Compact Equipment Standard</button>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-4 h-4 text-purple-600" />
                        <div class="text-left">
                            <div class="text-sm text-gray-900">2 questions</div>
                            <button class="text-xs text-purple-600 hover:text-purple-800 hover:underline transition-colors" title="Go to this Rental Ready Template">Compact Equipment Customer Checklist</button>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 flex gap-2">
                    <a href="#" class="text-blue-600">
                        <x-heroicon-o-pencil class="w-4 h-4" />
                    </a>
                    <a href="#" class="text-red-600">
                        <x-heroicon-o-trash class="w-4 h-4" />
                    </a>
                </td>
            </tr>
          
            <tr data-status="Power Equipment">
                <td class="px-4 py-3 font-semibold text-gray-900">Power Equipment System</td>
                <td class="px-4 py-3"><span class="bg-gray-100 px-2 py-1 rounded-full text-xs font-medium">Power Equipment</span></td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-4 h-4 text-blue-600" />
                        <div class="text-left">
                            <div class="text-sm text-gray-900">10 questions</div>
                            <button class="text-xs text-blue-600 hover:text-blue-800 hover:underline transition-colors" title="Go to this Rental Ready Template">Power Equipment Standard</button>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-4 h-4 text-purple-600" />
                        <div class="text-left">
                            <div class="text-sm text-gray-900">1 questions</div>
                            <button class="text-xs text-purple-600 hover:text-purple-800 hover:underline transition-colors" title="Go to this Rental Ready Template">Power Equipment Customer Checklist</button>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 flex gap-2">
                    <a href="#" class="text-blue-600">
                        <x-heroicon-o-pencil class="w-4 h-4" />
                    </a>
                    <a href="#" class="text-red-600">
                        <x-heroicon-o-trash class="w-4 h-4" />
                    </a>
                </td>
            </tr>

            <tr data-status="Heavy Equipment">
                <td class="px-4 py-3 font-semibold text-gray-900">Premium Inspection System</td>
                <td class="px-4 py-3"><span class="bg-gray-100 px-2 py-1 rounded-full text-xs font-medium">Heavy Equipment</span></td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-4 h-4 text-blue-600" />
                        <div class="text-left">
                            <div class="text-sm text-gray-900">12 questions</div>
                            <button class="text-xs text-blue-600 hover:text-blue-800 hover:underline transition-colors" title="Go to this Rental Ready Template">Heavy Equipment Standard</button>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-4 h-4 text-purple-600" />
                        <div class="text-left">
                            <div class="text-sm text-gray-900">3 questions</div>
                            <button class="text-xs text-purple-600 hover:text-purple-800 hover:underline transition-colors" title="Go to this Rental Ready Template">Heavy Equipment Customer Checklist</button>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 flex gap-2">
                    <a href="#" class="text-blue-600">
                        <x-heroicon-o-pencil class="w-4 h-4" />
                    </a>
                    <a href="#" class="text-red-600">
                        <x-heroicon-o-trash class="w-4 h-4" />
                    </a>
                </td>
            </tr>

            <tr data-status="Compact Equipment">
                <td class="px-4 py-3 font-semibold text-gray-900">Basic Equipment System</td>
                <td class="px-4 py-3"><span class="bg-gray-100 px-2 py-1 rounded-full text-xs font-medium">Compact Equipment</span></td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-4 h-4 text-blue-600" />
                        <div class="text-left">
                            <div class="text-sm text-gray-900">10 questions</div>
                            <button class="text-xs text-blue-600 hover:text-blue-800 hover:underline transition-colors" title="Go to this Rental Ready Template">Compact Equipment Standard</button>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-4 h-4 text-purple-600" />
                        <div class="text-left">
                            <div class="text-sm text-gray-900">2 questions</div>
                            <button class="text-xs text-purple-600 hover:text-purple-800 hover:underline transition-colors" title="Go to this Rental Ready Template">Compact Equipment Customer Checklist</button>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 flex gap-2">
                    <a href="#" class="text-blue-600">
                        <x-heroicon-o-pencil class="w-4 h-4" />
                    </a>
                    <a href="#" class="text-red-600">
                        <x-heroicon-o-trash class="w-4 h-4" />
                    </a>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="bg-blue-50 border border-blue-200 rounded-md p-4 mt-6">
    <div class="flex items-start space-x-3">
        <!-- Note icon -->
        <x-heroicon-o-document-text class="w-6 h-6 text-blue-900" />
        <!-- Text content -->
        <div>
            <h3 class="font-semibold text-blue-900">System Assignment</h3>
            <p class="text-sm text-blue-800 leading-snug mt-1">
                These checklist systems are independent and can be assigned to multiple equipment items 
                (3, 5, 12, or more) through the Equipment Profile screen in a separate module. 
                Each system combines both rental ready and customer checklists for complete equipment management.
            </p>
        </div>
    </div>
</div>




    @endsection

@push('js')
<script>
    document.getElementById('categoryFilter').addEventListener('change', function () {
        const selected = this.value;
        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const status = row.getAttribute('data-status');
            if (!selected || selected === status) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>

@endpush
