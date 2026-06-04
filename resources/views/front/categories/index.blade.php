@extends('front.layouts.app')


@section('title', $title)

@push('meta')
    @if (!empty($category->seo_title))
        <meta name="title" content="{{ $category->seo_title }}">
        <meta property="og:title" content="{{ $category->seo_title }}">
        <meta name="twitter:title" content="{{ $category->seo_title }}">
    @endif
    @if (!empty($category->seo_description))
        <meta name="description" content="{{ $category->seo_description }}">
        <meta property="og:description" content="{{ $category->seo_description }}">
        <meta name="twitter:description" content="{{ $category->seo_description }}">
    @endif
@endpush

@section('content')
    <!-- Page Title Section -->
    <section
        class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-white md:border-r-[30px] md:border-r-white bg-gray-100">
        <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-[30px] md:px-0">
            <div id="breadcrumbs" class="pt-[100px] pb-[20px] ">
                <div class="w-full">
                    <div class="flex flex-col lg:flex-row justify-between items-center ">
                        <h1 class="text-3xl lg:text-4xl tracking-[-2px] leading-[110%] font-bold">{{ $category->title }}</h1>
                        <ul class="px-5 py-4 mt-4 lg:mt-0 text-sm font-medium max-w-full flex flex-wrap items-center gap-3">
                            <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
                                <a href="{{ route('front.home.index') }}" class="opacity-75">Home</a>
                            </li>
                            @if ($category->isParentCategory())
                                <li>
                                    <a href="javascript:void(0)" class="cursor-not-allowed">{{ $category->title }}</a>
                                </li>
                            @else
                                <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
                                    @php
                                        $parentCategory = $category->parentCategory;
                                    @endphp
                                    <a href="{{ route('front.categories.index', ['slug' => $parentCategory ? $parentCategory->slug : $category->slug]) }}"
                                        class="opacity-75">
                                        {{ $parentCategory ? $parentCategory->title : $category->title }}
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" class="cursor-not-allowed">{{ $category->title }}</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories & Products Listing -->
    <section class="pb-[60px] relative overflow-hidden">
        <div class="rent-bg">
            <div class="rent-bg-inner">
                <div></div>
            </div>
        </div>
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($items as $item)
                    @if ($item->item_type === 'subcategory')
                        <div class="py-[20px] lg:py-[30px] rounded-lg text-center">
                            <div class="transition-all duration-300 ease-in-out hover:text-black">
                                <div class="lg:h-80 w-full flex items-center justify-center relative rounded-lg overflow-hidden group transition-all duration-500">
                                    <div class="w-full h-full flex items-center justify-center bg-white transition-all duration-500 rounded-lg">
                                        <img src="{{ $item->image_url }}" alt="{{ $item->title }}"
                                            class="w-[90%] h-full object-contain transition-opacity duration-500 ease-in-out group-hover:opacity-0"
                                            loading="lazy" />
                                        <img src="{{ $item->hover_image_url ?? $item->image_url }}" alt="{{ $item->title }} hover"
                                            class="absolute inset-0 w-full h-full object-contain opacity-0 transition-opacity duration-500 ease-in-out group-hover:opacity-100"
                                            loading="lazy" />
                                    </div>
                                </div>
                                <div>
                                    <h3 class="mt-4 font-bold text-black md:text-xl p-2 bg-gray-100 mb-4">
                                        {{ $item->title }}
                                    </h3>
                                        <div class="grid grid-cols-2 gap-2">
                                            @php
                                                // If current category is a parent, use child route, else use main route
                                                $parentCategorySlug = $item->parentCategory->slug ?? null;

                                                $categoryRoute = $parentCategorySlug ? route('front.categories.sub-category', ['slug' => $parentCategorySlug, 'childCategorySlug' => $item->slug]) : route('front.categories.index', ['slug' => $item->slug]);
                                            @endphp
                                            <a href="{{ $categoryRoute }}"
                                                class="relative border-0 bg-yellow-400 text-black font-medium flex flex-col items-center px-2 rounded-xl hover:bg-yellow-500 overflow-hidden min-h-[40] col-span-2">
                                                Click to View Multiple Options
                                            </a>
                                        </div>
                                    <h6 class="text-[12px] mt-2 text-black text-center ">
                                        Select an Option to Learn More or Reserve Today
                                    </h6>
                                </div>
                            </div>
                        </div>
                    @elseif ($item->item_type === 'product')
                        <div class="py-[20px] lg:py-[30px] rounded-lg text-center">
                            <div class="transition-all duration-300 ease-in-out hover:text-black">
                                <div class="lg:h-80 w-full flex items-center justify-center relative rounded-lg overflow-hidden group transition-all duration-500">
                                    <div class="w-full h-full flex items-center justify-center bg-white transition-all duration-500 rounded-lg">
                                        <img src="{{ $item->image_url }}" alt="{{ $item->product_name }}"
                                            class="w-[90%] h-full object-contain transition-opacity duration-500 ease-in-out group-hover:opacity-0"
                                            loading="lazy" />
                                        <img src="{{ $item->hover_image_url }}" alt="{{ $item->product_name }} hover"
                                            class="absolute inset-0 w-full h-full object-contain opacity-0 transition-opacity duration-500 ease-in-out group-hover:opacity-100"
                                            loading="lazy" />
                                    </div>
                                </div>
                                <div>
                                    <h3 class="mt-4 font-bold text-black md:text-xl p-2 bg-gray-100 mb-4">
                                        {{ $item->product_name }}
                                    </h3>
                                    @if ($item->product_type == 'Rental')
                                        <div class="grid grid-cols-2 gap-2">
                                            @foreach (['daily', 'weekend', 'weekly', 'monthly'] as $type)
                                                <a href="{{ route('front.products.index', ['slug' => $item->slug, 'productVariant' => $type]) }}"
                                                    class="relative border-0 bg-yellow-400 text-black font-medium flex flex-col items-center px-2 rounded-xl hover:bg-yellow-500 overflow-hidden min-h-[40]">
                                                    <span class="z-30">
                                                        {{ ucfirst(($type === 'weekend') ? 'Weekend Spcl.' : $type) }}
                                                        @if ($item->isRentalOnSale($type))
                                                            - <span class="font-bold bg-white text-yellow-400 text-xs me-2 px-2.5 py-0.5 rounded-full dark:bg-white dark:text-yellow-600">Sale</span>
                                                        @endif
                                                    </span>
                                                    <span>
                                                        <span class="font-normal text-black">
                                                            {{ App\Helpers\CustomHelper::formatCurrency($item->getRentalPrice($type)) }}
                                                        </span>
                                                        @if ($item->isRentalOnSale($type))
                                                            <span class="line-through text-gray-500 text-base ml-1 font-normal italic">
                                                                {{ App\Helpers\CustomHelper::formatCurrency($item->getRentalPrice($type, false)) }}
                                                            </span>
                                                        @endif
                                                    </span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="grid grid-cols-2 gap-2">
                                            <a href="{{ route('front.products.index', ['slug' => $item->slug, 'productVariant' => 'retail']) }}"
                                                class="relative border-0 bg-yellow-400 text-black font-medium flex flex-col items-center px-2 rounded-xl hover:bg-yellow-500 overflow-hidden min-h-[40]">
                                                <span class="z-30">
                                                    {{ ucfirst('Buy Now') }}
                                                    @if ($item->isRetailOnSale())
                                                        - <span class="font-bold bg-white text-yellow-400 text-xs me-2 px-2.5 py-0.5 rounded-full dark:bg-white dark:text-yellow-600">Sale</span>
                                                    @endif
                                                </span>
                                                <span>
                                                    <span class="font-normal text-lg text-black">
                                                        {{ App\Helpers\CustomHelper::formatCurrency($item->getRetailPrice(true)) }}
                                                    </span>
                                                    @if ($item->isRetailOnSale())
                                                        <span class="line-through text-gray-500 text-base ml-1 font-normal italic">
                                                            {{ App\Helpers\CustomHelper::formatCurrency($item->getRetailPrice(false)) }}
                                                        </span>
                                                    @endif
                                                </span>
                                            </a>
                                        </div>
                                    @endif
                                    <h6 class="text-[12px] mt-2 text-black text-center ">
                                        Select an Option to Learn More or Reserve Today
                                    </h6>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    <!-- Categories Listing details -->
    <section class="py-[20px]">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <div class="flex flex-col rich-content">
                {!! $category->content !!}
            </div>
        </div>
    </section>

    {{-- <section class="pb-[60px]">
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
    </section> --}}
@endsection
