@extends('admin.layouts.app')

@section('title', 'Customers')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Checklist Templates</h2>
            <p class="text-gray-600">Create and manage checklist templates for different equipment categories</p>
        </div>
        <a href="javascript:void(0)" id="openTemplatesModal"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
            + New Template
        </a>
    </div>

    
    <!-- Parent wrapper -->
    <div x-data="{ globalShowAnswers: false }" class="space-y-6">

        <!-- Top filters and toggle button -->
        <div class="bg-white border border-gray-200 rounded-xl p-6 space-y-4 shadow-sm">
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

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Question Visibility</label>
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
                        <span x-text="globalShowAnswers ? 'Hide All Questions' : 'Show All Questions'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- QUESTION CARD START -->
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm"
            x-data="{ showAnswers: false }"
            x-init="$watch('globalShowAnswers', value => showAnswers = value)">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between items-start">
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
                    <button @click="showAnswers = !showAnswers" class="text-blue-600 hover:underline flex items-center gap-1">
                        <!-- Right-side Arrow Icon -->
                        <svg class="w-4 h-4 transform transition-transform duration-200"
                            :class="showAnswers ? 'rotate-90' : 'rotate-0'"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>

                        <!-- Text -->
                        <span x-text="showAnswers ? 'Hide Questions' : 'Show Questions (12)'"></span>
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

            <!-- Answers -->
            <div x-show="showAnswers" x-transition>
                <div class="border-t border-gray-300 mt-5 mb-5"></div>
                <h4 class="text-sm font-semibold text-gray-800">Questions in Template:</h4>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">1</span>
                    <span class="flex-1 font-medium">Safety Equipment Present</span>
                    <span class="text-xs text-gray-500">Safety</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">2</span>
                    <span class="flex-1 font-medium">Warning Labels Visible</span>
                    <span class="text-xs text-gray-500">Safety</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">3</span>
                    <span class="flex-1 font-medium">Engine Oil Level</span>
                    <span class="text-xs text-gray-500">Engine</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">4</span>
                    <span class="flex-1 font-medium">Coolant Level</span>
                    <span class="text-xs text-gray-500">Engine</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">5</span>
                    <span class="flex-1 font-medium">Hydraulic Fluid Level</span>
                    <span class="text-xs text-gray-500">Hydraulics</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">6</span>
                    <span class="flex-1 font-medium">Hydraulic Hoses Condition</span>
                    <span class="text-xs text-gray-500">Hydraulics</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">7</span>
                    <span class="flex-1 font-medium">Fuel Level</span>
                    <span class="text-xs text-gray-500">Fuel</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">8</span>
                    <span class="flex-1 font-medium">Battery Condition</span>
                    <span class="text-xs text-gray-500">Electrical</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">9</span>
                    <span class="flex-1 font-medium">Lights Functioning</span>
                    <span class="text-xs text-gray-500">Electrical</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">10</span>
                    <span class="flex-1 font-medium">Track Condition</span>
                    <span class="text-xs text-gray-500">Tracks</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">11</span>
                    <span class="flex-1 font-medium">Bucket/Attachment Condition</span>
                    <span class="text-xs text-gray-500">Attachments</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">12</span>
                    <span class="flex-1 font-medium">Overall Cleanliness</span>
                    <span class="text-xs text-gray-500">General</span>
                </div>
            </div>
        </div>
        <!-- QUESTION CARD END -->

        <!-- QUESTION CARD START -->
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm"
            x-data="{ showAnswers: false }"
            x-init="$watch('globalShowAnswers', value => showAnswers = value)">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between items-start">
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
                    <button @click="showAnswers = !showAnswers" class="text-blue-600 hover:underline flex items-center gap-1">
                        <!-- Right-side Arrow Icon -->
                        <svg class="w-4 h-4 transform transition-transform duration-200"
                            :class="showAnswers ? 'rotate-90' : 'rotate-0'"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>

                        <!-- Text -->
                        <span x-text="showAnswers ? 'Hide Questions' : 'Show Questions (10)'"></span>
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

            <!-- Answers -->
            <div x-show="showAnswers" x-transition>
                <div class="border-t border-gray-300 mt-5 mb-5"></div>
                <h4 class="text-sm font-semibold text-gray-800">Questions in Template:</h4>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">1</span>
                    <span class="flex-1 font-medium">Safety Equipment Present</span>
                    <span class="text-xs text-gray-500">Safety</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">2</span>
                    <span class="flex-1 font-medium">Warning Labels Visible</span>
                    <span class="text-xs text-gray-500">Safety</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">3</span>
                    <span class="flex-1 font-medium">Engine Oil Level</span>
                    <span class="text-xs text-gray-500">Engine</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">4</span>
                    <span class="flex-1 font-medium">Coolant Level</span>
                    <span class="text-xs text-gray-500">Engine</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">5</span>
                    <span class="flex-1 font-medium">Hydraulic Fluid Level</span>
                    <span class="text-xs text-gray-500">Hydraulics</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">6</span>
                    <span class="flex-1 font-medium">Hydraulic Hoses Condition</span>
                    <span class="text-xs text-gray-500">Hydraulics</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">7</span>
                    <span class="flex-1 font-medium">Fuel Level</span>
                    <span class="text-xs text-gray-500">Fuel</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">8</span>
                    <span class="flex-1 font-medium">Battery Condition</span>
                    <span class="text-xs text-gray-500">Electrical</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">9</span>
                    <span class="flex-1 font-medium">Lights Functioning</span>
                    <span class="text-xs text-gray-500">Electrical</span>
                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium">Required</span>
                </div>
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg text-sm">
                    <span class="w-7 h-7 flex items-center justify-center bg-gray-200 rounded-full text-sm font-bold">10</span>
                    <span class="flex-1 font-medium">Overall Cleanliness</span>
                    <span class="text-xs text-gray-500">General</span>
                </div>
            </div>
        </div>
        <!-- QUESTION CARD END -->

    </div>



<div x-data="{ showTemplatesModal: false }" x-ref="templatesRoot" x-init="window.addEventListener('open-templates-modal', () => showTemplatesModal = true)">
    <!-- Modal -->  
    <div
        x-show="showTemplatesModal"
        x-transition
        x-cloak
        class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
        <div class="modal-scrollable w-full mx-auto">
            <div
                @click.away="showTemplatesModal = false"
                class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full mx-auto  max-w-5xl space-y-5 border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-full" >
                <div class="flex justify-between items-center px-6 pt-4 ">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">New Templates</h3>
                    <button @click="showTemplatesModal = false" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
                </div>

                <div x-data="questionBuilder" class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6 bg-white overflow-y-auto">
                    
                    <!-- Template Info -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Template Name *</label>
                            <input type="text" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Equipment Category *</label>
                            <select class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option>Compact Equipment</option>
                                <option>Heavy Equipment</option>
                            </select>
                        </div>

                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 mt-2">
                        <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600" />
                        Active Template
                        </label>
                    </div>

                    <!-- Available Questions -->
                    <div class="space-y-4">
                        <h3 class="text-base font-semibold text-gray-800">Available Questions</h3>
                        <input type="text" placeholder="Search questions..." class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">

                        <select class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option>All Categories</option>
                        </select>

                        <div id="available" class="space-y-2 min-h-[300px]">
                            <template x-for="q in availableQuestions" :key="q.id">
                                <div class="border border-gray-200 rounded-lg p-3 bg-white flex justify-between items-center cursor-move drag-handle">
                                    <div>
                                        <p class="font-medium text-sm text-gray-900" x-text="q.text"></p>
                                        <p class="text-xs text-gray-500" x-text="q.category"></p>
                                    </div>
                                    <span class="bg-red-100 text-red-600 text-xs font-medium px-2 py-0.5 rounded-full" x-show="q.required">Required</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Template Questions -->
                    <div class="space-y-4">
                        <h3 class="text-base font-semibold text-gray-800">Template Questions (<span x-text="templateQuestions.length"></span>)</h3>
                        <div id="template" class="border-2 border-dashed border-gray-300 rounded-md min-h-[300px] p-4 text-sm text-gray-400">
                            <template x-if="templateQuestions.length === 0">
                                <p>Drag questions here to build your template</p>
                            </template>
                            <template x-for="q in templateQuestions" :key="q.id">
                                <div class="border border-blue-200 rounded-lg p-3 bg-blue-50 mb-2">
                                    <p class="text-sm font-medium text-gray-800" x-text="q.text"></p>
                                    <p class="text-xs text-gray-500" x-text="q.category"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 px-6 pb-4">
                    <button type="button" @click="showTemplatesModal = false" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white dark:bg-gray-700 dark:text-white">Cancel</button>
                    <button type="button" id="submitTemplatesBtn" class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-brand-600"> Save Templates </button>
                </div>
            </div>
        </div>
    </div>
</div>
    @endsection

@push('js')

<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('questionBuilder', () => ({
    availableQuestions: [
      { id: 1, text: 'Safety Equipment Present', category: 'Safety', required: true },
      { id: 2, text: 'Warning Labels Visible', category: 'Safety', required: true },
      { id: 3, text: 'Engine Oil Level', category: 'Engine', required: true },
      { id: 4, text: 'Coolant Level', category: 'Engine', required: true }
    ],
    templateQuestions: [],

    init() {
      const available = document.getElementById('available');
      const template = document.getElementById('template');

      Sortable.create(available, {
        group: {
          name: 'questions',
          pull: 'clone',
          put: false,
        },
        sort: false,
        animation: 150,
        handle: '.drag-handle',
      });

      Sortable.create(template, {
        group: {
          name: 'questions',
          pull: false,
          put: true,
        },
        animation: 150,
        onAdd: (evt) => {
          // Fix: Prevent duplication
          const draggedIndex = evt.oldIndex;
          const item = this.availableQuestions[draggedIndex];

          // Check if already added
          if (!this.templateQuestions.some(q => q.id === item.id)) {
            this.templateQuestions.push(item);
          }

          // Remove cloned DOM element inserted by Sortable
          evt.item.parentNode.removeChild(evt.item);
        },
      });
    }
  }));
});
</script>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('openTemplatesModal').addEventListener('click', () => {
            window.dispatchEvent(new CustomEvent('open-templates-modal'));
        });
    });
</script>
