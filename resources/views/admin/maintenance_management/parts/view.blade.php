@extends('admin.layouts.app')

@section('title', 'Parts Management')

@section('content')

<div class="min-h-screen ">

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3 sm:gap-4">
        <!-- Back Button -->
        <a href="{{ route('admin.maintenance-management.parts.index') }}" class="flex items-center text-sm font-medium gap-2 text-gray-600 ">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left h-5 w-5"><path d="m12 19-7-7 7-7"></path><path d="M19 12H5"></path></svg>
            Back to Parts
        </a>

        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 w-full sm:w-auto">

            <a href="{{ route('admin.maintenance-management.parts.edit', $part->unique_id) }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 text-md font-medium shadow transition-colors focus:outline-none focus:ring-2 focus:ring-blue-400 dark:focus:ring-blue-500 w-full sm:w-auto text-center">
                Edit Parts
            </a>
        </div>
    </div>
    

    <!-- Main Card -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package h-7 w-7 text-white"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
            <h1 class="text-2xl font-semibold text-white"> {{   $part->part_name }}</h1>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <div>
                    <h3 class="text-gray-800 font-semibold mb-3 text-lg">Inventory Status 
                    <span class="ml-3 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                        {{ $part->stock_status == 'in-stock' ? 'text-green-700 bg-green-100 border-green-200' : '' }}
                        {{ $part->stock_status == 'buy-now' ? 'text-yellow-700 bg-yellow-100 border-yellow-200' : '' }}
                        {{ $part->stock_status == 'out-of-stock' ? 'text-red-700 bg-red-100 border-red-200' : '' }}
                        {{ $part->stock_status == 'dni' ? 'text-gray-700 bg-gray-100 border-gray-200' : '' }}">
                        {{ ucfirst(str_replace('-', ' ', $part->stock_status)) }}
                    </span></h3>
                    <div class="space-y-4 text-sm text-gray-700">
                        <div class="flex justify-strat gap-20">
                            <span>Current Stock</span>
                            <span class="font-medium">{{   $part->stock_level }}</span>
                        </div>
                        <div class="flex justify-strat gap-18">
                            <span>Minimum Stock</span>
                            <span class="font-medium">{{   $part->min_stock }} </span>
                        </div>


                      

                    </div>
                </div>

                <div>
                    <h3 class="text-gray-800 font-semibold mb-3 text-lg">Part Details</h3>
                    <p class="text-sm text-gray-600 block mb-1">Description</p>
                    <p class="text-sm text-gray-900"> {{   $part->description }}</p>
                </div>
            </div>


            <div>
                <h3 class="text-gray-800 font-semibold mb-3 text-lg">Supplier Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                  @php
                        $suppliers = [
                            [
                                'label' => 'Part Number',
                                'supplier' => $part->primarySupplier,
                                'brand' => $part->primaryBrand,
                                'part_number' => $part->primary_part_number,
                                'cost' => $part->primary_part_cost
                            ],
                            [
                                'label' => 'Alt Part Number / Supplier',
                                'supplier' => $part->alt1Supplier,
                                'brand' => $part->alt1Brand,
                                'part_number' => $part->alt_1_part_number,
                                'cost' => $part->alt_1_part_cost
                            ],
                            [
                                'label' => 'Alt Part Number / Supplier',
                                'supplier' => $part->alt2Supplier,
                                'brand' => $part->alt2Brand,
                                'part_number' => $part->alt_2_part_number,
                                'cost' => $part->alt_2_part_cost
                            ],
                        ];
                    @endphp


                   @foreach($suppliers as $item)
                    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm space-y-5">

                        <!-- Row 1: Part Number | Brand -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">
                                    {{ $item['label'] }}
                                </label>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $item['part_number'] ?? '-' }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Brand</label>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $item['brand']->name ?? '—' }}
                                </p>
                            </div>
                        </div>

                        <!-- Row 2: Cost | Supplier -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Cost</label>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <line x1="12" x2="12" y1="2" y2="22"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>

                                    <p class="text-lg font-bold text-green-600">
                                        {{ number_format($item['cost'] ?? 0, 2) }}
                                    </p>

                                    @php
                                        $fieldMap = [
                                            'Part Number' => 'primary_part_cost',
                                            'Alt Part Number / Supplier' => 'alt_1_part_cost',
                                            'Alt Part Number / Supplier' => 'alt_2_part_cost',
                                        ];
                                    @endphp

                                    <button
                                        onclick="openCostModal(this)"
                                        class="p-1 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded"
                                        title="Edit price"
                                        data-part-id="{{ $part->id }}"
                                        data-part-field="{{ $fieldMap[$item['label']] }}"
                                        data-cost="{{ $item['cost'] ?? 0 }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                                            <path d="m15 5 4 4"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Supplier</label>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $item['supplier']->name ?? '-' }}

                                    @if(($item['supplier']->is_preferred_supplier ?? false))
                                    <br>
                                    <span
                                        class="text-sm font-medium flex items-center gap-1"
                                        style="color:#d4af37"
                                    >
                                         *Preferred Supplier
                                    </span>
                                @endif
                                </p>
                            </div>
                        </div>

                        <!-- Supplier Details -->
                        @if($item['supplier'])
                        <div class="pt-4 border-t border-gray-200 space-y-4">

                            <label class="block text-sm font-medium text-gray-500">
                                Supplier Details
                            </label>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                                <div>
                                    <span class="font-medium">Address</span>
                                    <p class="mt-0.5">{{ $item['supplier']->full_address ?? '-' }}</p>
                                </div>

                                <div>
                                    <span class="font-medium">Phone</span>
                                    <p class="mt-0.5">{{ $item['supplier']->phone ?? '-' }}</p>
                                </div>

                                <div>
                                    <span class="font-medium">Contact</span>
                                    <p class="mt-0.5">{{ $item['supplier']->primary_contact_name ?? '-' }}</p>
                                </div>

                                <div>
                                    <span class="font-medium"><span class="text-red-700 font-bold"> Parts </span> Contact </span>
                                    <p class="mt-0.5">{{ $item['supplier']->inside_sales_name ?? '-' }}</p>
                                </div>

                                 <div>
                                    <span class="font-medium">Phone</span>
                                    <p class="mt-0.5">{{ $item['supplier']->primary_contact_phone ?? '-' }}</p>
                                </div>

                                 <div>
                                    <span class="font-medium">Phone</span>
                                    <p class="mt-0.5">{{ $item['supplier']->inside_sales_phone ?? '-' }}</p>
                                </div>

                                <div>
                                    <span class="font-medium">Email</span>
                                    <p class="mt-0.5">{{ $item['supplier']->primary_contact_email ?? '-' }}</p>
                                </div>

                                 <div>
                                    <span class="font-medium">Email</span>
                                    <p class="mt-0.5">{{ $item['supplier']->inside_sales_email ?? '-' }}</p>
                                </div>
                            </div>

                        </div>
                        @endif

                    </div>
                    @endforeach



                </div>
            </div>

            <div>
    <h3 class="text-gray-800 font-semibold mb-3 text-lg">Part Assignment</h3>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm items-stretch">

        <!-- Category Card -->
        <div class="border border-gray-200 rounded-xl p-4 h-full flex flex-col">
            <label class="block text-sm font-medium text-gray-500 mb-2">Category</label>

           <div class="flex-1 flex">
                <p class="text-base font-medium text-gray-900">
                    @if($part->partsLists->isNotEmpty())
                        {{ $part->partsLists
                            ->pluck('category.title')
                            ->filter()
                            ->unique()
                            ->implode(', ') }}
                    @else
                        -
                    @endif
                </p>
            </div>

        </div>

        <!-- Parts List Card -->
        <div class="border border-gray-200 rounded-xl p-4 h-full flex flex-col">
            <label class="block text-sm font-medium text-gray-500 mb-2">Parts List</label>

           <div class="flex-1 flex">
    <p class="text-base font-medium text-gray-900">
        @if($part->partsLists->isNotEmpty())
            {{ $part->partsLists
                ->pluck('name')
                ->filter()
                ->unique()
                ->implode(', ') }}
        @else
            -
        @endif
    </p>
</div>

        </div>

        <!-- Equipment Assignment Card -->
        <div class="border border-gray-200 rounded-xl p-4 h-full flex flex-col">
            <label class="block text-sm font-medium text-gray-500 mb-2">
                Equipment Assignment
            </label>

            <div class="flex-1 overflow-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200 text-gray-600 text-left sticky top-0">
                        <tr>
                            <th class="py-3 px-4">Equipment Name</th>
                            <th class="py-3 px-4">Equipment ID</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($assignedEquipments as $equipment)
                            <tr>
                                <td class="py-3 px-4">{{ $equipment->equipment_name }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono bg-gray-100 border">
                                        {{ $equipment->equipment_id }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-3 px-4 text-center text-gray-500">
                                    No assigned equipment
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

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
<form id="costForm">
            @csrf
            <input type="hidden" name="part_id" id="modalPartId">
            <input type="hidden" name="field" id="modalField">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Price</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign h-5 w-5 text-gray-400"><line x1="12" x2="12" y1="2" y2="22"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                                </div>
                                <input type="number" step="0.01" min="0" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm" placeholder="0.00" data-digit-input = 'true'
                        data-parsley-maxlength = "8"  id="modalCost" >
                            </div>
                        </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="closeCostModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">Cancel</button>
        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Save</button>
                    </div>
              </form>
            </div>

        </div>
    </div>
</div>



@endsection

@push('js')
<script>
function openCostModal(button) {
    const partId = button.dataset.partId;
    const field = button.dataset.partField;
    const cost = parseFloat(button.dataset.cost) || 0;

    // Set hidden inputs
    document.getElementById('modalPartId').value = partId;
    document.getElementById('modalField').value = field;

    // Reset and prefill the cost input properly
    const costInput = document.getElementById('modalCost');

    // Reset custom 'digits' property if used
    if (costInput.digits) costInput.digits = "";

    // Set the value in proper decimal format
    costInput.value = cost.toFixed(2);
    costInput.digits = String(Math.round(cost * 100));

    // Trigger input event in case you have JS listening
    costInput.dispatchEvent(new Event("input", { bubbles: true }));

    // Show modal
    document.getElementById('costModal').classList.remove('hidden');
}


function closeCostModal() {
    document.getElementById('costModal').classList.add('hidden');
}



document.getElementById('costForm').addEventListener('submit', function(e){
    e.preventDefault();

    const partId = document.getElementById('modalPartId').value;
    const field = document.getElementById('modalField').value;
    const cost = document.getElementById('modalCost').value;

    const formData = new FormData();
    formData.append('part_id', partId);
    formData.append('field', field);
    formData.append('cost', cost);

    fetch("{{ route('admin.maintenance-management.parts.update-cost') }}", {
        method: "POST",
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Update the cost dynamically in the card
            const button = document.querySelector(`[data-part-id="${partId}"][data-part-field="${field}"]`);
            button.dataset.cost = data.cost; // Update the data attribute
            button.closest('.bg-white').querySelector('p.text-lg').textContent = data.cost;

            closeCostModal();
                    notyf.success(data.message || "Cost updated.");


        } else {
                    notyf.error(data.message || "Update failed!");


        }
    })
    .catch(err => {
        console.error(err);
                    notyf.error(data.message || "Update failed!");
    });
});


</script>

@endpush
