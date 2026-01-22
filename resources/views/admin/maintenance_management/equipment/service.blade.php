@extends('admin.layouts.app')

@section('title', 'Service Management - ' . $equipment->equipment_name)

@section('content')
<div class="h-screen bg-gray-50 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-auto p-6">
        {{-- Header --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.maintenance-management.equipment.index') }}" 
                       class="text-gray-600 hover:text-gray-900">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Service Management</h1>
                        <p class="text-gray-600">{{ $equipment->equipment_name }} ({{ $equipment->equipment_id }})</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Equipment Service Template Info --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Service Template Information</h2>
            
            @if($equipment->serviceTemplate)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Service Template</label>
                        <p class="text-gray-900">{{ $equipment->serviceTemplate->name }}</p>
                    </div>
                    
                    @if($equipment->serviceTemplate->preset)
                        <div>
                            <label class="text-sm font-medium text-gray-700">Preset</label>
                            <p class="text-gray-900">{{ $equipment->serviceTemplate->preset->name }}</p>
                        </div>
                    @endif
                    
                    @if($equipment->bring_service_flag)
                        <div>
                            <label class="text-sm font-medium text-gray-700">Bring Service Current To</label>
                            <p class="text-gray-900">{{ $equipment->bring_service_hour }} hours</p>
                        </div>
                    @endif
                </div>

                {{-- Service Tasks --}}
                @if($equipment->serviceTemplate->templateTasks && $equipment->serviceTemplate->templateTasks->count() > 0)
                    <div class="mt-6">
                        <h3 class="text-md font-semibold text-gray-900 mb-3">Service Tasks</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Task Name</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Description</th>
                                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @foreach($equipment->serviceTemplate->templateTasks as $templateTask)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3">{{ $templateTask->task->name ?? '-' }}</td>
                                                <td class="px-4 py-3">{{ $templateTask->task->description ?? '-' }}</td>
                                                <td class="px-4 py-3 text-center">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        Pending
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mt-6 text-center py-8">
                        <p class="text-gray-500">No service tasks assigned to this template.</p>
                    </div>
                @endif
            @else
                <div class="text-center py-8">
                    <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Service Template Assigned</h3>
                    <p class="text-gray-600 mb-4">This equipment does not have a service template assigned yet.</p>
                    <a href="{{ route('admin.maintenance-management.equipment.edit', $equipment->unique_id) }}" 
                       class="inline-flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                        <span>Assign Service Template</span>
                    </a>
                </div>
            @endif
        </div>

        {{-- Service History (Future Implementation) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Service History</h2>
            <div class="text-center py-8">
                <p class="text-gray-500">Service history tracking will be available soon.</p>
            </div>
        </div>
    </div>
</div>
@endsection
