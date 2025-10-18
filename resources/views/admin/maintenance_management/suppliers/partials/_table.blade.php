  {{-- Suppliers Table --}}


  <div id="supplier-table-wrapper" class="overflow-x-auto rounded-lg shadow border border-gray-200 bg-white dark:bg-gray-900">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm" id="suppliers-table-wrapper">
          <thead class="bg-gray-100 text-gray-600">
              <tr>
                  <th class="px-4 py-3 text-left font-semibold">Supplier</th>
                  <th class="px-4 py-3 text-left font-semibold">Contact</th>
                  <th class="px-4 py-3 text-left font-semibold">Category</th>
                  <th class="px-4 py-3 text-left font-semibold">Status</th>
                  <th class="px-4 py-3 text-left font-semibold">Parts</th>
                  <th class="px-4 py-3 text-left font-semibold">Tags</th>
                  <th class="px-4 py-3 text-left font-semibold">Actions</th>
              </tr>
          </thead>

          <tbody class="bg-white divide-y divide-gray-200">

              @forelse($suppliers as $supplier)
              <tr class="hover:bg-gray-50 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                          <div class="flex-shrink-0 h-10 w-10">
                              @if (optional($supplier->media)->getUrl())
                              <img src="{{ $supplier->media->getUrl() }}" alt="{{ $supplier->name }}" class="h-10 w-10 rounded-full object-cover">
                              @else

                              <div class="h-10 w-10 rounded-full bg-gradient-to-r from-purple-500 to-violet-600 flex items-center justify-center">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-white">
                                      <rect width="20" height="14" x="2" y="3" rx="2"></rect>
                                      <line x1="8" x2="16" y1="21" y2="21"></line>
                                      <line x1="12" x2="12" y1="17" y2="21"></line>
                                  </svg>
                              </div>

                              @endif

                          </div>
                          <div class="ml-4">
                              <div class="text-sm font-medium text-gray-900">{{ $supplier->name }}</div>

                          </div>
                      </div>
                  </td>

                  <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900 flex items-center">
                          <x-heroicon-o-envelope class="w-4 h-4 mr-2 text-gray-400" />
                          {{ $supplier->email ?? 'N/A'}}
                      </div>
                      <div class="text-sm text-gray-500 flex items-center mt-1">
                          <x-heroicon-o-phone class="w-4 h-4 mr-2 text-gray-400" />
                          {{ $supplier->phone ?? 'N/A'}}
                      </div>
                  </td>

                  <td class="px-6 py-4 whitespace-nowrap">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                          {{ $supplier->category->name ?? 'N/A' }}
                      </span>
                  </td>

                  <td class="px-6 py-4 whitespace-nowrap">
                      @php
                      $statusColor = match(strtolower($supplier->status)) {
                      'active' => 'bg-green-100 text-green-800 border-green-200',
                      'inactive' => 'bg-red-100 text-red-800 border-red-200',
                      default => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                      };
                      @endphp
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $statusColor }}">
                          <span class="capitalize">{{ $supplier->status }}</span>
                      </span>
                  </td>

                  {{-- Categories --}}
                  <td class="px-6 py-4 text-sm text-gray-900">
                      <div class="flex flex-wrap gap-1">
                          <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-orange-100 text-orange-700 whitespace-nowrap">Engine Oil Filter</span>
                          <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-orange-100 text-orange-700 whitespace-nowrap">LED Display Panel</span>
                      </div>
                  </td>

                  {{-- Tags --}}
                  <td class="px-6 py-4 text-sm text-gray-900">
                      <div class="flex flex-wrap gap-1">
                          @foreach ($supplier->tag_objects as $tag)
                          <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 whitespace-nowrap">
                              #{{ Str::slug($tag->name) }}
                          </span>
                          @endforeach
                      </div>
                  </td>

                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
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
