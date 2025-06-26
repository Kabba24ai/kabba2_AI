
{{-- Filter Controls --}}
<div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
    <form method="GET" action="{{ route('admin.maintenance-management.equipments.index') }}" class="flex items-center justify-between space-x-4">
        <div class="flex items-center space-x-4 flex-1">
            {{-- Search --}}
            <div class="relative max-w-sm">
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    type="text"
                    name="search"
                    placeholder="Search by Equipment Name..."
                    value="{{ request('search') }}"
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                />
            </div>

            {{-- Category Filter --}}
            <select name="category" class="px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white min-w-[160px]">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
                @endforeach
            </select>

            {{-- Service Due Filter --}}
            <select name="serviceDue" class="px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white min-w-[140px]">
                <option value="">All Service</option>
                <option value="due-soon" {{ request('serviceDue') === 'due-soon' ? 'selected' : '' }}>Due Soon</option>
                <option value="overdue" {{ request('serviceDue') === 'overdue' ? 'selected' : '' }}>Past Due</option>
            </select>

            {{-- Rental Ready Filter --}}
            <select name="rentalReady" class="px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white min-w-[140px]">
                <option value="">All Rental Ready</option>
                <option value="assigned" {{ request('rentalReady') === 'assigned' ? 'selected' : '' }}>Rental Ready Assigned</option>
                <option value="not-assigned" {{ request('rentalReady') === 'not-assigned' ? 'selected' : '' }}>No Rental Ready</option>
            </select>

            {{-- Equipment Service Filter --}}
            <select name="equipService" class="px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white min-w-[140px]">
                <option value="">All Equip. Service</option>
                <option value="assigned" {{ request('equipService') === 'assigned' ? 'selected' : '' }}>Service Assigned</option>
                <option value="not-assigned" {{ request('equipService') === 'not-assigned' ? 'selected' : '' }}>No Service</option>
            </select>

            {{-- Clear Filters --}}
            @if(request()->hasAny(['search', 'category', 'status', 'serviceDue', 'rentalReady', 'equipService']))
                <a href="{{ route('admin.maintenance-management.equipments.index') }}" class="flex items-center space-x-2 px-4 py-3 text-gray-600 hover:text-gray-800 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>Clear</span>
                </a>
            @endif
            
            <button type="submit" class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                Filter
            </button>
        </div>

        {{-- Add Equipment Button --}}
        <a href="{{ route('admin.maintenance-management.equipments.create') }}" class="flex items-center space-x-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex-shrink-0">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Add Equipment</span>
        </a>
    </form>
</div>
