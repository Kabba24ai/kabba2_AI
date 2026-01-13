@forelse($rows as $index => $row)
<div
    class="flex flex-col gap-4 p-4 bg-white rounded-xl border hover:shadow-md transition
         md:flex-row md:items-center"
    data-row-index="{{ $index }}"
    data-row-source="{{ $row['source'] ?? 'manual' }}"
>
    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">

        {{-- NAME --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Person / Description
            </label>
            <input
                type="text"
                class="manual-name w-full pl-2 pr-2 px-3 py-3 w-full border rounded-md text-sm border-gray-300
                    {{ ($row['source'] ?? '') === 'hrm' ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                value="{{ $row['name'] }}"
                data-manual-name="{{ $index }}"
                {{ ($row['source'] ?? '') === 'hrm' ? 'readonly' : '' }}
            >

        </div>

        {{-- PHONE --}}
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
                Phone Number
            </label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <x-heroicon-o-phone class="w-4 h-4"/>
                </span>
                <input 
    type="text"
    class="manual-phone masked-phone w-full pl-9 pr-2 px-3 py-3 w-full border rounded-md text-sm border-gray-300
           {{ ($row['source'] ?? '') === 'hrm' ? 'bg-gray-100 cursor-not-allowed' : '' }}"
    value="{{ $row['phone'] }}"
    data-manual-phone="{{ $index }}"
    {{ ($row['source'] ?? '') === 'hrm' ? 'readonly' : '' }}
>

            </div>
        </div>
    </div>

    {{-- ACTIONS --}}
    <div class="flex  gap-2 ">

        {{-- HRM UPDATE --}}
        @if(($row['source'] ?? '') === 'hrm')
            <!-- <button
                type="button"
                data-update-hrm
                class="px-3 py-1.5 text-sm rounded-lg
                       bg-blue-600 text-white hover:bg-blue-700"
            >
                <x-heroicon-o-pencil-square class="w-5 h-5" />

            </button> -->
        @else
            {{-- MANUAL UPDATE --}}
           <button
                type="button"
                data-update-manual="{{ $index }}"
                 data-id="{{ $row['id'] }}"
                data-type="{{ $row['type'] }}"
                class="data-update-manual px-3 py-1.5 text-sm rounded-sm md:mt-5 bg-green-600 text-white hover:bg-green-700"
            >

                <x-heroicon-o-pencil-square class="w-5 h-5" />
            </button>

        @endif

       {{-- DELETE --}}
            <button
                type="button"
                data-delete="{{ $index }}"
                data-id="{{ $row['id'] }}"
                data-type="{{ $row['type'] }}"
                class="px-3 py-1.5 text-sm rounded-sm md:mt-5 bg-red-50 text-red-600 hover:bg-red-100"
            >
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"></path>
                </svg>
            </button>

    </div>
</div>
@empty
<div class="text-sm text-gray-500 text-center py-4">
    No recipients configured
</div>
@endforelse
