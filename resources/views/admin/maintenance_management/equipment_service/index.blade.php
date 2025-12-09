@extends('admin.layouts.app')

@section('title', 'Equipment Service')

@section('content')
<div class="h-screen bg-gray-50 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-auto p-6">
        {{-- Header --}}
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-900">Equipment Service Schedule</h2>
            <p class="text-gray-600 mt-1">View and manage service history for equipment</p>
        </div>

        {{-- Equipment Selector --}}
        <div class="bg-white rounded-lg p-6 mb-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category:</label>
                    <select id="category-selector" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Categories</option>
                        @foreach($equipmentWithService->pluck('productCategory')->unique('id')->filter()->sortBy('title') as $category)
                            <option value="{{ $category->id }}">{{ $category->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Equipment:</label>
                    <select id="equipment-selector" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Equipment</option>
                        @foreach($equipmentWithService->sortBy(function($item) { return strtolower($item->equipment_name); }) as $item)
                            <option value="{{ $item->unique_id }}" data-category-id="{{ $item->productCategory?->id }}">
                                {{ $item->equipment_name }} ({{ $item->equipment_id }}) - {{ $item->equipment_hours ?? 0 }} hrs
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @if($equipmentWithService->isNotEmpty())
            {{-- Equipment Info Card --}}
            <div id="equipment-info-card" class="bg-blue-50 rounded-lg p-4 mb-6" style="display: none;">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-wrench class="w-5 h-5 text-blue-600" />
                    <div>
                        <div class="font-semibold text-blue-900" id="equipment-template-name">No Template</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 mt-2">
                    <x-heroicon-o-clock class="w-5 h-5 text-blue-600" />
                    <div class="font-semibold text-blue-900" id="equipment-current-hours">Current Hours: 0</div>
                </div>
            </div>

            {{-- Service Schedule Table --}}
            <div id="service-schedule-table" class="bg-white rounded-lg overflow-x-auto mb-6" style="display: none;">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left px-6 py-3 bg-gray-50 font-medium text-gray-700 w-[300px] sticky left-0 z-10">
                                Service Task
                            </th>
                            <th class="text-center px-6 py-3 bg-gray-50 font-medium text-gray-700" id="interval-headers">
                            </th>
                        </tr>
                    </thead>
                    <tbody id="task-rows">
                    </tbody>
                </table>
            </div>

            {{-- Status Legend --}}
            <div class="bg-white rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Status Legend</h3>
                <div class="grid grid-cols-4 gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-100 border-2 border-green-300 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-wrench class="w-5 h-5 text-green-600" />
                        </div>
                        <span class="text-sm text-gray-700">Completed</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-yellow-100 border-2 border-yellow-300 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-clock class="w-5 h-5 text-yellow-600" />
                        </div>
                        <span class="text-sm text-gray-700">Pending</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-red-100 border-2 border-red-300 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-600" />
                        </div>
                        <span class="text-sm text-gray-700">Overdue</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gray-100 border-2 border-gray-300 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-exclamation-circle class="w-5 h-5 text-gray-600" />
                        </div>
                        <span class="text-sm text-gray-700">Not Due</span>
                    </div>
                </div>
            </div>
        @else
            {{-- Empty State --}}
            <div class="bg-white rounded-lg p-12 text-center">
                <p class="text-gray-500">No equipment with service templates assigned</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelector = document.getElementById('category-selector');
    const equipmentSelector = document.getElementById('equipment-selector');
    const equipmentInfoCard = document.getElementById('equipment-info-card');
    const serviceScheduleTable = document.getElementById('service-schedule-table');
    
    if (!categorySelector || !equipmentSelector) return;
    
    // Store all equipment data
    const equipmentData = @json($equipmentWithService->keyBy('unique_id'));
    
    // Store all equipment options with their data
    const allEquipmentData = [];
    Array.from(equipmentSelector.options).forEach(option => {
        allEquipmentData.push({
            value: option.value,
            text: option.textContent,
            categoryId: option.getAttribute('data-category-id')
        });
    });
    
    // Sort all equipment data alphabetically
    allEquipmentData.sort((a, b) => {
        if (a.value === '' || b.value === '') return 0;
        return a.text.localeCompare(b.text);
    });

    // Handle equipment selection change
    equipmentSelector.addEventListener('change', function() {
        const selectedId = this.value;
        
        if (!selectedId) {
            // Hide info card and table when no equipment selected
            equipmentInfoCard.style.display = 'none';
            serviceScheduleTable.style.display = 'none';
            return;
        }
        
        const equipment = equipmentData[selectedId];
        if (!equipment) return;
        
        // Update equipment info card
        document.getElementById('equipment-template-name').textContent = 
            equipment.service_template?.name || 'No Template';
        document.getElementById('equipment-current-hours').textContent = 
            'Current Hours: ' + (equipment.equipment_hours || 0);
        equipmentInfoCard.style.display = 'block';
        
        // Update service schedule table
        if (equipment.service_template && equipment.service_template.preset && equipment.service_template.preset.intervals) {
            const intervals = equipment.service_template.preset.intervals;
            const tasks = equipment.service_template.template_tasks || [];
            
            // Update interval headers
            const intervalHeadersRow = document.querySelector('#service-schedule-table thead tr');
            intervalHeadersRow.innerHTML = `
                <th class="text-left px-6 py-3 bg-gray-50 font-medium text-gray-700 w-[300px] sticky left-0 z-10">
                    Service Task
                </th>
                ${intervals.map(interval => `
                    <th class="text-center px-6 py-3 bg-gray-50 font-medium text-gray-700">
                        ${interval}h
                    </th>
                `).join('')}
            `;
            
            // Build availability map: taskId -> Set of intervals defined for that task
            const availabilityMap = {};
            tasks.forEach(templateTask => {
                const tid = templateTask.task?.id;
                if (!tid) return;
                const ints = templateTask.intervals || templateTask.intervals_json || templateTask.interval || [];
                // normalize ints to array
                let arr = [];
                if (Array.isArray(ints)) arr = ints;
                else if (typeof ints === 'string') {
                    try { arr = JSON.parse(ints); } catch(e) { arr = []; }
                } else if (typeof ints === 'number') arr = [ints];

                if (!availabilityMap[tid]) availabilityMap[tid] = new Set();
                arr.forEach(v => availabilityMap[tid].add(v));
            });

            // Get unique tasks preserving order
            const uniqueTasks = [];
            const seenTaskIds = new Set();
            tasks.forEach(templateTask => {
                const t = templateTask.task;
                if (t && !seenTaskIds.has(t.id)) {
                    seenTaskIds.add(t.id);
                    uniqueTasks.push(t);
                }
            });
            
            // Sort tasks alphabetically by name
            uniqueTasks.sort((a, b) => (a.name || '').localeCompare(b.name || ''));

            // Update task rows
            const taskRowsBody = document.getElementById('task-rows');
            taskRowsBody.innerHTML = uniqueTasks.map(task => {
                const tid = task.id;
                return `
                <tr class="border-b border-gray-100">
                    <td class="px-6 py-4 font-medium text-gray-900 w-[300px] sticky left-0 bg-white z-10">
                        ${task.name || 'Unknown Task'}
                    </td>
                    ${intervals.map(interval => {
                        const hasInterval = availabilityMap[tid] ? availabilityMap[tid].has(interval) : true;
                        if (hasInterval) {
                            return `
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center">
                                        <button class="w-12 h-12 rounded-lg border-2 bg-gray-100 border-gray-300 transition-colors hover:opacity-80 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-600">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            `;
                        }
                        return `<td class="px-6 py-4"></td>`;
                    }).join('')}
                </tr>
            `}).join('');
            
            serviceScheduleTable.style.display = 'block';
        } else {
            serviceScheduleTable.style.display = 'none';
        }
    });

    categorySelector.addEventListener('change', function() {
        const selectedCategoryId = this.value;
        
        // Clear current options
        equipmentSelector.innerHTML = '';
        
        // Add "Select Equipment" placeholder first
        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = 'Select Equipment';
        equipmentSelector.appendChild(placeholderOption);
        
        // Filter and add options based on selected category
        // Filter and sort equipment data
        const filteredEquipment = allEquipmentData
            .filter(data => data.value !== '' && (selectedCategoryId === '' || data.categoryId === selectedCategoryId))
            .sort((a, b) => a.text.localeCompare(b.text));
        
        filteredEquipment.forEach(data => {
            const option = document.createElement('option');
            option.value = data.value;
            option.textContent = data.text;
            option.setAttribute('data-category-id', data.categoryId);
            equipmentSelector.appendChild(option);
        });
        
        // Hide info and table when category changes
        equipmentInfoCard.style.display = 'none';
        serviceScheduleTable.style.display = 'none';
    });
});
</script>
@endpush
