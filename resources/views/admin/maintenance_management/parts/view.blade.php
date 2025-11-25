@extends('admin.layouts.app')

@section('title', 'Parts Management')

@section('content')

<div class="min-h-screen ">
    <!-- Back Button -->
    <a href="{{ route('admin.maintenance-management.parts.index') }}" class="flex items-center text-sm font-medium mb-4 gap-2 text-gray-600 ">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left h-5 w-5"><path d="m12 19-7-7 7-7"></path><path d="M19 12H5"></path></svg>
        Back to Parts
    </a>

    <!-- Main Card -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package h-7 w-7 text-white"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
            <h1 class="text-2xl font-semibold text-white">Hydraulic Filter</h1>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-gray-800 font-semibold mb-3 text-lg">Inventory Status</h3>
                    <div class="space-y-4 text-sm text-gray-700">
                        <div class="flex justify-between">
                            <span>Current Stock</span>
                            <span class="font-medium">12</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Minimum Stock</span>
                            <span class="font-medium">5</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                            <span>Status</span>
                            <span class="bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">In Stock</span>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-gray-800 font-semibold mb-3 text-lg">Part Details</h3>
                    <p class="text-sm text-gray-600 block mb-1">Description</p>
                    <p class="text-sm text-gray-900"> High-performance hydraulic filter designed for heavy-duty excavator operations </p>
                </div>
            </div>

            <div>
                <h3 class="text-gray-800 font-semibold mb-3 text-lg">Supplier Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <!-- Card 1 -->
                    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Part Number</label>
                            <p class="text-sm font-medium text-gray-900">HF-2024-001</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Cost</label>
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign h-4 w-4 text-green-600"><line x1="12" x2="12" y1="2" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                                <p class="text-lg font-bold text-green-600">45.99</p>
                                <button  onclick="openCostModal()" class="p-1 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors" title="Edit price">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil h-4 w-4"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path><path d="m15 5 4 4"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Supplier</label>
                            <p class="text-sm font-medium text-gray-900">Caterpillar Inc.</p>
                        </div>

                        <div class="pt-3 border-t border-gray-200">
                            <label class="block text-sm font-medium text-gray-500 mb-2">Supplier Details</label>
                            <div class="space-y-2 text-sm text-gray-600">
                                <div>
                                    <span class="font-medium">Address:</span>
                                    <p class="mt-0.5">100 NE Adams St, Peoria, IL 61629</p>
                                </div>
                                <div>
                                    <span class="font-medium">Phone:</span>
                                    <p class="mt-0.5">(309) 675-1000</p>
                                </div>
                                <div>
                                    <span class="font-medium">Contact:</span>
                                    <p class="mt-0.5">Mike Johnson</p>
                                </div>
                                <div>
                                    <span class="font-medium">Email:</span>
                                    <p class="mt-0.5">mike.johnson@cat.com</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Alt 1 Part Number</label>
                            <p class="text-sm font-medium text-gray-900">HF-ALT-2024-001</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Cost</label>
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign h-4 w-4 text-green-600"><line x1="12" x2="12" y1="2" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                                <p class="text-lg font-bold text-green-600">42.50</p>
                                <button class="p-1 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors" title="Edit price">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil h-4 w-4"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path><path d="m15 5 4 4"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Supplier</label>
                            <p class="text-sm font-medium text-gray-900">Parker Hannifin</p>
                        </div>

                        <div class="pt-3 border-t border-gray-200">
                            <label class="block text-sm font-medium text-gray-500 mb-2">Supplier Details</label>
                            <div class="space-y-2 text-sm text-gray-600">
                                <div>
                                    <span class="font-medium">Address:</span>
                                    <p class="mt-0.5">6035 Parkland Blvd, Cleveland, OH 44124</p>
                                </div>
                                <div>
                                    <span class="font-medium">Phone:</span>
                                    <p class="mt-0.5">(216) 896-3000</p>
                                </div>
                                <div>
                                    <span class="font-medium">Contact:</span>
                                    <p class="mt-0.5">Sarah Williams</p>
                                </div>
                                <div>
                                    <span class="font-medium">Email:</span>
                                    <p class="mt-0.5">sarah.williams@parker.com</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3 -->
                    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Alt 2 Part Number</label>
                            <p class="text-sm font-medium text-gray-900">HF-ALT2-2024-001</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Cost</label>
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign h-4 w-4 text-green-600"><line x1="12" x2="12" y1="2" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                                <p class="text-lg font-bold text-green-600">48.75</p>
                                <button class="p-1 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors" title="Edit price">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil h-4 w-4"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path><path d="m15 5 4 4"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Supplier</label>
                            <p class="text-sm font-medium text-gray-900">John Deere</p>
                        </div>

                        <div class="pt-3 border-t border-gray-200">
                            <label class="block text-sm font-medium text-gray-500 mb-2">Supplier Details</label>
                            <div class="space-y-2 text-sm text-gray-600">
                                <div>
                                    <span class="font-medium">Address:</span>
                                    <p class="mt-0.5">1 John Deere Pl, Moline, IL 61265</p>
                                </div>
                                <div>
                                    <span class="font-medium">Phone:</span>
                                    <p class="mt-0.5">(309) 765-8000</p>
                                </div>
                                <div>
                                    <span class="font-medium">Contact:</span>
                                    <p class="mt-0.5">Robert Chen</p>
                                </div>
                                <div>
                                    <span class="font-medium">Email:</span>
                                    <p class="mt-0.5">robert.chen@johndeere.com</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-gray-800 font-semibold mb-3 text-lg">Part Assignment</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div class="border border-gray-200 rounded-xl p-4">
                        <label class="block text-sm font-medium text-gray-500 mb-2">Category</label>
                        <p class="text-base font-medium text-gray-900">Excavators</p>
                    </div>
                    <div class="border border-gray-200 rounded-xl p-4">
                        <label class="block text-sm font-medium text-gray-500 mb-2">Parts List</label>
                        <p class="text-base font-medium text-gray-900">Excavator Standard Maintenance</p>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-gray-800 font-semibold mb-3">Equipment Assignment</h3>
                <div class="overflow-x-auto  border border-gray-200 rounded-xl">  
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200 text-gray-600 text-left">
                            <tr>
                                <th class="py-4 px-6">Equipment Name</th>
                                <th class="py-4 px-6">Equipment ID</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr>
                                <td class="py-4 px-6">CAT 320D Excavator</td>
                                <td class="py-4 px-6"><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">EXC-001</span></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-6">CAT 330F</td>
                                <td class="py-4 px-6"><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">EXC-003</span></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-6">Komatsu PC200</td>
                                <td class="py-4 px-6"><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">EXC-004</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>



 <div id="costModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-sm flex flex-col max-h-full overflow-hidden border border-gray-200">
            <div class="flex items-center justify-between px-6 pt-4">
                <div class="flex items-center space-x-3">
                    <h2 class="text-lg font-semibold text-gray-900">Edit Price</h2>
                </div>
                <button onclick="closeCostModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <!-- Scrollable Content -->
            <div class=" p-6  overflow-y-auto max-h-[70vh]">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Price</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign h-5 w-5 text-gray-400"><line x1="12" x2="12" y1="2" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </div>
                        <input type="number" step="0.01" min="0" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm" placeholder="0.00" value="45.99">
                    </div>
                </div>
                   <div class="flex gap-3">
                <button onclick="closeCostModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">Cancel</button>
                <button class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center justify-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check h-4 w-4"><path d="M20 6 9 17l-5-5"></path></svg>Save</button>
            </div>
            </div>
         
        </div>
    </div>
</div>



@endsection

@push('js')
<script>
function openCostModal() {
    document.getElementById('costModal').classList.remove('hidden');
    renderOptions();
}

function closeCostModal() {
    document.getElementById('costModal').classList.add('hidden');
}
</script>