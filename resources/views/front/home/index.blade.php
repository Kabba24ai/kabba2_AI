@extends('front.layouts.app')

@section('title', $title)

@section('content')
    <!-- Hero Section -->
    <section id="banner"
        class="pb-[60px] md:pt-[68px] pt-[79px] lg:pt-[79px] transform transition-all duration-300 ease-in-out">
        <div class="max-w-full mx-auto px-0">
            <div class="flex flex-col items-center ">
                <img src="{{ asset('storage/front/images/banner.jpg') }}"
                    class="w-[100%] lg:w-full h-[180px] lg:h-[500px] 2xl:h-[730px] object-cover object-top ml-auto"
                    alt="Equipment 1">
                <h1 class="text-[30px] md:text-[26px] lg:text-[30px] mt-[20px] mx-[30px] text-center md:text-left ">Call For
                    Live Assistance from a Real Person: <a href="{{ route('front.contact-us.index') }}">(615) 815-6734</a> </h1>
            </div>
        </div>
    </section>

    <section class="pb-[30px]">
    <div class="mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
      <form action="#">
        <div class="relative z-0 w-96 mr-auto ml-auto my-6 group flex shadow-md rounded-lg">
          <input type="text" placeholder="" class="block bg-[#f9fafc] py-3 px-4 w-full rounded-l-lg text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer" required="">
          <label for="name" class="pointer-events-none  z-1 px-6 absolute text-[16px] rounded-l-lg text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-[#000]">
            Search for Products
          </label>
          <button class="border-0 bg-yellow-500 items-center font-medium flex px-6 py-1 leading-4 rounded-r-lg text-[14px] hover:bg-yellow-400  transition-all duration-500 ease-in-out">
            <i class="fa-solid fa-magnifying-glass"></i>
          </button>
        </div>
      </form>
    </div>
  </section>

    <!-- Featured Rentals -->
    <section class="pb-[30px]">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <h2 class="text-[28px] md:text-[2rem] font-bold text-center mb-10  ">Featured Rentals</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">

                @foreach ($category_tree as $category)
                    <div
                        class="border py-[20px] lg:py-[16px] lg:px-[16px] rounded-lg text-center shadow-[0_2px_5px_rgba(0,0,0,0.05)] transition-transform duration-300 hover:translate-y-[-5px]">
                        <a href="{{ route('front.categories.index', $category->slug) }}"
                            class=" transition-all duration-300 ease-in-out hover:text-black">
                            <div class="w-full">
                                <img src="{{ $category->media?->url }}" alt="Skid Steer"
                                    class="mx-auto lg:w-full w-[90%] object-contain rounded-[5px] object-cover h-[auto]" />
                            </div>
                            <div>
                                <h3
                                    class="mt-4 font-bold text-[15px]  transition-all duration-300 ease-in-out hover:text-black underline">
                                    {{ $category->title }}</h3>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="pb-[48px] pt-[48px] bg-[#fafafa]">
        <div
            class="container mx-auto md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] px-[30px] md:px-[.7rem] relative overflow-hidden">
            <h2 class="text-[28px] md:text-[2rem] font-bold text-center mb-[16px] ">About Rent 'n King</h2>
            <!-- about content -->
            <div class="max-w-[800px] text-center mx-auto">
                <p>Locally owned and committed to 1st-tier customer service that encourages long-term, repeat customers.
                    We’re growing fast to serve you better across multiple locations.</p>
            </div>
        </div>
    </section>
@endsection
