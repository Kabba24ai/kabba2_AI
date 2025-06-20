@extends('front.layouts.app')

@section('title', $title)

@section('content')
    <!-- Page Title Section -->
    <section
        class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-[#fff] md:border-r-[30px] md:border-r-[#fff] bg-[#f9fafc]">
        <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-[30px] md:px-0">
            <div id="breadcrumbs" class="pt-[100px] pb-[20px] ">
                <div class="w-full">
                    <div class="flex flex-col lg:flex-row justify-between items-center ">
                        <h1 class="text-[40px] tracking-[-2px] leading-[110%] font-bold">{{ $category->title }}</h1>
                        <ul
                            class="bg-yellow-400 px-[20px] py-2 lg:py-3 max-w-full text-[14px] font-medium items-center inline-flex gap-3 relative border-2 border-[#fff]">
                            <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
                                <a href="{{ route('front.home.index') }}" class="opacity-75">Home</a>
                            </li>
                            <li>
                                <a href="javascript:void(0)" class="cursor-not-allowed">{{ $category->title }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Listing -->
    <section class="pb-[60px] relative overflow-hidden">
        <div class="rent-bg">
            <div class="rent-bg-inner">
                <div></div>
            </div>
        </div>
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

                @foreach ($products as $product)
                    <div class="py-[20px] lg:py-[30px] rounded-lg text-center">
                        <div class="transition-all duration-300 ease-in-out hover:text-black">
                            <div class="lg:h-80 w-full group relative">
                                <img src="{{ $product->image_url }}" alt="{{ $product->product_name }}"
                                    class="mx-auto h-full lg:w-full w-[90%] object-contain opacity-100 group-hover:opacity-0 transition-opacity duration-1000 ease-in-out" />
                                <img src="{{ $product->image_url }}" alt="{{ $product->product_name }}"
                                    class="mx-auto h-full lg:w-full w-[90%] object-contain opacity-0 absolute top-0 group-hover:opacity-100 transition-opacity duration-1000 ease-in-out" />
                            </div>

                            <div>
                                <h3 class="mt-4 font-bold text-[18px] text-black md:text-[22px] p-2 bg-[#f2f3f5] mb-4">
                                    {{ $product->product_name }}</h3>
                                <div class="grid grid-cols-2 gap-x-6 gap-y-2">
                                    <a href="{{ route('front.products.details', ['slug' => $product->slug, 'productType' => 'daily']) }}"
                                        class="border-0 bg-yellow-400 text-black font-medium flex flex-col px-6 py-2 leading-4 rounded-xl text-[14px] hover:bg-yellow-500  transition-all duration-500 ease-in-out">
                                        Daily
                                        <span>{{ $product->rental_daily }}</span>
                                    </a>
                                    <a href="{{ route('front.products.details', ['slug' => $product->slug, 'productType' => 'weekend']) }}"
                                        class="border-0 bg-yellow-400 text-black font-medium flex flex-col px-6 py-2 leading-4 rounded-xl text-[14px] hover:bg-yellow-500  transition-all duration-500 ease-in-out">
                                        Weekend Spcl.
                                        <span>{{ $product->rental_weekend }}</span>
                                    </a>
                                    <a href="{{ route('front.products.details', ['slug' => $product->slug, 'productType' => 'weekly']) }}"
                                        class="border-0 bg-yellow-400 text-black font-medium flex flex-col px-6 py-2 leading-4 rounded-xl text-[14px] hover:bg-yellow-500  transition-all duration-500 ease-in-out">
                                        Weekly
                                        <span>{{ $product->rental_weekly }}</span>
                                    </a>
                                    <a href="{{ route('front.products.details', ['slug' => $product->slug, 'productType' => 'monthly']) }}"
                                        class="border-0 bg-yellow-400 text-black font-medium flex flex-col px-6 py-2 leading-4 rounded-xl text-[14px] hover:bg-yellow-500  transition-all duration-500 ease-in-out">
                                        Monthly
                                        <span>{{ $product->rental_monthly }}</span>
                                    </a>
                                </div>
                                <h6 class="text-[12px] mt-2 text-black">Select an Option to Learn More or Reserve Today
                                </h6>
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </section>

    <!-- Categories Listing details -->
    <section class="pb-[60px]">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <div class="flex flex-col gap-y-4">
                {!! $category->content !!}
            </div>
        </div>
    </section>

    <section class="pb-[60px]">
        <div
            class="container mx-auto md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] px-[30px] md:px-[.7rem] relative overflow-hidden">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-y-10 lg-gap-10 2xl:gap-y-12 md:gap-8 ">
                <div>
                    <iframe class="w-full h-full md:w-full md:h-[200px] lg:w-full lg:h-[320px] 2xl:w-[610px] 2xl:h-[350px]"
                        src="https://www.youtube.com/embed/gKdAVVo9NvY?si=g57-VsreHP3dFbAx" title="YouTube video player"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen="" frameborder="0"></iframe>
                </div>
                <div>
                    <iframe class="w-full h-full md:w-full md:h-[200px] lg:w-full lg:h-[320px] 2xl:w-[610px] 2xl:h-[350px]"
                        src="https://www.youtube.com/embed/gKdAVVo9NvY?si=g57-VsreHP3dFbAx" title="YouTube video player"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen="" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </section>
@endsection
