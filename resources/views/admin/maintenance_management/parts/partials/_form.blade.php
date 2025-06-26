<div class="space-y-6">
    {{-- Basic Information --}}
    <div class="grid grid-cols-6 gap-4">
        <div class="col-span-2">
            <label for="part_name" class="block text-sm font-medium text-gray-700 mb-1">
                Part Name <span class="text-red-500">*</span>
            </label>
            <input type="text" id="part_name" name="part_name" 
                   value="{{ old('part_name', $part->part_name ?? '') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors @error('part_name') border-red-500 @enderror"
                   placeholder="Enter part name" required>
            @error('part_name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="col-span-2">
            <label for="equipment_id" class="block text-sm font-medium text-gray-700 mb-1">
                Equipment <span class="text-red-500">*</span>
            </label>
            <select id="equipment_id" name="equipment_id" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors @error('equipment_id') border-red-500 @enderror" 
                    required>
                <option value="">Select equipment</option>
                @foreach($equipmentOptions as $equipment)
                    <option value="{{ $equipment['id'] }}" 
                            data-category="{{ $equipment['category'] }}"
                            {{ old('equipment_id', $part->equipment_id ?? '') == $equipment['id'] ? 'selected' : '' }}>
                        {{ $equipment['name'] }} ({{ $equipment['id'] }})
                    </option>
                @endforeach
            </select>
            @error('equipment_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <div id="category_display" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-600 text-sm">
                {{ old('category', $part->category ?? 'Select equipment first') }}
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                <span class="flex items-center">
                    <input type="checkbox" id="dni" name="dni" value="1" 
                           {{ old('dni', $part->dni ?? false) ? 'checked' : '' }}
                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded mr-2">
                    Do Not Inventory
                </span>
            </label>
        </div>
    </div>

    {{-- Stock Information --}}
    <div class="grid grid-cols-2 gap-4" id="stock_section">
        <div>
            <label for="current_stock" class="block text-sm font-medium text-gray-700 mb-1">
                Current Stock <span class="text-red-500" id="stock_required">*</span>
            </label>
            <input type="number" id="current_stock" name="current_stock" min="0"
                   value="{{ old('current_stock', $part->stock_level ?? '') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors @error('current_stock') border-red-500 @enderror"
                   placeholder="0">
            @error('current_stock')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="min_stock" class="block text-sm font-medium text-gray-700 mb-1">
                Min Stock <span class="text-red-500" id="min_required">*</span>
            </label>
            <input type="number" id="min_stock" name="min_stock" min="0"
                   value="{{ old('min_stock', $part->min_stock ?? '') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors @error('min_stock') border-red-500 @enderror"
                   placeholder="0">
            @error('min_stock')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Primary Supplier --}}
    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
        <h3 class="text-md font-semibold text-gray-900 mb-4">Primary Supplier</h3>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label for="part_number" class="block text-sm font-medium text-gray-700 mb-1">
                    Part Number <span class="text-red-500">*</span>
                </label>
                <input type="text" id="part_number" name="part_number"
                       value="{{ old('part_number', $part->part_number ?? '') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors font-mono text-sm bg-white @error('part_number') border-red-500 @enderror"
                       placeholder="Primary part number" required>
                @error('part_number')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="unit_cost" class="block text-sm font-medium text-gray-700 mb-1">
                    Cost <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400">$</span>
                    <input type="number" id="unit_cost" name="unit_cost" step="0.01" min="0"
                           value="{{ old('unit_cost', $part->unit_cost ?? '') }}"
                           class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors bg-white @error('unit_cost') border-red-500 @enderror"
                           placeholder="0.00" required>
                </div>
                @error('unit_cost')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="supplier" class="block text-sm font-medium text-gray-700 mb-1">
                    Supplier <span class="text-red-500">*</span>
                </label>
                <select id="supplier" name="supplier"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors bg-white @error('supplier') border-red-500 @enderror"
                        required>
                    <option value="">Select primary supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier }}" {{ old('supplier', $part->supplier ?? '') == $supplier ? 'selected' : '' }}>
                            {{ $supplier }}
                        </option>
                    @endforeach
                </select>
                @error('supplier')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Alternative Supplier 1 --}}
    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
        <h3 class="text-md font-semibold text-gray-900 mb-4">Alternative Supplier 1</h3>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label for="part_number_alt_1" class="block text-sm font-medium text-gray-700 mb-1">Alt 1 Part Number</label>
                <input type="text" id="part_number_alt_1" name="part_number_alt_1"
                       value="{{ old('part_number_alt_1', $part->part_number_alt_1 ?? '') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors font-mono text-sm bg-white"
                       placeholder="Alternative part number">
            </div>

            <div>
                <label for="cost_alt_1" class="block text-sm font-medium text-gray-700 mb-1">Cost</label>
                <div class="relative">
                    <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400">$</span>
                    <input type="number" id="cost_alt_1" name="cost_alt_1" step="0.01" min="0"
                           value="{{ old('cost_alt_1', $part->cost_alt_1 ?? '') }}"
                           class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors bg-white"
                           placeholder="0.00">
                </div>
            </div>

            <div>
                <label for="supplier_alt_1" class="block text-sm font-medium text-gray-700 mb-1">Select Supplier</label>
                <select id="supplier_alt_1" name="supplier_alt_1"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors bg-white">
                    <option value="">Select alt supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier }}" {{ old('supplier_alt_1', $part->supplier_alt_1 ?? '') == $supplier ? 'selected' : '' }}>
                            {{ $supplier }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Alternative Supplier 2 --}}
    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
        <h3 class="text-md font-semibold text-gray-900 mb-4">Alternative Supplier 2</h3>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label for="part_number_alt_2" class="block text-sm font-medium text-gray-700 mb-1">Alt 2 Part Number</label>
                <input type="text" id="part_number_alt_2" name="part_number_alt_2"
                       value="{{ old('part_number_alt_2', $part->part_number_alt_2 ?? '') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors font-mono text-sm bg-white"
                       placeholder="Alternative part number">
            </div>

            <div>
                <label for="cost_alt_2" class="block text-sm font-medium text-gray-700 mb-1">Cost</label>
                <div class="relative">
                    <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400">$</span>
                    <input type="number" id="cost_alt_2" name="cost_alt_2" step="0.01" min="0"
                           value="{{ old('cost_alt_2', $part->cost_alt_2 ?? '') }}"
                           class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors bg-white"
                           placeholder="0.00">
                </div>
            </div>

            <div>
                <label for="supplier_alt_2" class="block text-sm font-medium text-gray-700 mb-1">Select Supplier</label>
                <select id="supplier_alt_2" name="supplier_alt_2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors bg-white">
                    <option value="">Select alt supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier }}" {{ old('supplier_alt_2', $part->supplier_alt_2 ?? '') == $supplier ? 'selected' : '' }}>
                            {{ $supplier }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Description --}}
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea id="description" name="description" rows="4"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                  placeholder="Enter part description">{{ old('description', $part->description ?? '') }}</textarea>
    </div>
</div>

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
            document.getElementById('stock_required').style.display = 'none';
            document.getElementById('min_required').style.display = 'none';
            stockSection.classList.add('opacity-50');
        } else {
            currentStockInput.disabled = false;
            minStockInput.disabled = false;
            currentStockInput.placeholder = '0';
            minStockInput.placeholder = '0';
            document.getElementById('stock_required').style.display = 'inline';
            document.getElementById('min_required').style.display = 'inline';
            stockSection.classList.remove('opacity-50');
        }
    }

    dniCheckbox.addEventListener('change', toggleStockFields);
    
    // Initialize on page load
    toggleStockFields();
});
</script>


