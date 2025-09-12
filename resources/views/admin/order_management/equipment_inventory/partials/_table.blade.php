 <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
     <thead class="bg-gray-100 text-gray-600 ">
         <tr>
             <th class="px-4 py-3 text-left font-semibold">Category</th>
             <th class="px-4 py-3 text-left font-semibold">Equipment Name</th>
             <th class="px-4 py-3 text-left font-semibold">Equip. ID</th>
             <th class="px-4 py-3 text-left font-semibold">Status</th>
             <th class="px-4 py-3 text-left font-semibold">Tech / Mgt.</th>
             <th class="px-4 py-3 text-left font-semibold">Location</th>
             <th class="px-4 py-3 text-left font-semibold">Delivery Date</th>
             <th class="px-4 py-3 text-left font-semibold">Return Date</th>
             <th class="px-4 py-3 text-right font-semibold">Actions</th>
         </tr>
     </thead>
     <tbody class="divide-y divide-gray-100 text-gray-900">
         @forelse($equipment as $eq)

         <!-- ROW 1 -->
         <tr class="hover:bg-gray-50">
             <td class="px-4 py-4 font-semibold  text-gray-700">{{ $eq->category_name }}</td>
             <td class="px-4 py-4 break-words">{{ $eq->equipment_name }}</td>
             <td class="px-4 py-4">
                 <a href="{{ route('admin.maintenance-management.equipment.edit', $eq->unique_id) }}" class="text-blue-600 uppercase">{{ $eq->equipment_id }}</a>
             </td>
             <td class="px-4 py-4">
                 <div class="inline-flex items-center gap-1.5 whitespace-nowrap">
                     @switch($eq->status_label)
                     @case('Damaged')
                     <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                         <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                         <path d="M12 9v4" />
                         <path d="M12 17h.01" />
                     </svg>
                     <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 uppercase">{{ $eq->status_label}} </span>
                     @break

                     @case('Maint. Hold')
                     <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                         <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 1 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z" />
                     </svg>
                     <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 uppercase">{{ $eq->status_label}}</span>
                     @break

                     @case('Rented')
                     <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                         <path d="M16 21v-2a4 4 0 0 0-8 0v2" />
                         <circle cx="12" cy="7" r="4" />
                     </svg>
                     <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 uppercase">{{ $eq->status_label}}</span>
                     @break

                     @case('Available')
                     <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                         <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                         <path d="m9 11 3 3L22 4" />
                     </svg>
                     <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 uppercase">{{ $eq->status_label}}</span>
                     @break

                     @default
                     <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 uppercase">
                         {{ $eq->status_label ?? 'Unknown' }}
                     </span>
                     @endswitch
                 </div>
             </td>
             <td class="px-4 py-4">
                 <div class="font-medium">{{ $eq->activeEquipmentRentalReadyTemplate?->employee_name ?? '-' }}</div>
                 <div class="text-xs text-gray-500">
                 
                 </div>
             </td>
             <td class="px-4 py-4">
                 <div class="inline-flex items-center gap-1">

                     @if ($eq->status_label == 'Rented' && $eq->order?->customer?->unique_id)
                     <a href="{{ route('admin.crm.customers.view', $eq->order->customer->unique_id) }}" target="_blank" class="text-blue-600 hover:underline">
                         {{ $eq->order->customer_name ?? '-' }}
                     </a>
                     @else


                     -

                     <!-- @if($eq->orderProduct?->deliveryStore->store_name) -->
                     <!-- <svg class="w-3 h-3 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                         <path stroke-linecap="round" stroke-linejoin="round"
                             d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                         <path stroke-linecap="round" stroke-linejoin="round"
                             d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                     </svg>
                     {{ $eq->orderProduct->deliveryStore->store_name }} -->
                     <!-- @endif -->
                     @endif



                 </div>
             </td>
             <td class="px-4 py-4">
                 <div class="inline-flex items-center gap-1">
                     @if ($eq->orderproduct?->delivery_date)
                     <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                         <path d="M8 2v4M16 2v4M3 10h18M5 22h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"></path>
                     </svg>

                     {{ \Carbon\Carbon::parse($eq->orderproduct?->delivery_date)->format('M d') }}

                     @else
                     -
                     @endif

                 </div>
             </td>
             <td class="px-4 py-4">
                 <div class="inline-flex items-center gap-1">


                     @if ($eq->orderproduct?->pickup_date)

                     <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                         <path d="M8 2v4M16 2v4M3 10h18M5 22h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"></path>
                     </svg>

                     {{ \Carbon\Carbon::parse($eq->orderproduct?->pickup_date)->format('M d') }}
                     @else
                     -
                     @endif
                 </div>
             </td>
             <td class="px-4 py-4">
                 <div class="flex items-center justify-end gap-3">
                     <a href="{{ route('admin.checklist-management.equipment-management.show', $eq->unique_id) }}" class="p-1.5 rounded text-green-600" aria-label="Edit">
                         <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                             <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"></path>
                         </svg>
                     </a>

                 </div>
             </td>
         </tr>
         @empty
         <tr>
             <td colspan="9" class="px-4 py-4 text-center text-gray-500">
                 No equipment found.
             </td>
         </tr>
         @endforelse


     </tbody>
 </table>
