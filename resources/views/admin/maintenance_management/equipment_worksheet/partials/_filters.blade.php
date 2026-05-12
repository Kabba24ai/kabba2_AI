<div class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-gray-100 mb-6">
    <form id="equipment-worksheet-filters-form" method="GET" class="flex flex-wrap items-end gap-4 w-full">
        <div class="w-full sm:w-auto">
            <button type="button" id="clear-filters"
                class="text-sm text-gray-600 bg-white px-4 py-3 flex gap-2 items-center rounded-md border border-gray-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
                Clear
            </button>
        </div>

        <div class="w-full sm:w-64">
            <div class="relative bg-white">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-5 w-5" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search name, equipment id, brand or model"
                    class="w-full pl-10 text-sm pr-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            </div>
        </div>

        <div class="w-full sm:w-48">
            <select name="category"
                class="w-full choice-select rounded-md border border-gray-300 bg-white px-3 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">All categories</option>
                @foreach ($categories as $id => $title)
                    <option value="{{ $id }}" @selected((string) request('category') === (string) $id)>
                        {{ $title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-48">
            <select name="store"
                class="w-full rounded-md border border-gray-300 bg-white px-3 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">All stores</option>
                @foreach ($stores as $id => $name)
                    <option value="{{ $id }}" @selected((string) request('store') === (string) $id)>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-48">
            <select name="status"
                class="w-full rounded-md border border-gray-300 bg-white px-3 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">All statuses</option>
                <option value="available" @selected(request('status') === 'available')>Available</option>
                <option value="rented" @selected(request('status') === 'rented')>Rented</option>
                <option value="maintenance" @selected(request('status') === 'maintenance')>Maintenance</option>
                <option value="damaged" @selected(request('status') === 'damaged')>Damaged</option>
            </select>
        </div>
    </form>
</div>
