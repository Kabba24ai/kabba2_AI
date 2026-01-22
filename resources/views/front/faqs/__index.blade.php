@extends('front.layouts.app')

@section('title', $title)

@section('content')
    <!-- Page Title Section -->

    <div class="text-center px-4 py-12 sm:py-20 w-full pt-130 pb-15">
        <div class="max-w-2xl mx-auto space-y-5 px-4 sm:px-6 lg:px-8">
            <!-- Title -->
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                Frequently Asked Questions
            </h1>

            <!-- Subtitle -->
            <p class="text-gray-600 text-base sm:text-lg">
                Find answers to questions about our equipment rentals & services.
            </p>

            <!-- Search Box -->
            <div class="relative max-w-xl mx-auto mt-6">
                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z" />
                </svg>
                </span>
                <input type="text" placeholder="Search for answers..." class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 text-sm sm:text-base" />
            </div>
        </div>
    </div>

    <div class="max-w-3xl mx-auto space-y-4  px-4 sm:px-6 lg:px-8 py-6">

        <!-- Category 1 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Header -->
            <button class="category-toggle w-full flex justify-between items-center p-4 text-left cursor-pointer">
                <div class="flex items-start gap-3">
                    <div class="text-yellow-500 text-2xl">📁</div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Booking & Reservations</h3>
                        <p class="text-gray-600 text-sm">Everything you need to know about making and managing your reservations</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 whitespace-nowrap">2 questions</span>
                    <!-- Category Arrow -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </button>

            <!-- Category Content -->
            <div class="category-content hidden border-t border-gray-100">
                <!-- Question 1 -->
                <div class="px-6 py-4 transition-colors border-b border-gray-100">
                    <button class="faq-toggle flex justify-between items-center w-full text-left font-medium text-gray-900 hover:text-blue-600 cursor-pointer">
                        Can I modify or cancel my reservation?
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div class="faq-content hidden text-gray-700 text-sm leading-relaxed mt-3">
                        <p class="text-gray-700 leading-relaxed prose prose-sm max-w-none">Yes! You can modify or cancel your reservation through your account dashboard.</p>
                        <hr class="my-3 border-gray-200" />

                        <div class="mt-4 mb-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Was this helpful?</span>
                                <div class="flex items-center space-x-2">
                                    <button class="flex items-center px-3 py-1 text-sm text-gray-600 hover:text-green-600 hover:bg-green-50 rounded-md transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-thumbs-up w-4 h-4 mr-1">
                                        <path d="M7 10v12"></path>
                                        <path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2h0a3.13 3.13 0 0 1 3 3.88Z"></path>
                                        </svg>
                                        Yes
                                    </button>
                                    <button class="flex items-center px-3 py-1 text-sm text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-thumbs-down w-4 h-4 mr-1">
                                        <path d="M17 14V2"></path>
                                        <path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22h0a3.13 3.13 0 0 1-3-3.88Z"></path>
                                        </svg>
                                        No
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-3">
                            <p class="flex items-center gap-1 font-medium text-gray-900 text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lightbulb w-4 h-4 text-yellow-500 mr-2"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"></path><path d="M9 18h6"></path><path d="M10 22h4"></path></svg>
                                Related 
                            </p>
                            <a href="#" class="text-blue-600 text-sm hover:underline block mt-1">
                                How do I make a reservation?
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Question 2 -->
                <div class="px-6 py-4 cursor-pointer">
                    <button class="faq-toggle flex justify-between items-center w-full text-left font-medium text-gray-900 hover:text-blue-600 cursor-pointer">
                        How do I make a reservation?
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div class="faq-content hidden text-gray-700 text-sm leading-relaxed mt-3">
                        <p class="text-gray-700 leading-relaxed prose prose-sm max-w-none">Making a reservation is simple! Browse our available properties, select your dates, and click Book Now.</p>
                        <hr class="my-3 border-gray-200" />

                        <div class="mt-4 mb-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Was this helpful?</span>
                                <div class="flex items-center space-x-2"><span class="text-sm text-gray-500">Thanks for your feedback!</span></div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-3">
                            <p class="flex items-center gap-1 font-medium text-gray-900 text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lightbulb w-4 h-4 text-yellow-500 mr-2"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"></path><path d="M9 18h6"></path><path d="M10 22h4"></path></svg>
                                Related 
                            </p>
                            <a href="#" class="text-blue-600 text-sm hover:underline block mt-1">
                                How do I make a reservation?
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Category 2 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden  cursor-pointer">
            <button class="category-toggle w-full flex justify-between items-center p-4 text-left cursor-pointer">
                <div class="flex items-start gap-3">
                    <div class="text-yellow-500 text-2xl">📁</div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Property Information</h3>
                        <p class="text-gray-600 text-sm">Details about our properties and amenities</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 whitespace-nowrap">1 question</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </button>

            <div class="category-content hidden border-t border-gray-100 cursor-pointer">
                <div class="px-6 py-4 ">
                    <button class="faq-toggle flex justify-between items-center w-full text-left font-medium text-gray-900 hover:text-blue-600 cursor-pointer">
                        What amenities are included?
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div class="faq-content hidden text-gray-700 text-sm leading-relaxed  mt-3">
                        <p class="text-gray-700 leading-relaxed prose prose-sm max-w-none">Amenities vary by property but typically include WiFi, kitchen, linens, and towels.</p>
                        <hr class="my-3 border-gray-200" />

                        <div class="mt-4 mb-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Was this helpful?</span>
                                <div class="flex items-center space-x-2">
                                    <button class="flex items-center px-3 py-1 text-sm text-gray-600 hover:text-green-600 hover:bg-green-50 rounded-md transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-thumbs-up w-4 h-4 mr-1">
                                        <path d="M7 10v12"></path>
                                        <path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2h0a3.13 3.13 0 0 1 3 3.88Z"></path>
                                        </svg>
                                        Yes
                                    </button>
                                    <button class="flex items-center px-3 py-1 text-sm text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-thumbs-down w-4 h-4 mr-1">
                                        <path d="M17 14V2"></path>
                                        <path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22h0a3.13 3.13 0 0 1-3-3.88Z"></path>
                                        </svg>
                                        No
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="w-full max-w-4xl bg-gray-100 text-black rounded-lg shadow p-8 sm:p-12 text-center space-y-5 mb-100 mt-100">
            <!-- Heading -->
            <h2 class="text-2xl sm:text-3xl font-bold">Still need help?</h2>
            <p class="text-sm sm:text-lg max-w-2xl mx-auto">
                Can't find what you're looking for? Our support team is here to help you with any questions.
            </p>

            <!-- Buttons -->
            <div class="flex flex-wrap justify-center gap-4">
                <!-- Text Us Button -->
                <a href="#" class="flex items-center gap-2 bg-yellow-400 text-black hover:bg-yellow-500 font-medium px-5 py-2.5 rounded-md shadow-sm transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle w-5 h-5 mr-2"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"></path></svg>
                    Click to Text Us
                </a>

                <!-- Call Us Button -->
                <a href="tel:+1234567890" class="flex items-center gap-2 bg-yellow-400 text-black hover:bg-yellow-500 font-medium px-5 py-2.5 rounded-md shadow-sm transition-all">
                    Call Us: (123) 456-7890
                </a>
            </div>
        </div>

    </div> 
    


@endsection


@push('js')

<!-- Simple JS -->
  <script>
    // Toggle Category
    document.querySelectorAll('.category-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        const content = btn.nextElementSibling;
        const icon = btn.querySelector('svg');
        content.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
      });
    });

    // Toggle FAQ
    document.querySelectorAll('.faq-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        const content = btn.nextElementSibling;
        const icon = btn.querySelector('svg');
        content.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
      });
    });
    
  </script>

@endpush
