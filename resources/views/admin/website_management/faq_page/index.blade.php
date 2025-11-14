@extends('admin.layouts.app')

@section('title', 'Faq Page')

@push('css')
@endpush

@section('content')

@include('flash::message')
@include('admin.partials.formErrors')

@php
$activeTab = session('active_tab', 'faq'); // default to "faq" if not set
@endphp



<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-md p-5 shadow-sm border border-gray-100 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <!-- Left Section -->
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h1 class="text-2xl font-semibold text-gray-900">FAQ</h1>
            </div>
            <p class="text-gray-600">Manage your frequently asked questions and categories</p>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200">
        <nav id="tabs" class="flex flex-wrap gap-2 sm:gap-6">
            <button
                class="tab-link text-sm font-medium px-3 py-2 {{ $activeTab === 'faq' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600 hover:text-blue-600' }}"
                data-tab="faq">
                FAQs Management
            </button>

            <button
                class="tab-link text-sm font-medium px-3 py-2 {{ $activeTab === 'categories' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600 hover:text-blue-600' }}"
                data-tab="categories">
                Categories
            </button>

            <!-- <button
                class="tab-link text-sm font-medium px-3 py-2 {{ $activeTab === 'analytics' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600 hover:text-blue-600' }}"
                data-tab="analytics">
                Analytics
            </button>

            <button
                class="tab-link text-sm font-medium px-3 py-2 {{ $activeTab === 'settings' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600 hover:text-blue-600' }}"
                data-tab="settings">
                Settings
            </button> -->

        </nav>

    </div>

    <!-- Tabs Content -->
    <div class="mt-6">
        <div id="faq" class="tab-content {{ $activeTab === 'faq' ? 'block' : 'hidden' }}">
            <!-- Header -->
            <div class="flex flex-wrap justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-900 mb-4 sm:mb-0">Manage FAQs</h1>
                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                    <select class="bulk-action-select border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium">
                        <option>Bulk Actions</option>
                        <option value="Delete">Delete</option>
                        <!-- <option value="Mark Active">Mark Active</option>
                        <option value="Mark Inactive">Mark Inactive</option> -->
                    </select>

                    <button id="applyButton" class="bg-gray-400 text-white px-4 py-2 rounded-lg font-medium text-sm" disabled>
                        Apply (0)
                    </button>
                    <button onclick="openNewFAQModal()" class="bg-blue-600 rounded-lg px-4 py-2 text-sm font-medium text-white">
                        + Add New FAQ
                    </button>
                </div>
            </div>
            <div class=" space-y-4 sm:space-y-6">
                <!-- Select All -->
                <div class="flex items-center gap-2 mb-5 mt-5">
                    <input type="checkbox" id="selectAll" class="w-4 h-4 border-gray-300 rounded">
                    <label for="selectAll" class="text-sm text-gray-700">
                        Select All <span id="selectedCount" class="text-gray-500">0 of 3 selected</span>
                    </label>
                </div>
                <!-- Accordion -->
                <!-- <div class="bg-white border rounded-lg shadow-sm">
                    <button class="accordion-btn w-full flex justify-between items-center p-4 text-left font-semibold text-gray-900 hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <svg class="accordion-arrow w-5 h-5 text-gray-600 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            <span>Booking & Reservations</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">2 FAQs</span>
                        </div>
                    </button>
                    <div class="accordion-content border-t divide-y hidden px-6 mb-5">

                        <div id="faqItem" class="faq-item p-4 flex flex-wrap justify-between items-start sm:items-center gap-2 sm:gap-4 border rounded-lg p-4 cursor-move hover:shadow-md transition-shadow border-gray-200 mt-5" draggable="true">
                            <div class="flex items-start gap-3">
                                <input type="checkbox" class="faq-checkbox mt-1 w-4 h-4 border-gray-300 rounded">
                                <span class="drag-handle text-gray-400 select-none">⋮⋮</span>
                                <div>
                                    <h3 class="font-medium text-gray-900">How do I make a reservation?</h3>
                                    <p class="text-gray-600 text-sm">Making a reservation is simple! Browse our available properties, select your dates, and click “Book Now”.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-md">Active</span>
                                <button class="p-1 text-gray-400 hover:text-gray-600 transition-colors" title="Settings">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings w-4 h-4">
                                        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                                <button id="editBtn" class="p-1 text-gray-400 hover:text-blue-600 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-4 h-4">
                                        <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                        <line x1="2" x2="22" y1="10" y2="10"></line>
                                    </svg>
                                </button>
                                <button class="p-1 text-gray-400 hover:text-red-600 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2 w-4 h-4">
                                        <path d="M3 6h18"></path>
                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                        <line x1="10" x2="10" y1="11" y2="17"></line>
                                        <line x1="14" x2="14" y1="11" y2="17"></line>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div id="editForm" class="hidden bg-white border border-gray-200 rounded-lg shadow p-4 sm:p-6 mt-4 space-y-5">
                            <div class="flex items-center"><input type="checkbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded mr-3"></div>
                            <div>
                                <input id="questionInput" type="text" value="How do I make a reservation?"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            </div>

                            <div>
                                <textarea id="answerInput" rows="5"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">Making a reservation is simple! Browse our available properties, select your dates, and click Book Now.</textarea>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <input id="activeCheck" type="checkbox" checked class="w-4 h-4 border-gray-300 rounded text-blue-600">
                                    <label for="activeCheck" class="text-sm font-medium text-gray-700">Active</label>
                                </div>
                                <div class="flex space-x-2">
                                    <button id="saveBtn" class="inline-flex items-center rounded-lg px-4 py-2 font-medium border border-transparent text-sm text-white bg-green-600 hover:bg-green-700 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-save w-3 h-3 mr-1">
                                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                            <polyline points="7 3 7 8 15 8"></polyline>
                                        </svg>
                                        Save
                                    </button>
                                    <button id="cancelBtn" class="inline-flex items-center rounded-lg px-4 py-2 font-medium border border-gray-300 text-sm  text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x w-3 h-3 mr-1">
                                            <path d="M18 6 6 18"></path>
                                            <path d="m6 6 12 12"></path>
                                        </svg>
                                        Cancel
                                    </button>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Related Questions</h3>
                                <p class="text-sm text-gray-500 mb-2">Select questions that are related to this FAQ</p>
                                <select id="relatedSelectfaq" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm mb-3 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Related Questions</option>
                                    <option value="booking">Booking & Reservations</option>
                                    <option value="property">Property Information</option>
                                </select>
                                <div id="relatedQuestionsfaq" class="space-y-2 text-sm max-h-32 overflow-y-auto border border-gray-300 rounded-md p-2 "></div>
                            </div>
                        </div>

                    </div>
                </div> -->



                @foreach($categories as $category)
                <div class="bg-white border rounded-lg shadow-sm mb-5">
                    <button class="accordion-btn w-full flex justify-between items-center p-4 text-left font-semibold text-gray-900 hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <svg class="accordion-arrow w-5 h-5 text-gray-600 transition-transform duration-200" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                            <span>{{ $category->category_name }}</span>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $category->questions->count() }} FAQs
                            </span>
                        </div>
                    </button>

                    <div class="accordion-content border-t divide-y hidden px-6 mb-5">
                        @forelse($category->questions as $question)
                        <div
                            class="faq-item p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4 border rounded-lg cursor-move hover:shadow-md transition-shadow border-gray-200 mt-5"
                            draggable="true" data-id="{{ $question->id }}">
                            <div class="flex items-start gap-3">
                                <input type="checkbox" class="faq-checkbox mt-1 w-4 h-4 border-gray-300 rounded">
                                <span class="drag-handle text-gray-400 select-none">⋮⋮</span>
                                <div>
                                    <h3 class="font-medium text-gray-900">{{ $question->question_name }}</h3>
                                    <p class="text-gray-600 text-sm">{{ $question->answer }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 w-full sm:w-auto justify-end sm:justify-start">
                                <span
                                    class="status-label bg-{{ $question->status === 'Active' ? 'green' : 'gray' }}-100 text-{{ $question->status === 'Active' ? 'green' : 'gray' }}-800 text-xs font-medium px-2 py-1 rounded-md">
                                    {{ $question->status }}
                                </span>

                                <button class="edit-btn p-1 text-gray-400 hover:text-blue-600 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-4 h-4">
                                        <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                        <line x1="2" x2="22" y1="10" y2="10"></line>
                                    </svg>
                                </button>

                                <form action="{{ route('admin.website-management.faq-page.faq-question.delete', $question->unique_id) }}" method="POST"
                                    class="delete-question-form" data-faq-question-name="{{ $question->question_name }}">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class=" delete-btn  p-1 text-gray-400 hover:text-red-600 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2 w-4 h-4">
                                            <path d="M3 6h18"></path>
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                            <line x1="10" x2="10" y1="11" y2="17"></line>
                                            <line x1="14" x2="14" y1="11" y2="17"></line>
                                        </svg>
                                    </button>

                                </form>

                            </div>
                        </div>

                        <!-- Edit Form (Hidden) -->
                        <div
                            class="edit-form hidden bg-white border border-gray-200 rounded-lg shadow p-4 sm:p-6 mt-4 space-y-5">
                            <div class="flex items-center"><input type="checkbox"
                                    class="active-check h-4 w-4 text-blue-600 border-gray-300 rounded mr-3"></div>
                            <div>
                                <input type="hidden" class="faq-unique-id" value="{{ $question->unique_id }}">

                                <input type="text" value="{{ $question->question_name }}"
                                    class="question-input w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            </div>

                            <div>
                                <textarea rows="5"
                                    class="answer-input w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ $question->answer }}</textarea>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" class="active-check w-4 h-4 border-gray-300 rounded text-blue-600"
                                        {{ $question->status === 'Active' ? 'checked' : '' }}>
                                    <label class="text-sm font-medium text-gray-700">Active</label>
                                </div>
                                <div class="flex space-x-2">
                                    <button
                                        class="save-btn inline-flex items-center rounded-lg px-4 py-2 font-medium border border-transparent text-sm text-white bg-green-600 hover:bg-green-700 transition-colors">
                                        Save
                                    </button>
                                    <button
                                        class="cancel-btn inline-flex items-center rounded-lg px-4 py-2 font-medium border border-gray-300 text-sm  text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                        Cancel
                                    </button>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Related Questions</h3>
                                <p class="text-sm text-gray-500 mb-2">Select questions that are related to this FAQ</p>
                                <select class="related-question-select w-full border border-gray-300 rounded-md px-3 py-2 text-sm mb-3 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Related Question</option>
                                    @foreach($relatedCategory as $related)
                                    @if($related->id !== $question->id)
                                    <option value="{{ $related->id }}" {{ $question->related_question_id == $related->id ? 'selected' : '' }}>
                                        {{ $related->question_name }}
                                    </option>
                                    @endif
                                    @endforeach
                                </select>

                                <!-- <div id="relatedQuestionsfaq" class="space-y-2 text-sm max-h-32 overflow-y-auto border border-gray-300 rounded-md p-2 "></div> -->
                            </div>

                        </div>

                        @empty
                        <p class="text-gray-500 text-sm mt-3">No questions found in this category.</p>
                        @endforelse
                    </div>
                </div>
                @endforeach


            </div>
        </div>

        <div id="categories" class="tab-content {{ $activeTab === 'categories' ? 'block' : 'hidden' }}">

            @include('admin.website_management.faq_page.partials._tab_categories')
        </div>

        <div id="analytics" class="tab-content {{ $activeTab === 'analytics' ? 'block' : 'hidden' }}">

            <div class="max-w-7xl mx-auto space-y-6">
                <!-- Header -->
                <div class="flex flex-wrap justify-between items-center">
                    <h1 class="text-xl font-semibold text-gray-900">Analytics Dashboard</h1>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bar-chart3 w-5 h-5 text-gray-400">
                            <path d="M3 3v18h18"></path>
                            <path d="M18 17V9"></path>
                            <path d="M13 17V5"></path>
                            <path d="M8 17v-3"></path>
                        </svg>
                        Real-time insights
                    </div>
                </div>
                <!-- Top Metrics -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Card 1 -->
                    <div class="bg-white p-5 rounded-lg shadow border border-gray-200 flex items-center gap-4">
                        <div class="p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye h-8 w-8 text-blue-600">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Views</p>
                            <p class="text-2xl font-semibold text-gray-900">3</p>
                        </div>
                    </div>
                    <!-- Card 2 -->
                    <div class="bg-white p-5 rounded-lg shadow border border-gray-200 flex items-center gap-4">
                        <div class="p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search h-8 w-8 text-green-600">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.3-4.3"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Searches</p>
                            <p class="text-2xl font-semibold text-gray-900">0</p>
                        </div>
                    </div>
                    <!-- Card 3 -->
                    <div class="bg-white p-5 rounded-lg shadow border border-gray-200 flex items-center gap-4">
                        <div class=" p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bar-chart3 h-8 w-8 text-purple-600">
                                <path d="M3 3v18h18"></path>
                                <path d="M18 17V9"></path>
                                <path d="M13 17V5"></path>
                                <path d="M8 17v-3"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Active FAQs</p>
                            <p class="text-2xl font-semibold text-gray-900">3</p>
                        </div>
                    </div>
                    <!-- Card 4 -->
                    <div class="bg-white p-5 rounded-lg shadow border border-gray-200 flex items-center gap-4">
                        <div class=" p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up h-8 w-8 text-orange-600">
                                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                                <polyline points="16 7 22 7 22 13"></polyline>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Categories</p>
                            <p class="text-2xl font-semibold text-gray-900">2</p>
                        </div>
                    </div>
                </div>
                <!-- Middle Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Top Search Terms -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Top Search Terms</h3>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-500 text-center py-4">No search data yet</p>
                        </div>
                    </div>
                    <!-- Most Viewed FAQs -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Most Viewed FAQs</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium text-gray-500 w-6">#1</span>
                                        <span class="text-sm text-gray-900 ml-3 truncate max-w-xs">Can I modify or cancel my reservation?</span>
                                    </div>
                                    <span class="text-sm text-gray-500">3 views</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Bottom Section -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">FAQ Helpfulness Ratings</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-500 text-center py-4">No rating data yet</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="settings" class="tab-content {{ $activeTab === 'settings' ? 'block' : 'hidden' }}">

            <div class="mx-auto space-y-6">
                <!-- Page Title -->
                <h1 class="text-xl font-semibold text-gray-900">Settings</h1>
                <!-- Settings Card -->
                <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-5 sm:p-6">
                    <h2 class="text-lg sm:text-xl font-medium text-gray-900 mb-5">FAQ Display Settings</h2>
                    <!-- Settings List -->
                    <div class="space-y-5">
                        <!-- Option 1 -->
                        <div class="flex justify-between items-start sm:items-center flex-wrap sm:flex-nowrap gap-3">
                            <div>
                                <p class="font-medium text-gray-900">Show search box</p>
                                <p class="text-gray-500 text-sm">Allow users to search through FAQs</p>
                            </div>
                            <input type="checkbox" checked class="w-4 h-4 accent-blue-600 cursor-pointer">
                        </div>
                        <!-- Option 2 -->
                        <div class="flex justify-between items-start sm:items-center flex-wrap sm:flex-nowrap gap-3">
                            <div>
                                <p class="font-medium text-gray-900">Auto-expand first category</p>
                                <p class="text-gray-500 text-sm">Automatically expand the first FAQ category</p>
                            </div>
                            <input type="checkbox" class="w-4 h-4 accent-blue-600 cursor-pointer">
                        </div>
                        <!-- Option 3 -->
                        <div class="flex justify-between items-start sm:items-center flex-wrap sm:flex-nowrap gap-3">
                            <div>
                                <p class="font-medium text-gray-900">Auto-expand all categories</p>
                                <p class="text-gray-500 text-sm">Automatically expand all FAQ categories</p>
                            </div>
                            <input type="checkbox" checked class="w-4 h-4 accent-blue-600 cursor-pointer">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="NewFAQModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-xl flex flex-col max-h-full overflow-hidden border border-gray-200">

            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <h2 class="text-lg font-semibold text-gray-900">Add New FAQ</h2>
                </div>
                <button onclick="closeNewFAQModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <!-- Scrollable Content -->
            <div class=" overflow-y-auto max-h-[70vh]">
                <!-- {{-- Form --}} -->
                {{ html()->form('POST', route('admin.website-management.faq-page.faq-question.store'))
                ->id('questionForm')
                ->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => ''
                ])
                ->acceptsFiles()
                ->open() }}


                <input type="hidden" name="options" id="optionsInput">
                <div class="mx-auto bg-white rounded-lg shadow p-6 space-y-5">
                    <div class="mb-3">
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        {!! html()->select(
                        'category',
                        ['' => '-- Select Category --'] + $categories->pluck('category_name', 'id')->toArray()
                        )
                        ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500')
                        ->attributes(['id' => 'category_id'])
                        ->required()
                        !!}

                    </div>

                    <div class="mb-3">
                        <label for="question" class="block text-sm font-medium text-gray-700 mb-1">Question</label>
                        <!-- <input id="question" type="text" placeholder="Enter FAQ question"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" /> -->
                        {!! html()->text('question')
                        ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm')
                        ->attributes(['id' => 'question', 'placeholder' => 'Enter FAQ question'])
                        ->required() !!}
                    </div>

                    <div class="mb-3">
                        <label for="answer" class="block text-sm font-medium text-gray-700 mb-1">Answer</label>
                        <!-- <textarea id="answer" placeholder="Enter FAQ answer" rows="5"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"></textarea> -->
                        {!! html()->textarea('answer')
                        ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500')
                        ->attributes(['id' => 'answer', 'placeholder' => 'Enter FAQ answer', 'rows' => '5'])
                        ->required() !!}
                    </div>

                    <div class="flex items-center space-x-2 mb-3">
                        <!-- <input id="active" type="checkbox" checked class="w-4 h-4 border-gray-300 rounded" /> -->
                        {!! html()->checkbox('active', true)
                        ->class('w-4 h-4 border-gray-300 rounded focus:ring-blue-500')
                        ->attributes(['id' => 'active']) !!}
                        <label for="active" class="text-sm font-medium text-gray-700">Active</label>
                    </div>

                    <div class="mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">Related Questions</h3>
                        <p class="text-xs text-gray-500 mb-2">Select questions that are related to this FAQ</p>

                        {!! html()->select(
                        'relatedCategory',
                        ['' => '-- Select Question --'] + $relatedCategory->pluck('question_name', 'id')->toArray()
                        )
                        ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500')
                        ->attributes(['id' => 'relatedCategory'])
                        !!}

                        <div id="relatedQuestions" class="space-y-2 text-sm">
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-4 pb-4 px-4 border-t border-gray-200">
                <button type="button" onclick="closeNewFAQModal()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white">Close</button>
                <button type="submit" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600">Save Question</button>
            </div>
            {{ html()->form()->close() }}
        </div>
    </div>
</div>


<form id="bulkDeleteForm"
    action="{{ route('admin.website-management.faq-page.faq-question.bulk-delete') }}"
    method="POST" class="hidden">
    @csrf

    <input type="hidden" name="ids" id="bulkDeleteIds">
</form>



@endsection

@push('js')

<script>
    const fileInput = document.getElementById("iconUpload");
    const previewContainer = document.getElementById("previewContainer");
    const previewImage = document.getElementById("previewImage");
    const removeIcon = document.getElementById("removeIcon");

    // Handle upload
    fileInput.addEventListener("change", (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                previewImage.src = event.target.result;
                previewContainer.classList.remove("hidden");
            };
            reader.readAsDataURL(file);
        }
    });

    // Remove image
    removeIcon.addEventListener("click", () => {
        previewContainer.classList.add("hidden");
        previewImage.src = "";
        fileInput.value = "";
    });
</script>
<!-- Tab 1 script  -->
<script>
    // --- Related Questions ---
    const relatedData = {
        booking: [
            "Can I modify or cancel my reservation?",

        ],
        property: [
            "What amenities are included?",
        ],
        all: [
            "Can I modify or cancel my reservation?",
            "What amenities are included?",
        ]
    };

    const relatedSelectfaq = document.getElementById('relatedSelectfaq');
    const relatedQuestionsfaq = document.getElementById('relatedQuestionsfaq');

    function renderRelated(category) {
        const list = category && relatedData[category] ? relatedData[category] : relatedData.all;
        relatedQuestionsfaq.innerHTML = list.map(q => `
        <div class="flex items-center space-x-2">
          <input type="checkbox" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
          <label class="text-gray-700">${q}</label>
        </div>
      `).join('');
    }
    relatedSelectfaq.addEventListener('change', e => renderRelated(e.target.value));
    renderRelated();
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        console.log(' FAQ Edit Script Loaded');

        const token = document.querySelector('meta[name="csrf-token"]')?.content;

        document.querySelectorAll('.faq-item').forEach((faqItem) => {
            const editBtn = faqItem.querySelector('.edit-btn');
            if (!editBtn) return;

            let editForm = faqItem.nextElementSibling;
            while (editForm && !editForm.classList.contains('edit-form')) {
                editForm = editForm.nextElementSibling;
            }
            if (!editForm) return;

            const saveBtn = editForm.querySelector('.save-btn');
            const cancelBtn = editForm.querySelector('.cancel-btn');

            editBtn.addEventListener('click', () => {
                document.querySelectorAll('.edit-form').forEach(f => f.classList.add('hidden'));
                document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('hidden'));
                faqItem.classList.add('hidden');
                editForm.classList.remove('hidden');
            });

            cancelBtn.addEventListener('click', () => {
                editForm.classList.add('hidden');
                faqItem.classList.remove('hidden');
            });

            saveBtn.addEventListener('click', async () => {

                const relatedSelect = editForm.querySelector('.related-question-select');
                const related_question_id = relatedSelect?.value || null;


                const uniqueId = editForm.querySelector('.faq-unique-id').value;
                const questionInput = editForm.querySelector('.question-input');
                const answerInput = editForm.querySelector('.answer-input');
                const activeCheck = editForm.querySelectorAll('.active-check')[1];

                const question = questionInput.value.trim();
                const answer = answerInput.value.trim();
                const status = activeCheck.checked ? 'Active' : 'Inactive';

                console.log(` Saving FAQ ${uniqueId}`, {
                    question,
                    answer,
                    status
                });
                const faqUpdateRoute = "{{ route('admin.website-management.faq-page.faq-question.update', ['unique_id' => '__ID__']) }}";


                try {
                    const url = faqUpdateRoute.replace('__ID__', uniqueId);

                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            question_name: question,
                            answer: answer,
                            status: status,
                            related_question_id: related_question_id
                        })
                    });

                    const data = await res.json();

                    if (data.success) {
                        console.log(` FAQ ${uniqueId} updated successfully`);

                        faqItem.querySelector('h3').textContent = question;
                        faqItem.querySelector('p').textContent = answer;

                        const statusLabel = faqItem.querySelector('.status-label');
                        if (status === 'Active') {
                            statusLabel.textContent = 'Active';
                            statusLabel.className = 'status-label bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-md';
                        } else {
                            statusLabel.textContent = 'Inactive';
                            statusLabel.className = 'status-label bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded-md';
                        }

                        editForm.classList.add('hidden');
                        faqItem.classList.remove('hidden');

                        notyf.success(' FAQ updated successfully!');
                    } else {
                        console.error(data.message);
                        notyf.error(' Update failed. Please try again.');
                    }
                } catch (error) {
                    console.error('❌ Error updating FAQ:', error);
                    notyf.error('❌ Something went wrong.');
                }
            });
        });
    });
</script>



<script>
    function openNewFAQModal() {
        document.getElementById('NewFAQModal').classList.remove('hidden');
        renderOptions();
    }

    function closeNewFAQModal() {
        document.getElementById('NewFAQModal').classList.add('hidden');
    }
</script>

<script>
    const relatedData = {
        booking: [
            "Can I modify or cancel my reservation?",
            "How do I make a reservation?",
        ],
        property: [
            "What amenities are included?",
        ],
        all: [
            "Can I modify or cancel my reservation?",
            "How do I make a reservation?",
            "What amenities are included?",
        ],
    };

    const relatedCategory = document.getElementById("relatedCategory");
    const relatedQuestions = document.getElementById("relatedQuestions");

    function renderQuestions(category) {
        const questions = category && relatedData[category] ? relatedData[category] : relatedData.all;
        relatedQuestions.innerHTML = "";
        questions.forEach((q) => {
            const div = document.createElement("div");
            div.className = "flex items-center space-x-2";
            div.innerHTML = `
          <input type="checkbox" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" id="${q}">
          <label for="${q}" class="text-gray-700">${q}</label>
        `;
            relatedQuestions.appendChild(div);
        });
    }

    relatedCategory.addEventListener("change", (e) => {
        renderQuestions(e.target.value);
    });

    // Load default "All Categories" on start
    renderQuestions("");
</script>


<script>
    const tabs = document.querySelectorAll('.tab-link');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // reset styles
            tabs.forEach(t => {
                t.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
                t.classList.add('text-gray-600');
            });
            contents.forEach(c => c.classList.add('hidden'));

            // set active styles (no `.active` class used)
            tab.classList.remove('text-gray-600');
            tab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
            document.getElementById(tab.dataset.tab).classList.remove('hidden');
        });
    });
</script>


<script>
    // Accordion toggle
    // const accordionBtn = document.querySelector('.accordion-btn');
    // const accordionContent = document.querySelector('.accordion-content');
    // const accordionArrow = document.querySelector('.accordion-arrow');

    // accordionBtn.addEventListener('click', () => {
    //     accordionContent.classList.toggle('hidden');
    //     accordionArrow.classList.toggle('rotate-180');
    // });
    document.addEventListener('DOMContentLoaded', () => {
        // Select ALL accordions, not just the first one
        const accordions = document.querySelectorAll('.accordion-btn');

        accordions.forEach(btn => {
            btn.addEventListener('click', () => {
                const content = btn.nextElementSibling; // Find content after button
                const arrow = btn.querySelector('.accordion-arrow');

                // Toggle only this accordion
                content.classList.toggle('hidden');
                arrow.classList.toggle('rotate-180');
            });
        });
    });

    // Checkbox counting
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.faq-checkbox');
    const selectedCount = document.getElementById('selectedCount');
    const applyButton = document.getElementById('applyButton');

    function updateCount() {
        const selected = document.querySelectorAll('.faq-checkbox:checked').length;
        selectedCount.textContent = `${selected} of ${checkboxes.length} selected`;
        applyButton.textContent = `Apply (${selected})`;
        applyButton.disabled = selected === 0;
        applyButton.className = selected ?
            'bg-blue-600 text-white px-4 py-2 rounded-md text-sm' :
            'bg-gray-400 text-white px-4 py-2 rounded-md text-sm';
        selectAll.checked = selected === checkboxes.length;
    }

    selectAll.addEventListener('change', e => {
        checkboxes.forEach(cb => cb.checked = e.target.checked);
        updateCount();
    });

    checkboxes.forEach(cb => cb.addEventListener('change', updateCount));


    // Drag & drop sorting
    const faqItems = document.querySelectorAll('.faq-item');
    let draggedItem = null;

    faqItems.forEach(item => {
        item.addEventListener('dragstart', () => {
            draggedItem = item;
            setTimeout(() => item.classList.add('hidden'), 0);
        });
        item.addEventListener('dragend', () => {
            item.classList.remove('hidden');
            draggedItem = null;
        });
        item.addEventListener('dragover', e => {
            e.preventDefault();
            item.classList.add('drag-over');
        });
        item.addEventListener('dragleave', () => item.classList.remove('drag-over'));
        item.addEventListener('drop', e => {
            e.preventDefault();
            item.classList.remove('drag-over');
            const parent = item.parentNode;
            const items = Array.from(parent.querySelectorAll('.faq-item'));
            const draggedIndex = items.indexOf(draggedItem);
            const droppedIndex = items.indexOf(item);
            if (draggedIndex < droppedIndex) {
                parent.insertBefore(draggedItem, item.nextSibling);
            } else {
                parent.insertBefore(draggedItem, item);
            }
        });
    });
</script>

<!-- delete- -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.delete-question-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // stop auto submit

                const templateName = form.getAttribute('data-faq-question-name') || 'this item';

                window.showConfirm(
                    `Delete "${templateName}"? This action cannot be undone!`,
                    'Delete item'
                ).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>

<!-- delete- -->

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const bulkSelect = document.querySelector(".bulk-action-select");
        const applyBtn = document.getElementById("applyButton");

        applyBtn.addEventListener("click", function() {

            const action = bulkSelect.value;

            if (action !== "Delete") return;

            const selectedCheckboxes = [...document.querySelectorAll(".faq-checkbox:checked")];

            if (selectedCheckboxes.length === 0) return;

            const ids = selectedCheckboxes.map(cb =>
                cb.closest(".faq-item").getAttribute("data-id")
            );

            // Confirm delete
            window.showConfirm(
                `Delete ${ids.length} selected FAQ questions? This action cannot be undone!`,
                "Bulk Delete"
            ).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById("bulkDeleteIds").value = JSON.stringify(ids);
                    document.getElementById("bulkDeleteForm").submit();
                }
            });

        });

    });
</script>


@endpush