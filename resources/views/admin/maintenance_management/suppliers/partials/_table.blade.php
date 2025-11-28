

  <div id="supplier-table-wrapper" class="overflow-x-auto rounded-2xl  shadow border border-gray-200 bg-white dark:bg-gray-900">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm" id="suppliers-table-wrapper">
          <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                  <th class="py-4 px-6 text-left font-semibold">Supplier</th>
                  <th class="py-4 px-6 text-left font-semibold">Phone</th>
                  <th class="py-4 px-6 text-left font-semibold"> Category</th>
                  <th class="py-4 px-6 text-left font-semibold">Parts</th>
                  <th class="py-4 px-6 text-left font-semibold">Tags</th>
                  <th class="py-4 px-6 text-left font-semibold">Actions</th>
              </tr>
          </thead>

          <tbody class="bg-white divide-y divide-gray-200">

              @forelse($suppliers as $supplier)

                @php
                    $rowBg = match(strtolower($supplier->status)) {
                        'active'   => '',
                        'inactive' => 'bg-red-50',
                        'pending'  => 'bg-yellow-50',
                        default    => '',
                    };
                @endphp


              <tr class=" transition-colors {{ $rowBg }}">
                  <td class="py-4 px-6 whitespace-nowrap">
                      <div class="flex items-center">

                          <div class="">
                              <div class="text-sm font-medium text-gray-900">{{ $supplier->name }}</div>
<div class="text-sm text-gray-500 flex items-center mt-1">

                          {{ $supplier->primary_contact_name ?? 'N/A'}}
                      </div>
                          </div>
                      </div>
                  </td>

                  <td class="py-4 px-6 whitespace-nowrap">

                      <div class="text-sm text-gray-500 flex items-center mt-1">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-2 text-gray-400"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg>
                          {{ $supplier->phone ?? 'N/A'}}
                      </div>
                       <div class="text-sm text-gray-500 flex items-center mt-1">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-2 text-gray-400"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                          {{ $supplier->primary_contact_phone ?? 'N/A'}}
                      </div>
                  </td>


                  @php
$categories = $supplier->all_supplied_parts
    ->flatMap(fn($part) => $part->partsLists)
    ->pluck('category.title')
    ->unique()
    ->filter();
@endphp

<td class="py-4 px-6 whitespace-nowrap">
    @forelse($categories as $cat)
        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800">
            {{ $cat }}
        </span>
    @empty
        -
    @endforelse
</td>



                  <td class="py-4 px-6 text-sm text-gray-900">
                      <div class="flex flex-wrap gap-1">
                          <!-- <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-orange-100 text-orange-700 whitespace-nowrap">-</span> -->
                           @foreach ($supplier->all_supplied_parts as $part)
                          <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-orange-100 text-orange-700 whitespace-nowrap">{{ $part->part_name }} </span>
                           @endforeach

                      </div>
                  </td>

                  {{-- Tags --}}
                  <td class="py-4 px-6 text-sm text-gray-900">
                      <div class="flex flex-wrap gap-1">
                          @foreach ($supplier->tag_objects as $tag)
                          <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 whitespace-nowrap">
                              #{{ Str::slug($tag->name) }}
                          </span>
                          @endforeach
                      </div>
                  </td>

                  <td class="py-4 px-6 whitespace-nowrap text-sm font-medium">
                      <div class="flex items-center space-x-2">
                          <button class="text-blue-600 rounded transition-colors" title="View Details" onclick="viewSupplier({{ $supplier->id }})">
                              <x-heroicon-o-eye class="w-5 h-5" />
                          </button>
                          <button class="text-green-600 rounded transition-colors" title="Edit" onclick="editSupplier({{ $supplier->id }})">
                              <x-heroicon-o-pencil class="w-5 h-5" />
                          </button>
                          <button class="text-red-600 rounded transition-colors" title="Delete" onclick="deleteSupplier({{ $supplier->id }}, '{{ $supplier->name }}')">
                              <x-heroicon-o-trash class="w-5 h-5" />
                          </button>
                      </div>
                  </td>
              </tr>
              @empty
              <tr>
                  <td colspan="7" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                      No Data found.
                  </td>
              </tr>
              @endforelse




          </tbody>

      </table>

  </div>
