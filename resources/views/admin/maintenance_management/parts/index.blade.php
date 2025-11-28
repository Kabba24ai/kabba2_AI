@extends('admin.layouts.app')

@section('title', 'Parts Management')

@section('content')

<div class="min-h-screen bg-gray-50" x-data="{ selected: '{{ session('active_tab', 'parts') }}' }">
    {{-- Header --}}
    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
        <div class="flex items-center space-x-4 ">
            <h2 class="text-xl font-bold text-gray-900">Equipment Management System </h2>

            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <button @click="selected = 'parts'"
                    :class="selected === 'parts' ? ' bg-blue-600 text-white ' : 'bg-gray-100 text-gray-700s'"
                    class="w-full sm:w-auto px-6 py-3 rounded-lg text-md flex items-center gap-2"
                    type="button">
                    <svg class="h-4 w-4 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg> Parts Management
                </button>
                <button @click="selected = 'template'"
                    :class="selected === 'template' ? ' bg-blue-600 text-white ' : 'bg-gray-100 text-gray-700'"
                    class="w-full sm:w-auto px-6 py-3 rounded-lg text-md flex items-center gap-2 "
                    type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 " fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                        <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                        <path d="M10 9H8"></path>
                        <path d="M16 13H8"></path>
                        <path d="M16 17H8"></path>
                    </svg> Parts List
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
                        class="flex items-center text-md justify-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-3 text-md rounded-lg transition-colors w-full sm:w-auto">
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
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100" data-status="">
                    <div class="flex items-center justify-between">
                        <div class="text-left">
                            <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Total</p>
                            <p class="text-2xl font-bold text-gray-700 mt-1">{{ $stockCounts['total'] }} </p>
                        </div>
                        <svg class="h-6 w-6 text-gray-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100" data-status="in-stock">
                    <div class="flex items-center justify-between" >
                        <div class="text-left">
                            <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">In Stock</p>
                            <p class="text-2xl font-bold text-green-600 mt-1">{{ $stockCounts['in_stock'] }}</p>
                        </div>
                        <svg class="h-6 w-6 text-green-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100" data-status="buy-now">
                    <div class="flex items-center justify-between">
                        <div class="text-left">
                            <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Buy Now</p>
                            <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $stockCounts['buy_now'] }}</p>
                        </div>
                        <svg class="h-6 w-6 text-yellow-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100" data-status="out-of-stock">
                    <div class="flex items-center justify-between">
                        <div class="text-left">
                            <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Out of Stock</p>
                            <p class="text-2xl font-bold text-red-600 mt-1">{{ $stockCounts['out_of_stock'] }}</p>
                        </div>
                        <svg class="h-6 w-6 text-red-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100" data-status="dni">
                    <div class="flex items-center justify-between">
                        <div class="text-left">
                            <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">DNI</p>
                            <p class="text-2xl font-bold text-gray-700 mt-1">{{ $stockCounts['dni'] }}</p>
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
                            class="pl-9 pr-4 py-3 border border-gray-300 rounded-md w-full text-sm">
                    </div>

                    {{-- Category Filter --}}
                    <div class="w-full sm:w-48">

                        <!-- Category Filter -->
                        <select id="partCategorys-filter" name="category" class="w-full px-4 py-3 border border-gray-300 rounded-md text-sm text-gray-900">
                            <option value="">All Categories</option>
 @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->title }}</option>
                                @endforeach

                        </select>
                    </div>

                    {{-- Category Filter --}}
                    <div class="w-full sm:w-48">

                        <!-- partlist Filter -->
                        <select id="partlist-filter" name="partlist" class="w-full px-4 py-3 border border-gray-300 rounded-md text-sm text-gray-900">
                            <option value="">All Parts List</option>
 @foreach($allpartlists as $list)
                                <option value="{{ $list->id }}">{{ $list->name }}</option>
                                @endforeach

                        </select>
                    </div>

                    {{-- Category Filter --}}
                    <div class="w-full sm:w-48">

                        <select id="equipment_id" name="equipment_id" class="w-full px-3 py-3  border border-gray-300 rounded-md text-sm text-gray-900">
                            <option value="">All Equipment</option>

                            @foreach($equipments as $equipment)
                            <option value="{{ $equipment->id }}">{{ $equipment->equipment_name }}</option>
                            @endforeach

                        </select>
                    </div>

                    <div class="flex gap-2">

                        <a href="#" class="text-sm bg-blue-100 text-blue-700 px-4 py-3 flex gap-2 items-center rounded-md ">
                            Quick: Supplies
                        </a>

                    </div>


                    <div class="flex gap-2">
                        <button type="button" id="clear-filters"
                        class="hidden text-sm text-gray-600 bg-white px-4 py-3 flex gap-2 items-center rounded-md border border-gray-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Clear
                        </button>
                    </div>

                    <div class="flex gap-2">
                        <a href="javascript:void(0)" onclick="openSupplierModal()" class="text-sm text-gray-600 bg-white px-4 py-3 flex gap-2 items-center rounded-md border border-gray-300">
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
 <div id="parts-table-wrapper">
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

                    <h1 class="text-2xl font-semibold text-gray-900">Parts List </h1>
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
                            <input type="text" name="tsearch" value="{{ request('tsearch') }}" placeholder="Search Parts List..."
                                class="w-full pl-10 text-sm px-3 py-3 border border-gray-300 rounded-md">
                        </div>

                        {{-- Category Filter --}}
                        <div class="w-full sm:w-48">
                            <select id="tcategory" name="tcategory"
                                class="w-full px-3 py-3 border border-gray-300 rounded-md text-sm text-gray-900">
                                <option value="">All Category</option>
                                  @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Right Side: Action Buttons --}}
                    <div class="flex gap-2 justify-end w-full sm:w-auto">
                        <a href="{{ route('admin.maintenance-management.parts.parts-list.create') }}"
                            class="text-md text-white bg-blue-600  px-6 py-3 flex gap-2 items-center rounded-lg hover:bg-blue-700 transition border border-gray-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create Parts List
                        </a>


                    </div>

                </form>
            </div>

            <!-- table -->

             <div id="templates-table-wrapper">
                @include('admin.maintenance_management.parts.parts_list.partials._table', ['partlists' => $partlists])
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

<div id="partListModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-md flex flex-col max-h-full border border-gray-200">

            <div class="flex items-center justify-between px-6 pt-4">
                <h2 class="text-lg font-semibold text-gray-900">Assigned Parts Lists</h2>
                <button onclick="closePartListModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div class="p-6 overflow-y-auto max-h-[70vh]" id="partListModalContent">
                <!-- Content injected via JS -->
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



        function openPartListModal(element) {
    const modal = document.getElementById('partListModal');
    const container = document.getElementById('partListModalContent');

    let lists = JSON.parse(element.getAttribute('data-lists'));

 

    if (!lists.length) {
        container.innerHTML = `<p class='text-center text-gray-500'>No lists assigned.</p>`;
    } else {
        let html = "<ul class='space-y-2'>";
        lists.forEach(name => {
            html += `
                <li class="p-3 bg-gray-100 rounded-lg text-gray-800 font-medium">
                    ${name}
                </li>
            `;
        });
        html += "</ul>";
        container.innerHTML = html;
    }

    modal.classList.remove('hidden');
}

function closePartListModal() {
    document.getElementById('partListModal').classList.add('hidden');
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
    // Define global variable (like your static example)
    let SUPPLIERS_DATA = [];

    // Call your Laravel API route
    fetch("{{ route('admin.maintenance-management.parts.get-all-supplier') }}", {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('Suppliers data:');
            console.log(data);
            console.log('Suppliers data:');

            // Assign response to global variable
            SUPPLIERS_DATA = data;

            // Example check: log first supplier
            if (SUPPLIERS_DATA.length > 0) {
                console.log("First supplier:", SUPPLIERS_DATA[0]);
            }

            // Now you can use SUPPLIERS_DATA in your modal, dropdown, etc.
        })
        .catch(err => {
            console.error("Error fetching suppliers:", err);
        });

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
        let partlistSelect = document.querySelector('select[name="partlist"]');



        let equipmentSelect = document.querySelector('select[name="equipment_id"]');
        let wrapper = document.querySelector('#parts-table-wrapper');
        let loader = document.querySelector('#parts-loader');
        let clearBtn = document.querySelector('#clear-filters');

        let timeout = null;
        let selectedStatus = '';

        function updateClearButton() {
            const hasFilters =
                (searchInput?.value.trim() !== '') ||
                (categorySelect && categorySelect.value) ||
                (partlistSelect && partlistSelect.value) ||
                (equipmentSelect && equipmentSelect.value) ||
                (selectedStatus !== '');
            if (hasFilters) clearBtn?.classList.remove('hidden');
            else clearBtn?.classList.add('hidden');
        }

        function fetchParts() {
            const params = new URLSearchParams();

            if (searchInput.value.length >= 2 || searchInput.value.length === 0)
                params.append('search', searchInput.value);

            if (categorySelect && categorySelect.value)
                params.append('category', categorySelect.value);
             if (partlistSelect && partlistSelect.value)
                params.append('partlist', partlistSelect.value);


            if (equipmentSelect && equipmentSelect.value)
                params.append('equipment_id', equipmentSelect.value);

            if (selectedStatus)
                params.append('stock_status', selectedStatus);

            updateClearButton();

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
        partlistSelect?.addEventListener('change', fetchParts);

        equipmentSelect?.addEventListener('change', fetchParts);

        //  Status card click handler
        const statusCards = document.querySelectorAll('[data-status]');
        statusCards.forEach(card => {
            card.addEventListener('click', function() {
                const newStatus = this.dataset.status;

                // toggle off if same card clicked again
                if (selectedStatus === newStatus) {
                    selectedStatus = '';
                } else {
                    selectedStatus = newStatus;
                }

                // remove highlight from all
                statusCards.forEach(c => c.classList.remove(
                    'ring-2', 'ring-green-500', 'ring-yellow-500', 'ring-red-500', 'ring-gray-400'
                ));

                // add highlight to selected one
                if (selectedStatus) {
                    switch (selectedStatus) {
                        case 'in-stock':
                            this.classList.add('ring-2', 'ring-green-500');
                            break;
                        case 'buy-now':
                            this.classList.add('ring-2', 'ring-yellow-500');
                            break;
                        case 'out-of-stock':
                            this.classList.add('ring-2', 'ring-red-500');
                            break;
                        case 'dni':
                            this.classList.add('ring-2', 'ring-gray-400');
                            break;
                    }
                }

                fetchParts();
            });
        });


        //  Clear button logic
        clearBtn?.addEventListener('click', function() {
            searchInput.value = '';
            if (categorySelect) categorySelect.value = '';
            if (partlistSelect) partlistSelect.value = '';
            if (equipmentSelect) equipmentSelect.value = '';
            selectedStatus = '';

            // Remove card highlights
            statusCards.forEach(c => c.classList.remove(
                'ring-2', 'ring-green-500', 'ring-yellow-500', 'ring-red-500', 'ring-gray-400'
            ));

            fetchParts();
        });

        // <!-- delete  -->

        // Fetch templates


        let tsearchInput = document.querySelector('input[name="tsearch"]');
        let tcategorySelect = document.querySelector('select[name="tcategory"]');

        let twrapper = document.querySelector('#templates-table-wrapper');

        function fetchTemplates() {
            const params = new URLSearchParams();

            if (tsearchInput && (tsearchInput.value.length >= 2 || tsearchInput.value.length === 0))
                params.append('tsearch', tsearchInput.value);

            if (tcategorySelect && tcategorySelect.value)
                params.append('tcategory', tcategorySelect.value);

            loader?.classList.remove('hidden');
            twrapper?.classList.add('opacity-50', 'pointer-events-none');

            fetch("{{ route('admin.maintenance-management.parts.index') }}?" + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    twrapper.innerHTML = data.html; // Partial HTML view returned from controller
                })
                .catch(err => {
                    console.error(err);
                    twrapper.innerHTML = '<div class="text-red-500 p-4">Error loading templates.</div>';
                })
                .finally(() => {
                    loader?.classList.add('hidden');
                    twrapper?.classList.remove('opacity-50', 'pointer-events-none');
                });
        }

        // Search debounce
        tsearchInput?.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(fetchTemplates, 400);
        });

        // Filter change
        tcategorySelect?.addEventListener('change', fetchTemplates);


        // --- Global delete Template function ---
        window.deleteTemplate = function(id, name) {
            window.showConfirm(
                `Are you sure you want to delete "${name}"?`,
                'Delete Template'
            ).then((result) => {
                if (result.isConfirmed) {
                    let deleteUrl = `{{ route('admin.maintenance-management.parts.parts-list.delete', ['list' => ':id']) }}`;
                    deleteUrl = deleteUrl.replace(':id', id);

                    fetch(deleteUrl, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                notyf.success(data.message);
                                fetchTemplates(); // Refresh table after deletion
                            } else {
                                notyf.error(data.message || "Failed to delete template!");
                            }
                        })
                        .catch(() => notyf.error("Error deleting template!"));
                }
            });
        };


    });
</script>



@endpush
