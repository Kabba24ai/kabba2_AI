@extends('admin.layouts.app')

@section('title', 'Rental Ready Admin')

@push('css')

@endpush

@section('content')

@include('flash::message')
@include('admin.partials.formErrors')


<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

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
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                    <path d="M3 5V19A9 3 0 0 0 21 19V5"></path>
                    <path d="M3 12A9 3 0 0 0 21 12"></path>
                </svg>
                <h4 class="text-lg font-semibold text-gray-900">Question & Categories Mgt</h4>
            </div>
            <p class="text-sm text-gray-600 mb-4">Create and manage inspection questions organized by categories.</p>
            <button onclick="showTab('questions', document.getElementById('tab-questions'))" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium w-full">Manage Questions & Categories</button>
        </div>

        <!-- Card 2 -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm flex flex-col justify-between">
            <div class="flex items-start gap-4 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                    <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                    <path d="M10 9H8"></path>
                    <path d="M16 13H8"></path>
                    <path d="M16 17H8"></path>
                </svg>
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
        <div class="w-full bg-white border border-gray-200 rounded-lg shadow-sm mb-6">
            <div class="flex gap-6 border-b" data-tab-group="inner">
                <button id="tab-questions-sub" onclick="showTab('questionssub', this)"
                    class="inner-tab px-3 py-3 text-sm font-medium text-blue-600 border-b-2 border-blue-600">
                    Questions ({{ $totalQuestions }})

                </button>
                <button id="tab-categories-sub" onclick="showTab('categoriessub', this)"
                    class="inner-tab px-3 py-3 text-sm font-medium text-gray-600 hover:text-blue-600 border-b-2 border-transparent">
                    Questions Categories ({{ $rentalreadycategory->count() }})
                </button>
            </div>


            @include('admin.checklist_management.rental_ready.partials._sub_tab_questions')

            @include('admin.checklist_management.rental_ready.partials._sub_tab_categories')

        </div>
    </div>

    @include('admin.checklist_management.rental_ready.partials._tab_templates')

</div>





@endsection

@push('js')



<script>
    document.addEventListener("DOMContentLoaded", function() {
        let activeTab = @json(session('active_tab', 'overview'));
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
    function attachValidatedSubmit(formId, btnId, btnTextId, spinnerId, loadingText) {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', function(e) {
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
    document.addEventListener("DOMContentLoaded", function() {
        attachValidatedSubmit(
            'categoryForm',
            'submitCategoryBtn',
            'categoryBtnText',
            'categoryBtnSpinner',
            'Saving...'
        );
    });
</script>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.querySelector('input[placeholder="Search Templates..."]');
        const cards = document.querySelectorAll(".question-cardtemp");
        const wrapper = document.getElementById("cardsWrapper");

        function filterCards() {
            // Add loading effect
            wrapper.classList.add('opacity-50', 'pointer-events-none');

            const searchText = searchInput.value.toLowerCase();
           
            setTimeout(() => {
                cards.forEach(card => {
                    const title = card.querySelector("h3").textContent.toLowerCase();
                    const cardCategoryId = card.getAttribute("data-category-id");

                    let matchesSearch = !searchText || title.includes(searchText);
                 
                    if (matchesSearch) {
                        card.style.display = "block";
                    } else {
                        card.style.display = "none";
                    }
                });

                // Remove loading effect
                wrapper.classList.remove('opacity-50', 'pointer-events-none');
            }, 150); // simulate small delay for UX
        }

        searchInput.addEventListener("input", filterCards);
      
    });
</script>


@endpush