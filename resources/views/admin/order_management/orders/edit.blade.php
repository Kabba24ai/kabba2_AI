@extends('admin.layouts.app')

@section('title', 'Edit Order')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    {{-- Order Header Section --}}
    <div class="bg-white px-4 py-4 rounded-md shadow-sm mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">

            {{-- Order Info + Customer --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:gap-4">
                <h2 class="text-lg font-semibold text-gray-800">
                    <span class="text-gray-700">Order ID:</span> 345678
                </h2>
                <span class="text-sm text-gray-600">Customer: Sasha Hill</span>
            </div>

            {{-- Payment Status + Refund Button --}}
            <div class="flex flex-wrap items-center gap-2">
                <span
                    class="inline-flex items-center px-3 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">
                    <span class="w-2 h-2 bg-green-600 rounded-full mr-2"></span>
                    Paid In Full Via - Credit/Debit Card
                </span>
                <button
                    class="text-xs px-2 py-1 border rounded text-gray-600 border-gray-300 hover:bg-gray-100">Refund</button>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap gap-2">
                <button
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-orange-500 text-white rounded hover:bg-orange-600">
                    <x-heroicon-o-arrow-path-rounded-square class="w-4 h-4 mr-1" /> Reorder
                </button>
                <button
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-blue-500 text-white rounded hover:bg-blue-600">
                    <x-heroicon-o-envelope class="w-4 h-4 mr-1" /> Email Invoice
                </button>
                <button
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                    <x-heroicon-o-printer class="w-4 h-4 mr-1" /> Print Invoice
                </button>
                <button
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-green-500 text-white rounded hover:bg-green-600">
                    <x-heroicon-o-check class="w-4 h-4 mr-1" /> Save
                </button>
            </div>
        </div>

        {{-- Sub-links under customer --}}
        <div class="mt-2 flex gap-4 text-sm text-blue-600">
            <a href="#" class="inline-flex items-center hover:underline">
                <x-heroicon-o-user class="w-4 h-4 mr-1" /> Customer Portal
            </a>
            <a href="#" class="inline-flex items-center hover:underline">
                <x-heroicon-o-link class="w-4 h-4 mr-1" /> Website Login
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        {{-- Billing Info --}}
        <div class="bg-white rounded-md shadow-sm p-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-800">Billing Information</h3>
                <x-heroicon-o-pencil-square class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-pointer" />
            </div>
            <div class="text-sm text-gray-700 space-y-1">
                <p><span class="font-medium">Customer Name:</span> Sasha Hill</p>
                <p><span class="font-medium">Email:</span> <a href="mailto:sashabrown089@icloud.com"
                        class="text-blue-600 hover:underline">sashabrown089@icloud.com</a></p>
                <p><span class="font-medium">Phone:</span> (229) 699-0100</p>
                <p><span class="font-medium">Billing Address:</span> 123 Abraham Street, Dickson TN 37055</p>
                <a href="https://maps.google.com/?q=123+Abraham+Street,+Dickson+TN+37055" target="_blank"
                    class="text-blue-600 text-xs hover:underline">See on maps</a>
            </div>
        </div>

        {{-- Delivery Info --}}
        <div class="bg-white rounded-md shadow-sm p-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-800">Delivery Information</h3>
                <x-heroicon-o-pencil-square class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-pointer" />
            </div>
            <p class="text-sm text-blue-600">Same as billing</p>
        </div>

        {{-- History --}}
        <div class="bg-white rounded-md shadow-sm p-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-800">History</h3>
                <x-heroicon-o-pencil-square class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-pointer" />
            </div>
            <ul class="text-sm text-gray-700 space-y-1">
                <li class="flex justify-between">
                    <span>• New Order Placed: Sasha Hill</span>
                    <span class="text-xs text-gray-500">5/16/2025 – 08:32 AM</span>
                </li>
                <li class="flex justify-between">
                    <span>• Order Paid: Credit / Debit Card</span>
                    <span class="text-xs text-gray-500">5/16/2025 – 08:32 AM</span>
                </li>
                <li class="flex justify-between">
                    <span>• Terms Signed</span>
                    <span class="text-xs text-gray-500">5/16/2025 – 08:35 AM</span>
                </li>
                <li class="flex justify-between">
                    <span>• Order Delivered</span>
                    <span class="text-xs text-gray-500">5/16/2025 – 03:43 AM</span>
                </li>
            </ul>
        </div>

    </div>

    <div class="bg-white rounded-md shadow-sm p-6 mb-6 space-y-6">
        <h3 class="text-base font-semibold text-gray-800">Equipment Orders & Delivery Schedule</h3>

        {{-- Equipment Info --}}
        <div class="grid md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div class="flex gap-4">
                    <div class="w-[150px] h-[150px] bg-gray-100 flex items-center justify-center text-gray-400">
                        150 × 150
                    </div>
                    <div>
                        <a href="#" class="text-blue-600 font-semibold hover:underline">
                            Skid Steer w/Cab – Weekend Special
                        </a>
                        <p class="text-sm text-gray-500">Equipment ID: Kub-SB-1</p>
                    </div>
                </div>

                <div class="border rounded p-4 text-sm text-gray-700 space-y-2 bg-gray-50">
                    <div class="flex justify-between">
                        <span>Equipment Cost</span>
                        <span>$591.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Options Total:</span>
                        <span>$237.00</span>
                    </div>
                    <ul class="pl-5 list-disc text-gray-600 text-sm">
                        <li>Prepaid Diesel - 15 Gal</li>
                        <li>Tooth Bucket</li>
                        <li>Delivery</li>
                        <li>Pick Up</li>
                    </ul>
                    <div class="flex justify-between font-semibold">
                        <span>Qty - 1</span>
                        <span>Sub Total: $828.00</span>
                    </div>
                </div>

                <div class="text-sm px-3 py-2 bg-gray-100 rounded text-gray-600">
                    Customer Inspected equipment – no issues noted on delivery
                </div>
            </div>

            {{-- Schedule Panels --}}
            <div class="bg-white rounded-md shadow-sm p-6 space-y-6">

                {{-- Delivery Schedule --}}
                <div class="space-y-3">
                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                        <span>📦</span> Delivery Schedule
                    </div>
                    <div class="grid grid-cols-5 gap-3 text-sm">
                        <input type="date" value="2025-05-16" class="border rounded px-3 py-2" />
                        <input type="time" value="14:00" class="border rounded px-3 py-2" />
                        <select class="border rounded px-3 py-2">
                            <option selected>Delivery</option>
                        </select>
                        <select class="border rounded px-3 py-2 text-blue-600">
                            <option selected>Completed</option>
                        </select>
                        <select class="border rounded px-3 py-2">
                            <option selected>Bon Aqua</option>
                        </select>
                    </div>
                    <div class="mt-2">
                        <select class="border rounded px-3 py-2 text-sm w-1/3">
                            <option selected>John Smith</option>
                        </select>
                    </div>
                </div>

                {{-- Return Schedule --}}
                <div class="space-y-3">
                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                        <span>📦</span> Return Schedule
                    </div>
                    <div class="grid grid-cols-5 gap-3 text-sm">
                        <input type="date" value="2025-05-19" class="border rounded px-3 py-2" />
                        <input type="time" value="09:00" class="border rounded px-3 py-2" />
                        <select class="border rounded px-3 py-2">
                            <option selected>Return</option>
                        </select>
                        <select class="border rounded px-3 py-2 text-yellow-600">
                            <option selected>Pending</option>
                        </select>
                        <select class="border rounded px-3 py-2">
                            <option selected>Bon Aqua</option>
                        </select>
                    </div>
                    <div class="mt-2">
                        <select class="border rounded px-3 py-2 text-sm w-1/3">
                            <option selected disabled>Select Technician</option>
                        </select>
                    </div>
                </div>

                {{-- Status Checklist --}}
                <div class="border-t pt-4 grid grid-cols-6 gap-2 text-center text-xs font-medium text-gray-700">
                    <div>
                        <div class="text-red-600 text-lg font-bold">✗</div>
                        <span>Terms</span>
                    </div>
                    <div>
                        <div class="text-green-600 text-lg font-bold">✓</div>
                        <span>License</span>
                    </div>
                    <div>
                        <div class="text-green-600 text-sm font-bold">D <span class="text-red-500">R</span></div>
                        <span>Checklist</span>
                    </div>
                    <div>
                        <div class="text-green-600 text-sm font-bold">D <span class="text-red-500">R</span></div>
                        <span>Machine Hours</span>
                    </div>
                    <div>
                        <div class="text-green-600 text-sm font-bold">D <span class="text-red-500">R</span></div>
                        <span>Video</span>
                    </div>
                    <div class="col-span-6 mt-2 text-[10px] text-gray-400">
                        ✓ Completed | ● Pending | ✗ N/A | <span class="text-blue-600">D = Delivery</span> | <span
                            class="text-red-500">R = Return</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary & Notes --}}
        <div class="grid md:grid-cols-2 gap-4">
            <div class="text-sm text-gray-700 space-y-2 border-t pt-4">
                <div class="flex justify-between">
                    <span>Sub-Total:</span>
                    <span>$977.00</span>
                </div>
                <div class="flex justify-between">
                    <span>Taxes:</span>
                    <span>$77.90</span>
                </div>
                <div class="flex justify-between font-bold text-gray-900 border-t pt-2">
                    <span>Grand Total:</span>
                    <span>$1,054.90</span>
                </div>
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-gray-700">Order Notes:</label>
                <input type="text" value="Customer requested early morning delivery"
                    class="w-full border rounded px-3 py-2 text-sm text-gray-800" />
                <button class="mt-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                    Save Note
                </button>
            </div>
        </div>
    </div>


@endsection

@push('js')
@endpush
@endpush
