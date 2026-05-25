@extends('front.layouts.app')

@section('title', $title)

@push('meta')
    @if (!empty($branding['home_seo_title']))
        <meta name="title" content="{{ $branding['home_seo_title'] }}">
        <meta property="og:title" content="{{ $branding['home_seo_title'] }}">
        <meta name="twitter:title" content="{{ $branding['home_seo_title'] }}">
    @endif
    @if (!empty($branding['home_seo_description']))
         <meta name="description" content="{{ $branding['home_seo_description'] }}">
        <meta property="og:description" content="{{ $branding['home_seo_description'] }}">
        <meta name="twitter:description" content="{{ $branding['home_seo_description'] }}">
    @endif
@endpush

@section('content')

    @php
        $homeImage = \App\Helpers\ConfigurationHelper::getHomePageImage();
    @endphp

    <!-- Hero Section -->
    <section id="banner" class="pb-3 pt-16 transform transition-all duration-300 ease-in-out">
        <div class="max-w-full mx-auto px-0">
            <div class="flex flex-col items-center ">
                <img src="{{ $homeImage ? $homeImage : asset('storage/front/images/banner.jpg') }}" {{-- class="w-[100%] lg:w-full banner object-cover object-top ml-auto" --}}
                    class="block w-[100%] lg:w-full banner ml-auto" alt="Equipment Banner">
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
                        {{ $branding['top_text'] ?? 'Call For Live Assistance from a Real Person:' }}

                        @php

                            $top_phone = $branding['top_phone'] ?? '(xxx) xxx-xxxx';

                        @endphp
                        <a href="{{ $top_phone }}" class="text-yellow-600 hover:underline ml-1">
                            {{ $top_phone }}
                        </a>
                    </h3>

                    <form action="#" class="w-full md:w-auto relative">
                        <div class="relative z-0 w-full md:w-96 mr-auto ml-auto  group flex shadow-md rounded-lg">
                            <input type="text" placeholder="Search for Products"
                                class="block bg-light py-3 px-4 w-full rounded-l-lg text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer"
                                required id="search-input" autocomplete="off" />

                            <button type="submit"
                                class="border-0 bg-yellow-500 items-center font-medium flex px-6 py-1 leading-4 rounded-r-lg text-sm hover:bg-yellow-400 transition-all duration-500 ease-in-out">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </div>

                        <!-- Dropdown for autocomplete -->
                        <div id="search-results"
                            class="absolute left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-72 overflow-y-auto z-50 text-left">
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
                            <div class="w-full relative group">
                                <img src="{{ $category->image_url }}" alt="{{ $category->title }}"
                                    class="mx-auto category-product rounded-md object-cover h-auto transition-opacity duration-500 ease-in-out group-hover:opacity-0"
                                    loading="lazy" />

                                <!-- Hover image (first gallery image) -->
                                <img src="{{ $category->hover_image_url }}" alt="{{ $category->title }} hover"
                                    class="absolute inset-0 w-full h-full object-cover rounded-md opacity-0 transition-opacity duration-500 ease-in-out group-hover:opacity-100"
                                    loading="lazy" />
                            </div>
                            <div>
                                <h3
                                    class="mt-4 font-bold text-base transition-all duration-300 ease-in-out hover:text-black underline">
                                    {{ $category->title }}
                                </h3>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="pb-20 pt-20 bg-gray-2">
        <div class="container relative overflow-hidden">
            <h2 class="section-title"> {{ $branding['bottom_title'] ?? 'About the company' }}</h2>
            <!-- about content -->
            <div class="about-content text-center mx-auto">
                <p>

                    {{ $branding['bottom_text'] ??
                        'Locally owned and committed to 1st-tier customer service that encourages long-term, repeat customers.
                                    We’re growing fast to serve you better across multiple locations.' }}


                </p>
            </div>
        </div>
    </section>
@endsection

@push('js')
    <script>
        // Custom JavaScript for this page
        document.addEventListener('DOMContentLoaded', function() {
            @if (!empty($cart_data))
                window.CartStorage.setCart(@json($cart_data));
            @endif
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const input = document.getElementById("search-input");
            const results = document.getElementById("search-results");

            input.addEventListener("keyup", async function() {
                const query = this.value.trim();

                if (query.length < 2) {
                    results.classList.add("hidden");
                    return;
                }

                try {
                    const response = await fetch(
                        "{{ route('front.home.search.autocomplete') }}?query=" +
                        encodeURIComponent(query));
                    const data = await response.json();

                    let html = "";

                    // PRODUCTS
                    if (data.products.length > 0) {
                        html +=
                            `<div class='px-3 py-2 font-semibold text-gray-600 border-b bg-gray-50'>Products</div>`;
                        data.products.forEach(product => {
                            const categories = product.categories.length ?
                                product.categories.map(c => c.title).join(", ") :
                                "Uncategorized";

                            // Use first category link with keyword
                            const link = product.category_links.length ?
                                product.category_links[0] :
                                `/products/${product.id}?search=${encodeURIComponent(product.product_name)}`;

                            html += `
                        <a href="${link}"
                           class="block px-4 py-3 hover:bg-yellow-100 transition rounded-md">
                            <div class="font-medium text-gray-800">${product.product_name}</div>
                            <div class="text-xs text-gray-500 mt-1">${categories}</div>
                        </a>
                    `;
                        });
                    }


                    if (html === "") {
                        html = "<div class='px-4 py-2 text-gray-500'>No results found</div>";
                    }

                    results.innerHTML = html;
                    results.classList.remove("hidden");

                    // Add click listener to autocomplete items
                    results.querySelectorAll(".autocomplete-item").forEach(item => {
                        item.addEventListener("click", () => {
                            // Clear search input
                            input.value = "";
                            // Hide dropdown
                            results.classList.add("hidden");
                        });
                    });

                } catch (err) {
                    console.error("Autocomplete error:", err);
                }
            });

            // Hide dropdown when clicking outside
            document.addEventListener("click", (e) => {
                if (!results.contains(e.target) && e.target !== input) {
                    results.classList.add("hidden");
                }
            });

            // Optional: Clear input & dropdown on page load (prevents stale results after back button)
            input.value = "";
            results.classList.add("hidden");
        });
    </script>
@endpush
