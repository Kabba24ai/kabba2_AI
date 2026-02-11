@extends('admin.layouts.app')

@section('title', 'Equipment Service - ' . $equipment->equipment_name)

@section('content')
<div class="h-screen bg-gray-50 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-auto p-6">
        {{-- Header --}}
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.maintenance-management.equipment.index') }}" 
                       class="text-gray-600 hover:text-gray-900 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">{{ $equipment->equipment_name }}</h2>
                        <p class="text-gray-600 mt-1">{{ $equipment->equipment_id }} - Service Schedule</p>
                    </div>
                </div>
            </div>
        </div>

        @if($equipment)
            {{-- Equipment Info Card --}}
            <div id="equipment-info-card" class="bg-blue-50 rounded-lg p-4 mb-6">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-wrench class="w-5 h-5 text-blue-600" />
                    <div>
                        <div class="font-semibold text-blue-900" id="equipment-template-name">No Template</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 mt-2">
                    <x-heroicon-o-clock class="w-5 h-5 text-blue-600" />
                    <div class="font-semibold text-blue-900" id="equipment-current-value">Current Hours: 0</div>
                </div>
            </div>

            {{-- Service Schedule Table --}}
            <div id="service-schedule-table" class="bg-white rounded-lg overflow-x-auto mb-6">
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
// Toast notification helper
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 z-[9999] px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 transition-all transform translate-x-0 opacity-100';
    
    if (type === 'success') {
        toast.classList.add('bg-green-600', 'text-white');
        toast.innerHTML = `
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span class="font-medium">${message}</span>
        `;
    } else if (type === 'error') {
        toast.classList.add('bg-red-600', 'text-white');
        toast.innerHTML = `
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span class="font-medium">${message}</span>
        `;
    }
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.transform = 'translateX(400px)';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    const equipmentInfoCard = document.getElementById('equipment-info-card');
    const serviceScheduleTable = document.getElementById('service-schedule-table');
    
    // Store all equipment data
    const equipmentData = @json($equipmentWithService->keyBy('unique_id'));
    
    // Store service records by equipment_task key
    const serviceRecordsData = @json($serviceRecords);
    
    // Get the current equipment
    const currentEquipment = equipmentData['{{ $equipment->unique_id }}'];

    // Helper function for calculating days - make it global
    const calculateDaysSinceAcquisition = (dateAcquired) => {
        if (!dateAcquired) return 0;
        const acquired = new Date(dateAcquired);
        const today = new Date();
        const diffTime = Math.abs(today - acquired);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays;
    };

    // Work Order Modal Functions
    window.openWorkOrderModal = function(interval) {
        const equipment = currentEquipment;
        if (!equipment || !equipment.service_template) return;
        
        // Get tasks for this interval
        const tasks = equipment.service_template.template_tasks || [];
        const tasksForInterval = [];
        const seenTaskIds = new Set();
        
        tasks.forEach(templateTask => {
            const task = templateTask.task;
            if (!task || seenTaskIds.has(task.id)) return;
            
            const ints = templateTask.intervals || templateTask.intervals_json || templateTask.interval || [];
            let arr = [];
            if (Array.isArray(ints)) arr = ints;
            else if (typeof ints === 'string') {
                try { arr = JSON.parse(ints); } catch(e) { arr = []; }
            } else if (typeof ints === 'number') arr = [ints];
            
            if (arr.includes(interval)) {
                seenTaskIds.add(task.id);
                tasksForInterval.push(task);
            }
        });
        
        // Sort tasks alphabetically
        tasksForInterval.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
        
        // Determine interval type
        const intervalType = equipment?.service_template?.preset?.interval_type || 'hour';
        const isDateBased = intervalType === 'date';
        
        // Populate work order modal
        document.getElementById('wo-equipment-name').textContent = equipment.equipment_name || 'Unknown';
        document.getElementById('wo-equipment-id').textContent = equipment.equipment_id || 'N/A';
        
        if (isDateBased) {
            const currentDays = calculateDaysSinceAcquisition(equipment.date_acquired);
            document.getElementById('wo-current-hours-label').textContent = 'Passed Days';
            document.getElementById('wo-current-hours').textContent = currentDays + ' days (since ' + (equipment.date_acquired ? new Date(equipment.date_acquired).toLocaleDateString() : 'N/A') + ')';
            document.getElementById('wo-service-interval').textContent = interval + ' days';
            document.getElementById('wo-actual-hours-label').textContent = 'Passed Days at Service';
        } else {
            document.getElementById('wo-current-hours-label').textContent = 'Current Hours';
            document.getElementById('wo-current-hours').textContent = (equipment.equipment_hours || 0) + ' hours';
            document.getElementById('wo-service-interval').textContent = interval + ' hours';
            document.getElementById('wo-actual-hours-label').textContent = 'Actual Hours at Service';
        }
        
        // Populate tasks checklist
        const tasksContainer = document.getElementById('wo-tasks-checklist');
        tasksContainer.innerHTML = tasksForInterval.map((task, index) => `
            <div class="flex items-start gap-4 p-3 border-2 border-gray-300 rounded">
                <div class="flex-shrink-0 mt-1">
                    <div class="w-6 h-6 border-2 border-gray-800 rounded"></div>
                </div>
                <div class="flex-1">
                    <div class="font-bold text-gray-900 text-lg">${index + 1}. ${task.name || 'Unnamed Task'}</div>
                    ${task.description ? `<div class="text-sm text-gray-600 mt-1">${task.description}</div>` : ''}
                    ${task.estimated_duration > 0 ? `<div class="text-sm text-gray-500 mt-1">Est. Duration: ${task.estimated_duration} min</div>` : ''}
                </div>
                ${task.inspection_required ? `
                    <div class="flex-shrink-0 flex flex-col items-end gap-2">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 border-2 border-gray-800 rounded"></div>
                            <span class="text-gray-600 font-semibold">Inspection Required</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600 font-semibold">Initials:</span>
                            <div class="border-b-2 border-gray-800 w-32"></div>
                        </div>
                    </div>
                ` : ''}
            </div>
        `).join('');
        
        // Show modal
        document.getElementById('work-order-modal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };
    
    window.closeWorkOrderModal = function() {
        document.getElementById('work-order-modal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };
    
    window.printWorkOrder = function() {
        window.print();
    };

    // Load equipment data function
    function loadEquipmentData() {
        const equipment = currentEquipment;
        if (!equipment) return;
        
        // Get interval type from template
        const intervalType = equipment.service_template?.preset?.interval_type || 'hour';
        const isDateBased = intervalType === 'date';
        
        // Update equipment info card
        document.getElementById('equipment-template-name').textContent = 
            equipment.service_template?.name || 'No Template';
        
        if (isDateBased) {
            const currentDays = calculateDaysSinceAcquisition(equipment.date_acquired);
            document.getElementById('equipment-current-value').textContent = 
                'Current Days: ' + currentDays + ' (since ' + (equipment.date_acquired ? new Date(equipment.date_acquired).toLocaleDateString() : 'N/A') + ')';
        } else {
            document.getElementById('equipment-current-value').textContent = 
                'Current Hours: ' + (equipment.equipment_hours || 0);
        }
        equipmentInfoCard.style.display = 'block';
        
        // Update service schedule table
        if (equipment.service_template && equipment.service_template.preset && equipment.service_template.preset.intervals) {
            const intervals = equipment.service_template.preset.intervals;
            const tasks = equipment.service_template.template_tasks || [];
            
            // Helper function to determine if this is the current service interval
            const currentValue = isDateBased ? calculateDaysSinceAcquisition(equipment.date_acquired) : (equipment.equipment_hours || 0);
            
            // Helper to check if an interval has any incomplete tasks
            const hasIncompleteTasks = (interval) => {
                // Get all tasks for this interval
                const tasksForInterval = tasks.filter(templateTask => {
                    const ints = templateTask.intervals || templateTask.intervals_json || templateTask.interval || [];
                    let arr = [];
                    if (Array.isArray(ints)) arr = ints;
                    else if (typeof ints === 'string') {
                        try { arr = JSON.parse(ints); } catch(e) { arr = []; }
                    } else if (typeof ints === 'number') arr = [ints];
                    return arr.includes(interval);
                });
                
                // Check if any task at this interval is incomplete
                return tasksForInterval.some(templateTask => {
                    const taskId = templateTask.task?.id;
                    if (!taskId) return false;
                    
                    const recordKey = equipment.id + '_' + taskId;
                    const records = serviceRecordsData[recordKey] || [];
                    const hasCompletedRecord = records.some(record => record.interval_value == interval);
                    
                    return !hasCompletedRecord; // Task is incomplete
                });
            };
            
            const isCurrentServiceInterval = (interval) => {
                // Check if this interval has any incomplete tasks
                if (!hasIncompleteTasks(interval)) return false;
                
                // Find all intervals with incomplete tasks
                const intervalsWithIncompleteTasks = intervals.filter(int => hasIncompleteTasks(int));
                
                if (intervalsWithIncompleteTasks.length === 0) return false;
                
                // Return true if this is the earliest interval with incomplete tasks
                const earliestIncomplete = Math.min(...intervalsWithIncompleteTasks);
                return interval === earliestIncomplete;
            };
            
            const intervalSuffix = isDateBased ? 'd' : 'h';

            // Update interval headers
            const intervalHeadersRow = document.querySelector('#service-schedule-table thead tr');
            intervalHeadersRow.innerHTML = `
                <th class="text-left px-6 py-3 bg-gray-50 font-medium text-gray-700 w-[300px] sticky left-0 z-10">
                    Service Task
                </th>
                ${intervals.map(interval => `
                    <th class="text-center px-6 py-3 bg-gray-50 font-medium text-gray-700">
                        <div class="flex flex-col items-center gap-2">
                            <div class="font-medium text-gray-700">${interval}${intervalSuffix}</div>
                            ${isCurrentServiceInterval(interval) ? `
                                <button 
                                    onclick="openWorkOrderModal(${interval})" 
                                    class="flex items-center gap-1 px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors"
                                    title="Print work order for this interval"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                                    </svg>
                                    Work Order
                                </button>
                            ` : ''}
                        </div>
                    </th>
                `).join('')}
            `;

            // Settings thresholds (from server-side) - use Blade guards to avoid undefined variable
            const settings = {
                pending_before_hours: @isset($settings) {{ $settings->pending_before_hours }} @else 20 @endisset,
                pending_after_hours: @isset($settings) {{ $settings->pending_after_hours }} @else 15 @endisset,
            };

            // Helper to determine status based on current value, interval and thresholds
            const getStatusForInterval = (currentValue, intervalValue) => {
                const before = parseInt(settings.pending_before_hours ?? 20);
                const after = parseInt(settings.pending_after_hours ?? 15);
                const greyThreshold = intervalValue - before; // current < greyThreshold => Grey
                const yellowMax = intervalValue + after; // current <= yellowMax => Yellow

                if (currentValue < greyThreshold) return 'grey';
                if (currentValue <= yellowMax) return 'yellow';
                return 'red';
            };

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
                            // Check if there's a completed service record for this specific interval
                            const recordKey = equipment.id + '_' + tid;
                            const records = serviceRecordsData[recordKey] || [];
                            const intervalVal = Number(interval);
                            const hasCompletedRecord = records.some(record => record.interval_value == intervalVal);
                            
                            // determine status
                            const status = hasCompletedRecord ? 'completed' : getStatusForInterval(currentValue, intervalVal);

                            let btnClasses = 'w-12 h-12 rounded-lg border-2 transition-colors flex items-center justify-center';
                            let svgClasses = 'w-5 h-5';
                            const equipmentId = equipment.id;
                            
                            if (status === 'completed') {
                                btnClasses += ' bg-green-100 border-green-300';
                                svgClasses += ' text-green-600';
                            } else if (status === 'grey') {
                                btnClasses += ' bg-gray-100 border-gray-300';
                                svgClasses += ' text-gray-600';
                            } else if (status === 'yellow') {
                                btnClasses += ' bg-yellow-100 border-yellow-300';
                                svgClasses += ' text-yellow-600';
                            } else if (status === 'red') {
                                btnClasses += ' bg-red-100 border-red-300';
                                svgClasses += ' text-red-600';
                            }

                            // Status-specific SVG markup
                            let svgInner = '';
                            if (status === 'completed') {
                                svgInner = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="' + svgClasses + '" aria-hidden="true" data-slot="icon">'
                                    + '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 0 1-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 1 1-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 0 1 6.336-4.486l-3.276 3.276a3.004 3.004 0 0 0 2.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852Z"></path>'
                                    + '<path stroke-linecap="round" stroke-linejoin="round" d="M4.867 19.125h.008v.008h-.008v-.008Z"></path>'
                                    + '</svg>';
                            } else if (status === 'yellow') {
                                svgInner = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="' + svgClasses + '" aria-hidden="true" data-slot="icon">'
                                    + '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>'
                                    + '</svg>';
                            } else if (status === 'red') {
                                svgInner = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="' + svgClasses + '" aria-hidden="true" data-slot="icon">'
                                    + '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"></path>'
                                    + '</svg>';
                            } else {
                                svgInner = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="' + svgClasses + '">'
                                    + '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>'
                                    + '</svg>';
                            }

                            // include data attributes so we can open the record modal with context
                            // Prefer a description defined on the template_task (template-level) if available,
                            // otherwise fall back to the main task's description.
                            let templateLevelDesc = '';
                            try {
                                const tt = (tasks || []).find(tt => tt.task && String(tt.task.id) === String(tid));
                                templateLevelDesc = tt && (tt.description || tt.task_description) ? (tt.description || tt.task_description) : '';
                            } catch (e) {
                                templateLevelDesc = '';
                            }
                            const safeTaskName = (task.name || '').toString().replace(/"/g, '&quot;');
                            const safeTaskDesc = (templateLevelDesc || task.description || '').toString().replace(/"/g, '&quot;');
                            const templateId = equipment.service_template?.id || '';
                            // Find the record that matches this specific interval
                            const matchingRecord = records.find(r => r.interval_value == intervalVal);
                            const recordId = matchingRecord ? matchingRecord.id : '';
                            return `
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center">
                                        <button class="${btnClasses} hover:opacity-80" data-interval="${intervalVal}" data-task-id="${tid}" data-task-name="${safeTaskName}" data-task-desc="${safeTaskDesc}" data-current-hours="${currentValue}" data-equipment-id="${equipmentId}" data-template-id="${templateId}" data-record-id="${recordId}" data-is-completed="${hasCompletedRecord ? '1' : '0'}">` + svgInner + `
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
    }
    
    // Load equipment data on page load
    loadEquipmentData();
    
    // Delegate click for interval buttons to open Record Service modal
    serviceScheduleTable.addEventListener('click', function(e) {
        const btn = e.target.closest('button[data-interval]');
        if (!btn) return;
        const interval = btn.getAttribute('data-interval');
        const taskName = btn.getAttribute('data-task-name') || '';
        const taskDesc = btn.getAttribute('data-task-desc') || '';
        const currentHours = btn.getAttribute('data-current-hours') || 0;
        const equipmentId = btn.getAttribute('data-equipment-id');
        const templateId = btn.getAttribute('data-template-id');
        const taskId = btn.getAttribute('data-task-id');
        const recordId = btn.getAttribute('data-record-id');
        const isCompleted = btn.getAttribute('data-is-completed') === '1';
        
        openRecordModal({ 
            interval: Number(interval), 
            taskName, 
            taskDesc,
            currentHours: Number(currentHours),
            equipmentId,
            templateId,
            taskId,
            recordId,
            isCompleted
        });
    });

    // Modal helpers
    function openRecordModal({ interval, taskName, taskDesc, currentHours, equipmentId, templateId, taskId, recordId, isCompleted }) {
        const modal = document.getElementById('record-service-modal');
        if (!modal) return;
        
        // Store context data in modal for form submission
        modal.setAttribute('data-equipment-id', equipmentId || '');
        modal.setAttribute('data-template-id', templateId || '');
        modal.setAttribute('data-task-id', taskId || '');
        modal.setAttribute('data-interval', interval || '');
        modal.setAttribute('data-record-id', recordId || '');
        modal.setAttribute('data-is-completed', isCompleted ? '1' : '0');
        modal.setAttribute('data-is-edit-mode', '0');
        
        // Get equipment to determine interval type
        const equipment = currentEquipment;
        const intervalType = equipment?.service_template?.preset?.interval_type || 'hour';
        const isDateBased = intervalType === 'date';
        const suffix = isDateBased ? 'd' : 'h';
        const label = isDateBased ? 'days' : 'hours';
        
        const recordModalIntervalEl = modal.querySelector('#record-modal-interval');
        if (recordModalIntervalEl) recordModalIntervalEl.textContent = "Scheduled Service: " + interval + suffix;
        const recordModalTaskEl = modal.querySelector('#record-modal-task');
        if (recordModalTaskEl) recordModalTaskEl.textContent = (taskName ? taskName + ' - ' + interval + suffix : 'Service Task');
        const recordModalTaskDescEl = modal.querySelector('#record-modal-task-desc');

        // Helper: render description and reference links into the container safely
        function renderTaskDescriptionAndLinks(container, description, equipmentObj, taskIdRef) {
            if (!container) return;
            container.innerHTML = '';

            if (description && String(description).trim() !== '') {
                const descDiv = document.createElement('div');
                descDiv.textContent = 'Description: ' + description;
                container.appendChild(descDiv);
            }

            // Resolve reference links from template_task or task
            let refLinks = [];
            try {
                const templateTasks = equipmentObj?.service_template?.template_tasks || [];
                const templateTask = templateTasks.find(tt => String(tt.task?.id) === String(taskIdRef)) || null;
                if (templateTask) {
                    if (Array.isArray(templateTask.reference_links) && templateTask.reference_links.length) {
                        refLinks = templateTask.reference_links;
                    } else if (Array.isArray(templateTask.task?.reference_links) && templateTask.task.reference_links.length) {
                        refLinks = templateTask.task.reference_links;
                    }
                }
            } catch (e) {
                console.warn('Failed to resolve reference links for task', e);
            }

            if (refLinks && refLinks.length) {
                const refWrapper = document.createElement('div');
                refWrapper.className = 'mt-2';
                const label = document.createElement('div');
                label.textContent = 'Reference Links:';
                label.className = 'font-medium text-sm';
                refWrapper.appendChild(label);

                const list = document.createElement('div');
                list.className = 'flex flex-col gap-1 mt-1';
                refLinks.forEach(link => {
                    const a = document.createElement('a');
                    try { a.href = link; } catch (e) { a.href = '#'; }
                    a.target = '_blank';
                    a.rel = 'noopener noreferrer';
                    a.textContent = link;
                    a.className = 'text-blue-600 underline text-sm';
                    list.appendChild(a);
                });
                refWrapper.appendChild(list);
                container.appendChild(refWrapper);
            }
        }

        // Render initial description + links (button-provided desc preferred)
        renderTaskDescriptionAndLinks(recordModalTaskDescEl, taskDesc, equipment, taskId);
        
        // Show/hide actual hours field based on interval type
        const actualHoursContainer = document.getElementById('actual-hours-container');
        const actualHoursInput = document.getElementById('record-input-actual-hours');
        
        if (isDateBased) {
            const currentDays = currentHours; // This is actually days passed from the button
            modal.querySelector('#record-modal-current-hours').textContent = 'Current equipment days: ' + currentDays + ' (since ' + (equipment.date_acquired ? new Date(equipment.date_acquired).toLocaleDateString() : 'N/A') + ')';
            // Hide actual hours field for date-based and set to 0
            actualHoursContainer.style.display = 'none';
            actualHoursInput.value = '0';
        } else {
            modal.querySelector('#record-modal-current-hours').textContent = 'Current equipment hours: ' + currentHours;
            // Show actual hours field for hour-based
            actualHoursContainer.style.display = 'block';
        }

        // Determine if this task requires inspection and toggle checked fields
        try {
            const templateTasks = equipment.service_template?.template_tasks || [];
            const templateTask = templateTasks.find(tt => String(tt.task?.id) === String(taskId)) || null;
            const inspectionRequired = templateTask ? (templateTask.inspection_required || templateTask.task?.inspection_required || false) : false;

            const checkedFields = modal.querySelectorAll('.checked-field');
            const checkedByInput = modal.querySelector('#record-input-checked-by');
            const checkedDateInput = modal.querySelector('#record-input-checked-date');

            if (inspectionRequired) {
                checkedFields.forEach(el => el.style.display = 'block');
                if (checkedByInput) checkedByInput.required = true;
                if (checkedDateInput) checkedDateInput.required = true;
            } else {
                checkedFields.forEach(el => el.style.display = 'none');
                if (checkedByInput) {
                    checkedByInput.required = false;
                    checkedByInput.value = '';
                }
                if (checkedDateInput) {
                    checkedDateInput.required = false;
                    checkedDateInput.value = '';
                    if (window._fpInstances && window._fpInstances['record-input-checked-date']) {
                        try { window._fpInstances['record-input-checked-date'].clear(); } catch(e) {}
                    }
                }
            }
        } catch (e) {
            console.warn('Failed to determine inspection requirement for task', e);
        }
        
        // If completed, load the existing data
            if (isCompleted && recordId) {
            const recordKey = equipmentId + '_' + taskId;
            const records = serviceRecordsData[recordKey] || [];
            // Find the record that matches this specific interval
            const record = records.find(r => r.interval_value == interval) || null;
            
                if (record) {
                document.getElementById('record-input-performed-by').value = record.performed_by || '';
                document.getElementById('record-input-checked-by').value = record.checked_by || '';
                document.getElementById('record-input-actual-hours').value = isDateBased ? '0' : (record.actual_hours || '');
                document.getElementById('record-input-notes').value = record.notes || '';
                    // Also set description element if present — only overwrite when record provides a description
                    const recordModalTaskDescEl = modal.querySelector('#record-modal-task-desc');
                    if (recordModalTaskDescEl) {
                        if (record.task_description && String(record.task_description).trim() !== '') {
                            renderTaskDescriptionAndLinks(recordModalTaskDescEl, record.task_description, equipment, taskId);
                        } else {
                            renderTaskDescriptionAndLinks(recordModalTaskDescEl, '', equipment, taskId);
                        }

                        // Append any record-level reference links if present
                        try {
                            if (Array.isArray(record.reference_links) && record.reference_links.length) {
                                const rr = record.reference_links;
                                const refWrapper = document.createElement('div');
                                refWrapper.className = 'mt-2';
                                const label = document.createElement('div');
                                label.textContent = 'Reference Links:';
                                label.className = 'font-medium text-sm';
                                refWrapper.appendChild(label);
                                const list = document.createElement('div');
                                list.className = 'flex flex-col gap-1 mt-1';
                                rr.forEach(link => {
                                    const a = document.createElement('a');
                                    try { a.href = link; } catch (e) { a.href = '#'; }
                                    a.target = '_blank';
                                    a.rel = 'noopener noreferrer';
                                    a.textContent = link;
                                    a.className = 'text-blue-600 underline text-sm';
                                    list.appendChild(a);
                                });
                                refWrapper.appendChild(list);
                                recordModalTaskDescEl.appendChild(refWrapper);
                            }
                        } catch (e) { console.warn('Failed to append record-level reference links', e); }
                    }
                
                // Update modal's record-id with the correct record
                modal.setAttribute('data-record-id', record.id || '');
                
                // Format dates for display
                if (record.performed_date) {
                    const pd = new Date(record.performed_date);
                    const pdStr = String(pd.getMonth() + 1).padStart(2, '0') + '/' + String(pd.getDate()).padStart(2, '0') + '/' + pd.getFullYear();
                    document.getElementById('record-input-performed-date').value = pdStr;
                    if (window._fpInstances && window._fpInstances['record-input-performed-date']) {
                        try { window._fpInstances['record-input-performed-date'].setDate(pdStr, false, 'm/d/Y'); } catch (e) {}
                    }
                }
                
                if (record.checked_date) {
                    const cd = new Date(record.checked_date);
                    const cdStr = String(cd.getMonth() + 1).padStart(2, '0') + '/' + String(cd.getDate()).padStart(2, '0') + '/' + cd.getFullYear();
                    document.getElementById('record-input-checked-date').value = cdStr;
                    if (window._fpInstances && window._fpInstances['record-input-checked-date']) {
                        try { window._fpInstances['record-input-checked-date'].setDate(cdStr, false, 'm/d/Y'); } catch (e) {}
                    }
                }
                
                // Disable all fields
                setFormFieldsDisabled(true);
                
                // Show Edit Record button, hide Save button
                document.getElementById('btn-save-record').style.display = 'none';
                document.getElementById('btn-edit-record').style.display = 'inline-block';
            }
        } else {
            // Clear all fields for new record
            const actualHoursInput = modal.querySelector('#record-input-actual-hours');
            if (actualHoursInput) actualHoursInput.value = isDateBased ? '0' : '';

            const performedBy = modal.querySelector('#record-input-performed-by');
            const checkedBy = modal.querySelector('#record-input-checked-by');
            if (performedBy) performedBy.value = '';
            if (checkedBy) checkedBy.value = '';

            const performedDate = modal.querySelector('#record-input-performed-date');
            const checkedDate = modal.querySelector('#record-input-checked-date');
            const now = new Date();
            const todayStr = String(now.getMonth() + 1).padStart(2, '0') + '/' + String(now.getDate()).padStart(2, '0') + '/' + now.getFullYear();
            if (performedDate) {
                performedDate.value = todayStr;
                if (window._fpInstances && window._fpInstances['record-input-performed-date']) {
                    try { window._fpInstances['record-input-performed-date'].setDate(todayStr, false, 'm/d/Y'); } catch (e) {}
                }
            }
            if (checkedDate) {
                checkedDate.value = '';
                if (window._fpInstances && window._fpInstances['record-input-checked-date']) {
                    try { window._fpInstances['record-input-checked-date'].clear(); } catch (e) {}
                }
            }
            
            // Render description and/or reference links (if button provided a description it will be used)
            const recordModalTaskDescEl = modal.querySelector('#record-modal-task-desc');
            if (recordModalTaskDescEl) {
                renderTaskDescriptionAndLinks(recordModalTaskDescEl, taskDesc, equipment, taskId);
            }

            document.getElementById('record-input-notes').value = '';
            
            // Enable all fields
            setFormFieldsDisabled(false);
            
            // Show Save button, hide Edit Record button
            document.getElementById('btn-save-record').style.display = 'inline-block';
            document.getElementById('btn-edit-record').style.display = 'none';
        }
        
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
    
    function setFormFieldsDisabled(disabled) {
        const performedByInput = document.getElementById('record-input-performed-by');
        const performedDateInput = document.getElementById('record-input-performed-date');
        const checkedByInput = document.getElementById('record-input-checked-by');
        const checkedDateInput = document.getElementById('record-input-checked-date');
        const actualHoursInput = document.getElementById('record-input-actual-hours');
        const notesInput = document.getElementById('record-input-notes');
        
        performedByInput.disabled = disabled;
        performedDateInput.disabled = disabled;
        checkedByInput.disabled = disabled;
        checkedDateInput.disabled = disabled;
        actualHoursInput.disabled = disabled;
        notesInput.disabled = disabled;
        
        // Toggle readonly attribute
        if (disabled) {
            performedByInput.setAttribute('readonly', 'true');
            performedDateInput.setAttribute('readonly', 'true');
            checkedByInput.setAttribute('readonly', 'true');
            checkedDateInput.setAttribute('readonly', 'true');
            actualHoursInput.setAttribute('readonly', 'true');
            notesInput.setAttribute('readonly', 'true');
            performedDateInput.classList.add('bg-gray-100', 'cursor-not-allowed');
            checkedDateInput.classList.add('bg-gray-100', 'cursor-not-allowed');
            document.querySelectorAll('.date-icon').forEach(icon => icon.style.pointerEvents = 'none');
        } else {
            performedByInput.removeAttribute('readonly');
            performedDateInput.removeAttribute('readonly');
            checkedByInput.removeAttribute('readonly');
            checkedDateInput.removeAttribute('readonly');
            actualHoursInput.removeAttribute('readonly');
            notesInput.removeAttribute('readonly');
            performedDateInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
            checkedDateInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
            document.querySelectorAll('.date-icon').forEach(icon => icon.style.pointerEvents = 'auto');
        }
    }

    function closeRecordModal() {
        const modal = document.getElementById('record-service-modal');
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // Close modal when clicking backdrop or close button
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('record-service-modal');
        if (!modal || modal.classList.contains('hidden')) return;
        if (e.target.id === 'record-service-backdrop' || e.target.closest('[data-action="close-record-modal"]')) {
            closeRecordModal();
        }
    });

    // Click on calendar icon focuses/opens the corresponding date input
    document.addEventListener('click', function(e) {
        const icon = e.target.closest('.date-icon');
        if (!icon) return;
        const wrapper = icon.closest('.date-wrapper');
        if (!wrapper) return;
        const input = wrapper.querySelector('.date-field');
        if (!input) return;
        // If flatpickr is available, open it; otherwise focus the input (native picker)
        if (window._fpInstances && window._fpInstances[input.id]) {
            try { window._fpInstances[input.id].open(); } catch (err) { input.focus(); }
        } else {
            input.focus();
        }
    });

    // Load Flatpickr (CSS + JS) dynamically and initialize datepickers for modal fields
    (function setupDatepickr() {
        const cssUrl = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
        const jsUrl = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js';

        function loadCss(url) {
            return new Promise((resolve) => {
                if (document.querySelector('link[href="' + url + '"]')) return resolve();
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = url;
                link.onload = resolve;
                document.head.appendChild(link);
            });
        }

        function loadScript(url) {
            return new Promise((resolve, reject) => {
                if (window.flatpickr) return resolve();
                const s = document.createElement('script');
                s.src = url;
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }

        Promise.resolve()
            .then(() => loadCss(cssUrl))
            .then(() => loadScript(jsUrl))
            .then(() => {
                try {
                    window._fpInstances = window._fpInstances || {};
                    const perf = flatpickr('#record-input-performed-date', {dateFormat: 'm/d/Y', allowInput: true});
                    const chk = flatpickr('#record-input-checked-date', {dateFormat: 'm/d/Y', allowInput: true});
                    window._fpInstances['record-input-performed-date'] = perf;
                    window._fpInstances['record-input-checked-date'] = chk;
                    
                    // Add validation listener for date changes
                    const performedInput = document.getElementById('record-input-performed-date');
                    const checkedInput = document.getElementById('record-input-checked-date');
                    
                    function validateDates() {
                        const performedVal = performedInput.value;
                        const checkedVal = checkedInput.value;
                        
                        if (performedVal && checkedVal) {
                            const performedDate = new Date(performedVal);
                            const checkedDate = new Date(checkedVal);
                            
                            if (checkedDate < performedDate) {
                                checkedInput.setCustomValidity('Checked date cannot be before performed date');
                                checkedInput.classList.add('border-red-500');
                            } else {
                                checkedInput.setCustomValidity('');
                                checkedInput.classList.remove('border-red-500');
                            }
                        }
                    }
                    
                    performedInput.addEventListener('change', validateDates);
                    checkedInput.addEventListener('change', validateDates);
                } catch (e) {
                    console.warn('flatpickr init failed', e);
                }
            })
            .catch(err => console.warn('Failed to load flatpickr', err));
    })();

    // Handle form submit
    const recordForm = document.getElementById('record-service-form');
    if (recordForm) {
        recordForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const modal = document.getElementById('record-service-modal');
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.textContent;
            
            // Validate dates
            const performedDateStr = document.getElementById('record-input-performed-date').value;
            const checkedDateStr = document.getElementById('record-input-checked-date').value;
            
            if (performedDateStr && checkedDateStr) {
                const performedDate = new Date(performedDateStr);
                const checkedDate = new Date(checkedDateStr);
                
                if (checkedDate < performedDate) {
                    showToast('Checked date cannot be before performed date', 'error');
                    return;
                }
            }
            
            // Check if this is an edit or new record
            const recordId = modal.getAttribute('data-record-id');
            const isEditMode = modal.getAttribute('data-is-edit-mode') === '1';
            
            // Get form data
            const formData = {
                equipment_id: modal.getAttribute('data-equipment-id'),
                service_template_id: modal.getAttribute('data-template-id'),
                service_task_id: modal.getAttribute('data-task-id'),
                interval: modal.getAttribute('data-interval'),
                interval_value: modal.getAttribute('data-interval'),
                interval_type: 'hour',
                performed_by: document.getElementById('record-input-performed-by').value,
                performed_date: document.getElementById('record-input-performed-date').value,
                checked_by: document.getElementById('record-input-checked-by').value,
                checked_date: document.getElementById('record-input-checked-date').value,
                actual_hours: document.getElementById('record-input-actual-hours').value,
                notes: document.getElementById('record-input-notes').value,
            };
            
            // Add record ID if editing
            if (isEditMode && recordId) {
                formData.record_id = recordId;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
            
            try {
                const response = await fetch('{{ route("admin.maintenance-management.equipment-service.record") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeRecordModal();
                    // Show success message
                    showToast(isEditMode ? 'Service record updated successfully!' : 'Service record saved successfully!', 'success');
                    
                    // Update the button to green (completed) status without page reload
                    const equipmentId = modal.getAttribute('data-equipment-id');
                    const taskId = modal.getAttribute('data-task-id');
                    const interval = modal.getAttribute('data-interval');
                    
                    // Update the serviceRecordsData with the new/updated record
                    const recordKey = equipmentId + '_' + taskId;
                    const updatedRecord = {
                        id: recordId || result.record_id,
                        equipment_id: equipmentId,
                        service_task_id: taskId,
                        interval_value: formData.interval_value,
                        interval_type: formData.interval_type,
                        performed_by: formData.performed_by,
                        performed_date: formData.performed_date,
                        checked_by: formData.checked_by,
                        checked_date: formData.checked_date,
                        actual_hours: formData.actual_hours,
                        notes: formData.notes
                    };
                    
                    // Update or add to serviceRecordsData
                    if (!serviceRecordsData[recordKey]) {
                        serviceRecordsData[recordKey] = [];
                    }
                    
                    if (isEditMode && recordId) {
                        // Update existing record in the array
                        const existingIndex = serviceRecordsData[recordKey].findIndex(r => r.id == recordId);
                        if (existingIndex >= 0) {
                            serviceRecordsData[recordKey][existingIndex] = updatedRecord;
                        } else {
                            serviceRecordsData[recordKey].push(updatedRecord);
                        }
                    } else {
                        // Add new record
                        serviceRecordsData[recordKey].push(updatedRecord);
                    }
                    
                    // Find and update the specific button
                    const targetBtn = serviceScheduleTable.querySelector(
                        `button[data-equipment-id="${equipmentId}"][data-task-id="${taskId}"][data-interval="${interval}"]`
                    );
                    
                    if (targetBtn) {
                        // Update button classes to green (completed)
                        targetBtn.className = 'w-12 h-12 rounded-lg border-2 transition-colors flex items-center justify-center bg-green-100 border-green-300 hover:opacity-80';
                        
                        // Update SVG to wrench icon (completed)
                        targetBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-green-600" aria-hidden="true" data-slot="icon">' +
                            '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 0 1-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 1 1-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 0 1 6.336-4.486l-3.276 3.276a3.004 3.004 0 0 0 2.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852Z"></path>' +
                            '</svg>';
                        
                        // Update button's data-is-completed attribute and record-id
                        targetBtn.setAttribute('data-is-completed', '1');
                        if (updatedRecord.id) {
                            targetBtn.setAttribute('data-record-id', updatedRecord.id);
                        }
                    }
                } else {
                    showToast(result.message || 'Failed to save service record', 'error');
                }
            } catch (error) {
                console.error('Error saving service record:', error);
                showToast('Failed to save service record. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        });
    }
    
    // Handle Edit Record button click
    document.getElementById('btn-edit-record').addEventListener('click', function() {
        openAdminAuthModal();
    });
    
    // Admin authorization modal handlers
    function openAdminAuthModal() {
        const modal = document.getElementById('admin-auth-modal');
        if (!modal) return;
        document.getElementById('admin-code-input').value = '';
        document.getElementById('admin-code-error').classList.add('hidden');
        modal.classList.remove('hidden');
    }
    
    function closeAdminAuthModal() {
        const modal = document.getElementById('admin-auth-modal');
        if (!modal) return;
        modal.classList.add('hidden');
    }
    
    // Close admin auth modal
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('admin-auth-modal');
        if (!modal || modal.classList.contains('hidden')) return;
        if (e.target.id === 'admin-auth-backdrop' || e.target.closest('[data-action="close-admin-auth-modal"]')) {
            closeAdminAuthModal();
        }
    });
    
    // Handle admin authorization form submit
    const adminAuthForm = document.getElementById('admin-auth-form');
    if (adminAuthForm) {
        adminAuthForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const adminCode = document.getElementById('admin-code-input').value;
            const errorDiv = document.getElementById('admin-code-error');
            const submitBtn = document.getElementById('btn-authorize');
            const originalBtnText = submitBtn.innerHTML;
            
            errorDiv.classList.add('hidden');
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Verifying...';
            
            try {
                const response = await fetch('{{ route("admin.maintenance-management.equipment-service.verify-admin-code") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ admin_code: adminCode })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeAdminAuthModal();
                    // Enable edit mode
                    const recordModal = document.getElementById('record-service-modal');
                    recordModal.setAttribute('data-is-edit-mode', '1');
                    setFormFieldsDisabled(false);
                    document.getElementById('btn-edit-record').style.display = 'none';
                    const saveBtn = document.getElementById('btn-save-record');
                    saveBtn.style.display = 'inline-block';
                    saveBtn.textContent = 'Update Service Record';
                } else {
                    errorDiv.textContent = result.message || 'Invalid admin code';
                    errorDiv.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error verifying admin code:', error);
                errorDiv.textContent = 'Failed to verify admin code. Please try again.';
                errorDiv.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }

});
</script>
@endpush

{{-- Work Order Modal --}}
<div id="work-order-modal" class="fixed inset-0 z-[99999] overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center py-4 hidden">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 p-4 flex items-center justify-between no-print">
            <h2 class="text-xl font-bold text-gray-900">Work Order Preview</h2>
            <div class="flex items-center gap-2">
                <button
                    onclick="printWorkOrder()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Print Work Order
                </button>
                <button
                    onclick="closeWorkOrderModal()"
                    class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="p-8 print-content">
            <div class="border-4 border-gray-800 p-8">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">SERVICE WORK ORDER</h1>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-8 pb-6 border-b-2 border-gray-300">
                    <div>
                        <div class="mb-3">
                            <div class="text-sm font-semibold text-gray-600 uppercase">Equipment Name</div>
                            <div class="text-xl font-bold text-gray-900" id="wo-equipment-name">-</div>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-600 uppercase">Equipment ID</div>
                            <div class="text-lg text-gray-900" id="wo-equipment-id">-</div>
                        </div>
                    </div>
                    <div>
                        <div class="mb-3">
                            <div class="text-sm font-semibold text-gray-600 uppercase" id="wo-current-hours-label">Current Hours</div>
                            <div class="text-xl font-bold text-gray-900" id="wo-current-hours">0 hours</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-sm font-semibold text-gray-600 uppercase">Service Interval</div>
                            <div class="text-2xl font-bold text-blue-600" id="wo-service-interval">0 hours</div>
                        </div>
                    </div>
                </div>

                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 uppercase">Service Tasks Checklist</h2>
                    <div class="space-y-3" id="wo-tasks-checklist">
                        {{-- Tasks will be populated by JavaScript --}}
                    </div>
                </div>

                <div class="mb-8 space-y-4">
                    <div>
                        <div class="text-sm font-semibold text-gray-600 uppercase mb-2">Service Notes / Additional Comments</div>
                        <div class="border-2 border-gray-300 rounded p-3 bg-gray-50" style="min-height: 100px;"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 pt-6 border-t-2 border-gray-300">
                    <div>
                        <div class="text-sm font-semibold text-gray-600 uppercase mb-2">Service Date</div>
                        <div class="border-b-2 border-gray-800 pb-2"></div>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-gray-600 uppercase mb-2" id="wo-actual-hours-label">Actual Hours at Service</div>
                        <div class="border-b-2 border-gray-800 pb-2"></div>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-gray-600 uppercase mb-2">Technician Name</div>
                        <div class="border-b-2 border-gray-800 pb-2 mb-8"></div>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-gray-600 uppercase mb-2">Technician Signature</div>
                        <div class="border-b-2 border-gray-800 pb-2 mb-8"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        /* Reset everything for print */
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
        }
        
        /* Hide everything except work order modal */
        body > *:not(#work-order-modal) {
            display: none !important;
        }
        
        /* Reset modal for print */
        #work-order-modal {
            position: static !important;
            z-index: 1 !important;
            background: transparent !important;
            overflow: visible !important;
            display: block !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        
        #work-order-modal > div {
            max-width: 100% !important;
            max-height: none !important;
            overflow: visible !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Hide non-printable elements */
        .no-print,
        #work-order-modal .sticky {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            overflow: hidden !important;
        }
        
        /* Print content */
        .print-content {
            display: block !important;
            position: static !important;
            padding: 0 !important;
            margin: 0 !important;
            overflow: visible !important;
        }
        
        /* Keep border on content */
        .border-4 {
            border: 4px solid #1f2937 !important;
            padding: 2rem !important;
            overflow: visible !important;
            page-break-inside: auto !important;
            box-sizing: border-box !important;
        }
        
        /* Page settings */
        @page {
            margin: 0.5in;
            size: letter;
        }
        
        /* Prevent individual task items from breaking */
        #wo-tasks-checklist > div {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        
        /* Ensure all styles are preserved in print */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        
        /* Typography */
        .text-3xl {
            font-size: 1.875rem !important;
            line-height: 2.25rem !important;
        }
        
        .text-2xl {
            font-size: 1.5rem !important;
            line-height: 2rem !important;
        }
        
        .text-xl {
            font-size: 1.25rem !important;
            line-height: 1.75rem !important;
        }
        
        .text-lg {
            font-size: 1.125rem !important;
            line-height: 1.75rem !important;
        }
        
        .text-sm {
            font-size: 0.875rem !important;
            line-height: 1.25rem !important;
        }
        
        .font-bold {
            font-weight: 700 !important;
        }
        
        .font-semibold {
            font-weight: 600 !important;
        }
        
        .uppercase {
            text-transform: uppercase !important;
        }
        
        .text-center {
            text-align: center !important;
        }
        
        /* Colors */
        .text-gray-600 {
            color: #4b5563 !important;
        }
        
        .text-gray-900 {
            color: #111827 !important;
        }
        
        .text-blue-600 {
            color: #2563eb !important;
        }
        
        .bg-gray-50 {
            background-color: #f9fafb !important;
        }
        
        .bg-white {
            background-color: white !important;
        }
        
        /* Borders */
        .border-4 {
            border-width: 4px !important;
            border-style: solid !important;
        }
        
        .border-2 {
            border-width: 2px !important;
            border-style: solid !important;
        }
        
        .border-b-2 {
            border-bottom-width: 2px !important;
            border-bottom-style: solid !important;
        }
        
        .border-t-2 {
            border-top-width: 2px !important;
            border-top-style: solid !important;
        }
        
        .border-gray-800 {
            border-color: #1f2937 !important;
        }
        
        .border-gray-300 {
            border-color: #d1d5db !important;
        }
        
        /* Spacing */
        .p-8 {
            padding: 2rem !important;
        }
        
        .p-3 {
            padding: 0.75rem !important;
        }
        
        .pb-2 {
            padding-bottom: 0.5rem !important;
        }
        
        .pb-6 {
            padding-bottom: 1.5rem !important;
        }
        
        .pt-6 {
            padding-top: 1.5rem !important;
        }
        
        .mb-8 {
            margin-bottom: 2rem !important;
        }
        
        .mb-4 {
            margin-bottom: 1rem !important;
        }
        
        .mb-3 {
            margin-bottom: 0.75rem !important;
        }
        
        .mb-2 {
            margin-bottom: 0.5rem !important;
        }
        
        /* Layout */
        .grid {
            display: grid !important;
        }
        
        .grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
        
        .gap-6 {
            gap: 1.5rem !important;
        }
        
        .gap-4 {
            gap: 1rem !important;
        }
        
        .space-y-3 > * + * {
            margin-top: 0.75rem !important;
        }
        
        .space-y-4 > * + * {
            margin-top: 1rem !important;
        }
        
        /* Flexbox */
        .flex {
            display: flex !important;
        }
        
        .flex-shrink-0 {
            flex-shrink: 0 !important;
        }
        
        .flex-1 {
            flex: 1 1 0% !important;
        }
        
        .items-start {
            align-items: flex-start !important;
        }
        
        .items-center {
            align-items: center !important;
        }
        
        .items-end {
            align-items: flex-end !important;
        }
        
        .flex-col {
            flex-direction: column !important;
        }
        
        /* Borders and corners */
        .rounded,
        .rounded-lg {
            border-radius: 0.5rem !important;
        }
        
        /* Width */
        .w-6 {
            width: 1.5rem !important;
        }
        
        .h-6 {
            height: 1.5rem !important;
        }
        
        .w-32 {
            width: 8rem !important;
        }
    }
</style>

{{-- Record Service Modal --}}
<div id="record-service-modal" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-8 hidden">
    <div id="record-service-backdrop" class="fixed inset-0 bg-black/40" aria-hidden="true"></div>
    <div class="bg-white rounded-lg max-w-2xl w-full shadow-xl relative z-10">
        <div class="p-6 border-b">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-xl font-semibold">Record Service</h3>
                    <div class="text-sm text-gray-600 mt-1" id="record-modal-task">Service Task</div>
                </div>
                <button type="button" data-action="close-record-modal" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        <div class="p-6">
            <div class="mb-4 p-4 bg-blue-50 rounded-lg">
                <div class="flex gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock w-5 h-5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <div class="flex-1">
                        <div class="text-sm text-blue-700" id="record-modal-current-hours">Current equipment hours: 0</div>
                        <div class="text-sm text-gray-700 mt-1" id="record-modal-task-desc"></div>
                    </div>
                </div>
            </div>

            <form id="record-service-form">
                @php $modalUsers = $users ?? \App\Models\Iam\Personnel\User::orderBy('first_name')->get(); @endphp
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Performed By *</label>
                        <select id="record-input-performed-by" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Select employee...</option>
                            @foreach($modalUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->full_name ?? ($u->first_name . ' ' . ($u->last_name ?? '')) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Performed Date *</label>
                        <div class="relative date-wrapper">
                            <input id="record-input-performed-date" type="text" class="date-field w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ date('m/d/Y') }}" />
                            <button type="button" class="absolute inset-y-0 right-2 flex items-center date-icon" aria-hidden="true">
                                <svg class="w-5 h-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="checked-field">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Checked By *</label>
                        <select id="record-input-checked-by" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Select checker...</option>
                            @foreach($modalUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->full_name ?? ($u->first_name . ' ' . ($u->last_name ?? '')) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="checked-field">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Checked Date *</label>
                        <div class="relative date-wrapper">
                            <input id="record-input-checked-date" type="text" class="date-field w-full px-4 py-2 border border-gray-300 rounded-lg" value="" />
                            <button type="button" class="absolute inset-y-0 right-2 flex items-center date-icon" aria-hidden="true">
                                <svg class="w-5 h-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div id="actual-hours-container">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Actual Machine Hours *</label>
                        <input id="record-input-actual-hours" type="number" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                    </div>
                    <div></div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea id="record-input-notes" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="Add any notes about the service..."></textarea>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" data-action="close-record-modal" class="px-4 py-2 bg-gray-100 rounded-lg">Cancel</button>
                    <button type="submit" id="btn-save-record" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Save Service Record</button>
                    <button type="button" id="btn-edit-record" class="px-4 py-2 bg-red-600 text-white rounded-lg" style="display: none;">Edit Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Master Admin Authorization Modal --}}
<div id="admin-auth-modal" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-8 hidden">
    <div id="admin-auth-backdrop" class="fixed inset-0 bg-black/40" aria-hidden="true"></div>
    <div class="bg-white rounded-lg max-w-md w-full shadow-xl relative z-10">
        <div class="p-6 border-b">
            <div class="flex items-start justify-between">
                <h3 class="text-xl font-semibold">Master Admin Authorization</h3>
                <button type="button" data-action="close-admin-auth-modal" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        <div class="p-6">
            <div class="mb-4 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-orange-600 flex-shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-orange-900">Authorization Required</p>
                        <p class="text-sm text-orange-700 mt-1">Enter master admin code to edit completed service record</p>
                    </div>
                </div>
            </div>
            
            <form id="admin-auth-form">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Master Admin Code <span class="text-red-600">*</span></label>
                    <input type="password" id="admin-code-input" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Enter admin code" required />
                </div>
                
                <div id="admin-code-error" class="mt-2 text-sm text-red-600 hidden"></div>
                
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" data-action="close-admin-auth-modal" class="px-4 py-2 bg-gray-100 rounded-lg">Cancel</button>
                    <button type="submit" id="btn-authorize" class="px-4 py-2 bg-orange-600 text-white rounded-lg flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        Authorize
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
