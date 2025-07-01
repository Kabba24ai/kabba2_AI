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
            <a href="{{ route('admin.crm.customer_portal.index') }}" class="flex items-center text-gray-600 hover:text-gray-800 transition">
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
            <a href="{{ route('admin.crm.customer_portal.edit', $customer->unique_id) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition">
                <x-heroicon-o-pencil-square class="w-5 h-5 mr-2" />
                Edit Customer
            </a>

            <!-- Delete Button -->
            <form action="{{ route('admin.crm.customer_portal.delete', $customer->unique_id) }}"
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
                <span class="text-gray-900 font-medium text-sm">{{ $customer->full_name }}</span>
            </div>
            <div>
                <p class="text-gray-500 text-xs">Phone</p>
                <span class="text-gray-900 flex items-center gap-1 text-sm">
                    <x-heroicon-o-phone class="w-4 h-4" /> {{ App\Helpers\CustomHelper::formatPhone($customer->phone) }}
                </span>
            </div>
            <div>
                <p class="text-gray-500 text-xs">Customer Since</p>
                <span class="text-gray-900 flex items-center gap-1 text-sm">
                    <x-heroicon-o-calendar class="w-4 h-4" />  
                    
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
                <span class="text-gray-900 font-medium text-sm">{{ $customer->company_name }}</span>
            </div>
            <div>
                <p class="text-gray-500 text-xs">Company Phone</p>
                <span class="text-gray-900 flex items-center gap-1 text-sm">
                    <x-heroicon-o-phone class="w-4 h-4" /> {{ App\Helpers\CustomHelper::formatPhone($customer->company_phone) }} 
                </span>
            </div>
            <div>
                <p class="text-gray-500 text-xs">Website</p>
                <span class="text-blue-600 hover:underline flex items-center gap-1 text-sm">
                    <x-heroicon-o-globe-alt class="w-4 h-4" />
                    <a href="{{ $customer->company_website }}" target="_blank">{{ $customer->company_website }}</a>
                </span>
            </div>
        </div>
    </div>

    <!-- Address Information -->
    <div class="bg-white rounded-lg border shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Addresses</h3>
        <div class="space-y-3 text-sm">
            <div>
                <p class="text-gray-500 text-xs">Billing Address</p>
                <span class="text-gray-900 flex items-center gap-1 text-sm">
                    <x-heroicon-o-map-pin class="w-4 h-4" /> 123 Main St, New York, NY 10001
                </span>
            </div>
            <div>
                <p class="text-gray-500 text-xs">Delivery Address</p>
                <span class="text-gray-900 flex items-center gap-1 text-sm">
                    <x-heroicon-o-map-pin class="w-4 h-4" /> 456 Oak Ave, New York, NY 10002
                </span>
            </div>
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

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6 mt-6">
    <!-- Card 1: Customer Account -->
    <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Customer Account</h2>
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


                <p class="text-base text-gray-900 text-sm"> {{ $customer->accountApprovedBy->full_name ?? ''}} </p>


            </div>
        </div>

        <!-- Row 2 -->
        <div class="flex justify-between mb-5">
            <div class="float-left">
              <p class=" text-gray-500 text-xs">Credit Limit</p>
                <p class="text-base text-gray-900 text-sm">{{ config('app.currency.code') }} {{ $customer->credit_limit }} </p>
            </div>
            <div class="float-right text-right">
                <p class=" text-gray-500 text-xs">Application Completed</p>
                <p class="text-base text-gray-900 text-sm"> {{ App\Helpers\CustomHelper::formatDate($customer->account_application_completed) }}</p>
            </div>
        </div>
        <div class="">
            <p class="text-gray-500 text-xs">Status</p>
            <span class="inline-block rounded-full bg-green-100 text-green-800 text-sm font-semibold px-2 py-1">
                Good Standing
            </span>
        </div>
    </div>

    <div class="bg-white border border-gray-200 shadow rounded-lg p-6 w-full max-w-md mx-auto">
        <h2 class="text-lg font-semibold text-gray-900 mb-5">Tax Exempt</h2>

        <!-- Row 1 -->
        <div class="flex justify-between mb-5">
            <div class="float-left">
                <p class="text-gray-500 text-xs">Status</p>
                <p class="text-base text-gray-900 text-sm"> {{ $customer->tax_status }}</p>
            </div>
            <div class="float-right text-right">
                <p class="text-gray-500 text-xs">Approved By</p>
                <p class="text-base text-gray-900 text-sm">{{ $customer->taxStatusApprovedBy->full_name ?? ''}} </p>
            </div>
        </div>

        <!-- Row 2 -->
        <div class="flex justify-between">
            <div class="float-left">
                <p class=" text-gray-500 text-xs">Upload Date</p>
                <p class="text-base text-gray-900 text-sm">{{ App\Helpers\CustomHelper::formatDate($customer->tax_document_upload_date) }}</p>
            </div>
            <div class="float-right text-right">
                <p class=" text-gray-500 text-xs">Valid Until</p>
                <p class="text-base text-gray-900 text-sm">{{ App\Helpers\CustomHelper::formatDate($customer->tax_document_valid_until) }}</p>
            </div>
        </div>
    </div>

    <!-- Card 3: Tax Exempt Upload -->
    <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Tax Exempt Upload</h2>

        <!-- File preview -->
        <div class="bg-gray-100 rounded flex items-center justify-between p-3 mb-4">
            <div>
                <p class="font-medium text-gray-900 text-xs">{{$customer->media->original_file_name ?? ''}}</p>
                <p class="text-xs text-gray-500">Uploaded: {{ App\Helpers\CustomHelper::formatDate($customer->tax_document_upload_date) }}</p>
            </div>
            <div class="flex items-center space-x-3">
            
            
            <a href="{{ isset($customer->media) ? $customer->media->getUrl() : '' }}" target="_blank">
            <svg class="w-5 h-5 text-gray-600 hover:text-blue-600 cursor-pointer" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" />
                    <circle cx="12" cy="12" r="3" />
                    </svg>
            </a>
                 
                  
                    <svg class="w-5 h-5 text-gray-600 hover:text-red-600 cursor-pointer" fill="none" stroke="currentColor"
                        stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m5 0V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" />
                    </svg>
            </div>
        </div>

        <!-- Status and metadata -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-600">Status:</span>
                <span class="text-xs font-semibold bg-green-100 text-green-800 px-3 py-1 rounded-full">Approved</span>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-600">Reviewed By:</span>
                <span class="text-sm text-gray-900">{{ $customer->taxStatusApprovedBy->full_name ?? ''}}</span>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-600">Review Date:</span>
                <span class="text-sm text-gray-900">{{ App\Helpers\CustomHelper::formatDate($customer->tax_document_upload_date) }}</span>
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
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-semibold text-gray-900">ORD-001</td>
                <td class="px-4 py-3 text-gray-700">Premium Widget</td>
                <td class="px-4 py-3 text-gray-700">$299.99</td>
                <td class="px-4 py-3 text-gray-700">Credit Card</td>
                <td class="px-4 py-3">
                    <span class="inline-block px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                        Paid
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-700">Jun 14, 2024</td>
            </tr>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-semibold text-gray-900">ORD-002</td>
                <td class="px-4 py-3 text-gray-700">Standard Widget</td>
                <td class="px-4 py-3 text-gray-700">$199.99</td>
                <td class="px-4 py-3 text-gray-700">PayPal</td>
                <td class="px-4 py-3 ">
                    <span class="inline-block px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                        Pending
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-700">Jun 19, 2024</td>
            </tr>
        </tbody>
    </table>
</div>
<!-- <div class="h-screen bg-gray-50 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-auto">
        <div class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="" class="flex items-center space-x-2 text-gray-600 hover:text-gray-800 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Back to Equipment</span>
                    </a>
                    <div class="h-6 border-l border-gray-300"></div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Add New Equipment</h1>
                        <p class="text-sm text-gray-600">Create new equipment information</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> -->


@endsection


