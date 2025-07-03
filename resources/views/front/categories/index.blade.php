@extends('front.layouts.app')

@section('title', $title)

@section('content')
    <!-- Page Title Section -->
    <section
        class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-white md:border-r-[30px] md:border-r-white bg-gray-100">
        <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-[30px] md:px-0">
            <div id="breadcrumbs" class="pt-[100px] pb-[20px] ">
                <div class="w-full">
                    <div class="flex flex-col lg:flex-row justify-between items-center ">
                        <h1 class="text-[40px] tracking-[-2px] leading-[110%] font-bold">{{ $category->title }}</h1>
                        <ul
                            class="border-yellow-400 px-[20px] py-2 lg:py-3 max-w-full font-medium items-center inline-flex gap-3 relative border-2">
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
                            <div
                                class="lg:h-80 w-full flex items-center justify-center relative rounded-lg overflow-hidden group transition-all duration-500">
                                <div
                                    class="w-full h-full flex items-center justify-center bg-white transition-all duration-500 rounded-lg">
                                    <img src="{{ $product->image_url }}" alt="{{ $product->product_name }}"
                                        class="w-[90%] h-full object-contain transition-all duration-500 ease-in-out group-hover:scale-105 "
                                        loading="lazy" />
                                </div>
                            </div>


                            <div>
                                <h3 class="mt-4 font-bold text-black md:text-xl p-2 bg-gray-100 mb-4">
                                    {{ $product->product_name }}
                                </h3>
                                @if ($product->product_type == 'Rental')
                                    <div class="grid grid-cols-2 gap-2">
                                        <!-- Repeat this button block for each rental period -->
                                        @foreach (['daily', 'weekend', 'weekly', 'monthly'] as $type)
                                            <a href="{{ route('front.products.details', ['categorySlug' => $category->slug, 'slug' => $product->slug, 'productType' => $type]) }}"
                                                class="relative border-0 bg-yellow-400 text-black font-medium flex flex-col items-center px-2 rounded-xl hover:bg-yellow-500 overflow-hidden min-h-[40]">
                                                {{-- @if ($product->isRentalOnSale($type))
                                                    <span
                                                        class="absolute left-[-25px] top-2 w-[100px] h-6 bg-white text-yellow-400 font-bold text-xs flex items-center justify-center"
                                                        style="transform: rotate(-50deg); z-index: 20; box-shadow: 0 4px 6px rgba(0,0,0,0.10); letter-spacing: 0.5px;">
                                                        Sale
                                                    </span>
                                                @endif --}}
                                                <span class="z-30">
                                                    {{ ucfirst($type) }}
                                                    @if ($product->isRentalOnSale($type))
                                                        - <span class="font-bold bg-white text-yellow-400 text-xs me-2 px-2.5 py-0.5 rounded-full dark:bg-white dark:text-yellow-600">Sale</span>
                                                    @endif
                                                </span>
                                                <span>
                                                    <span class="font-bold text-black">
                                                        {{ App\Helpers\CustomHelper::formatCurrency($product->getRentalPrice($type)) }}
                                                    </span>
                                                    @if ($product->isRentalOnSale($type))
                                                        <span
                                                            class="line-through text-gray-500 text-base ml-1 font-normal italic">
                                                            {{ App\Helpers\CustomHelper::formatCurrency($product->getRentalPrice($type, false)) }}
                                                        </span>
                                                    @endif
                                                </span>

                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <!-- Retail Product (centered button, no inline styles) -->
                                    <div class="grid grid-cols-2 gap-2">
                                        <a href="{{ route('front.products.details', ['categorySlug' => $category->slug, 'slug' => $product->slug, 'productType' => 'retail']) }}"
                                            class="relative border-0 bg-yellow-400 text-black font-medium flex flex-col items-center px-2 rounded-xl hover:bg-yellow-500 overflow-hidden min-h-[40]">
                                            {{-- @if ($product->isRetailOnSale())
                                                <span
                                                    class="absolute left-[-25px] top-2 w-[100px] h-6 bg-white text-yellow-400 font-bold text-xs flex items-center justify-center"
                                                    style="transform: rotate(-50deg); z-index: 20; box-shadow: 0 4px 6px rgba(0,0,0,0.10); letter-spacing: 0.5px;">
                                                    Sale
                                                </span>
                                            @endif --}}
                                            <span class="z-30">
                                                {{ ucfirst('retail') }}
                                                @if ($product->isRetailOnSale())
                                                    - <span class="font-bold bg-white text-yellow-400 text-xs me-2 px-2.5 py-0.5 rounded-full dark:bg-white dark:text-yellow-600">Sale</span>
                                                @endif
                                            </span>
                                            <span>
                                                <span class="font-bold text-lg text-black">
                                                    {{ App\Helpers\CustomHelper::formatCurrency($product->getRetailPrice(false)) }}
                                                </span>
                                                @if ($product->isRetailOnSale())
                                                    <span
                                                        class="line-through text-gray-500 text-base ml-1 font-normal italic">
                                                        {{ App\Helpers\CustomHelper::formatCurrency($product->getRetailPrice(false)) }}
                                                    </span>
                                                @endif
                                            </span>
                                        </a>
                                    </div>
                                @endif
                                <h6 class="text-[12px] mt-2 text-black text-center ">Select an Option to Learn More or
                                    Reserve Today</h6>
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
