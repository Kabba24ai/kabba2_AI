@extends('front.layouts.app')

@section('title', $title)

@section('content')

    <!-- Page Title Section -->
    <section
            class="transform transition-all duration-300 ease-in-out md:border-l-30 md:border-l-white md:border-r-30 md:border-r-white bg-light">
            <div class="container md:px-0">
                <div id="breadcrumbs" class="pt-100 pb-5 ">
                    <div class="w-full">
                        <div class="flex flex-col lg:flex-row justify-between items-center gap-y-2 md:gap-y-0">
                            <h1 class="header-title">Order</h1>
                            <ul
                                class="border-yellow-400 px-5 py-2 lg:py-3 max-w-full text-sm font-medium items-center inline-flex gap-3 relative border-2 border-white">
                                <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
                                    <a href="{{ route('front.home.index') }}" class="opacity-25">Home</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" class="cursor-not-allowed">Order</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
    </section>

     <section class="section-padding">
         <div class="container">
            <div class="lg:grid grid-cols-4 gap-4">
                <div class="mt-6 lg:mt-0">
                @include('front.customer.partials._sidebar')
                </div>
                <div class="my-6 lg:my-0 lg:col-span-3 box-shadow p-6 overflow-hidden">
                    <h3 class="text-22 tracking-[-1px] font-medium mb-5  border-b pb-3">Orders - Equipment Rentals Hickman / Dickson County</h3>
                    <div class="overflow-x-scroll md:overflow-x-hidden">
                        <table class="border-collapse w-full border text-left">
                            <thead>
                            <tr>
                                <th class="border px-4 py-2">ID number</th>
                                <th class="border px-4 py-2">Date</th>
                                <th class="border px-4 py-2">Total</th>
                                <th class="border px-4 py-2">Status</th>
                                <th class="border px-4 py-2">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                                @forelse ($customer->orders as $order)
                                    <tr class="odd:bg-white even:bg-gray-100">
                                        <td class="border px-4 py-2">
                                            <p>{{ $order->unique_id }}</p>
                                        </td>
                                        <td class="border px-4 py-2">
                                            <p>{{ App\Helpers\CustomHelper::formatDate($order->order_date) ?? 'N/A' }}</p>
                                        </td>
                                        <td class="border px-4 py-2">
                                            <p>{{ App\Helpers\CustomHelper::formatCurrency($order->grand_total) }} </p>
                                        </td>
                                        <td class="border px-4 py-2">
                                            <p>{{ $order->status->label() }}</p>
                                        </td>
                                        <td class="border px-4 py-2">
                                            <div class="flex gap-x-4">
                                                <a href="javascript:void(0)" disabled="disabled" title="Pending" class="border-0 block text-center font-bold bg-yellow-400 text-sm rounded px-6 py-3 hover:bg-yellow-300 transition-all duration-500 ease-in-out">VIEW</a>
                                                <a href="javascript:void(0)" disabled="disabled" title="Pending" class="border-0 block text-center font-bold bg-yellow-400 text-sm rounded px-6 py-3 hover:bg-yellow-300 transition-all duration-500 ease-in-out">TERMS</a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="border px-4 py-6 text-center text-gray-500">
                                            No orders found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>

                </div>
            </div>
         </div>
     </section>

@endsection
