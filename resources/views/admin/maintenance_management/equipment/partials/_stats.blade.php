{{-- Stats Cards --}}
<div class="grid grid-cols-5 gap-4 mb-6 p-2">
    <a href="#" data-status=""
       class="stats-filter bg-white rounded-xl p-4 shadow-sm border transition-all hover:shadow-md {{ !request('status') ? 'border-gray-100 bg-gray-50' : 'border-gray-100 hover:border-gray-200' }}">
        <div class="flex items-center justify-between">
            <div class="text-left">
                <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Total</p>
                <p class="text-2xl font-bold text-gray-700 mt-1">{{ $stats['total'] }}</p>
            </div>
            {{-- Heroicon: Chart Bar --}}
            <x-heroicon-o-cog-6-tooth class="h-6 w-6 text-gray-600 opacity-80"/>
        </div>
    </a>

    <a href="#"
       data-status="available"
       class="stats-filter bg-white rounded-xl p-4 shadow-sm border transition-all hover:shadow-md {{ request('status') === 'available' ? 'border-green-300 bg-green-50' : 'border-gray-100 hover:border-green-200' }}">
        <div class="flex items-center justify-between">
            <div class="text-left">
                <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Available</p>
                <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['available'] }}</p>
            </div>
            <x-heroicon-o-check-circle class="h-6 w-6 text-green-600 opacity-80"/>
        </div>
    </a>

    <a href="#"
       data-status="rented"
       class="stats-filter bg-white rounded-xl p-4 shadow-sm border transition-all hover:shadow-md {{ request('status') === 'rented' ? 'border-blue-300 bg-blue-50' : 'border-gray-100 hover:border-blue-200' }}">
        <div class="flex items-center justify-between">
            <div class="text-left">
                <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Rented</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['rented'] }}</p>
            </div>
            <x-heroicon-o-user class="h-6 w-6 text-blue-600 opacity-80"/>
        </div>
    </a>

    <a  href="#"
       data-status="maintenance"
       class="stats-filter bg-white rounded-xl p-4 shadow-sm border transition-all hover:shadow-md {{ request('status') === 'maintenance' ? 'border-yellow-300 bg-yellow-50' : 'border-gray-100 hover:border-yellow-200' }}">
        <div class="flex items-center justify-between">
            <div class="text-left">
                <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Maintenance</p>
                <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['maintenance'] }}</p>
            </div>
            <x-heroicon-o-cog class="h-6 w-6 text-yellow-600 opacity-80"/>
        </div>
    </a>

    <a href="#"
       data-status="damaged"
       class="stats-filter bg-white rounded-xl p-4 shadow-sm border transition-all hover:shadow-md {{ request('status') === 'damaged' ? 'border-red-300 bg-red-50' : 'border-gray-100 hover:border-red-200' }}">
        <div class="flex items-center justify-between">
            <div class="text-left">
                <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Damaged</p>
                <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['damaged'] }}</p>
            </div>
            <x-heroicon-o-exclamation-circle class="h-6 w-6 text-red-600 opacity-80"/>
        </div>
    </a>
</div>
