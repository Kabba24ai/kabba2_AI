

<div id="assignModal" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-md flex flex-col max-h-full overflow-hidden border border-gray-200">

            <div class="flex items-center justify-between px-6 pt-4">
                <h2 class="text-lg font-semibold text-gray-900">Assign Part to Supplier</h2>
                <button onclick="closeAssignModal()" class="text-gray-400 hover:text-gray-700 text-xl">×</button>
            </div>

            <div class="p-6 overflow-y-auto max-h-[70vh]">

               {!! html()->form()
                ->attributes([
                'autocomplete' => 'off',
                'data-parsley-validate' => true,
                'class' => '',
                'id'=>'assignForm'
                ])
                
                ->open() !!}

                <!-- Hidden Supplier Unique ID -->
                <input type="hidden" id="assign_supplier_unique_id">

                <!-- Partlist Dropdown -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Part List</label>
                    <select id="partlist" class="w-full border border-gray-300 rounded-md text-sm px-4 py-3" required onchange="fetchPartDetails()">
                        <option value="">Select Part</option>
                        @foreach ($parts as $part)
                            <option value="{{ $part->unique_id }}">{{ $part->part_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Part Number -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Primary Part Number</label>
                    {!! html()->text('part_number', old('part_number'))
                    ->class([
                    'w-full border border-gray-300 rounded-md text-sm px-4 py-3',
                    'border-red-500' => $errors->has('part_number'),
                    'border-gray-300' => !$errors->has('part_number'),
                    ])
                    ->attributes([
                    'id' => 'part_number',
                    'placeholder' => 'Primary part number',
                    'required' => true,
                    ]) !!}
                    <!-- <input id="part_number" type="text" class="w-full border border-gray-300 rounded-md text-sm p-2" placeholder="Enter part number" required> -->
                </div>

                <!-- Cost -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Primary Cost</label>
                    <!-- <input id="cost" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-md text-sm p-2" placeholder="0.00" required> -->

                    {!! html()->number('unit_cost', old('unit_cost'))
                        ->class([
                        'w-full border border-gray-300 rounded-md text-sm px-4 py-3',
                        'border-red-500' => $errors->has('unit_cost'),
                        'border-gray-300' => !$errors->has('unit_cost'),
                        ])
                        ->attributes([
                        'id' => 'unit_cost',
                        'step' => '0.01',
                        'min' => 0,
                        'data-digit-input' => 'true',
                        'placeholder' => '0.00',
                        'required' => true,
                        ]) !!}

                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeAssignModal()" 
                            class="flex-1 px-6 py-3 text-md border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">
                        Cancel
                    </button>

                    <button type="submit" onclick="saveAssignment(event)"  id="saveBtn"
                            class="flex-1 px-6 py-3 text-md bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium flex items-center justify-center gap-2">
                           <span id="saveBtnText">Save</span>

                    </button>
                </div>
            {!! html()->form()->close() !!}

            </div>
        </div>
    </div>
</div>

@push('js')
    <script>
function openAssignModal(supplierId, supplierUniqueId) {
    document.getElementById("assign_supplier_unique_id").value = supplierUniqueId;

    document.getElementById("assignModal").classList.remove("hidden");
    document.getElementById("assignModal").classList.add("flex");


    // RESET FORM FULLY
        document.getElementById("assignForm").reset();

            // Reset parsley state also
            $('#assignForm').parsley().reset();

               // Reset digit-input script
            let unitCost = document.getElementById("unit_cost");
            if (unitCost) unitCost.digits = "";


}

function closeAssignModal() {
    document.getElementById("assignModal").classList.add("hidden");
    document.getElementById("assignModal").classList.remove("flex");
}
function fetchPartDetails() {
    let partId = document.getElementById("partlist").value;
    if (!partId) return;

    let partNumber = document.getElementById("part_number");
    let unitCostInput = document.getElementById("unit_cost");

    // UI Lock
    partNumber.value = "Loading...";
    unitCostInput.value = "Loading...";
    partNumber.disabled = true;
    unitCostInput.disabled = true;

    // Clear parsley errors
    $('#part_number').parsley().reset();
    $('#unit_cost').parsley().reset();

    let url = "{{ route('admin.maintenance-management.parts.get-part-details', ':id') }}"
        .replace(':id', partId);

    fetch(url, {
        method: "GET",
        headers: {
            "X-Requested-With": "XMLHttpRequest"
        }
    })
    .then(res => res.json())
    .then(data => {

        if (data.status === "success") {

            // Fill fields
            partNumber.value = data.part.primary_part_number;

            // Set cost
            unitCostInput.value = data.part.primary_cost;

            // IMPORTANT: sync with global digit formatter
            unitCostInput.digits = data.part.primary_cost.replace(/\D/g, "");

            notyf.success(data.message);

        } else {
            notyf.error(data.message || "Failed to load part details!");
        }
    })
    .catch(() => {
        notyf.error("Something went wrong!");
    })
    .finally(() => {
        partNumber.disabled = false;
        unitCostInput.disabled = false;
    });
}


async function saveAssignment(event) {
    event.preventDefault(); 

    let saveBtn = document.getElementById("saveBtn");
    let saveBtnText = document.getElementById("saveBtnText");
    let form = document.getElementById("assignForm");
    let supplierUniqueId = document.getElementById("assign_supplier_unique_id").value;
    let partId = document.getElementById("partlist").value;
    let partNumber = document.getElementById("part_number").value;
    let cost = document.getElementById("unit_cost").value;

    // Validation
    if (!supplierUniqueId || !partId || !partNumber || !cost) {
        // notyf.error("All fields are required!");
        return;
    }

    // Show loader
    saveBtn.disabled = true;
    saveBtnText.textContent = "Saving...";

    try {

        // Build Laravel route URL
        let url = "{{ route('admin.maintenance-management.parts.assign-to-supplier') }}";

        let response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify({
                supplier_unique_id: supplierUniqueId,
                part_unique_id: partId,
                part_number: partNumber,
                cost: cost,
            })
        });

        let data = await response.json();

        if (data.status === "success") {

            notyf.success(data.message || "Saved successfully!");

            
            // RESET FORM FULLY
            form.reset();

            // Reset parsley state also
            $('#assignForm').parsley().reset();

               // Reset digit-input script
            let unitCost = document.getElementById("unit_cost");
            if (unitCost) unitCost.digits = "";
            
            // Auto-close modal
            closeAssignModal();

            // Refresh after slight delay
            // setTimeout(() => location.reload(), 700);

        } else {
            notyf.error(data.message || "Failed to save assignment!");
        }

    } catch (error) {
        console.error(error);
        notyf.error("Something went wrong!");
    }

    // Restore button
    saveBtn.disabled = false;
    saveBtnText.textContent = "Save";
}


</script>

@endpush