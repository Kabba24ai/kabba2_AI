@extends('admin.layouts.app')

@section('title', 'Rental Ready Admin')

@push('css')

@endpush

@section('content')

    @include('flash::message')
    @include('admin.partials.formErrors')


    <div class="w-full max-w-md sm:max-w-lg md:max-w-xl lg:max-w-2xl xl:max-w-2xl mx-auto">
 

        <!-- Outer Tabs -->
        <div class="w-full bg-white border border-gray-200 rounded-lg shadow-sm mb-6">
        <div class="flex flex-wrap sm:flex-nowrap p-6 gap-2" data-tab-group="main">
            <button onclick="showTab('overview', this)" id="tab-overview" 
                    class="tab-button bg-green-100 text-green-800 px-4 py-2 text-sm font-medium rounded-md transition">
            Overview
            </button>
            <button onclick="showTab('questions', this)" id="tab-questions"
                    class="tab-button text-gray-700 px-4 py-2 text-sm font-medium rounded-md hover:bg-gray-100 transition">
            Questions & Categories
            </button>
            <button onclick="showTab('templates', this)" id="tab-templates"
                    class="tab-button text-gray-700 px-4 py-2 text-sm font-medium rounded-md hover:bg-gray-100 transition">
            Templates
            </button>
        </div>
        </div>

        <!-- Outer Tab Contents -->
        <div id="overview" class="tab-content grid grid-cols-1 md:grid-cols-2 gap-6" data-tab-group="main">
            <!-- Card 1 -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm flex flex-col justify-between">
                <div class="flex items-start gap-4 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M3 5V19A9 3 0 0 0 21 19V5"></path><path d="M3 12A9 3 0 0 0 21 12"></path></svg>
                <h4 class="text-lg font-semibold text-gray-900">Question & Categories Mgt</h4>
            </div>
            <p class="text-sm text-gray-600 mb-4">Create and manage inspection questions organized by categories.</p>
            <button onclick="showTab('questions', document.getElementById('tab-questions'))" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium w-full">Manage Questions & Categories</button>
            </div>
 
            <!-- Card 2 -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm flex flex-col justify-between">
                <div class="flex items-start gap-4 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path></svg>
                    <h4 class="text-lg font-semibold text-gray-800">Checklist Templates</h4>
                </div>
                <p class="text-sm text-gray-600 mb-4">Build custom checklist templates for different equipment categories.</p>
                <button onclick="showTab('templates', document.getElementById('tab-templates'))" class="bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium w-full">Manage Templates</button>
            </div>
        </div>

        <div id="questions" class="tab-content hidden" data-tab-group="main">
            <h3 class="text-2xl font-semibold text-gray-900 mb-2">Question & Categories Mgt</h3>
            <p class="text-gray-600 mb-6">Manage inspection questions, answers, and categories</p>

            <!-- Inner Tabs -->
            <div class="w-full bg-white border border-gray-200 rounded-lg shadow-sm mb-6" >
                <div class="flex gap-6 border-b" data-tab-group="inner">
                    <button id="tab-questions-sub" onclick="showTab('questionssub', this)" 
                            class="inner-tab px-4 py-3 text-sm font-medium text-blue-600 border-b-2 border-blue-600">
                        Questions ({{ $totalQuestions }})

                    </button>
                    <button id="tab-categories-sub" onclick="showTab('categoriessub', this)" 
                            class="inner-tab px-4 py-3 text-sm font-medium text-gray-600 hover:text-blue-600 border-b-2 border-transparent">
                         Questions Categories ( {{ $rentalreadycategory->count() }} )
                    </button>
                </div>

                
                <!-- Inner Tab Contents -->
                <div id="questionssub" class="tab-content" data-tab-group="inner">
                    <div class="space-y-6 p-4" id="questionWrapper">
                        <!-- Header -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Questions</h2>
                                <p class="text-gray-600">Create and manage inspection questions and answer options</p>
                            </div>
                            <a href="javascript:void(0)"  onclick="openQuestionModal()"
                                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 mt-3">
                                + New Question
                            </a>
                        </div>
                        <!-- Top Filters -->
                        <div class="bg-white border border-gray-200 rounded-lg p-4 space-y-4 shadow-sm">
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
                                    <select
                                    class="w-full text-sm px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option>All Categories</option>
                                    @foreach ($rentalreadycategory as $category)
                                         <option value="{{ $category->id }}"> {{ $category->category_name }} </option>
                                    @endforeach
                                    
                                    
                                    <!-- <option>Engine</option> -->
                                    </select>
                                </div>

                                <!-- Answer Visibility Toggle -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Answer Visibility</label>
                                    <button id="toggleGlobalAnswers"
                                    class="w-full font-medium px-4 py-2 text-sm rounded-md flex items-center justify-center gap-2 border border-blue-300 text-blue-600 hover:bg-gray-50 transition">
                                        <svg id="icon-show" class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg id="icon-hide" class="w-5 h-5 text-gray-900 hidden" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.042 10.042 0 013.03-4.362M6.873 6.876A9.953 9.953 0 0112 5c4.477 0 8.267 2.943 9.541 7a9.966 9.966 0 01-1.249 2.527M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                        </svg>
                                        <span id="toggleText">Show All Answers</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        @foreach($rentalreadycategory as $category)
                            @foreach($category->questions as $question)
                                <!-- Sample Question Card -->
                                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm question-card" data-required="{{ $question->required_question }}">
                                    <!-- Header -->
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h3 class="text-base font-semibold text-gray-900"> {{ $question->question_name }}</h3>
                                        
                                                @if($question->required_question)
                                                    <span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full font-medium">Required</span>
                                                @endif

                                            </div>
                                            <p class="text-sm text-gray-600 mt-1">Category:  {{ $category->category_name }} </p>
                                        </div>
                                        <div class="flex items-center gap-4 mt-3 md:mt-0 text-gray-600 text-sm">
                                            <button class="toggle-answers text-blue-600 hover:underline flex items-center gap-1">
                                                <svg class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor"
                                                    stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>
                                            <span>Show Questions ({{ $question->answers->count() }}) </span>
                                            </button>
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

                                    <!-- Answers (initially hidden) -->
                                    <div class="answers hidden mt-5 border-t pt-5 space-y-2">
                                        <h4 class="text-sm font-semibold text-gray-800">Answer Options:</h4>
                                        <div class="grid sm:grid-cols-2 gap-3">

                                        @foreach($question->answers as $index => $answer)
                                            @php
                                                $statusMap = [
                                                    'Rental Ready' => [
                                                        'bg' => 'bg-green-100 text-green-700',
                                                        'icon' => ''
                                                    ],
                                                    'Maint. Hold' => [
                                                        'bg' => 'bg-yellow-100 text-yellow-700',
                                                        'icon' => ''
                                                    ],
                                                    'Damaged' => [
                                                        'bg' => 'bg-red-100 text-red-600',
                                                        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a1.75 1.75 0 001.51 2.62h17.34a1.75 1.75 0 001.51-2.62L13.71 3.86a1.75 1.75 0 00-3.42 0z" />
                                                        </svg>'
                                                    ],
                                                ];

                                                $status = $statusMap[$answer->type] ?? $statusMap['Rental Ready'];
                                            @endphp

                                            <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                                <div class="flex items-center gap-3">
                                                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-normal">
                                                        {{ $index + 1 }}
                                                    </span>
                                                    <span class="flex items-center gap-1 text-black text-sm font-medium">
                                                    
                                                    {{-- Icon based on type --}}
                                                        @if($answer->type === 'Rental Ready')
                                                            <x-heroicon-o-check class="w-5 h-5 text-green-700" />
                                                        @elseif($answer->type === 'Maint. Hold')
                                                            <x-heroicon-o-clock class="w-5 h-5 text-yellow-700" />
                                                        @elseif($answer->type === 'Damaged')
                                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a1.75 1.75 0 001.51 2.62h17.34a1.75 1.75 0 001.51-2.62L13.71 3.86a1.75 1.75 0 00-3.42 0z" />
                                                            </svg>
                                                        @endif
                                                    
                                                    {{ $answer->answer_name }}
                                                    </span>
                                                </div>
                                                <span class="{{ $status['bg'] }} text-xs px-2 py-0.5 rounded-full">
                                                    {{ $answer->type ?? 'N/A' }}
                                                </span>
                                            </div>
                                        @endforeach


                                        
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                       
                    </div>
                </div>

                <div id="categoriessub" class="tab-content hidden" data-tab-group="inner">
                    <div class=" p-4 space-y-6">
                        <!-- Header -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800">Categories</h2>
                                <p class="text-sm text-gray-500">Organize questions into logical categories</p>
                            </div>
                            <a href="javascript:void(0)" onclick="openCategoriesModal()"
                                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 mt-3">
                                + New Category
                            </a>
                        </div>
                        <!-- Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($rentalreadycategory as $category)
                                <!-- Category Card -->
                                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h3.6a1 1 0 01.7.3l1.4 1.4a1 1 0 00.7.3H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                            </svg>
                                        <div>
                                            <h3 class="text-base font-semibold text-gray-800">{{ $category->category_name }}</h3>
                                            <p class="text-sm text-gray-600">2 questions</p>
                                        </div>
                                        </div>
                                        <div class="flex gap-3 text-gray-500">
                                            <!-- Edit -->
                                            <button class="text-green-600 hover:text-green-800 edit-category-btn"
                                                    title="Edit"
                                                    data-id="{{ $category->id }}"
                                                    data-name="{{ $category->category_name }}"
                                                    data-description="{{ $category->description }}"
                                                    data-route="{{ route('admin.checklist_management.rental-ready.categories.update', $category->unique_id ) }}">
                                                <x-heroicon-o-pencil class="w-5 h-5" />
                                            </button>
                                                <!-- Delete -->
                                                <form action="{{ route('admin.checklist_management.rental-ready.categories.delete', $category->unique_id) }}"
                                                    method="POST"
                                                    class="inline delete-category-form"
                                                    data-category-name="{{ $category->category_name }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                                        <x-heroicon-o-trash class="w-5 h-5" />
                                                    </button>
                                                </form>
                                            
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-sm text-gray-500 mt-1">{{ $category->description }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="templates" class="tab-content hidden" data-tab-group="main">
            <div class="space-y-6" id="questionWrappertemp">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900 mb-2">Checklist Templates</h2>
                        <p class="text-gray-600">Create and manage checklist templates for different equipment categories</p>
                    </div>
                    <a href="javascript:void(0)" id="openTemplatesModal"
                        class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 mt-3">
                        + New Template
                    </a>
                </div>

                <!-- Top Filters -->
                <div class="bg-white border border-gray-200 rounded-lg p-4 space-y-4 shadow-sm mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search Templates</label>
                            <input type="text" placeholder="Search Templates..." class="w-full px-4 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>

                        <!-- Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Template Count</label>
                            <input type="text"  class="w-full px-4 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>

                        <!-- Answer Visibility Toggle -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Answer Visibility</label>
                            <button id="toggleGlobalAnswerstemp"
                            class="w-full font-medium px-4 py-2 text-sm rounded-md flex items-center justify-center gap-2 border border-blue-300 text-blue-600 hover:bg-gray-50 transition">
                                <svg id="icon-showtemp" class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg id="icon-hidetemp" class="w-5 h-5 text-gray-900 hidden" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.042 10.042 0 013.03-4.362M6.873 6.876A9.953 9.953 0 0112 5c4.477 0 8.267 2.943 9.541 7a9.966 9.966 0 01-1.249 2.527M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                </svg>
                                <span id="toggleTexttemp">Show All Answers</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sample Question Card -->
                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm question-cardtemp mb-6" data-required="true">
                    <!-- Header -->
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <h3 class="text-base font-semibold text-gray-900">Heavy Equipment Standard</h3>
                                <span class="bg-green-100 text-green-600 text-xs px-2 py-0.5 rounded-full font-medium">Active</span>
                            </div>
                            <p class="text-sm text-gray-600 mb-1">Standard checklist for heavy equipment like excavators, bulldozers, and loaders </p>
                            <p class="text-sm text-gray-600 mb-1">Equipment Category: Heavy Equipment</p>
                            <p class="text-sm text-gray-600 mt-1">12 Questions</p>
                        </div>

                        <div class="flex items-center gap-4 mt-3 md:mt-0 text-gray-600 text-sm">
                            <button class="toggle-answerstemp text-blue-600 hover:underline flex items-center gap-1">
                                <svg class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                                <span>Show Questions (5)</span>
                            </button>
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

                    <!-- Answers (initially hidden) -->
                    <div class="answerstemp hidden mt-5 border-t pt-5 space-y-2">
                        <!-- <div class="border-t border-gray-300 mt-5 mb-5"></div> -->
                            <h4 class="text-sm font-semibold text-gray-800">Questions in Template:</h4>
                            <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-normal">1</span>
                                <span class="flex-1 font-medium">Safety Equipment Present</span>
                                <span class="text-xs text-gray-500">Safety</span>
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                            </div>
                            <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-normal">2</span>
                                <span class="flex-1 font-medium">Warning Labels Visible</span>
                                <span class="text-xs text-gray-500">Safety</span>
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                            </div>
                            <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-normal">3</span>
                                <span class="flex-1 font-medium">Engine Oil Level</span>
                                <span class="text-xs text-gray-500">Engine</span>
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                            </div>
                            <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-normal">4</span>
                                <span class="flex-1 font-medium">Coolant Level</span>
                                <span class="text-xs text-gray-500">Engine</span>
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                            </div>
                            <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-normal">5</span>
                                <span class="flex-1 font-medium">Hydraulic Fluid Level</span>
                                <span class="text-xs text-gray-500">Hydraulics</span>
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                            </div>
                            <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-normal">6</span>
                                <span class="flex-1 font-medium">Hydraulic Hoses Condition</span>
                                <span class="text-xs text-gray-500">Hydraulics</span>
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                            </div>
                            <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-normal">7</span>
                                <span class="flex-1 font-medium">Fuel Level</span>
                                <span class="text-xs text-gray-500">Fuel</span>
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                            </div>
                            <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-normal">8</span>
                                <span class="flex-1 font-medium">Battery Condition</span>
                                <span class="text-xs text-gray-500">Electrical</span>
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                            </div>
                            <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-normal">9</span>
                                <span class="flex-1 font-medium">Lights Functioning</span>
                                <span class="text-xs text-gray-500">Electrical</span>
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                            </div>
                            <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-normal">10</span>
                                <span class="flex-1 font-medium">Track Condition</span>
                                <span class="text-xs text-gray-500">Tracks</span>
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                            </div>
                            <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-normal">11</span>
                                <span class="flex-1 font-medium">Bucket/Attachment Condition</span>
                                <span class="text-xs text-gray-500">Attachments</span>
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                            </div>
                            <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                                <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-normal">12</span>
                                <span class="flex-1 font-medium">Overall Cleanliness</span>
                                <span class="text-xs text-gray-500">General</span>
                            </div>
                    </div>
                </div>

                <!-- Sample Question Card -->
                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm question-cardtemp mb-6" data-required="true">
                    <!-- Header -->
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <h3 class="text-base font-semibold text-gray-900">Compact Equipment Standard</h3>
                                <span class="bg-green-100 text-green-600 text-xs px-2 py-0.5 rounded-full font-medium">Active</span>
                            </div>
                            <p class="text-sm text-gray-600 mb-1">Standard checklist for compact equipment like skid steers and mini excavators</p>
                            <p class="text-sm text-gray-600 mb-1">Equipment Category: Compact Equipment</p>
                            <p class="text-sm text-gray-600 mt-1">10 Questions</p>
                        </div>

                        <div class="flex items-center gap-4 mt-3 md:mt-0 text-gray-600 text-sm">
                            <button class="toggle-answerstemp text-blue-600 hover:underline flex items-center gap-1">
                                <svg class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                                <span>Show Questions (10)</span>
                            </button>
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

                    <!-- Answers (initially hidden) -->
                    <div class="answerstemp hidden mt-5 border-t pt-5 space-y-2">
                        <h4 class="text-sm font-semibold text-gray-800">Questions in Template:</h4>
                        <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                            <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">1</span>
                            <span class="flex-1 font-medium">Safety Equipment Present</span>
                            <span class="text-xs text-gray-500">Safety</span>
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                        </div>
                        <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                            <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">2</span>
                            <span class="flex-1 font-medium">Warning Labels Visible</span>
                            <span class="text-xs text-gray-500">Safety</span>
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                        </div>
                        <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                            <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">3</span>
                            <span class="flex-1 font-medium">Engine Oil Level</span>
                            <span class="text-xs text-gray-500">Engine</span>
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                        </div>
                        <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                            <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">4</span>
                            <span class="flex-1 font-medium">Coolant Level</span>
                            <span class="text-xs text-gray-500">Engine</span>
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                        </div>
                        <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                            <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">5</span>
                            <span class="flex-1 font-medium">Hydraulic Fluid Level</span>
                            <span class="text-xs text-gray-500">Hydraulics</span>
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                        </div>
                        <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                            <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">6</span>
                            <span class="flex-1 font-medium">Hydraulic Hoses Condition</span>
                            <span class="text-xs text-gray-500">Hydraulics</span>
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                        </div>
                        <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                            <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">7</span>
                            <span class="flex-1 font-medium">Fuel Level</span>
                            <span class="text-xs text-gray-500">Fuel</span>
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                        </div>
                        <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                            <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">8</span>
                            <span class="flex-1 font-medium">Battery Condition</span>
                            <span class="text-xs text-gray-500">Electrical</span>
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                        </div>
                        <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                            <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">9</span>
                            <span class="flex-1 font-medium">Lights Functioning</span>
                            <span class="text-xs text-gray-500">Electrical</span>
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                        </div>
                        <div class="bg-white flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                            <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">10</span>
                            <span class="flex-1 font-medium">Overall Cleanliness</span>
                            <span class="text-xs text-gray-500">General</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>


   <!--Question Modal -->
<div id="questionModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
  <div class="modal-scrollable w-full mx-auto">
    <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-2xl flex flex-col max-h-full overflow-hidden border border-gray-200">
      <div class="flex justify-between items-center px-6 pt-4">
        <h3 class="text-lg font-semibold text-gray-800">New Question</h3>
        <button onclick="closeQuestionModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
      </div>

        <!-- Scrollable Content -->
      <div class=" overflow-y-auto max-h-[70vh]">
            <!-- {{-- Form --}} -->
            {{ html()->form('POST', route('admin.checklist_management.rental-ready.questions.store'))
                ->id('questionForm')
                ->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'flex flex-col flex-1'
                ])
                ->acceptsFiles()
                ->open() }}

                 
                    <input type="hidden" name="options" id="optionsInput">


                <div class="max-w-3xl px-6 py-4 space-y-6 overflow-y-auto">

                    <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Question Name *</label>
                    <!-- <input type="text" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"> -->

                    {!! html()->text('question_name')
                    ->class('w-full px-4 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500')
                    ->attributes(['id' => 'question_name', 'placeholder' => 'Enter Question name'])
                    ->required() !!}

                    </div>

                    <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Question Category *</label>
                    {!! html()->select('category_id', $rentalreadycategory->pluck('category_name', 'id'))
                    ->class('w-full text-sm border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500')
                    ->attributes(['id' => 'category_id'])
                    ->required() !!}

                    </div>

                    <div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <!-- <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600"> Required Question -->
                        {!! html()->checkbox('required_question', false, 1)
                        ->class('form-checkbox h-4 w-4 text-blue-600') !!}

                        Required Question 
                    </label>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Answer Options <span class="text-xs text-gray-400 font-normal">(Drag to reorder)</span></span>
                            <button type="button" onclick="addOption()" class="text-blue-600 text-sm font-medium hover:underline">+ Add Option</button>
                        </div>

                        <div id="sortable-list" class="space-y-3"></div>
                    </div>

                </div>

                <div class="flex justify-end gap-2 px-6 pb-4">
                    <button type="button" onclick="closeQuestionModal()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600">Save Question</button>
                </div>

            {{ html()->form()->close() }}

 </div>
    </div>
  </div>
</div>

<!-- Category Modal -->
<div id="CategoryModalWrapper" class="hidden fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div
        class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-xl space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full"
        onclick="event.stopPropagation()"
        >
            <div class="flex justify-between items-center px-6 pt-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">New Category</h3>
                <button onclick="closeCategoryModal()" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>

             
                {{ html()->form()->attributes([
                    'method' => 'POST',
                    'id' => 'categoryForm',
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'action' => route('admin.checklist_management.rental-ready.categories.store'),
                ])->open() }}

                @csrf
            

            <div class="px-6 grid grid-cols-1 gap-6 bg-gray-50">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Category Name *</label>
                    <!-- <input type="text" class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required> -->

                     {{ html()->text('category_name')->attributes([
                        'class' => 'mt-1 w-full px-4 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500',
                        'required' => true,
                        'id' => 'category_name',
                        'data-parsley-required-message' => 'Category name is required.',
                    ])->placeholder('Enter category name') }}
                    @error('category_name')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror


                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <!-- <textarea class="mt-1 w-full px-4 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Optional description..."></textarea> -->
                     {{ html()->textarea('description')->attributes([
                        'class' => 'mt-1 w-full px-4 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500',
                        'rows' => 3,
                        'id' => 'description',
                    ])->placeholder('Optional description...') }}
                </div>
            </div>

            <div class="flex justify-end gap-2 px-6 pb-4">
                <button type="button" onclick="closeCategoryModal()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white dark:bg-gray-700 dark:text-white">Cancel</button>
                 <!-- Submit Button with Loader -->
                <button type="submit" id="submitCategoryBtn" class="relative px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-teal-700 flex items-center justify-center gap-2">
                    <span id="categoryBtnText">Save Category</span>
                    <svg id="categoryBtnSpinner" xmlns="http://www.w3.org/2000/svg" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </button>
            </div>

              {{ html()->form()->close() }}
        </div>
    </div>
</div>

<!-- Modal Wrapper -->
<div id="templatesModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto max-w-6xl space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">New Template</h3>
                <button id="closeModalBtn" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6 overflow-y-auto">
                <!-- Template Info -->
                <div class="space-y-4 pr-0 md:pr-6 md:border-r">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Template Name *</label>
                        <input type="text" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Equipment Category *</label>
                        <select class="w-full border text-sm border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <option>Compact Equipment</option>
                            <option>Heavy Equipment</option>
                        </select>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 mt-2">
                        <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600" /> Active Template
                    </label>
                </div>

                <!-- Available Questions -->
                <div class="space-y-4 pr-0 md:pr-6 md:border-r">
                    <h3 class="text-base font-semibold text-gray-800">Available Questions</h3>
                    <input type="text" placeholder="Search questions..." class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option>All Categories</option>
                    </select>
                    <div id="available" class="bg-white space-y-2 min-h-[300px] bg-gray-50 rounded-md"></div>
                </div>

                <!-- Template Questions -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        Template Questions (<span id="templateCount">0</span>)
                    </h3>
                    <div id="template" class="space-y-2 min-h-[300px] bg-white border-2 border-dashed border-gray-300 p-3 rounded-md"></div>
                </div>
            </div>

            <div class="flex justify-end gap-2 px-6 pb-4">
                <button type="button" id="cancelBtn" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white text-gray-700">Cancel</button>
                <button type="button" id="submitTemplatesBtn" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-blue-700">Save Template</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')

<script>
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('templatesModalWrapper');
  const openBtn = document.getElementById('openTemplatesModal');
  const closeBtn = document.getElementById('closeModalBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const availableEl = document.getElementById('available');
  const templateEl = document.getElementById('template');
  const templateCount = document.getElementById('templateCount');
 
  const data = [
    { id: 1, text: 'Safety Equipment Present', category: 'Safety', required: true },
    { id: 2, text: 'Warning Labels Visible', category: 'Safety', required: true },
    { id: 3, text: 'Engine Oil Level', category: 'Engine', required: true },
    { id: 4, text: 'Coolant Level', category: 'Engine', required: true },
  ];
 
  let templateData = [];
 
  const openModal = () => {
    modal.style.display = 'flex';
    renderAvailable();
    renderTemplate();
  };
 
  const closeModal = () => {
    modal.style.display = 'none';
  };
 
  openBtn.addEventListener('click', openModal);
  closeBtn.addEventListener('click', closeModal);
  cancelBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });
 
  function renderAvailable() {
    availableEl.innerHTML = '';
    data.forEach(item => {
      if (!templateData.some(t => t.id === item.id)) {
        const div = document.createElement('div');
        div.className = 'p-3 bg-white border border-gray-300 rounded-md flex items-center justify-between gap-3 cursor-move';
        div.setAttribute('data-id', item.id);
 
        div.innerHTML = `
          <div class="flex items-center gap-3 w-full">
            <!-- Drag Handle -->
            <span class="drag-handle w-6 h-6 flex items-center justify-center text-gray-400">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M10 6h.01M10 10h.01M10 14h.01M14 6h.01M14 10h.01M14 14h.01" />
              </svg>
            </span>
 
            <!-- Question Text -->
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-900">${item.text}</p>
              <p class="text-xs text-gray-500">${item.category}</p>
            </div>
 
            <!-- Badge -->
            ${item.required ? '<span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full font-medium">Required</span>' : ''}
          </div>
        `;
 
        availableEl.appendChild(div);
      }
    });
  }
 
  function renderTemplate() {
    templateEl.innerHTML = '';
 
    if (templateData.length === 0) {
      templateEl.innerHTML = `
        <div class="text-center text-sm text-gray-500 py-12">
          <p class="mb-2">Drag questions here to build your template</p>
        </div>
      `;
    } else {
      templateData.forEach((item, index) => {
        const div = document.createElement('div');
        div.className = 'p-3 bg-blue-50 border border-blue-200 rounded-md space-y-1';
        div.innerHTML = `
          <div class="flex justify-between items-center">
            <div class="flex items-center gap-2">
              <span class="w-6 h-6 flex items-center justify-center bg-blue-100 text-blue-600 rounded-full text-xs font-bold">${index + 1}</span>
              <p class="font-medium text-gray-900 text-sm">${item.text}</p>
            </div>
            <button data-remove="${index}" class="text-red-500 hover:text-red-700 font-bold">✕</button>
          </div>
 
          <div class="flex items-center gap-2 mt-1 text-xs text-gray-600 pl-8">
            <label class="inline-flex items-center">
              <input type="checkbox" class="h-3 w-3 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-1" 
                     ${item.required ? 'checked' : ''} data-checkbox="${index}">
              Required
            </label>
          </div>
 
          <div class="text-xs text-gray-600 pl-8">
            <button data-up="${index}" class="text-gray-500 hover:underline">↑ Up</button>
            <button data-down="${index}" class="text-gray-500 hover:underline">↓ Down</button>
          </div>
        `;
        templateEl.appendChild(div);
      });
    }
 
    templateCount.textContent = templateData.length;
  }
 
  // Handle all template interactions including checkboxes
  templateEl.addEventListener('click', (e) => {
    const remove = e.target.dataset.remove;
    const up = e.target.dataset.up;
    const down = e.target.dataset.down;
    const checkbox = e.target.dataset.checkbox;
 
    if (remove !== undefined) {
      templateData.splice(remove, 1);
      renderTemplate();
      renderAvailable();
    } else if (up !== undefined && up > 0) {
      const i = parseInt(up);
      [templateData[i - 1], templateData[i]] = [templateData[i], templateData[i - 1]];
      renderTemplate();
    } else if (down !== undefined && down < templateData.length - 1) {
      const i = parseInt(down);
      [templateData[i], templateData[i + 1]] = [templateData[i + 1], templateData[i]];
      renderTemplate();
    }
  });
 
  // Handle checkbox changes separately to prevent event bubbling issues
  templateEl.addEventListener('change', (e) => {
    if (e.target.type === 'checkbox') {
      const index = parseInt(e.target.dataset.checkbox);
      if (index !== undefined && templateData[index]) {
        templateData[index].required = e.target.checked;
        console.log(`Item ${templateData[index].text} required status changed to: ${e.target.checked}`);
      }
    }
  });
 
  // DRAGGING SETUP
  Sortable.create(availableEl, {
    group: {
      name: 'questions',
      pull: 'clone', // allow drag copy
      put: false     // don't accept drop here
    },
    animation: 150,
    sort: false,
    onClone: (evt) => {
      evt.clone.setAttribute('data-id', evt.item.getAttribute('data-id'));
    }
  });
 
  Sortable.create(templateEl, {
    group: {
      name: 'questions',
      pull: false,
      put: true
    },
    animation: 150,
    onAdd: (evt) => {
      const id = parseInt(evt.item.getAttribute('data-id'));
      const item = data.find(q => q.id === id);
 
      // Remove clone immediately
      evt.item.parentNode.removeChild(evt.item);
 
      if (item && !templateData.some(q => q.id === id)) {
        // Create a copy of the item to avoid modifying the original
        templateData.push({ ...item });
      }
 
      renderTemplate();
      renderAvailable(); // repopulate if any got visually removed
    }
  });
 
  renderAvailable();
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  let globalVisible = false;

  const globalToggleBtn = document.getElementById('toggleGlobalAnswerstemp');
  const iconShow = document.getElementById('icon-showtemp');
  const iconHide = document.getElementById('icon-hidetemp');
  const toggleText = document.getElementById('toggleTexttemp');

  const toggleAllCards = (show) => {
    document.querySelectorAll('.question-cardtemp').forEach(card => {
      const answers = card.querySelector('.answerstemp');
      const btn = card.querySelector('.toggle-answerstemp');
      const span = btn.querySelector('span');
      const icon = btn.querySelector('svg');

      if (show) {
        answers.classList.remove('hidden');
        span.textContent = 'Hide Questions';
        icon.classList.add('rotate-90');
      } else {
        answers.classList.add('hidden');
        span.textContent = 'Show Questions (5)';
        icon.classList.remove('rotate-90');
      }
    });
  };

  globalToggleBtn.addEventListener('click', () => {
    globalVisible = !globalVisible;

    // Toggle icon and text
    iconShow.classList.toggle('hidden', globalVisible);
    iconHide.classList.toggle('hidden', !globalVisible);
    toggleText.textContent = globalVisible ? 'Hide All Answers' : 'Show All Answers';

    globalToggleBtn.classList.toggle('border-blue-300', !globalVisible);
    globalToggleBtn.classList.toggle('border-gray-300', globalVisible);
    globalToggleBtn.classList.toggle('text-blue-600', !globalVisible);
    globalToggleBtn.classList.toggle('text-gray-600', globalVisible);

    toggleAllCards(globalVisible);
  });

  // Individual toggle per card
  document.querySelectorAll('.toggle-answerstemp').forEach(button => {
    button.addEventListener('click', () => {
      const card = button.closest('.question-cardtemp');
      const answers = card.querySelector('.answerstemp');
      const icon = button.querySelector('svg');
      const text = button.querySelector('span');

      const isVisible = !answers.classList.contains('hidden');
      if (isVisible) {
        answers.classList.add('hidden');
        text.textContent = 'Show Questions (5)';
        icon.classList.remove('rotate-90');
      } else {
        answers.classList.remove('hidden');
        text.textContent = 'Hide Questions';
        icon.classList.add('rotate-90');
      }
    });
  });
});
</script>

<script>
  // Open modal
  function openCategoriesModal() {
    document.getElementById('CategoryModalWrapper').classList.remove('hidden');
  }

  // Close modal
  function closeCategoryModal() {
    document.getElementById('CategoryModalWrapper').classList.add('hidden');
  }

  // Optional: Close when clicking outside modal content
  window.addEventListener('click', function (e) {
    const modal = document.getElementById('CategoryModalWrapper');
    if (e.target === modal) {
      closeCategoryModal();
    }
  });

  // Optional: Listen to external event to open modal (like Alpine's window event)
  window.addEventListener('open-address-modal', () => {
    openCategoriesModal();
  });
</script>


<script>
  // Toggle individual answer sets
  document.querySelectorAll('.toggle-answers').forEach(button => {
    button.addEventListener('click', () => {
      const card = button.closest('.question-card');
      const answers = card.querySelector('.answers');
      const icon = button.querySelector('svg');
      const text = button.querySelector('span');

      answers.classList.toggle('hidden');
      icon.classList.toggle('rotate-90');
      text.textContent = answers.classList.contains('hidden') ? 'Show Questions (5)' : 'Hide Questions';
    });
  });

  // Toggle ALL answer sections
  const globalToggle = document.getElementById('toggleGlobalAnswers');
  const iconShow = document.getElementById('icon-show');
  const iconHide = document.getElementById('icon-hide');
  const toggleText = document.getElementById('toggleText');
  let globalState = false;

  globalToggle.addEventListener('click', () => {
    globalState = !globalState;

    // Toggle UI
    iconShow.classList.toggle('hidden', globalState);
    iconHide.classList.toggle('hidden', !globalState);
    toggleText.textContent = globalState ? 'Hide All Answers' : 'Show All Answers';
    globalToggle.classList.toggle('border-blue-300', !globalState);
    globalToggle.classList.toggle('border-gray-300', globalState);
    globalToggle.classList.toggle('text-blue-600', !globalState);
    globalToggle.classList.toggle('text-gray-600', globalState);

    // Toggle all answers
    document.querySelectorAll('.question-card').forEach(card => {
      const answers = card.querySelector('.answers');
      const toggleBtn = card.querySelector('.toggle-answers span');
      const icon = card.querySelector('.toggle-answers svg');

      answers.classList.toggle('hidden', !globalState);
      icon.classList.toggle('rotate-90', globalState);
      toggleBtn.textContent = globalState ? 'Hide Questions' : 'Show Questions (5)';
    });
  });
</script>


<script>
document.addEventListener("DOMContentLoaded", function () {
    let activeTab    = @json(session('active_tab', 'overview'));
    let activeSubTab = @json(session('active_subtab', null));

    // Activate outer tab
    if (activeTab) {
        const outerBtn = document.getElementById('tab-' + activeTab);
        if (outerBtn) {
            showTab(activeTab, outerBtn);
        }
    }

    // Activate inner subtab
   
        if (activeSubTab) {
            const innerBtn = document.getElementById('tab-' + activeSubTab + '-sub');
            if (innerBtn) {
                showTab(activeSubTab + 'sub', innerBtn);
            }
        }


});
</script>



<script>
function showTab(tabId, clickedBtn) {
  const group = clickedBtn.closest('[data-tab-group]').getAttribute('data-tab-group');

  // hide all contents in this group
  document.querySelectorAll(`.tab-content[data-tab-group="${group}"]`).forEach(content => {
    content.classList.add('hidden');
  });

  // show selected content
  document.getElementById(tabId).classList.remove('hidden');

  // reset all buttons in this group
  const buttons = clickedBtn.closest('[data-tab-group]').querySelectorAll('button');
  buttons.forEach(btn => {
    if (group === 'main') {
      btn.classList.remove('bg-green-100', 'text-green-800');
      btn.classList.add('text-gray-700');
    } else if (group === 'inner') {
      btn.classList.remove('text-blue-600', 'border-blue-600');
      btn.classList.add('text-gray-600', 'border-transparent');
    }
  });

  // set active styles for clicked button
  if (group === 'main') {
    clickedBtn.classList.add('bg-green-100', 'text-green-800');
    clickedBtn.classList.remove('text-gray-700');
  } else if (group === 'inner') {
    clickedBtn.classList.add('text-blue-600', 'border-blue-600');
    clickedBtn.classList.remove('text-gray-600', 'border-transparent');
  }
}
</script>


   <script>
let answerOptions = [{ id: 1, text: '', status: 'Rental Ready' }];
let nextId = 2;

function openQuestionModal() {
  document.getElementById('questionModal').classList.remove('hidden');
  renderOptions();
}

function closeQuestionModal() {
  document.getElementById('questionModal').classList.add('hidden');
}

function renderOptions() {
  const container = document.getElementById('sortable-list');
  container.innerHTML = '';

  answerOptions.forEach((option, index) => {
    const wrapper = document.createElement('div');
    wrapper.className =
      'bg-white border border-gray-300 rounded-xl px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:gap-4';
    wrapper.setAttribute('data-id', option.id);

    wrapper.innerHTML = `
      <div class="flex items-center mb-2 sm:mb-0">
        <span class="drag-handle w-7 h-7 flex items-center justify-center rounded-full text-gray-900 cursor-move">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M10 6h.01M10 10h.01M10 14h.01M14 6h.01M14 10h.01M14 14h.01" />
          </svg>
        </span>
      </div>
      <input type="text" placeholder="Answer description..." 
        class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        value="${option.text}" 
        oninput="updateText(${index}, this.value)" required>

      <select class="mt-2 sm:mt-0 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" 
        onchange="updateStatus(${index}, this.value)">
        <option ${option.status === 'Rental Ready' ? 'selected' : ''}>Rental Ready</option>
        <option ${option.status === 'Maint. Hold' ? 'selected' : ''}>Maint. Hold</option>
        <option ${option.status === 'Damaged' ? 'selected' : ''}>Damaged</option>
      </select>

      <button type="button" onclick="removeOption(${index})" class="mt-2 sm:mt-0 sm:ml-2 text-red-600 rounded-full w-8 h-8 flex items-center justify-center hover:text-red-800" title="Delete">
        <x-heroicon-o-trash class="w-4 h-4" />
      </button>
    `;

    container.appendChild(wrapper);
  });

  // Re-init Sortable
  Sortable.create(container, {
    handle: '.drag-handle',
    animation: 150,
    onEnd: function (evt) {
      const movedItem = answerOptions.splice(evt.oldIndex, 1)[0];
      answerOptions.splice(evt.newIndex, 0, movedItem);
      renderOptions();
    }
  });
}

function addOption() {
  answerOptions.push({ id: nextId++, text: '', status: 'Rental Ready' });
  renderOptions();
}

function removeOption(index) {
  answerOptions.splice(index, 1);
  renderOptions();
}

function updateText(index, value) {
  answerOptions[index].text = value;
}

function updateStatus(index, value) {
  answerOptions[index].status = value;
}

//  Before submitting form, sync options into hidden input
document.getElementById('questionForm').addEventListener('submit', function(e) {
  document.getElementById('optionsInput').value = JSON.stringify(answerOptions);
});
</script>




<script>
function attachValidatedSubmit(formId, btnId, btnTextId, spinnerId, loadingText) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // Run Parsley validation
        if ($(form).parsley().isValid()) {
            const btn = document.getElementById(btnId);
            const btnText = document.getElementById(btnTextId);
            const spinner = document.getElementById(spinnerId);

            btn.disabled = true;
            btnText.textContent = loadingText;
            spinner.classList.remove('hidden');

            form.submit(); // now submit
        }
    });
}

// Attach to category form
document.addEventListener("DOMContentLoaded", function () {
    attachValidatedSubmit(
        'categoryForm',
        'submitCategoryBtn',
        'categoryBtnText',
        'categoryBtnSpinner',
        'Saving...'
    );
});
</script>



<!-- handal edit catagray model  -->

<script>
    document.addEventListener("DOMContentLoaded", function () {
    const modalWrapper = document.getElementById("CategoryModalWrapper");
    const form = document.getElementById("categoryForm");
    const categoryNameInput = document.getElementById("category_name");
    const descriptionInput = document.getElementById("description");
    const btnText = document.getElementById("categoryBtnText");

    // Handle Edit button clicks
    document.querySelectorAll(".edit-category-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            const name = this.dataset.name;
            const description = this.dataset.description || "";
            const updateRoute = this.dataset.route;

            // Prefill inputs
            categoryNameInput.value = name;
            descriptionInput.value = description;

            // Switch form to update route
            form.setAttribute("action", updateRoute);

            // Add hidden _method=PUT
            let methodField = form.querySelector("input[name='_method']");
            if (!methodField) {
                methodField = document.createElement("input");
                methodField.type = "hidden";
                methodField.name = "_method";
                form.appendChild(methodField);
            }
            methodField.value = "PUT";

            // Change button text
            btnText.textContent = "Update Category";

            modalWrapper.classList.remove("hidden");
        });
    });

    // Reset for Create
    window.openCategoryModal = function () {
        form.setAttribute("action", "{{ route('admin.checklist_management.rental-ready.categories.store') }}");

        let methodField = form.querySelector("input[name='_method']");
        if (methodField) methodField.remove();

        categoryNameInput.value = "";
        descriptionInput.value = "";
        btnText.textContent = "Save Category";

        modalWrapper.classList.remove("hidden");
    };
});

</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.delete-category-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault(); // stop auto submit

            const categoryName = form.getAttribute('data-category-name') || 'this category';

            window.showConfirm(
                `Delete "${categoryName}"? This action cannot be undone!`,
                'Delete Category'
            ).then((result) => {
                if (result.isConfirmed) {
                    form.submit(); 
                }
            });
        });
    });
});
</script>



@endpush

