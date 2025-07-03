@extends('admin.layouts.app')

@section('title', 'View Customer')

@push('css')
@endpush

@section('content')

<div class="bg-gray-50 px-4 py-4 border-b border-gray-200">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <!-- Left Section -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <!-- Back to Customers -->
            <a href="{{ route('admin.crm.customers.index') }}" class="flex items-center text-gray-600 hover:text-gray-800 transition">
                <x-heroicon-o-arrow-left class="w-5 h-5 mr-1" />
                <span class="text-sm font-medium">Back to Customers</span>
            </a>

            <!-- Divider -->
            <div class="hidden sm:block h-6 border-l border-gray-300"></div>

            <!-- Customer Info -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $customer->full_name }}</h1>
                <p class="text-sm text-gray-500">{{ $customer->unique_id }}</p>
            </div>
        </div>

        <!-- Right Section: Buttons -->
        <div class="flex flex-wrap gap-2">
            <!-- Edit Button -->
            <a href="{{ route('admin.crm.customers.edit', $customer->unique_id) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition">
                <x-heroicon-o-pencil-square class="w-5 h-5 mr-2" />
                Edit Customer
            </a>

            <!-- Delete Button -->
            <form action="{{ route('admin.crm.customers.delete', $customer->unique_id) }}"
                            method="POST" class="inline"
                            onsubmit="return confirm('Are you sure you want to delete this Customer?');">
                            @csrf
                            @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 transition">
                    <x-heroicon-o-trash class="w-5 h-5 mr-2" />
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6 mt-6">
    <!-- Personal Information -->
    <div class="bg-white rounded-lg border shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Personal Information</h3>
        <div class="space-y-3 text-sm">
            <div>
                <p class="text-gray-500 text-xs">Full Name</p>
                <span class="text-gray-900 font-medium text-sm">{{ $customer->full_name ?? 'N/A' }}</span>
            </div>
            <div>
                <p class="text-gray-500 text-xs">Phone</p>
                <span class="text-gray-900 flex items-center gap-1 text-sm">
                    <x-heroicon-o-phone class="w-4 h-4" /> {{ App\Helpers\CustomHelper::formatPhone($customer->phone) ?? 'N/A' }}
                </span>
            </div>
            <div>
                <p class="text-gray-500 text-xs">Customer Since</p>
                <span class="text-gray-900 flex items-center gap-1 text-sm">
                    <x-heroicon-o-calendar class="w-4 h-4" />  
                    {{ App\Helpers\CustomHelper::formatDate($customer->created_at) ?? 'N/A' }}
                    </span>
            </div>
            @php
    $status = $customer->status ?? 'Active';

    $statusStyles = [
        'Active'   => ['label' => 'Active',   'bg' => 'bg-green-100', 'text' => 'text-green-800'],
        'Inactive' => ['label' => 'Inactive', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'],
        'Archived' => ['label' => 'Archived', 'bg' => 'bg-gray-800',  'text' => 'text-white'],
    ];

    $style = $statusStyles[$status] ?? ['label' => $status, 'bg' => 'bg-gray-100', 'text' => 'text-gray-800'];
@endphp

<div>
    <p class="text-gray-500 text-xs">Status</p>
    <span class="inline-block rounded-full {{ $style['bg'] }} {{ $style['text'] }} text-sm font-semibold px-2 py-1">
            {{ $style['label'] }}
        </span>
    </div>

        </div>
    </div>

    <!-- Company Information -->
    <div class="bg-white rounded-lg border shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Company Information</h3>
        <div class="space-y-3 text-sm">
            <div>
                <p class="text-gray-500 text-xs">Company Name</p>
                <span class="text-gray-900 font-medium text-sm">{{ $customer->company_name  ?? 'N/A'}}</span>
            </div>
            <div>
                <p class="text-gray-500 text-xs">Company Phone</p>
                <span class="text-gray-900 flex items-center gap-1 text-sm">
                    <x-heroicon-o-phone class="w-4 h-4" /> {{ App\Helpers\CustomHelper::formatPhone($customer->company_phone) ?? 'N/A' }} 
                </span>
            </div>
            <div>
                <p class="text-gray-500 text-xs">Website</p>
                <span class="text-blue-600 hover:underline flex items-center gap-1 text-sm">
                    <x-heroicon-o-globe-alt class="w-4 h-4" />
                    @if($customer->company_website!='')
                    <a href="{{ $customer->company_website ?? '#' }}" target="_blank">{{ $customer->company_website ?? 'N/A' }}</a>
                    @else
                    <a href="#">N/A</a>
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Address Information -->
    <div class="bg-white rounded-lg border shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Addresses</h3>
        <div class="space-y-3 text-sm">

        @foreach ($customer->addresses as $addresse)
            
      

            <div>
                <p class="text-gray-500 text-xs">{{$addresse->type}} Address</p>
                <span class="text-gray-900 flex  gap-1 text-sm">
                    <x-heroicon-o-map-pin class="w-4 h-4" /> <div class="w-full">

                         {{$addresse->address}} , {{$addresse->city}} , {{$addresse->state->name}} , {{$addresse->zip_code}} .

                    </div>
                </span>
            </div>
            
            @endforeach

            <div>
                <p class="text-gray-500 text-xs">Alt. Billing Address</p>
                <span class="italic text-gray-400 text-sm">Not specified</span>
            </div>
            <div>
                <p class="text-gray-500 text-xs">Alt. Delivery Address</p>
                <span class="italic text-gray-400 text-sm">Not specified</span>
            </div>
        </div>
    </div>
</div>


        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 bg-gray-50 mt-6">
    <!-- Customer Account (smaller box) -->
    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm col-span-12 lg:col-span-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Customer Account</h2>
       <!-- Row 1 -->
             @php
                $approved = $customer->is_credit_account == 1;
            @endphp
            <div class="flex justify-between mb-5">
                <div class="float-left">
                <p class="text-gray-500 text-xs">Account Approved</p>
                    <p class="text-sm font-medium {{ $approved ? 'text-green-700' : 'text-red-600' }}">
                        {!! $approved ? '&#10003; Approved' : '&#10007; Not Approved' !!}
                    </p>
                </div>
                <div class="float-right text-right">
                    <p class=" text-gray-500 text-xs">Approved By</p>
                    <p class="text-base text-gray-900 text-sm"> {{ $customer->accountApprovedBy->full_name ?? 'N/A'}} </p>
                </div>
            </div>

           
            <div class="flex justify-between mb-5">
                <div class="float-left">
                <p class=" text-gray-500 text-xs">Credit Limit</p>
                    <p class="text-base text-gray-900 text-sm">{{ config('app.currency.code') }} {{ $customer->credit_limit ?? 0 }} </p>
                </div>
                <div class="float-right text-right">
                    <p class=" text-gray-500 text-xs">Application Completed</p>
                    <p class="text-base text-gray-900 text-sm"> {{ App\Helpers\CustomHelper::formatDate($customer->account_application_completed) ?? 'N/A' }}</p>
                </div>
            </div>
            @php
                $approved = $customer->is_credit_account == 1;
                $hasCreditLimit = !empty($customer->credit_limit);
            @endphp
            <div class="">
                
                
                @if ($approved && $hasCreditLimit)
            
                <label class=" text-gray-500 text-xs">Account Status: </label>

                    <span class="inline-block rounded-full bg-green-100 text-green-800 text-sm font-semibold px-2 py-1">
                        Good Standing
                    </span>
                @elseif ($approved && !$hasCreditLimit)
                <label class=" text-gray-500 text-xs">Account Status: </label>
            

                    <span class="inline-block rounded-full bg-yellow-100 text-yellow-800 text-sm font-semibold px-2 py-1">
                        Pending
                    </span>
                @else
                
                @endif
            </div>
    </div>

        <!-- Combined Tax Exempt + Upload (wider box) -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm col-span-12 lg:col-span-8">
                <div class="flex flex-col md:flex-row">
                    <!-- Tax Exempt Left Panel -->
                    <div class="md:w-1/2 pr-0 md:pr-6 border-b md:border-b-0 md:border-r border-gray-300 mb-6 pb-6 md:pb-0 md:mb-0">
                        <h2 class="text-lg font-semibold text-gray-900 mb-6">Tax Exempt</h2>
                        <!-- Row 1 -->
                        <div class="flex justify-between mb-5">
                            <div class="float-left">
                                <p class="text-gray-500 text-xs">Status</p>
                                <p class="text-base text-gray-900 text-sm"> {{ $customer->tax_status ?? 'N/A' }}</p>
                            </div>
                            <div class="float-right text-right">
                                <p class="text-gray-500 text-xs">Approved By</p>
                                <p class="text-base text-gray-900 text-sm">{{ $customer->taxStatusApprovedBy->full_name ?? 'N/A'}} </p>
                            </div>
                        </div>

                        <!-- Row 2 -->
                        <div class="flex justify-between">
                            <div class="float-left">
                                <p class=" text-gray-500 text-xs">Upload Date</p>
                                <p class="text-base text-gray-900 text-sm">{{ App\Helpers\CustomHelper::formatDate($customer->tax_document_upload_date) ?? 'N/A' }}</p>
                            </div>
                            <div class="float-right text-right">
                                <p class=" text-gray-500 text-xs">Valid Until</p>
                                <p class="text-base text-gray-900 text-sm">{{ App\Helpers\CustomHelper::formatDate($customer->tax_document_valid_until) ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Right Panel -->
                    <div class="md:w-1/2 md:pl-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-6">Tax Exempt Upload</h2>
                        @if ($customer->media)
                        <!-- File preview -->
                        <div class="bg-gray-100 rounded flex items-center justify-between p-3 mb-4">
                            <div>
                                <p class="font-medium text-gray-900 text-xs">{{$customer->media->original_file_name ?? ''}}</p>
                                <p class="text-xs text-gray-500">Uploaded: {{ App\Helpers\CustomHelper::formatDate($customer->tax_document_upload_date) }}</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <a href="{{ isset($customer->media) ? $customer->media->getUrl() : '' }}"  class="text-blue-600 hover:text-green-900  hover:bg-green-50 rounded" target="_blank">
                                    <x-heroicon-o-eye class="w-5 h-5" />
                                </a>
                               
                             
                            </div>
                        </div>

                        <!-- Status and metadata -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-600">Status:</span>
                                @if($customer->tax_document_status=='' || $customer->tax_document_status=='Pending Review')
                                    <span class="text-xs font-semibold bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full">
                                @elseif($customer->tax_document_status!='' && $customer->tax_document_status=='Approved')
                                    <span class="text-xs font-semibold bg-green-100 text-green-800 px-3 py-1 rounded-full">
                                @elseif($customer->tax_document_status!='' && $customer->tax_document_status=='Rejected')
                                    <span class="text-xs font-semibold bg-red-100 text-red-800 px-3 py-1 rounded-full">
                                @else
                                    <span class="text-xs font-semibold bg-gray-100 text-gray-800 px-3 py-1 rounded-full">
                                @endif
                                {{ $customer->tax_document_status ?? 'Pending Review'}} </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-600">Reviewed By:</span>
                                <span class="text-sm text-gray-900">{{ $customer->taxStatusApprovedBy->full_name ?? 'N/A'}}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-600">Review Date:</span>
                                <span class="text-sm text-gray-900">{{ App\Helpers\CustomHelper::formatDate($customer->tax_document_upload_date) ?? 'N/A' }}</span>
                            </div>
                        </div>

                        @else
                            <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 text-sm p-4 rounded-md">
                                No tax document has been uploaded yet.
                            </div>
                        @endif
                    </div>
                </div>
        </div>

</div>


<div class="bg-white border border-gray-200 shadow rounded-lg p-6 overflow-x-auto mt-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Order History</h2>

    <table class="min-w-full text-left text-sm text-gray-600">
        <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
            <tr>
                <th scope="col" class="px-4 py-2 font-medium uppercase">Order ID</th>
                <th scope="col" class="px-4 py-2 font-medium uppercase">Product</th>
                <th scope="col" class="px-4 py-2 font-medium uppercase">Amount</th>
                <th scope="col" class="px-4 py-2 font-medium uppercase">Payment Method</th>
                <th scope="col" class="px-4 py-2 font-medium uppercase">Status</th>
                <th scope="col" class="px-4 py-2 font-medium uppercase">Date</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">

        @forelse ($customer->orders as $order)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-semibold text-gray-900">{{ $order->order_number }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $customer->full_name }}</td>
                <td class="px-4 py-3 text-gray-700">{{ config('app.currency.code') }} {{ number_format($order->grand_total, 2) }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $order->payment_type ?? 'N/A' }}</td>
                <td class="px-4 py-3">
                @php
                    $statusColors = [
                        'Pending' => 'bg-yellow-100 text-yellow-800',
                        'In Progress' => 'bg-blue-100 text-blue-800',
                        'Completed' => 'bg-green-100 text-green-800',
                        'Cancelled' => 'bg-red-100 text-red-800',
                    ];
                    $statusColor = $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800';
                @endphp
                <span class="inline-block text-xs font-medium px-3 py-1 rounded-full {{ $statusColor }}">
                    {{ $order->status }}
                </span>
                </td>
                <td class="px-4 py-3 text-gray-700">  {{ App\Helpers\CustomHelper::formatDate($order->order_date) ?? 'N/A' }}</td>
            </tr>
        @empty
        <tr>
            <td colspan="6" class="px-4 py-4 text-center text-gray-500">
                No order found.
            </td>
        </tr>
    @endforelse
        </tbody>
    </table>
</div>



@endsection


