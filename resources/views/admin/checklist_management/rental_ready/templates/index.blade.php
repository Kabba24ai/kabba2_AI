@extends('admin.layouts.app')

@section('title', 'Customers')

@push('css')

@endpush

@section('content')

    @include('flash::message')

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Checklist Templates</h2>
            <p class="text-gray-600">Create and manage checklist templates for different equipment categories</p>
        </div>
        <a href="javascript:void(0)" id="openTemplatesModal"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 mt-3">
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
            <div id="available" class="space-y-2 min-h-[300px] bg-gray-50 rounded-md"></div>
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
  
<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalWrapper = document.getElementById('templatesModalWrapper');
        const openBtn = document.getElementById('openTemplatesModal');
        const closeBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelBtn');

        openBtn.addEventListener('click', () => {
            modalWrapper.style.display = 'flex';
        });

        const closeModal = () => {
            modalWrapper.style.display = 'none';
        };

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // Optional: Close when clicking outside the modal
        modalWrapper.addEventListener('click', (e) => {
            if (e.target === modalWrapper) {
                closeModal();
            }
        });
    });
</script>

<!-- SortableJS CDN (required) -->


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
