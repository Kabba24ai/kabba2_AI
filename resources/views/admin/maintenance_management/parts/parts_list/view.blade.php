@extends('admin.layouts.app')

@section('title', 'Parts Management')

@section('content')

<div class="min-h-screen">
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-4">
        <!-- Header Row -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Title + Description -->
            <div class="flex items-start sm:items-center gap-3">
                <a href="{{ route('admin.maintenance-management.parts.index') }}" class="flex items-center text-gray-500 hover:text-gray-700">
                   <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left h-6 w-6"><path d="m12 19-7-7 7-7"></path><path d="M19 12H5"></path></svg>
                </a>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text h-8 w-8 text-blue-600"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path></svg>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 flex items-center gap-2">
                       {{ $list->name }}
                    </h1>
                    <p class="text-gray-500 text-sm">
                        {{ $list->description }}
                    </p>
                </div>
            </div>

            <!-- Edit Button -->
            <a href="{{ route('admin.maintenance-management.parts.parts-list.edit', $list->unique_id) }}"  class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-md font-medium px-6 py-3 rounded-lg shadow-sm transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card h-4 w-4"><rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" x2="22" y1="10" y2="10"></line></svg>
                Edit Template
</a>
        </div>

        <!-- Divider -->
        <hr class="border-gray-200" />

        <!-- Stats Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 text-sm">
            <div>
            <p class="text-gray-500">Category</p>
            <p class="text-gray-900 font-semibold">

                {{ $list->category->title ?? '—' }}


    </p>
            </div>
            <div>
            <p class="text-gray-500">Total Parts</p>
            <p class="text-gray-900 font-semibold"> {{ $list->parts->count() }}</p>
            </div>
            <div>
            <p class="text-gray-500">Total Value</p>
            <p class="text-green-600 font-semibold">         {{ \App\Helpers\CustomHelper::formatCurrency($list->parts->sum(fn($p) => $p->primary_part_cost ?? 0)) }}
</p>
            </div>
            <div>
            <p class="text-gray-500">Last Modified</p>
            <p class="text-gray-900 font-semibold">{{ App\Helpers\CustomHelper::formatDate($list->updated_at) ?? 'N/A' }} </p>
            </div>
        </div>
    </div>

    <div class="bg-gray-50 mt-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left Card: Assigned Equipment -->
             <div class="lg:col-span-1">
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building2 h-5 w-5 text-gray-700"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg>
                            <h2 class="text-lg font-semibold text-gray-800">Assigned Equipment</h2>
                        </div>

                        <div class="space-y-3">


        @foreach($list->product_details as $equipment)
            <div onclick="window.location='{{ route('admin.maintenance-management.equipment.edit', $equipment['unique_id']) }}';" class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                <p class="font-medium text-gray-900 text-sm">{{ $equipment['name'] }}</p>
                <p class="text-xs text-gray-600 font-mono mt-1">{{ $equipment['equipment_id'] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ optional($list->category)->title ?? '—' }}</p>
            </div>
        @endforeach



                        </div>
                    </div>

                    <div class="border-t border-gray-200 mt-6 pt-4 text-sm text-gray-600 ">
                        <p><span class="font-medium">Created:</span> {{ App\Helpers\CustomHelper::formatDate($list->created_at) ?? 'N/A' }}</p>
                        <p><span class="font-medium">Created by:</span>   {{ $list->creator->full_name ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Right Card: Template Parts -->
             <div class="lg:col-span-2">
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package h-5 w-5 text-gray-700"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
                            <h2 class="text-lg font-semibold text-gray-800">Template Parts ({{ $list->parts->count() }})</h2>
                        </div>
                    </div>

                <!-- Part Card -->
                <div class="space-y-4">
                   @foreach($list->parts as $part)
                    <div onclick="window.location='{{ route('admin.maintenance-management.parts.view', $part->unique_id) }}';" class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 hover:shadow-sm transition-all bg-white">
                        <div class="flex flex-wrap items-start justify-between gap-2 mb-3">
                            <h3 class="font-semibold text-gray-900">{{ $part->part_name }}</h3>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                {{ $part->stock_status == 'in-stock' ? 'text-green-700 bg-green-100 border-green-200' : '' }}
                                {{ $part->stock_status == 'buy-now' ? 'text-yellow-700 bg-yellow-100 border-yellow-200' : '' }}
                                {{ $part->stock_status == 'out-of-stock' ? 'text-red-700 bg-red-100 border-red-200' : '' }}
                                {{ $part->stock_status == 'dni' ? 'text-gray-700 bg-gray-100 border-gray-200' : '' }}">
                                {{ ucfirst(str_replace('-', ' ', $part->stock_status)) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                            <!-- Part Details -->
                            <div>
                                <div class="pl-2 space-y-1 text-xs sm:text-sm">
                                    <div><span class="text-gray-500">Part #:</span> <span class="ml-2 font-mono text-gray-900">{{ $part->primary_part_number }}</span></div>
                                    <div><span class="text-gray-500">Supplier:</span> <span class="ml-2 text-gray-900">{{ $part->primarySupplier->name ?? '—' }}</span></div>
                                  
                                </div>
                            </div>

                            <!-- Inventory -->
                            <div>
                                <div class="pl-2 space-y-1 text-xs sm:text-sm">
                                    <div><span class="text-gray-500">Unit Cost:</span> <span class="ml-2 font-semibold text-green-600">${{ number_format($part->primary_part_cost ?? 0, 2) }}</span></div>
                                    <div><span class="text-gray-500">On Hand:</span> <span class="ml-2 text-gray-900">{{ $part->stock_level }}</span></div>
                                    <div><span class="text-gray-500">Min Stock:</span> <span class="ml-2 text-gray-900">{{ $part->min_stock }}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-200 mt-6 pt-4 flex justify-between items-center">
                    <p class="text-sm text-gray-600">Total template value (parts only)</p>
                    <p class="text-lg font-semibold text-green-600"> {{ \App\Helpers\CustomHelper::formatCurrency($totalValue) }} </p>
                </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('js')
