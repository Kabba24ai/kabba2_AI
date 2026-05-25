  <div id="parts-table-wrapper" class="overflow-x-auto max-w-full rounded-2xl shadow border border-gray-200 bg-white">
<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm" id="suppliers-table-wrapper">
    <thead class="bg-gray-50 border-b border-gray-200">
        <tr>
            <th class="py-4 px-6 text-left font-semibold">Category</th>


            <th class="py-4 px-6 text-left font-semibold">Equipment Name</th>
              <th class="py-4 px-6 text-left font-semibold whitespace-nowrap">Part Name</th>
            <th class="py-4 px-6 text-left font-semibold">Brand</th>
            <th class="py-4 px-6 text-left font-semibold whitespace-nowrap">Part Number</th>
            <th class="py-4 px-6 text-left font-semibold whitespace-nowrap">Parts List</th>

            <th class="py-4 px-6 text-left font-semibold whitespace-nowrap">Primary Supplier</th>
            <th class="py-4 px-6 text-left font-semibold whitespace-nowrap">Unit Cost</th>
            <th class="py-4 px-6 text-left font-semibold">Stock</th>
            <th class="py-4 px-6 text-left font-semibold">Actions</th>
        </tr>
    </thead>

    <tbody class="bg-white divide-y divide-gray-200">
        @forelse ($parts as $part)

        <tr class="hover:bg-gray-50 transition-colors">

         <td class="py-4 px-6 whitespace-nowrap">
               <span class="text-sm text-gray-900">
    @php
        $categories = $part->partsLists
            ->pluck('category.title')
            ->filter()
            ->unique();
    @endphp

    {{ $categories->isNotEmpty() ? $categories->implode(', ') : '-' }}
</span>

            </td>



            <td class="py-4 px-6 whitespace-nowrap">
    <span class="text-sm text-gray-900">
        @if($part->assigned_equipment_names->isNotEmpty())
            {{ $part->assigned_equipment_names->implode(', ') }}
        @else
            -
        @endif
    </span>
</td>



            <td class="py-4 px-6 whitespace-nowrap">
                <span class="text-sm font-medium text-gray-900">
                    {{ $part->part_name }}
                </span>
            </td>

               <td class="py-4 px-6 whitespace-nowrap">
    <span class="text-sm text-gray-900">
        {{ $part->primaryBrand->name ?? '—' }}
    </span>
</td>




            <td class="py-4 px-6 whitespace-nowrap">
                <span class="text-sm text-gray-900">
                    {{ $part->primary_part_number ?? '—' }}
                </span>
            </td>

             <td class="py-4 px-6 whitespace-nowrap">
    <span class="text-sm text-gray-900">
        @if($part->partsLists->isNotEmpty())
            @foreach($part->partsLists as $list)
                <a href="{{ route('admin.maintenance-management.parts.parts-list.view', $list->unique_id) }}"
                   class="text-blue-600 hover:underline font-medium">
                    {{ $list->name }}
                </a>@if(!$loop->last), @endif
            @endforeach
        @else
            -
        @endif
    </span>
</td>



            <td class="py-4 px-6 whitespace-nowrap">
                <span class="text-sm text-gray-900">
                    {{ optional($part->primarySupplier)->name ?? '—' }}
                </span>
            </td>

            <td class="py-4 px-6 whitespace-nowrap">
                <span class="text-sm font-medium text-green-600">
                    ${{ number_format($part->primary_part_cost, 2) }}

                </span>
            </td>

            <td class="py-4 px-6 text-sm text-gray-900">
                <div class="flex flex-wrap gap-1">
                    @php
                    $status = $part->stock_status;
                    $statusMap = [
                    'dni' => ['DNI', 'bg-gray-100 text-gray-700'],
                    'out-of-stock' => ['Out of Stock', 'bg-red-100 text-red-700'],
                    'buy-now' => ['Buy Now', 'bg-yellow-100 text-yellow-700'],
                    'in-stock' => ['In-Stock', 'bg-green-100 text-green-700'],
                    ];
                    [$label, $classes] = $statusMap[$status] ?? ['Unknown', 'bg-gray-50 text-gray-500'];
                    @endphp

                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium  {{ $classes }}  whitespace-nowrap"> {{ $label }}</span>
                </div>
            </td>

            <td class="py-4 px-6 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center space-x-2">

                    <a href="{{ route('admin.maintenance-management.parts.view', $part->unique_id) }}" class="text-blue-600 rounded transition-colors" title="View Details">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                        </svg>
                    </a>
                    <a href="{{ route('admin.maintenance-management.parts.edit', $part->unique_id) }}" class="text-green-600 rounded transition-colors" title="Edit">
                        {{-- <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"></path>
                        </svg> --}}

                                                        <x-heroicon-o-pencil-square class="w-5 h-5 cursor-pointer" />

                    </a>
                    <button class="text-red-600 rounded transition-colors" title="Delete">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>

        @empty
        <tr>
            <td colspan="9" class="text-center py-4 text-gray-500">No parts found.</td>
        </tr>
        @endforelse


    </tbody>

</table>
</div>

    @if ($parts)

{{-- Pagination --}}
<div class="mt-6">
    {{ $parts->links() }}
</div>
    @endif