@extends('front.layouts.app')

@section('title', $title)

@section('content')
    <!-- Hero Section -->
    <section id="banner" class="pb-3 md:pt-17 pt-17 lg:pt-20 transform transition-all duration-300 ease-in-out">
        <div class="max-w-full mx-auto px-0">
            <div class="flex flex-col items-center ">
                <img src="{{ asset('storage/front/images/banner.jpg') }}"
                    class="w-[100%] lg:w-full banner object-cover object-top ml-auto " alt="Equipment 1">
            </div>
        </div>
    </section>

    <!-- Featured Rentals -->
    <section class="pb-20">
        <div class="container">
            <div class="text-center my-6">
                <h2 class="section-title">Featured Rentals</h2>
                <div class="flex flex-col md:flex-row items-center justify-center gap-4 md:gap-8">
                    <!-- Heading with phone link -->
                    <h3 class="text-base md:text-lg font-semibold text-gray-700">
                        Call For Live Assistance from a Real Person:
                        <a href="{{ route('front.contact-us.index') }}" class="text-yellow-600 hover:underline ml-1">(615)
                            815-6734</a>
                    </h3>

                    <!-- Search Form -->
                    <form action="#" class="w-full md:w-auto">
                        <div class="relative z-0 w-full md:w-96 mr-auto ml-auto my-2 group flex shadow-md rounded-lg">
                            <input type="text" placeholder=""
                                class="block bg-light py-3 px-4 w-full rounded-l-lg text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer"
                                required="" id="name" />
                            <label for="name"
                                class="pointer-events-none z-1 px-6 absolute text-base rounded-l-lg text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black">
                                Search for Products
                            </label>
                            <button
                                class="border-0 bg-yellow-500 items-center font-medium flex px-6 py-1 leading-4 rounded-r-lg text-sm hover:bg-yellow-400 transition-all duration-500 ease-in-out">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                @foreach ($category_tree as $category)
                    <div
                        class="border py-5 px-5 md:px-1 lg:py-4 lg:px-4 rounded-lg text-center shadow-[0_2px_5px_rgba(0,0,0,0.05)] transition-transform duration-300 hover:-translate-y-1">
                        <a href="{{ route('front.categories.index', $category->slug) }}"
                            class=" transition-all duration-300 ease-in-out hover:text-black">
                            <div class="w-full">
                                <img src="{{ $category->media?->url }}" alt="Skid Steer"
                                    class="mx-auto category-product rounded-md object-cover h-[auto]" />
                            </div>
                            <div>
                                <h3
                                    class="mt-4 font-bold text-base transition-all duration-300 ease-in-out hover:text-black underline">
                                    {{ $category->title }}</h3>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="pb-20  bg-gray-2">
        <div class="container relative overflow-hidden">
            <h2 class="section-title">About Rent 'n King</h2>
            <!-- about content -->
            <div class="about-content text-center mx-auto">
                <p>Locally owned and committed to 1st-tier customer service that encourages long-term, repeat customers.
                    We’re growing fast to serve you better across multiple locations.</p>
            </div>
        </div>
    </section>
@endsection

@push('js')
    <script>
        // Custom JavaScript for this page
        document.addEventListener('DOMContentLoaded', function () {
            @if(!empty($cart_data))
                window.CartStorage.setCart(@json($cart_data));
            @endif
        });

    </script>
@endpush
