<div class="space-y-6">
    {{-- Unified Row: Basic + Stock Information --}}
    <div class="grid grid-cols-1 sm:grid-cols-5 md:grid-cols-5 lg:grid-cols-5 gap-4">

        {{-- Part Name --}}
        <div>
            <label for="part_name" class="block text-sm font-medium text-gray-700 mb-1 required">
                Part Name
            </label>
          

            {!! html()->text('part_name', old('part_name', $part->part_name ?? ''))
            ->class([
            'w-full px-3 py-3 border border-gray-300 rounded-md text-sm text-gray-900',
            'border-red-500' => $errors->has('part_name'),
            'border-gray-300' => !$errors->has('part_name'),
            ])
            ->attributes([
            'id' => 'part_name',
            'placeholder' => 'Enter part name',
            'required' => true,
            ]) !!}
            @error('part_name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror

        </div>

        {{-- Assign to List --}}
        <div>
            <label for="list_id" class="block text-sm font-medium text-gray-700 mb-1">
                Assign to List
            </label>
            <select id="list_id" name="list_id"
                class="w-full px-3 py-3  border border-gray-300 rounded-md text-sm text-gray-900 @error('equipment_id') border-red-500 @enderror">
                <option value="">None (Optional)</option>
              

                  @foreach($list as $l)
                    <option value="{{ $l->id }}">
                        {{ $l->name }}
                    </option>
                    @endforeach
                

            </select>

        </div>

        {{-- Current Stock --}}
        <div>
            <label for="current_stock" class="block text-sm font-medium text-gray-700 mb-1  ">
                Current Stock
            </label>

       

            {!! html()->number('current_stock', old('current_stock', $part->stock_level ?? '0'))
            ->class([
            'w-full px-3 py-3 border border-gray-300 rounded-md text-sm text-gray-900',
            'border-red-500' => $errors->has('current_stock'),
            'border-gray-300' => !$errors->has('current_stock'),
            ])
            ->attributes([
            'id' => 'current_stock',
            'min' => 0,
            'placeholder' => '0',
            ]) !!}

        </div>

        {{-- Min Stock --}}
        <div>
            <label for="min_stock" class="block text-sm font-medium text-gray-700 mb-1 ">
                Min Stock
            </label>

         
            {!! html()->number('min_stock', old('min_stock', $part->min_stock ?? '0'))
            ->class([
            'w-full px-3 py-3 border border-gray-300 rounded-md text-sm text-gray-900',
            'border-red-500' => $errors->has('min_stock'),
            'border-gray-300' => !$errors->has('min_stock'),
            ])
            ->attributes([
            'id' => 'min_stock',
            'min' => 0,
            'placeholder' => '0',
            ]) !!}

            @error('min_stock')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Do Not Inventory --}}
        <div class="flex items-end">
            <label class="block text-sm font-medium text-gray-700 mb-1">
                <span class="flex items-center">

                
                    {!! html()->checkbox('dni', old('dni', $part->dni ?? false), 1)
                    ->class('h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded mr-2')
                    ->attributes(['id' => 'dni']) !!}

                    Do Not Inventory
                </span>
            </label>
        </div>

    </div>

    {{-- Unified Row: Basic + Stock Information --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- Part Name --}}
        <div>
            <label for="part_description" class="block text-sm font-medium text-gray-700 mb-1 ">
                Description </label>


            {!! html()->text('part_description', old('part_description', $part->description ?? ''))
            ->class([
            'w-full px-3 py-3 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors',
            'border-red-500' => $errors->has('part_description'),
            'border-gray-300' => !$errors->has('part_description'),
            ])
            ->attributes([
            'id' => 'part_description',
            'placeholder' => 'Enter part Description',
         
            ]) !!}

     
        </div>

        {{-- General Supplier Item --}}
        <div class="flex items-center ">
            <label class="block text-sm font-medium text-gray-700 mb-1">
                <span class="flex items-center">

                 
                    {!! html()->checkbox('gsi', old('gsi', $part->general_supply_item ?? false), 1)
                    ->class('h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded mr-2')
                    ->attributes(['id' => 'gsi']) !!}

                    General Supply Item
                </span>
            </label>
        </div>

    </div>


    {{-- Supplier Section Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mt-6">

        {{-- Primary Supplier --}}
        <div class="border border-gray-200 rounded-xl p-6 shadow-sm col-span-12 lg:col-span-4 ">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div class="md:col-span-2">
                    <label for="part_number" class="block text-sm font-medium text-gray-700 mb-1 required">
                        Part Number
                    </label>

                    <!-- <input type="text" id="part_number" name="part_number"
                        value="{{ old('part_number', $part->part_number ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white @error('part_number') border-red-500 @enderror"
                        placeholder="Primary part number" required> -->

                    {!! html()->text('part_number', old('part_number', $part->primary_part_number ?? ''))
                    ->class([
                    'w-full px-3 py-3 border border-gray-300 rounded-md text-sm bg-white',
                    'border-red-500' => $errors->has('part_number'),
                    'border-gray-300' => !$errors->has('part_number'),
                    ])
                    ->attributes([
                    'id' => 'part_number',
                    'placeholder' => 'Primary part number',
                    'required' => true,
                    ]) !!}

                    @error('part_number')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="unit_cost" class="block text-sm font-medium text-gray-700 mb-1 required">
                        Cost
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 h-[35px] pl-3 flex items-center text-gray-500">$</span>

                        <!-- <input type="number" id="unit_cost" name="unit_cost" step="0.01" min="0" data-digit-input="true"
                            value="{{ old('unit_cost', $part->unit_cost ?? '') }}"
                            class="w-full pl-8 pr-3 py-2 border border-gray-300 text-sm rounded-md bg-white @error('unit_cost') border-red-500 @enderror"
                            placeholder="0.00" required> -->

                        {!! html()->number('unit_cost', old('unit_cost', $part->primary_part_cost ?? ''))
                        ->class([
                        'w-full pl-8 pr-3 py-3 border border-gray-300 text-sm rounded-md bg-white',
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
                    @error('unit_cost')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-4">
                <label for="supplier" class="block text-sm font-medium text-gray-700 mb-1 required">
                    Supplier
                </label>

                {!! html()->select('supplier',
                collect($suppliers)->pluck('name', 'unique_id')->prepend('Select primary supplier', ''),
                old('supplier', $part->primary_part_supplier_id ?? '')
                )
                ->class([
                'w-full px-3 py-3 border border-gray-300 rounded-md text-sm bg-white',
                'border-red-500' => $errors->has('supplier'),
                'border-gray-300' => !$errors->has('supplier'),
                ])
                ->attributes([
                'id' => 'primary-supplier',
                'required' => true,
                ]) !!}

                @error('supplier')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Supplier Details --}}
            <div id="primary-supplier-details" class="mt-4 p-4 border border-dashed border-gray-300 rounded-md flex items-center justify-center text-gray-400 text-sm bg-white">
                Select supplier to view details
            </div>

        </div>

        {{-- Alternative Supplier 1 --}}
        <div class=" border border-gray-200 rounded-xl p-6 shadow-sm col-span-12 lg:col-span-4">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div class="md:col-span-2">
                    <label for="part_number_alt_1" class="block text-sm font-medium text-gray-700 mb-1">
                        Alt 1 Part Number
                    </label>

                    {!! html()->text('part_number_alt_1', old('part_number_alt_1', $part->alt_1_part_number ?? ''))
                    ->class('w-full px-3 py-3 border border-gray-300 rounded-md text-sm bg-white')
                    ->attributes([
                    'id' => 'part_number_alt_1',
                    'placeholder' => 'Alternative part number',
                    ]) !!}

                </div>

                <div>
                    <label for="cost_alt_1" class="block text-sm font-medium text-gray-700 mb-1">Cost</label>
                    <div class="relative">
                        <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400">$</span>

                        <!-- <input type="number" id="cost_alt_1" name="cost_alt_1" step="0.01" min="0" data-digit-input="true"
                            value="{{ old('cost_alt_1', $part->cost_alt_1 ?? '') }}"
                            class="w-full pl-8 pr-3 py-2 text-sm border border-gray-300 rounded-md transition-colors bg-white"
                            placeholder="0.00"> -->

                        {!! html()->number('cost_alt_1', old('cost_alt_1', $part->alt_1_part_cost ?? ''))
                        ->class('w-full pl-8 pr-3 py-3 text-sm border border-gray-300 rounded-md transition-colors bg-white')
                        ->attributes([
                        'id' => 'cost_alt_1',
                        'step' => '0.01',
                        'min' => 0,
                        'data-digit-input' => 'true',
                        'placeholder' => '0.00',
                        ]) !!}

                    </div>
                </div>
            </div>

            <div class="mt-4">
                <label for="supplier_alt_1" class="block text-sm font-medium text-gray-700 mb-1">Select Supplier</label>

                <!-- <select id="supplier_alt_1" name="supplier_alt_1"
                    class="w-full px-3 py-2 border border-gray-300 text-sm rounded-md  bg-white">
                    <option value="">Select alt supplier</option>
                    @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->unique_id }}">
                        {{ $supplier->name }}
                    </option>
                    @endforeach
                </select> -->

                {!! html()->select('supplier_alt_1',
                collect($suppliers)->pluck('name', 'unique_id')->prepend('Select alt supplier', ''),
                old('supplier_alt_1', $part->alt_1_part_supplier_id ?? '')
                )
                ->class('w-full px-3 py-3 border border-gray-300 text-sm rounded-md bg-white')
                ->attributes(['id' => 'supplier_alt_1']) !!}

            </div>

            {{-- Supplier Details --}}
            <div id="supplier_alt_1_details" class="mt-4 p-4 border border-dashed border-gray-300 rounded-md flex items-center justify-center text-gray-400 text-sm bg-white">
                Select supplier to view details
            </div>
        </div>

        {{-- Alternative Supplier 2 --}}
        <div class=" border border-gray-200 rounded-xl p-6 shadow-sm col-span-12 lg:col-span-4">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div class="md:col-span-2">
                    <label for="part_number_alt_2" class="block text-sm font-medium text-gray-700 mb-1">
                        Alt 2 Part Number
                    </label>
                    <!-- <input type="text" id="part_number_alt_2" name="part_number_alt_2"
                        value="{{ old('part_number_alt_2', $part->part_number_alt_2 ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white"
                        placeholder="Alternative part number"> -->


                    {!! html()->text('part_number_alt_2', old('part_number_alt_2', $part->alt_2_part_number ?? ''))
                    ->class('w-full px-3 py-3 border border-gray-300 rounded-md text-sm bg-white')
                    ->attributes([
                    'id' => 'part_number_alt_2',
                    'placeholder' => 'Alternative part number',
                    ]) !!}

                </div>

                <div>
                    <label for="cost_alt_2" class="block text-sm font-medium text-gray-700 mb-1">Cost</label>
                    <div class="relative">
                        <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400">$</span>

                        <!-- <input type="number" id="cost_alt_2" name="cost_alt_2" step="0.01" min="0" data-digit-input="true"
                            value="{{ old('cost_alt_2', $part->cost_alt_2 ?? '') }}"
                            class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md text-sm bg-white"
                            placeholder="0.00"> -->

                        {!! html()->number('cost_alt_2', old('cost_alt_2', $part->alt_2_part_cost ?? ''))
                        ->class('w-full pl-8 pr-3 py-3 border border-gray-300 rounded-md text-sm bg-white')
                        ->attributes([
                        'id' => 'cost_alt_2',
                        'step' => '0.01',
                        'min' => 0,
                        'data-digit-input' => 'true',
                        'placeholder' => '0.00',
                        ]) !!}


                    </div>
                </div>
            </div>

            <div class="mt-4">
                <label for="supplier_alt_2" class="block text-sm font-medium text-gray-700 mb-1">Select Supplier</label>
                <!-- <select id="supplier_alt_2" name="supplier_alt_2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white">
                    <option value="">Select alt supplier</option>
                    @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->unique_id }}">
                        {{ $supplier->name }}
                    </option>
                    @endforeach
                </select> -->

                {!! html()->select('supplier_alt_2',
                collect($suppliers)->pluck('name', 'unique_id')->prepend('Select alt supplier', ''),
                old('supplier_alt_2', $part->alt_2_part_supplier_id ?? '')
                )
                ->class('w-full px-3 py-3 border border-gray-300 rounded-md text-sm bg-white')
                ->attributes(['id' => 'supplier_alt_2']) !!}

            </div>

            {{-- Supplier Details --}}
            <div id="supplier_alt_2_details" class="mt-4 p-4 border border-dashed border-gray-300 rounded-md flex items-center justify-center text-gray-400 text-sm bg-white">
                Select supplier to view details
            </div>
        </div>

    </div>

</div>

@push('js')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const equipmentSelect = document.getElementById('equipment_id');
        const categoryDisplay = document.getElementById('category_display');
        const dniCheckbox = document.getElementById('dni');
        const stockSection = document.getElementById('stock_section');
        const currentStockInput = document.getElementById('current_stock');
        const minStockInput = document.getElementById('min_stock');

        // Update category based on equipment selection
        equipmentSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const category = selectedOption.getAttribute('data-category') || 'Select equipment first';
            categoryDisplay.textContent = category;
        });

        // Handle DNI checkbox
        function toggleStockFields() {
            if (dniCheckbox.checked) {
                currentStockInput.disabled = true;
                minStockInput.disabled = true;
                currentStockInput.value = '';
                minStockInput.value = '';
                currentStockInput.placeholder = 'N/A';
                minStockInput.placeholder = 'N/A';


            } else {
                currentStockInput.disabled = false;
                minStockInput.disabled = false;
                currentStockInput.placeholder = '0';
                minStockInput.placeholder = '0';


            }
        }

        dniCheckbox.addEventListener('change', toggleStockFields);

        // Initialize on page load
        toggleStockFields();
    });
</script>
<!--
**
* Fetch and display supplier details dynamically.
**
-->
<script>
    document.addEventListener('DOMContentLoaded', function() {

        /**
         * Fetch and display supplier details dynamically.
         * @param {string} selectId - The ID of the <select> element.
         * @param {string} displayId - The ID of the div where details should appear.
         */
        function fetchSupplierDetails(selectId, displayId) {
            const select = document.getElementById(selectId);
            const display = document.getElementById(displayId);

            if (!select || !display) return;

            select.addEventListener('change', function() {
                const supplierId = this.value;

                if (!supplierId) {
                    display.innerHTML = `
                    <div > Select supplier to view details</div>
                `;
                    return;
                }

                display.innerHTML = `
                <div >Loading supplier details...</div>
            `;

                //  Use route() with a dummy parameter and replace it dynamically
                const routeTemplate = "{{ route('admin.maintenance-management.parts.get-supplier-details', ['unique_id' => '__SUPPLIER_ID__']) }}";
                const url = routeTemplate.replace('__SUPPLIER_ID__', supplierId);

                fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Network error');
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            display.innerHTML = `<div class="mt-4 border border-dashed border-gray-300 rounded-md h-20 flex items-center justify-center text-red-500 text-sm bg-white" >${data.error}</div>`;
                            return;
                        }

                        display.innerHTML = `
                     <div class="w-full px-2 py-3 bg-blue-50 border border-blue-200 rounded text-x">
                        <div class="font-medium text-blue-900 mb-1">${data.name ?? 'N/A'}</div>
                        <div class="space-y-0.5 text-blue-800">
                            <div class="truncate">${data.address ?? data.full_address ?? '—'}</div>
                            <div>${data.phone ?? '—'}</div>
                            <div>${data.primary_contact_name ?? data.contact_person ?? '—'}</div>
                        </div>
                    </div>
                `;
                    })
                    .catch(error => {
                        console.error(error);
                        display.innerHTML = `
                    <div class="text-red-500 text-sm">Failed to load supplier details.</div>
                `;
                    });
            });
        }

        // Initialize all supplier dropdowns
        fetchSupplierDetails('primary-supplier', 'primary-supplier-details');
        fetchSupplierDetails('supplier_alt_1', 'supplier_alt_1_details');
        fetchSupplierDetails('supplier_alt_2', 'supplier_alt_2_details');

        //  If editing, auto-fetch supplier details
        const currentSupplier = document.getElementById('primary-supplier')?.value;
        if (currentSupplier) {
            const event = new Event('change');
            document.getElementById('primary-supplier').dispatchEvent(event);
        }

        //  If editing, auto-fetch supplier details
        const supplier_alt_1 = document.getElementById('supplier_alt_1')?.value;
        if (supplier_alt_1) {
            const event = new Event('change');
            document.getElementById('supplier_alt_1').dispatchEvent(event);
        }

        //  If editing, auto-fetch supplier details
        const supplier_alt_2 = document.getElementById('supplier_alt_2')?.value;
        if (supplier_alt_2) {
            const event = new Event('change');
            document.getElementById('supplier_alt_2').dispatchEvent(event);
        }

    });
</script>

@endpush
