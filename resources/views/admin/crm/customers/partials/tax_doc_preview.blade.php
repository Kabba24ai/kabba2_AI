   <div class="border border-gray-200 rounded-lg p-4 text-sm bg-white w-full">
      <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">
         <div class="flex items-start gap-3 flex-1 min-w-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none"
               viewBox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M7 7h10M7 11h10M7 15h10M5 19h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <div class="min-w-0">
               <div class="font-medium text-gray-800 truncate">
                <!-- {{ $customer->media->original_file_name ?? '' }} -->
    {{ str($customer->media->original_file_name ?? '')->limit(20) }}

            </div>
               <div class="text-gray-500 text-xs">
                  Type: <span class="text-grey-500 text-xs">{{ $customer->tax_document_type ?? 'N/A' }}</span>
               </div>
               <div class="text-gray-500 text-xs">
                  Status:
                  <span id="tax-status-{{ $customer->id }}" class="font-medium
                     {{ $customer->tax_document_status === 'Rejected' ? 'text-red-600' :
                     ($customer->tax_document_status === 'Approved' ? 'text-green-600' : 'text-yellow-600') }}">
                  {{ $customer->tax_document_status ?: 'Pending Review' }}
                  </span>
               </div>
            </div>
         </div>
         <div class="flex gap-4 text-sm justify-end sm:justify-start">
            <a href="{{ $customer->media->getUrl() }}" target="_blank" class="text-blue-600">View</a>
            <button type="button" class="text-red-600" onclick="confirmAndDelete({{ $customer->id }})">Delete</button>
            {{-- Conditional Action Buttons --}}
            @php $status = $customer->tax_document_status; @endphp
            @if ($status !== 'Approved' && $status !== 'Rejected')
            <button type="button" class="text-green-600 approve-btn" data-customer-id="{{ $customer->id }}">Approve</button>
            <button type="button" class="text-red-600 reject-btn" data-customer-id="{{ $customer->id }}">Reject</button>
            @elseif ($status === 'Rejected')
            <button type="button" class="text-green-600 approve-btn" data-customer-id="{{ $customer->id }}">Approve</button>
            @endif
         </div>
      </div>
   </div>
