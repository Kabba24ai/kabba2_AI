@extends('admin.layouts.app')

@section('title', 'Message Management')

@push('css')
@endpush

@section('content')

@include('flash::message')

<!-- MAIN CONTAINER -->
<div class="max-w-7xl mx-auto ">


<!-- Page Header -->
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

        <!-- Left: Title & Subtitle -->
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Message Management</h2>
            <p class="text-sm text-gray-600 mt-1">
                Manage your SMS and Email broadcast & funnel content messages.
            </p>
        </div>

    </div>
</div>

<!-- Outer Tabs -->
<div class="w-full rounded-lg mb-6">
<div class="flex flex-wrap sm:flex-nowrap gap-6" data-tab-group="main">

        <!-- Messages Main Tab -->
<button onclick="showTab('messages', this)" id="tab-messages"
    class="tab-button inline-flex items-center gap-2 text-blue-600 font-medium text-sm px-3 pb-2 border-b-2 border-blue-600 transition">
    
    <x-heroicon-o-chat-bubble-left-right class="w-5 h-5" />
    <span>Messages</span>
</button>

<!-- Categories Main Tab -->
<button onclick="showTab('categories', this)" id="tab-categories"
    class="tab-button inline-flex items-center gap-2 text-gray-600 font-medium text-sm px-3 pb-2   hover:text-blue-600 transition">
    
    <x-heroicon-o-rectangle-stack class="w-5 h-5" />
    <span>Categories</span>
</button>


    </div>
</div>




    <!-- ============================ -->
    <!--    MESSAGES SECTION          -->
    <!-- ============================ -->
    <div id="messages" class="tab-content" data-tab-group="main">
       
        <!-- Inner Tabs + Action Buttons -->
        <div class="w-full bg-white border rounded-xl shadow-sm mb-6 ">
            <div class="flex items-center justify-between gap-6 border-b p-4 " data-tab-group="inner-msg">

                <!-- Left: Tabs -->
                <div class="flex gap-6">
                    <button onclick="showTab('smsbroadcast', this)"
                        class="inner-tab inline-flex items-center px-3 pt-3 pb-2
                        text-sm font-medium text-blue-600 border-b-2 border-blue-600">
                        SMS Broadcast
                    </button>

                    <button onclick="showTab('smsfunnel', this)"
                        class="inner-tab inline-flex items-center px-3 pt-3 pb-2
                        text-sm font-medium text-gray-600 hover:text-blue-600">
                        SMS Funnel Content
                    </button>

                    <button onclick="showTab('emailbroadcast', this)"
                        class="inner-tab inline-flex items-center px-3 pt-3 pb-2
                        text-sm font-medium text-gray-600 hover:text-blue-600">
                        Email Broadcast
                    </button>

                    <button onclick="showTab('emailfunnel', this)"
                        class="inner-tab inline-flex items-center px-3 pt-3 pb-2
                        text-sm font-medium text-gray-600 hover:text-blue-600">
                        Email Funnel Content
                    </button>
                </div>

                <!-- Right: Action Buttons -->
           
                <div class="flex items-center gap-3">

                    <!-- ====================== -->
                    <!-- 1. SMS BROADCAST BUTTONS -->
                    <!-- ====================== -->
                    <div id="btn-smsbroadcast" class="tab-action hidden flex items-center gap-3">

                        <!-- Send New SMS Broadcast (Orange) -->
                        <a href="javascript:void(0)" onclick="openModal('send-new-sms-broadcast')"
                        class="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600
                                text-white text-sm font-medium px-6 py-3 text-md rounded-lg transition">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" 
                                fill="none" stroke="currentColor" stroke-width="2" 
                                stroke-linecap="round" stroke-linejoin="round" 
                                class="w-4 h-4">
                                <path d="M22 2L11 13" />
                                <path d="M22 2L15 22L11 13L2 9L22 2Z" />
                            </svg> 
                            Send New SMS Broadcast 
                        </a>

                        <!-- Create Messenger (Green) -->
                        <a href="javascript:void(0)"  onclick="openModal('new-smsbroadcast')"
                        class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700
                                text-white text-sm font-medium px-6 py-3 text-md rounded-lg transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Create New Messenger
                        </a>
                    </div>

                    <!-- ====================== -->
                    <!-- 2. SMS FUNNEL BUTTONS -->
                    <!-- ====================== -->
                    <div id="btn-smsfunnel" class="tab-action hidden flex items-center gap-3">

                       <!-- Send New SMS Broadcast (green) -->
                        <a href="javascript:void(0)" onclick="openModal('send-new-sms-funnel')"
                        class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600
                                text-white text-sm font-medium px-6 py-3 text-md rounded-lg transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
                                class="w-4 h-4 transform rotate-45">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 12l16.5-9-4.5 9 4.5 9-16.5-9z" />
                            </svg>
                            Send New SMS Broadcast
                        </a>

                        <!-- Create Messenger (Green) -->
                        <a href="javascript:void(0)" onclick="openModal('new-sms-funnel')"
                        class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700
                                text-white text-sm font-medium px-6 py-3 text-md rounded-lg transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Create New Messenger
                        </a>
                    </div>

                    <!-- ====================== -->
                    <!-- 3. EMAIL BROADCAST BUTTONS -->
                    <!-- ====================== -->
                    <div id="btn-emailbroadcast" class="tab-action hidden flex items-center gap-3">

                        <!-- Send New Email Broadcast (Dark Blue) -->
                      <a href="javascript:void(0)"
                        class="inline-flex items-center gap-2 bg-blue-500 hover:bg-blue-600
                                text-white text-sm font-medium px-6 py-3 text-md rounded-lg transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
                                class="w-4 h-4 transform rotate-45">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 12l16.5-9-4.5 9 4.5 9-16.5-9z" />
                            </svg>
                            Send New SMS Broadcast
                        </a>

                        <!-- Create New Email Template (Green) -->
                        <a href="javascript:void(0)"
                        class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700
                                text-white text-sm font-medium px-6 py-3 text-md rounded-lg transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Create New Messenger
                        </a>
                    </div>

                    <!-- ====================== -->
                    <!-- 4. EMAIL FUNNEL BUTTONS -->
                    <!-- ====================== -->
                    <div id="btn-emailfunnel" class="tab-action hidden flex items-center gap-3">

                         <!-- Send New Email Broadcast (Dark Blue) -->
                     <a href="javascript:void(0)"
                        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-600
                                text-white text-sm font-medium px-6 py-3 text-md rounded-lg transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
                                class="w-4 h-4 transform rotate-45">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 12l16.5-9-4.5 9 4.5 9-16.5-9z" />
                            </svg>
                            Send New SMS Broadcast
                        </a>

                        <!-- Create New Email Template (Green) -->
                        <a href="javascript:void(0)"
                        class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700
                                text-white text-sm font-medium px-6 py-3 text-md rounded-lg transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Create New Messenger
                        </a>

                    </div>

                </div>


            </div>
        </div>

        

        <!-- INNER CONTENT SECTIONS -->
        <div id="smsbroadcast" class="tab-content" data-tab-group="inner-msg">
          
    @include('admin.message_management.partials._tab_sms_broadcast')


            <!-- Add your SMS Broadcast CRUD here -->
        </div>

        <div id="smsfunnel" class="tab-content hidden" data-tab-group="inner-msg">
            <!-- <h3 class="text-lg font-semibold text-gray-900 mb-4">SMS Funnel Content</h3> -->
            <!-- Add your SMS Funnel CRUD here -->
               @include('admin.message_management.partials._tab_sms_funnel_content')


        </div>

        <div id="emailbroadcast" class="tab-content hidden" data-tab-group="inner-msg">
            <!-- <h3 class="text-lg font-semibold text-gray-900 mb-4">Email Broadcast</h3> -->

            @include('admin.message_management.partials._tab_email_broadcast')



            <!-- Add your Email Broadcast CRUD here -->
        </div>

        <div id="emailfunnel" class="tab-content hidden" data-tab-group="inner-msg">
            <!-- <h3 class="text-lg font-semibold text-gray-900 mb-4">Email Funnel Content</h3> -->
                 @include('admin.message_management.partials._tab_email_funnel_content')

            <!-- Add your Email Funnel CRUD here -->
        </div>

    </div>



   <!-- ============================ -->
<!--    CATEGORIES SECTION        -->
<!-- ============================ -->
<div id="categories" class="tab-content hidden" data-tab-group="main">
<div class="w-full bg-white border border-gray-200 rounded-lg shadow-sm mb-6">

    <!-- Inner Tab Contents -->
    <div id="questionssub" class="tab-content" data-tab-group="inner">
        <div class="space-y-6 p-4" id="questionWrapper">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Category</h2>
                    <p class="text-gray-600">Manage your SMS & Email categories.</p>
                </div>

               <button  id="btn-smscat"
                class="cat-action  gap-2 bg-green-600 hover:bg-green-700
                        text-white text-sm font-medium px-4 py-2 rounded-lg transition"  onclick="openCategoryForm('sms')">
        <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                New SMS Category
</div>
            </button>

             <button  id="btn-emailcat"
                class="cat-action hidden  gap-2 bg-green-600 hover:bg-green-700
                        text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                         <div class="flex items-center  gap-2" onclick="openCategoryForm('email')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                New Email Category
                </div>
            </button>
        </div>


        <!-- Inner Tabs -->
        <div class="w-full bg-white border  rounded-lg shadow-sm mb-6">
            <div class="flex gap-6 border-b p-4 " data-tab-group="inner-cat">

            <button onclick="showTab('smscat', this)"
                class="inner-tab bg-blue-600 text-white border border-blue-600
                rounded-lg px-4 py-2 text-sm font-medium transition w-full sm:w-auto"
            >
                SMS Categories
            </button>

            <button onclick="showTab('emailcat', this)"
                class="inner-tab bg-white text-gray-700 border border-gray-300
                rounded-lg px-4 py-2 text-sm font-medium transition w-full sm:w-auto"
            >
                Email Categories
            </button>



            </div>
        </div>

        <div id="categoryFormCard"
     class="hidden w-full mb-6 bg-gray-100 border rounded-lg shadow-lg p-6 relative">

    <!-- Close Button -->
    <button onclick="closeCategoryForm()"
            class="absolute top-3 right-3 text-gray-500 hover:text-red-600 text-xl font-bold">
        ✕
    </button>

    <h3 id="formTitle" class="text-lg font-semibold mb-4"></h3>

    <!-- Form -->
    <form id="categoryForm">
        <input type="hidden" id="categoryType" name="type">

        <!-- Name -->
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-1">Name <span class="text-red-500">*</span></label>
            <input type="text" id="categoryName" name="name" placeholder="Enter category name"
                   class="bg-white w-full border-gray-300 text-sm rounded-md p-2 px-3 py-3 focus:border-blue-600 focus:ring-blue-600" required>
        </div>

        <!-- Description -->
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-1">Description</label>
            <textarea id="categoryDescription" name="description" placeholder="Enter Description (optional)"
                      class="bg-white w-full border-gray-300 text-sm rounded-md p-2 px-3 py-3 focus:border-blue-600 focus:ring-blue-600"></textarea>
        </div>

        <!-- Buttons -->
        <div class="flex justify-end gap-2">
            <button type="button"
                    onclick="closeCategoryForm()"
                    class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-200">
                Cancel
            </button>

            <button type="submit"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                Create
            </button>
        </div>

    </form>
</div>



        <div id="smscat" class="tab-content" data-tab-group="inner-cat">
            <!-- <h3 class="text-lg font-semibold text-gray-900 mb-4">SMS Categories</h3> -->
                 @include('admin.message_management.partials._tab_sms_category')

        </div>

        <div id="emailcat" class="tab-content hidden" data-tab-group="inner-cat">
            <!-- <h3 class="text-lg font-semibold text-gray-900 mb-4">Email Categories</h3> -->
                 @include('admin.message_management.partials._tab_email_category')

        </div>


        </div>
    </div>
</div>



</div>

@endsection



@push('js')
<script>

function openModal(id) {
    document.getElementById(id).classList.remove("hidden");
    document.getElementById(id).style.display = "flex";
}

function closeModal(id) {
    document.getElementById(id).classList.add("hidden");
    document.getElementById(id).style.display = "none";
}


  function showTab(tabId, clickedBtn) {
      const group = clickedBtn.closest('[data-tab-group]').getAttribute('data-tab-group');

      // Hide tab content
      document.querySelectorAll(`.tab-content[data-tab-group="${group}"]`)
          .forEach(content => content.classList.add('hidden'));

      // Show selected content
      document.getElementById(tabId).classList.remove('hidden');

      // Reset buttons in this tab group
      const buttons = clickedBtn.closest('[data-tab-group]').querySelectorAll('button');
      /* -----------------------------------------
       CUSTOM STYLE: INNER CATEGORY TABS
    ----------------------------------------- */
    if (group === 'inner-cat') {
        buttons.forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
            btn.classList.add('bg-white', 'text-gray-700', 'border-gray-300');
        });

        clickedBtn.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
        clickedBtn.classList.remove('bg-white', 'text-gray-700', 'border-gray-300');

        // Show/Hide "Add Category" buttons
        document.querySelectorAll('.cat-action').forEach(btn => btn.classList.add('hidden'));
        const categoryActions = { 'smscat': 'btn-smscat', 'emailcat': 'btn-emailcat' };
        if (categoryActions[tabId]) {
            document.getElementById(categoryActions[tabId]).classList.remove('hidden');
        }

        return; // stop here for category tab logic
    }

    /* -----------------------------------------
       DEFAULT STYLE: OTHER TABS
    ----------------------------------------- */
    buttons.forEach(btn => {
        btn.classList.remove('text-blue-600', 'border-blue-600', 'border-b-2');
        btn.classList.add('text-gray-600');
    });

    clickedBtn.classList.add('text-blue-600', 'border-blue-600', 'border-b-2');
    clickedBtn.classList.remove('text-gray-600', 'border-gray-200');


      /* ---------------------------------------------------
         ACTION BUTTON LOGIC — SHOW / HIDE BASED ON TAB
      --------------------------------------------------- */

      const actionGroups = {
          'smsbroadcast': 'btn-smsbroadcast',
          'smsfunnel': 'btn-smsfunnel',
          'emailbroadcast': 'btn-emailbroadcast',
          'emailfunnel': 'btn-emailfunnel'
      };

      // Hide all action button groups
      document.querySelectorAll('.tab-action').forEach(div => div.classList.add('hidden'));

      // Show correct action buttons (only for INNER tabs)
      if (actionGroups[tabId]) {
          document.getElementById(actionGroups[tabId]).classList.remove('hidden');
      }

// If switching to main Messages tab → auto select first inner message tab
    if (tabId === 'messages') {
        const firstInnerMsgTab = document.querySelector('[data-tab-group="inner-msg"] button');
        if (firstInnerMsgTab) firstInnerMsgTab.click();
    }

      /* ----------------------------------
       CATEGORY ACTION BUTTON HANDLING
    ---------------------------------- */
    const categoryActions = {
        'smscat': 'btn-smscat',
        'emailcat': 'btn-emailcat'
    };

    // When entering Categories → auto open first inner tab (SMS)
    if (tabId === 'questionssub') {
        const firstCatBtn = document.querySelector('[data-tab-group="inner-cat"] button');
        if (firstCatBtn) firstCatBtn.click();
        return;
    }

    // When switching inner category tabs
    if (group === 'inner-cat') {
        document.querySelectorAll('.cat-action').forEach(a => a.classList.add('hidden'));

        if (categoryActions[tabId]) {
            document.getElementById(categoryActions[tabId]).classList.remove('hidden');
        }
    }

  }


  function openCategoryForm(type) {
    const card = document.getElementById('categoryFormCard');
    card.classList.remove('hidden');

    document.getElementById('categoryType').value = type;

    if (type === 'sms') {
        document.getElementById('formTitle').innerText = "Create New SMS Category";
    } else {
        document.getElementById('formTitle').innerText = "Create New Email Category";
    }
}

function closeCategoryForm() {
    document.getElementById('categoryFormCard').classList.add('hidden');
    document.getElementById('categoryForm').reset();
}




  // Set default visible button on page load
  document.addEventListener("DOMContentLoaded", function () {
      document.getElementById("btn-smsbroadcast").classList.remove("hidden");
  });

</script>

@endpush
