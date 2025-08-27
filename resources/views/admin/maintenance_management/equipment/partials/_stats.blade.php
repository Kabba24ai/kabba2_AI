{{-- Stats Cards --}}
<div class="grid grid-cols-5 gap-4 mb-6">
    <a href="{{ route('admin.maintenance-management.equipment.index') }}"
       class="bg-white rounded-xl p-4 shadow-sm border transition-all hover:shadow-md {{ !request('status') ? 'border-gray-400 bg-gray-50' : 'border-gray-100 hover:border-gray-300' }}">
        <div class="flex items-center justify-between">
            <div class="text-left">
                <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Total</p>
                <p class="text-2xl font-bold text-gray-700 mt-1">{{ $stats['total'] }}</p>
            </div>
            <svg class="h-6 w-6 text-gray-600 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            </svg>
        </div>
    </a>

    <a href="{{ route('admin.maintenance-management.equipment.index', ['status' => 'available']) }}"
       class="bg-white rounded-xl p-4 shadow-sm border transition-all hover:shadow-md {{ request('status') === 'available' ? 'border-green-300 bg-green-50' : 'border-gray-100 hover:border-green-200' }}">
        <div class="flex items-center justify-between">
            <div class="text-left">
                <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Available</p>
                <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['available'] }}</p>
            </div>
            <svg class="h-6 w-6 text-green-600 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
    </a>

    <a href="{{ route('admin.maintenance-management.equipment.index', ['status' => 'rented']) }}"
       class="bg-white rounded-xl p-4 shadow-sm border transition-all hover:shadow-md {{ request('status') === 'rented' ? 'border-blue-300 bg-blue-50' : 'border-gray-100 hover:border-blue-200' }}">
        <div class="flex items-center justify-between">
            <div class="text-left">
                <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Rented</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['rented'] }}</p>
            </div>
            <svg class="h-6 w-6 text-blue-600 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        </div>
    </a>

    <a href="{{ route('admin.maintenance-management.equipment.index', ['status' => 'maintenance']) }}"
       class="bg-white rounded-xl p-4 shadow-sm border transition-all hover:shadow-md {{ request('status') === 'maintenance' ? 'border-yellow-300 bg-yellow-50' : 'border-gray-100 hover:border-yellow-200' }}">
        <div class="flex items-center justify-between">
            <div class="text-left">
                <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Maintenance</p>
                <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['maintenance'] }}</p>
            </div>
            <svg class="h-6 w-6 text-yellow-600 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            </svg>
        </div>
    </a>

    <a href="{{ route('admin.maintenance-management.equipment.index', ['status' => 'damaged']) }}"
       class="bg-white rounded-xl p-4 shadow-sm border transition-all hover:shadow-md {{ request('status') === 'damaged' ? 'border-red-300 bg-red-50' : 'border-gray-100 hover:border-red-200' }}">
        <div class="flex items-center justify-between">
            <div class="text-left">
                <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Damaged</p>
                <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['damaged'] }}</p>
            </div>
            <svg class="h-6 w-6 text-red-600 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
        </div>
    </a>
</div>
