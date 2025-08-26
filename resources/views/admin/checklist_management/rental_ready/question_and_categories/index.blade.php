@extends('admin.layouts.app')

@section('title', 'Customers')

@push('css')
@endpush

@section('content')

    @include('flash::message')


    {{-- Header --}}
    <div class=" mb-6 gap-4">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Question & Categories Mgt</h3>
        <p class="text-gray-600">Manage inspection questions, answers, and categories</p>
    </div>


    <div>
        <!-- Product Performance -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]" >
            <div x-data="{selected: 'daily'}">
                <div class="flex w-full items-center gap-0.5 rounded-lg bg-gray-100 p-0.5" >
                    <button
                        @click="selected = 'daily'"
                        :class="selected === 'daily' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
                        class="text-theme-sm rounded-md px-3 py-2 font-medium hover:text-gray-900 dark:hover:text-white"
                    >
                        Questions (14)
                    </button>
                    <button
                        @click="selected = 'online'"
                        :class="selected === 'online' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
                        class="text-theme-sm  rounded-md px-3 py-2 font-medium hover:text-gray-900 dark:hover:text-white"
                    >
                        Categories (14)
                    </button>
                </div>
                <!-- Tab Panels -->
                <div class="mt-4">
                    <!-- Questions -->
                    <div x-show="selected === 'daily'" class="space-y-4">
                        <div class="max-w-6xl mx-auto p-6 space-y-6">
                            <!-- Header -->
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-900">Questions</h2>
                                    <p class="text-gray-600">Create and manage inspection questions and answer options</p>
                                </div>
                                <a href="javascript:void(0)" id="openQuestionModal"
                                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 mt-3">
                                    + New Question
                                </a>
                            </div>

                            <!-- Parent wrapper -->
                            <div x-data="{ globalShowAnswers: false }" class="space-y-6">

                                <!-- Top filters and toggle button -->
                                <div class="bg-white border border-gray-200 rounded-xl p-6 space-y-4 shadow-sm">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <!-- Search -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Search Questions</label>
                                            <input type="text" placeholder="Search by question name..."
                                            class="w-full px-4 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                        </div>

                                        <!-- Filter -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Category</label>
                                            <select class="w-full text-sm px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option>All Categories</option>
                                            <option>Safety</option>
                                            <option>Engine</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Answer Visibility</label>
                                            <button
                                                @click="globalShowAnswers = !globalShowAnswers"
                                                class="w-full font-medium px-4 py-2 text-sm rounded-md flex items-center justify-center gap-2 hover:bg-gray-50 transition"
                                                :class="globalShowAnswers 
                                                ? 'border border-gray-300 text-gray-600' 
                                                : 'border border-blue-300 text-blue-600'"
                                            >
                                            <!-- Icons -->
                                            <template x-if="!globalShowAnswers">
                                                <!-- Eye Icon (Show) -->
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </template>

                                            <template x-if="globalShowAnswers">
                                                <!-- Eye-Off Icon (Hide) -->
                                                <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.042 10.042 0 013.03-4.362M6.873 6.876A9.953 9.953 0 0112 5c4.477 0 8.267 2.943 9.541 7a9.966 9.966 0 01-1.249 2.527M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M3 3l18 18" />
                                                </svg>
                                            </template>
                                                <!-- Text -->
                                                <span x-text="globalShowAnswers ? 'Hide All Answers' : 'Show All Answers'"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- QUESTION CARD START -->
                                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm"
                                    x-data="{ showAnswers: false }"
                                    x-init="$watch('globalShowAnswers', value => showAnswers = value)">

                                    <!-- Header -->
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <div class="flex items-center gap-2">
                                            <h3 class="text-base font-semibold text-gray-900">Safety Equipment Present</h3>
                                            <span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full font-medium">Required</span>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1">Category: Safety</p>
                                        </div>

                                        <div class="flex items-center gap-4 mt-3 md:mt-0 text-gray-600 text-sm">
                                            <button @click="showAnswers = !showAnswers" class="text-blue-600 hover:underline flex items-center gap-1">
                                                <!-- Right-side Arrow Icon -->
                                                <svg class="w-4 h-4 transform transition-transform duration-200"
                                                    :class="showAnswers ? 'rotate-90' : 'rotate-0'"
                                                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>

                                                <!-- Text -->
                                                <span x-text="showAnswers ? 'Hide Questions' : 'Show Questions (5)'"></span>
                                            </button>

                                            <!-- <button @click="showAnswers = !showAnswers" class="text-blue-600 hover:underline">
                                                <span x-text="showAnswers ? 'Hide Answers' : 'Show Answers (5)'"></span>
                                            </button> -->
                                            <!-- Edit -->
                                            <button class="text-green-600 hover:text-green-800" title="Edit">
                                                <x-heroicon-o-pencil class="w-5 h-5" />
                                            </button>
                                            <!-- Delete -->
                                             <button class="text-red-600 hover:text-red-800" title="Delete">
                                                <x-heroicon-o-trash class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Answers -->
                                    <div x-show="showAnswers" x-transition>
                                        <div class="border-t border-gray-300 mt-5 mb-5"></div>
                                        <h4 class="text-sm font-semibold text-gray-800">Answer Options:</h4>
                                        <div class="grid sm:grid-cols-2 gap-3">
                                            <!-- Option 1 -->
                                            <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                                <div class="flex items-center gap-3">
                                                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">1</span>
                                                    <span class="flex items-center gap-1 text-black text-sm font-medium"><x-heroicon-o-check class="w-5 h-5 text-green-700" /> All Present & Functional</span>
                                                </div>
                                                <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">Rental Ready</span>
                                            </div>

                                            <!-- Option 2 -->
                                            <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                                <div class="flex items-center gap-3">
                                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">2</span>
                                                    <span class="flex items-center gap-1   text-sm font-medium"><x-heroicon-o-check class="w-5 h-5 text-green-700" /> Present but Needs Cleaning</span>
                                                </div>
                                                <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">Rental Ready</span>
                                            </div>

                                            <!-- Option 3 -->
                                            <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                                <div class="flex items-center gap-3">
                                                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">3</span>
                                                    <span class="flex items-center gap-1 text-sm font-medium text-black">
                                                        <x-heroicon-o-clock class="w-5 h-5  text-yellow-700" />
                                                        Missing Non-Critical Items
                                                    </span>
                                                </div>

                                                <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full">Maint. Hold</span>
                                            </div>


                                            <!-- Option 4 -->
                                            <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                                <div class="flex items-center gap-3">
                                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">4</span>
                                                    <span class="flex items-center gap-1 text-sm font-medium text-black">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                                            viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a1.75 1.75 0 001.51 2.62h17.34a1.75 1.75 0 001.51-2.62L13.71 3.86a1.75 1.75 0 00-3.42 0z" />
                                                        </svg>
                                                         Missing Critical Items
                                                    </span>
                                                </div>
                                                <span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full">Damaged</span>
                                            </div>

                                            <!-- Option 5 -->
                                            <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg ">
                                                <div class="flex items-center gap-3">
                                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">5</span>
                                                    <span class="flex items-center gap-1 text-sm font-medium text-black">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                                            viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a1.75 1.75 0 001.51 2.62h17.34a1.75 1.75 0 001.51-2.62L13.71 3.86a1.75 1.75 0 00-3.42 0z" />
                                                        </svg>
                                                         Equipment Damaged/Non-Functional
                                                    </span>
                                                </div>
                                                <span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full">Damaged</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- QUESTION CARD END -->

                                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm"
                                    x-data="{ showAnswers: false }"
                                    x-init="$watch('globalShowAnswers', value => showAnswers = value)">

                                    <!-- Header -->
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <div class="flex items-center gap-2">
                                            <h3 class="text-base font-semibold text-gray-900">Warning Labels Visible</h3>
                                            <span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full font-medium">Required</span>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1">Category: Safety</p>
                                        </div>

                                        <div class="flex items-center gap-4 mt-3 md:mt-0 text-gray-600 text-sm">
                                            <button @click="showAnswers = !showAnswers" class="text-blue-600 hover:underline flex items-center gap-1">
                                                <!-- Right-side Arrow Icon -->
                                                <svg class="w-4 h-4 transform transition-transform duration-200"
                                                    :class="showAnswers ? 'rotate-90' : 'rotate-0'"
                                                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>

                                                <!-- Text -->
                                                <span x-text="showAnswers ? 'Hide Questions' : 'Show Questions (5)'"></span>
                                            </button>
                                            <!-- <button @click="showAnswers = !showAnswers" class="text-blue-600 hover:underline">
                                                <span x-text="showAnswers ? 'Hide Answers' : 'Show Answers (4)'"></span>
                                            </button> -->
                                            <!-- Edit -->
                                            <button class="text-green-600 hover:text-green-800" title="Edit">
                                                <x-heroicon-o-pencil class="w-5 h-5" />
                                            </button>
                                            <!-- Delete -->
                                            <button class="text-red-600 hover:text-red-800" title="Delete">
                                                <x-heroicon-o-trash class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Answers -->
                                    <div x-show="showAnswers" x-transition>
                                        <div class="border-t border-gray-300 mt-5 mb-5"></div>
                                        <h4 class="text-sm font-semibold text-gray-800">Answer Options:</h4>
                                        <div class="grid sm:grid-cols-2 gap-3">
                                            <!-- Option 1 -->
                                            <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                                <div class="flex items-center gap-3">
                                                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">1</span>
                                                    <span class="flex items-center gap-1 text-black text-sm font-medium"><x-heroicon-o-check class="w-5 h-5 text-green-700" /> All Labels Clear & Visible</span>
                                                </div>
                                                <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">Rental Ready</span>
                                            </div>

                                            <!-- Option 2 -->
                                            <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                                <div class="flex items-center gap-3">
                                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">2</span>
                                                    <span class="flex items-center gap-1   text-sm font-medium"><x-heroicon-o-check class="w-5 h-5 text-green-700" /> Labels Present but Faded</span>
                                                </div>
                                                <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">Rental Ready</span>
                                            </div>

                                            <!-- Option 3 -->
                                            <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                                <div class="flex items-center gap-3">
                                                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">3</span>
                                                    <span class="flex items-center gap-1 text-sm font-medium text-black">
                                                        <x-heroicon-o-clock class="w-5 h-5  text-yellow-700" />
                                                        Some Labels Missing
                                                    </span>
                                                </div>

                                                <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full">Maint. Hold</span>
                                            </div>


                                            <!-- Option 4 -->
                                            <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                                <div class="flex items-center gap-3">
                                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">4</span>
                                                    <span class="flex items-center gap-1 text-sm font-medium text-black">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                                            viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a1.75 1.75 0 001.51 2.62h17.34a1.75 1.75 0 001.51-2.62L13.71 3.86a1.75 1.75 0 00-3.42 0z" />
                                                        </svg>
                                                        Critical Labels Missing
                                                    </span>
                                                </div>
                                                <span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full">Damaged</span>
                                            </div>

                                        </div>
                                 
                                    </div>
                                </div>
                                <!-- QUESTION CARD END -->

                            </div>

                        </div>

                    </div>

                    <!-- Categories  -->
                    <div x-show="selected === 'online'" class="space-y-4">
                        <div class="max-w-6xl mx-auto p-6 space-y-6">
                            <!-- Header -->
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-800">Categories</h2>
                                    <p class="text-sm text-gray-500">Organize questions into logical categories</p>
                                </div>
                                <a href="javascript:void(0)" id="openAddressModal"
                                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 mt-3">
                                    + New Category
                                </a>
                            </div>

                            <!-- Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <!-- Category Card -->
                                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h3.6a1 1 0 01.7.3l1.4 1.4a1 1 0 00.7.3H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                            </svg>
                                        <div>
                                            <h3 class="text-base font-semibold text-gray-800">Safety</h3>
                                            <p class="text-sm text-gray-600">2 questions</p>
                                        </div>
                                        </div>
                                        <div class="flex gap-3 text-gray-500">
                                            <!-- Edit -->
                                            <button class="text-green-600 hover:text-green-800" title="Edit">
                                                <x-heroicon-o-pencil class="w-5 h-5" />
                                            </button>
                                            <!-- Delete -->
                                             <button class="text-red-600 hover:text-red-800" title="Delete">
                                                <x-heroicon-o-trash class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-sm text-gray-500 mt-1">Safety-related inspection items</p>
                                    </div>
                                </div>

                                <!-- Duplicate card for more categories -->
                                
                                 <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h3.6a1 1 0 01.7.3l1.4 1.4a1 1 0 00.7.3H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                            </svg>
                                        <div>
                                            <h3 class="text-base font-semibold text-gray-800">Engine</h3>
                                            <p class="text-sm text-gray-600">2 questions</p>
                                        </div>
                                        </div>
                                        <div class="flex gap-3 text-gray-500">
                                            <!-- Edit -->
                                            <button class="text-green-600 hover:text-green-800" title="Edit">
                                                <x-heroicon-o-pencil class="w-5 h-5" />
                                            </button>
                                            <!-- Delete -->
                                             <button class="text-red-600 hover:text-red-800" title="Delete">
                                                <x-heroicon-o-trash class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-sm text-gray-500 mt-1">Engine and motor inspection items</p>
                                    </div>
                                </div>

                                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h3.6a1 1 0 01.7.3l1.4 1.4a1 1 0 00.7.3H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                            </svg>
                                        <div>
                                            <h3 class="text-base font-semibold text-gray-800">Hydraulics</h3>
                                            <p class="text-sm text-gray-600">2 questions</p>
                                        </div>
                                        </div>
                                        <div class="flex gap-3 text-gray-500">
                                            <!-- Edit -->
                                            <button class="text-green-600 hover:text-green-800" title="Edit">
                                                <x-heroicon-o-pencil class="w-5 h-5" />
                                            </button>
                                            <!-- Delete -->
                                             <button class="text-red-600 hover:text-red-800" title="Delete">
                                                <x-heroicon-o-trash class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-sm text-gray-500 mt-1">Hydraulic system inspection items</p>
                                    </div>
                                </div>

                                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h3.6a1 1 0 01.7.3l1.4 1.4a1 1 0 00.7.3H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                            </svg>
                                        <div>
                                            <h3 class="text-base font-semibold text-gray-800">Electrical</h3>
                                            <p class="text-sm text-gray-600">2 questions</p>
                                        </div>
                                        </div>
                                        <div class="flex gap-3 text-gray-500">
                                            <!-- Edit -->
                                            <button class="text-green-600 hover:text-green-800" title="Edit">
                                                <x-heroicon-o-pencil class="w-5 h-5" />
                                            </button>
                                            <!-- Delete -->
                                             <button class="text-red-600 hover:text-red-800" title="Delete">
                                                <x-heroicon-o-trash class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-sm text-gray-500 mt-1">Electrical system inspection items</p>
                                    </div>
                                </div>

                                 <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h3.6a1 1 0 01.7.3l1.4 1.4a1 1 0 00.7.3H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                            </svg>
                                        <div>
                                            <h3 class="text-base font-semibold text-gray-800">Fuel</h3>
                                            <p class="text-sm text-gray-600">2 questions</p>
                                        </div>
                                        </div>
                                        <div class="flex gap-3 text-gray-500">
                                            <!-- Edit -->
                                            <button class="text-green-600 hover:text-green-800" title="Edit">
                                                <x-heroicon-o-pencil class="w-5 h-5" />
                                            </button>
                                            <!-- Delete -->
                                             <button class="text-red-600 hover:text-red-800" title="Delete">
                                                <x-heroicon-o-trash class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-sm text-gray-500 mt-1">Fuel system inspection items</p>
                                    </div>
                                </div>

                                 <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h3.6a1 1 0 01.7.3l1.4 1.4a1 1 0 00.7.3H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                            </svg>
                                        <div>
                                            <h3 class="text-base font-semibold text-gray-800">Tracks</h3>
                                            <p class="text-sm text-gray-600">2 questions</p>
                                        </div>
                                        </div>
                                        <div class="flex gap-3 text-gray-500">
                                            <!-- Edit -->
                                            <button class="text-green-600 hover:text-green-800" title="Edit">
                                                <x-heroicon-o-pencil class="w-5 h-5" />
                                            </button>
                                            <!-- Delete -->
                                             <button class="text-red-600 hover:text-red-800" title="Delete">
                                                <x-heroicon-o-trash class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-sm text-gray-500 mt-1">Track and undercarriage inspection items</p>
                                    </div>
                                </div>

                                 <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h3.6a1 1 0 01.7.3l1.4 1.4a1 1 0 00.7.3H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                            </svg>
                                        <div>
                                            <h3 class="text-base font-semibold text-gray-800">Attachments</h3>
                                            <p class="text-sm text-gray-600">2 questions</p>
                                        </div>
                                        </div>
                                        <div class="flex gap-3 text-gray-500">
                                            <!-- Edit -->
                                            <button class="text-green-600 hover:text-green-800" title="Edit">
                                                <x-heroicon-o-pencil class="w-5 h-5" />
                                            </button>
                                            <!-- Delete -->
                                             <button class="text-red-600 hover:text-red-800" title="Delete">
                                                <x-heroicon-o-trash class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-sm text-gray-500 mt-1">Attachment and implement inspection items</p>
                                    </div>
                                </div>

                                 <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h3.6a1 1 0 01.7.3l1.4 1.4a1 1 0 00.7.3H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                            </svg>
                                        <div>
                                            <h3 class="text-base font-semibold text-gray-800">Power</h3>
                                            <p class="text-sm text-gray-600">2 questions</p>
                                        </div>
                                        </div>
                                        <div class="flex gap-3 text-gray-500">
                                            <!-- Edit -->
                                            <button class="text-green-600 hover:text-green-800" title="Edit">
                                                <x-heroicon-o-pencil class="w-5 h-5" />
                                            </button>
                                            <!-- Delete -->
                                             <button class="text-red-600 hover:text-red-800" title="Delete">
                                                <x-heroicon-o-trash class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-sm text-gray-500 mt-1">Power generation and output inspection items</p>
                                    </div>
                                </div>

                                 <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h3.6a1 1 0 01.7.3l1.4 1.4a1 1 0 00.7.3H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                            </svg>
                                        <div>
                                            <h3 class="text-base font-semibold text-gray-800">General</h3>
                                            <p class="text-sm text-gray-600">2 questions</p>
                                        </div>
                                        </div>
                                        <div class="flex gap-3 text-gray-500">
                                            <!-- Edit -->
                                            <button class="text-green-600 hover:text-green-800" title="Edit">
                                                <x-heroicon-o-pencil class="w-5 h-5" />
                                            </button>
                                            <!-- Delete -->
                                             <button class="text-red-600 hover:text-red-800" title="Delete">
                                                <x-heroicon-o-trash class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-sm text-gray-500 mt-1">General condition and appearance items</p>
                                    </div>
                                </div>


                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
        <!-- Product Performance End -->

    </div>

    <div x-data="{ showQuestionModal: false }" x-ref="questionRoot" x-init="window.addEventListener('open-question-modal', () => showQuestionModal = true)">
        <!-- Modal -->  
        <div
            x-show="showQuestionModal"
            x-transition
            x-cloak
            class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
            <div class="modal-scrollable w-full mx-auto">
                <div
                    @click.away="showQuestionModal = false"
                    class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-xl space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full" >
                    <div class="flex justify-between items-center px-6 pt-4 ">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">New Question</h3>
                        <button @click="showQuestionModal = false" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
                    </div>
                    <div x-data="questionForm()" class="max-w-3xl px-6 space-y-6 bg-white rounded-lg overflow-y-auto">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Question Name *</label>
                            <input type="text" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                            <select class="w-full text-sm border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option>Select Category</option>
                                <option>Safety</option>
                                <option>Engine</option>
                            </select>
                        </div>

                        <!-- Required checkbox -->
                        <div>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600" />
                            Required Question
                            </label>
                        </div>

                        <!-- Answer Options Header -->
                        <div x-data="sortableAnswers" x-init="init()" class="space-y-4 max-w-3xl w-full mx-auto">
                            <!-- Header -->
                            <div class="flex justify-between items-center">
                                <div class="text-sm font-medium text-gray-700">
                                    Answer Options <span class="text-xs text-gray-400 font-normal">(Drag to reorder)</span>
                                </div>
                                <button @click="addOption" class="text-blue-600 text-sm font-medium hover:underline">+ Add Option</button>
                            </div>

                            <!-- Answer Option List -->
                            <div id="sortable-list" class="space-y-3">
                                <template x-for="(option, index) in answerOptions" :key="option.id">
                                    <div class="bg-white border border-gray-300 rounded-xl px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:gap-4">
                                        <!-- Drag Icon Only (No Number) -->
                                        <div class="flex items-center mb-2 sm:mb-0">
                                            <span class="drag-handle w-7 h-7 flex items-center justify-center rounded-full text-gray-900 cursor-move">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M10 6h.01M10 10h.01M10 14h.01M14 6h.01M14 10h.01M14 14h.01" />
                                                </svg>
                                            </span>
                                        </div>

                                        <!-- Text input -->
                                        <input type="text"
                                            x-model="option.text"
                                            placeholder="Answer description..."
                                            class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />

                                        <!-- Dropdown -->
                                        <select x-model="option.status"
                                            class="mt-2 sm:mt-0 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option>Rental Ready</option>
                                            <option>Maint. Hold</option>
                                            <option>Damaged</option>
                                        </select>

                                        <!-- Delete -->
                                        <button @click="removeOption(index)"
                                            class="mt-2 sm:mt-0 sm:ml-2 text-red-600 rounded-full w-8 h-8 flex items-center justify-center transition hover:text-red-800"
                                            title="Delete">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>
                                    </div>
                                </template>
                            </div>

                        </div>
                    </div>
                    <div class="flex justify-end gap-2 px-6 pb-4">
                        <button type="button" @click="showQuestionModal = false" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white dark:bg-gray-700 dark:text-white">Cancel</button>
                        <button type="button" id="submitQuestionBtn" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600"> Save Question </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-data="{ showAddressModal: false }" x-ref="addressRoot" x-init="window.addEventListener('open-address-modal', () => showAddressModal = true)">
        <!-- Modal -->  
        <div
            x-show="showAddressModal"
            x-transition
            x-cloak
            class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
            <div class="modal-scrollable w-full mx-auto">
                <div
                    @click.away="showAddressModal = false"
                    class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto  max-w-xl  space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col" >
                    <div class="flex justify-between items-center  px-6 pt-4 ">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">New Category</h3>
                        <button @click="showAddressModal = false" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
                    </div>
                    <div class="px-6 grid grid-cols-1  gap-6 bg-gray-50">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category Name *</label>
                            <input type="text" class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Optional description..."></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 px-6  pb-4">
                        <button type="button" @click="showAddressModal = false" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white dark:bg-gray-700 dark:text-white">Cancel</button>
                        <button type="button" id="submitAddressBtn" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-blue-700">Save Category </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection

@push('js')



<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('sortableAnswers', () => ({
    answerOptions: [
        { id: 1, text: '', status: 'Rental Ready' },
    ],
    nextId: 4,

    addOption() {
      this.answerOptions.push({ id: this.nextId++, text: '', status: 'Rental Ready' });
    },

    removeOption(index) {
      this.answerOptions.splice(index, 1);
    },

    init() {
      const el = document.getElementById('sortable-list');
      const self = this;

      Sortable.create(el, {
        handle: '.drag-handle',
        animation: 150,
        onEnd(evt) {
          const moved = self.answerOptions.splice(evt.oldIndex, 1)[0];
          self.answerOptions.splice(evt.newIndex, 0, moved);
        }
      });
    }
  }));
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('openAddressModal').addEventListener('click', () => {
            window.dispatchEvent(new CustomEvent('open-address-modal'));
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('openQuestionModal').addEventListener('click', () => {
            window.dispatchEvent(new CustomEvent('open-question-modal'));
        });
    });
</script>
@endpush
