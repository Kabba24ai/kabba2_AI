@extends('front.layouts.app')

@section('title', 'Terms & Conditions')

@section('content')

<!-- Page Title Section -->
<section
    class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-white md:border-r-[30px] md:border-r-white bg-gray-50">
    <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-8 md:px-0">
        <div id="breadcrumbs" class="pt-24 pb-5 ">
            <div class="w-full">
                <div class="flex justify-between items-center ">
                    <h1 class="text-2xl md:text-3xl lg:text-4xl tracking-tight leading-[110%] font-bold">
                        Terms & Conditions
                    </h1>

                    <ul class="px-5 py-2 lg:py-3 max-w-full text-sm font-medium items-center inline-flex gap-3 relative">
                        <li class="tracking-normal whitespace-nowrap after:content-['/'] after:pl-1.5">
                            <a href="{{ route('front.home.index') }}" class="opacity-25">Home</a>
                        </li>
                        <li>
                            <a href="javascript:void(0)">
                                Terms & Conditions
                            </a>
                        </li>
                    </ul>

                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-gray-50 py-16">
    <div class="container mx-auto max-w-4xl px-6">


        <div class="prose max-w-none rich-content">
            {!! $settings->content ?? '' !!}
        </div>


    </div>
</section>

@endsection