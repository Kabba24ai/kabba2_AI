<div class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-gray-100 mb-6">
    <div class="flex flex-wrap items-end gap-4 w-full">
        <div class="w-full sm:w-48">
            <div class="relative bg-white">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-5 w-5" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="search" placeholder="Search by Equipment Name..."
                    value="{{ request('search') }}"
                    class="w-full pl-10 text-sm pr-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            </div>
        </div>
        <div class="w-full sm:w-48">
            <select name="category"
                class="choices-select w-full rounded-md border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-gray-500 focus:ring-1 focus:ring-blue-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                <option value="">Select Category</option>
                @foreach ($categories as $id => $title)
                    <option value="{{ $id }}" @selected(request('category') == $id)>
                        {{ $title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-48">
            <div class="relative bg-white">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-5 w-5" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="equipment_id" placeholder="Search Equipment ID"
                    value="{{ request('equipment_id') }}"
                    class="w-full pl-10 text-sm pr-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            </div>
        </div>

        <div class="w-full sm:w-48">
            <select name="checklist_master"
                class="choices-select w-full mt-2 rounded-md border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-gray-500 focus:ring-1 focus:ring-blue-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                <option value="">Checklist Master</option>
                <option value="assigned" @selected(request('checklist_master') == 'assigned')>Assigned</option>
                <option value="Pending" @selected(request('checklist_master') == 'Pending')>Pending</option>
            </select>
        </div>

        <div class="w-full sm:w-48">
            <select name="location_store"
                class="choices-select w-full mt-2 rounded-md border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-gray-500 focus:ring-1 focus:ring-blue-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                <option value="">Location Master</option>
                @foreach ($stores as $storeId => $storeName)
                    <option value="{{ $storeId }}" @selected(request('location_store') == (string) $storeId)>
                        {{ $storeName }}
                    </option>
                @endforeach
                <option value="rented" @selected(request('location_store') == 'rented')>Rented</option>
            </select>
        </div>

        <div class="w-full sm:w-48">
            <select name="service_due"
                class="choices-select w-full mt-2 rounded-md border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-gray-500 focus:ring-1 focus:ring-blue-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                <option value="">Select Service Due</option>
                <option value="not_due" @selected(request('service_due') == 'not_due')>Not due</option>
                <option value="overdue" @selected(request('service_due') == 'overdue')>Overdue</option>
                <option value="pending" @selected(request('service_due') == 'pending')>Pending</option>
            </select>
        </div>

        <div class="w-full sm:w-auto sm:ml-auto">
            <a href="{{ route('admin.maintenance-management.equipment.create') }}"
                class="flex-shrink-0 inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Add Equipment</span>
            </a>
        </div>
    </div>
</div>

{{-- Service Due --}}
{{-- <select name="serviceDue"
            class="flex-1 min-w-[140px] px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white">
      <option value="">All Service</option>
      <option value="due-soon" {{ request('serviceDue') === 'due-soon' ? 'selected' : '' }}>Due Soon</option>
<option value="overdue" {{ request('serviceDue') === 'overdue' ? 'selected' : '' }}>Past Due</option>
</select> --}}

{{-- Rental Ready --}}
{{-- <select name="rentalReady"
            class="flex-1 min-w-[160px] px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white">
      <option value="">All Rental Ready</option>
      <option value="assigned" {{ request('rentalReady') === 'assigned' ? 'selected' : '' }}>Rental Ready Assigned</option>
<option value="not-assigned" {{ request('rentalReady') === 'not-assigned' ? 'selected' : '' }}>No Rental Ready</option>
</select> --}}

{{-- Equipment Service --}}
{{-- <select name="equipService"
            class="flex-1 min-w-[160px] px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white">
      <option value="">All Equip. Service</option>
      <option value="assigned" {{ request('equipService') === 'assigned' ? 'selected' : '' }}>Service Assigned</option>
<option value="not-assigned" {{ request('equipService') === 'not-assigned' ? 'selected' : '' }}>No Service</option>
</select> --}}

{{-- Clear button --}}
{{-- @if (request()->hasAny(['search', 'category', 'status', 'serviceDue', 'rentalReady', 'equipService']))
      <a href="{{ route('admin.maintenance-management.equipment.index') }}"
class="flex-shrink-0 inline-flex items-center gap-2 px-4 py-3 text-gray-600 hover:text-gray-800">
<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
</svg>
<span>Clear</span>
</a>
@endif --}}

{{-- Filter button --}}
{{-- <button type="submit"
            class="flex-shrink-0 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg">
      Filter
    </button> --}}
