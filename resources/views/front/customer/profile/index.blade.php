@extends('front.layouts.app')

@section('title', $title)

@section('content')

 <!-- Page Title Section -->
 <section
        class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-white md:border-r-[30px] md:border-r-white bg-light">
        <div class="container md:px-0">
            <div id="breadcrumbs" class="pt-[100px] pb-[20px] ">
                <div class="w-full">
                    <div class="flex flex-col lg:flex-row justify-between items-center gap-y-2 md:gap-y-0">
                        <h1 class="header-title">Profile</h1>
                        <ul
                            class="border-yellow-400 px-[20px] py-2 lg:py-3 max-w-full text-sm font-medium items-center inline-flex gap-3 relative border-2 border-white">
                            <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
                                <a href="{{ route('front.home.index') }}" class="opacity-25">Home</a>
                            </li>
                            <li>
                                <a href="javascript:void(0)" class="cursor-not-allowed">Profile</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

     <section class="lg:pb-[50px] lg:pt-[50px]">
         <div class="container">
            <div class="lg:grid grid-cols-4 gap-4">
                <div class="mt-6 lg:mt-0">

                @include('front.customer.partials._sidebar')

                </div>
                <div class="my-6 lg:my-0 lg:col-span-3 box-shadow p-6">
                    <h3 class="text-[22px] tracking-[-1px] font-medium mb-5 border-b pb-3">Profile - Equipment Rentals Hickman / Dickson County</h3>
                    <!-- <form action="#"> -->

                    {{ html()->form()->attributes([
                        'class' => 'space-y-5',
                        'method' => 'POST',     
                        'id' => 'yourFormId',
                        'autocomplete' => 'off',
                        'data-parsley-validate' => true,
                    ])->open() }}
                    @csrf


                    <div class="flex flex-col md:flex-row gap-4">    
                        <div class="relative z-0 w-full md:w-1/2 mt-2 group">
                                
                            {{ html()->text('first_name', $customer->first_name)->attributes([
                            'class' => 'check-on-input block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer',
                            'id' => 'first_name',
                            'placeholder' => ' ', 
                            'required' => true,
                                ]) }}

                            <label for="first_name" class=" z-1 pointer-events-none px-6 absolute text-base text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black">
                                First Name <i class="fa-solid fa-circle-check text-yellow-400 opacity-0"></i>
                            </label>
                        </div>
                        <div class="relative z-0 w-full md:w-1/2 mt-2 group">
                        
                                {{ html()->text('last_name', $customer->last_name)->attributes([
                                    'class' => 'check-on-input block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer',
                                    'id' => 'last_name',
                                    'placeholder' => '',
                                    'required' => true,
                                ]) }}
    
                                <label for="last_name" class=" z-1 pointer-events-none px-6 absolute text-base text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black">
                                    Last Name <i class="fa-solid fa-circle-check text-yellow-400 opacity-0"></i>
                                </label>
    
                            </div>
                        </div>
                        <div class="relative z-0 w-full my-6 mt-2 group">
                            {{ html()->email('email', $customer->email)->attributes([
                                'class' => 'check-on-input block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer',
                                'id' => 'email',
                                'placeholder' => ' ',
                                'disabled' => true,
                            ]) }}

                            <label for="email" class=" z-1 pointer-events-none px-6 absolute text-base text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black">
                                Email <i class="fa-solid fa-circle-check text-yellow-400 opacity-0"></i>
                            </label>
                        </div>
                        <div class="relative z-0 w-full my-6 group">
                            
                            <!-- <input type="phone" name="phone-number" id="p_number" class="check-on-input block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer" placeholder=" "> -->

                            {{ html()->text('number', $customer->phone)->attributes([
                                'class' => 'masked-phone check-on-input block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer',
                                'id' => 'phone',
                                'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                                'data-parsley-error-message' => 'Please enter phone number in format (123) 456-7890',
                                'placeholder' => '',
                                'autocomplete' => 'tel',
                            ])->required() }}

                            <label for="phone" class=" z-1 pointer-events-none px-6 absolute text-base text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black">
                                Phone <i class="fa-solid fa-circle-check text-yellow-400 opacity-0"></i>
                            </label>
                        </div>

                        <button type="submit" class="border-0 px-3 py-2 lg:px-5 lg:py-4 mx-auto font-medium bg-yellow-400 hover:bg-yellow-500 text-black transform transition-all duration-500 ease-in-out text-sm">
                            <i class="mr-3 fa-solid fa-arrow-right text-base lg:text-2xl lg:text-sm leading-4 text-purple -rotate-45 border-2 rounded-full link-icon border-purple"></i>
                            Update
                        </button>
                        {{ html()->form()->close() }}
                        </div>
            </div>
         </div>
     </section>
@endsection