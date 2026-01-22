@extends('admin.layouts.app')

@section('title', 'Parts Template Create')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="p-6">
        {{-- Header --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center space-x-3 mb-2">
                <a href="{{ route('admin.templates.index') }}" class="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h1 class="text-3xl font-bold text-gray-900">Create New Template</h1>
            </div>
            <p class="text-gray-600 ml-14">Create a reusable parts list template for equipment maintenance</p>
        </div>

        <form action="{{ route('admin.templates.create') }}" method="POST" id="templateForm">
            @csrf
            
            {{-- Template Information --}}
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Template Information</h2>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Template Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors @error('name') border-red-500 @enderror"
                               placeholder="Enter template name" required>
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select id="category" name="category"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors @error('category') border-red-500 @enderror"
                                required>
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ old('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
                            @endforeach
                        </select>
                        @error('category')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                              placeholder="Enter template description">{{ old('description') }}</textarea>
                </div>
            </div>

            {{-- Parts Management --}}
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Template Parts</h2>
                        <p class="text-sm text-gray-600">Parts included in this template (<span id="partsCount">0</span> parts)</p>
                    </div>
                    <button type="button" onclick="openAddPartsModal()"
                            class="flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>Add Parts</span>
                    </button>
                </div>

                @error('parts')
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-700 text-sm">{{ $message }}</p>
                    </div>
                @enderror

                <div id="selectedParts" class="hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700 w-8"></th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Part Name</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Part Number</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Category</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Unit Cost</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="partsTableBody" class="divide-y divide-gray-200">
                                {{-- Parts will be added here dynamically --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="noPartsMessage" class="text-center py-12 border-2 border-dashed border-gray-300 rounded-lg">
                    <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No parts added yet</h3>
                    <p class="text-gray-600 mb-4">Add parts to this template.</p>
                    <button type="button" onclick="openAddPartsModal()"
                            class="inline-flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>Add Parts</span>
                    </button>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('admin.templates.index') }}" 
                   class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center gap-2 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Create Template
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Add Parts Modal --}}
<div id="addPartsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Add Parts to Template</h2>
            <button onclick="closeAddPartsModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center space-x-4">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" id="partsSearch" placeholder="Search parts..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors">
                </div>
                <select id="categoryFilter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white min-w-[160px]">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-sm text-gray-600 mt-2"><span id="selectedCount">0</span> parts selected</p>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
            <div id="availablePartsList" class="space-y-2">
                @foreach($parts as $part)
                    <div class="part-item border rounded-lg p-4 cursor-pointer transition-colors border-gray-200 hover:border-gray-300"
                         data-part-id="{{ $part->id }}"
                         data-part-name="{{ $part->part_name }}"
                         data-part-number="{{ $part->part_number }}"
                         data-category="{{ $part->category }}"
                         data-unit-cost="{{ $part->unit_cost }}"
                         onclick="togglePartSelection(this)">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900">{{ $part->part_name }}</h3>
                                        <p class="text-xs text-gray-500">{{ $part->part_number }} • {{ $part->supplier }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $part->category }}
                                        </span>
                                        <p class="text-sm font-medium text-green-600 mt-1">${{ number_format($part->unit_cost, 2) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end space-x-3 p-6 border-t border-gray-200">
            <button onclick="closeAddPartsModal()" 
                    class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                Cancel
            </button>
            <button onclick="addSelectedParts()" 
                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                Add Selected Parts
            </button>
        </div>
    </div>
</div>

<script>
let selectedParts = [];
let templateParts = [];

function openAddPartsModal() {
    document.getElementById('addPartsModal').classList.remove('hidden');
    filterParts();
}

function closeAddPartsModal() {
    document.getElementById('addPartsModal').classList.add('hidden');
    clearPartSelections();
}

function togglePartSelection(element) {
    const partId = element.dataset.partId;
    const checkbox = element.querySelector('input[type="checkbox"]');
    
    if (selectedParts.includes(partId)) {
        selectedParts = selectedParts.filter(id => id !== partId);
        checkbox.checked = false;
        element.classList.remove('border-purple-300', 'bg-purple-50');
        element.classList.add('border-gray-200');
    } else {
        selectedParts.push(partId);
        checkbox.checked = true;
        element.classList.remove('border-gray-200');
        element.classList.add('border-purple-300', 'bg-purple-50');
    }
    
    updateSelectedCount();
}

function updateSelectedCount() {
    document.getElementById('selectedCount').textContent = selectedParts.length;
}

function clearPartSelections() {
    selectedParts = [];
    document.querySelectorAll('.part-item').forEach(item => {
        item.classList.remove('border-purple-300', 'bg-purple-50');
        item.classList.add('border-gray-200');
        item.querySelector('input[type="checkbox"]').checked = false;
    });
    updateSelectedCount();
}

function addSelectedParts() {
    selectedParts.forEach(partId => {
        const partElement = document.querySelector(`[data-part-id="${partId}"]`);
        if (partElement && !templateParts.find(p => p.id === partId)) {
            const part = {
                id: partId,
                name: partElement.dataset.partName,
                part_number: partElement.dataset.partNumber,
                category: partElement.dataset.category,
                unit_cost: parseFloat(partElement.dataset.unitCost)
            };
            templateParts.push(part);
        }
    });
    
    renderTemplateParts();
    closeAddPartsModal();
}

function renderTemplateParts() {
    const tbody = document.getElementById('partsTableBody');
    const noPartsMessage = document.getElementById('noPartsMessage');
    const selectedPartsSection = document.getElementById('selectedParts');
    
    tbody.innerHTML = '';
    
    if (templateParts.length === 0) {
        noPartsMessage.classList.remove('hidden');
        selectedPartsSection.classList.add('hidden');
    } else {
        noPartsMessage.classList.add('hidden');
        selectedPartsSection.classList.remove('hidden');
        
        templateParts.forEach((part, index) => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 transition-colors';
            row.draggable = true;
            row.dataset.index = index;
            
            row.innerHTML = `
                <td class="py-3 px-4">
                    <div class="cursor-move text-gray-400 hover:text-gray-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                        </svg>
                    </div>
                </td>
                <td class="py-3 px-4">
                    <span class="text-sm font-medium text-gray-900">${part.name}</span>
                </td>
                <td class="py-3 px-4">
                    <span class="text-sm font-mono text-gray-700">${part.part_number}</span>
                </td>
                <td class="py-3 px-4">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        ${part.category}
                    </span>
                </td>
                <td class="py-3 px-4">
                    <span class="text-sm font-medium text-green-600">${part.unit_cost.toFixed(2)}</span>
                </td>
                <td class="py-3 px-4">
                    <button onclick="removePartFromTemplate(${index})" class="p-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors" title="Remove from template">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </td>
                <input type="hidden" name="parts[]" value="${part.id}">
            `;
            
            tbody.appendChild(row);
        });
    }
    
    document.getElementById('partsCount').textContent = templateParts.length;
}

function removePartFromTemplate(index) {
    templateParts.splice(index, 1);
    renderTemplateParts();
}

function filterParts() {
    const search = document.getElementById('partsSearch').value.toLowerCase();
    const category = document.getElementById('categoryFilter').value;
    
    document.querySelectorAll('.part-item').forEach(item => {
        const partName = item.dataset.partName.toLowerCase();
        const partNumber = item.dataset.partNumber.toLowerCase();
        const partCategory = item.dataset.category;
        const partId = item.dataset.partId;
        
        const matchesSearch = !search || partName.includes(search) || partNumber.includes(search);
        const matchesCategory = !category || partCategory === category;
        const notInTemplate = !templateParts.find(p => p.id === partId);
        
        if (matchesSearch && matchesCategory && notInTemplate) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Event listeners
document.getElementById('partsSearch').addEventListener('input', filterParts);
document.getElementById('categoryFilter').addEventListener('change', filterParts);

// Form submission
document.getElementById('templateForm').addEventListener('submit', function(e) {
    if (templateParts.length === 0) {
        e.preventDefault();
        alert('Please add at least one part to the template.');
        return false;
    }
});
</script>
@endsection
