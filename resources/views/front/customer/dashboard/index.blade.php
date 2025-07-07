@extends('front.layouts.app')

@section('title', $title)

@section('content')

<form id="logout-form" method="POST" action="{{ route('front.auth.logout.index') }}" class="hidden">
                                @csrf
                            </form>


 <!-- Page Title Section -->
 <section
        class="transform transition-all duration-300 ease-in-out md:border-l-30 md:border-l-white md:border-r-30 md:border-r-white bg-light">
        <div class="container md:px-0">
            <div id="breadcrumbs" class="pt-100 pb-5 ">
                <div class="w-full">
                    <div class="flex flex-col lg:flex-row justify-between items-center gap-y-2 md:gap-y-0">
                        <h1 class="header-title">Account information</h1>
                        <ul
                            class="border-yellow-400 px-5 py-2 lg:py-3 max-w-full text-sm font-medium items-center inline-flex gap-3 relative border-2 border-white">
                            <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
                                <a href="{{ route('front.home.index') }}" class="opacity-25">Home</a>
                            </li>
                            <li>
                                <a href="javascript:void(0)" class="cursor-not-allowed">Account information</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

     <section class="section-padding">
         <div class="container ">
            <div class="lg:grid grid-cols-4 gap-4">
                <div class="mt-6 lg:mt-0">


                @include('front.customer.partials._sidebar')
                  

                </div>
                <div class="my-6 col-span-3 lg:my-0 lg:col-span-3 box-shadow p-6">
                    <h3 class="text-[22px] tracking-[-1px] font-medium mb-5 border-b  pb-3">Account information - Equipment Rentals Hickman / Dickson County</h3>
                    <div class="flex flex-col gap-y-4">
                        <p>
                            Hello, <strong>{{ auth('customer')->user()->full_name }}</strong> (not <strong>{{ auth('customer')->user()->full_name }}?</strong> <a href="javascript:void(0)" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-yellow-600">Log Out</a> )
                        </p>
                        <p class="text-light-gray">
                            From your account dashboard you can view your <a href="javascript:void(0)" class="text-yellow-600"> recent orders</a>, manage your <a href="javascript:void(0)" class="text-yellow-600"> shipping and billing addresses,</a> and <a href="javascript:void(0)" class="text-yellow-600"> edit your password and account details</a> .
                        </p>
                        <p>The following addresses will be used on the checkout page by default.</p>
                    </div>
                    <!-- <div class="mt-4">
                        <div class="flex justify-between mb-3 border-b pb-2 ">
                            <h4 class="font-medium text-light-gray">Address</h4>
                            <a href="javascript:void(0)" class="text-yellow-600 hover:text-black transition-all duration-500 ease-in-out ">Add</a>
                        </div>
                        <div class="md:grid grid-cols-2 gap-x-4">

                            <div class="address-box px-4 py-3 text-light-gray">
                                <p class="mb-2"></p>
                                <p class="mb-2">County Road</p>
                                <p class="mb-2">Pearland, Texas , 3333333</p>
                                <p class="mb-2">(888) 888-8888</p>
                                <div class="flex justify-between">
                                    <div>
                                        <a href="javascript:void(0)" class="text-yellow-600 text-sm hover:text-black transition-all duration-500 ease-in-out ">Edit</a>
                                        <a href="javascript:void(0)" class="text-red ms-2 transition-all duration-500 ease-in-out  text-sm">Remove</a>
                                    </div>
                                    <span class="bg-yellow-400 lh-20 text-purple text-xs font-medium me-2 px-2.5 py-0.5 rounded-sm flex items-center">Default Address</span>
                                </div>
                            </div>

                         
                        </div>
                    </div> -->
                </div>
            </div>
         </div>
     </section>

@push('js')
    <script>
        const fileInput = document.getElementById('dropzone-file');
        const previewImg = document.getElementById('image-preview');

        fileInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>

@endpush
@endsection