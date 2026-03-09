@extends('admin.layouts.app')

@section('title', 'Message Management')

@push('css')
@endpush

@section('content')
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
			<button onclick="showTab('created-messages', this)" id="tab-created-messages"
				class="tab-button inline-flex items-center gap-2 text-gray-600 font-medium text-sm px-3 pb-2 hover:text-blue-600 transition">
				<x-heroicon-o-queue-list class="w-5 h-5" />
				<span>Messages List</span>
			</button>
		</div>
	</div>
	<!-- ============================ -->
	<!--    MESSAGES SECTION          -->
	<!-- ============================ -->
	<div id="messages" class="tab-content" data-tab-group="main">
		<!-- Inner Tabs + Action Buttons -->
		<div class="w-full bg-white border rounded-xl shadow-sm mb-6 ">
			<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 md:gap-6 border-b p-4" data-tab-group="inner-msg">
				<!-- Left: Tabs -->
				<div class="flex flex-wrap gap-3">
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
							text-white font-medium px-6 py-3 text-md rounded-lg transition">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
								fill="none" stroke="currentColor" stroke-width="2"
								stroke-linecap="round" stroke-linejoin="round"
								class="w-4 h-4">
								<path d="M22 2L11 13" />
								<path d="M22 2L15 22L11 13L2 9L22 2Z" />
							</svg>
							Send New SMS Broadcast
						</a>
						<!-- Create Message (Green) -->
						<a href="javascript:void(0)"  onclick="openModal('new-smsbroadcast')"
							class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700
							text-white font-medium px-6 py-3 text-md rounded-lg transition">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none"
								viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
								class="w-4 h-4">
								<path stroke-linecap="round" stroke-linejoin="round"
									d="M12 4.5v15m7.5-7.5h-15"/>
							</svg>
							Create New Message
						</a>
					</div>
					<!-- ====================== -->
					<!-- 2. SMS FUNNEL BUTTONS -->
					<!-- ====================== -->
					<div id="btn-smsfunnel" class="tab-action hidden flex items-center gap-3">
						<!-- Send New SMS Broadcast (green) -->
						<a href="javascript:void(0)" onclick="openModal('send-new-sms-funnel')"
							class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600
							text-white font-medium px-6 py-3 text-md rounded-lg transition">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
								fill="none" stroke="currentColor" stroke-width="2"
								stroke-linecap="round" stroke-linejoin="round"
								class="w-4 h-4">
								<path d="M22 2L11 13" />
								<path d="M22 2L15 22L11 13L2 9L22 2Z" />
							</svg>
							Send New SMS Broadcast
						</a>
						<!-- Create Message (Green) -->
						<a href="javascript:void(0)" onclick="openModal('new-sms-funnel')"
							class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700
							text-white font-medium px-6 py-3 text-md rounded-lg transition">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none"
								viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
								class="w-4 h-4">
								<path stroke-linecap="round" stroke-linejoin="round"
									d="M12 4.5v15m7.5-7.5h-15"/>
							</svg>
							Create New Message
						</a>
					</div>
					<!-- ====================== -->
					<!-- 3. EMAIL BROADCAST BUTTONS -->
					<!-- ====================== -->
					<div id="btn-emailbroadcast" class="tab-action hidden flex items-center gap-3">
						<!-- Send New Email Broadcast (Dark Blue) -->
						<a href="javascript:void(0)"
							class="inline-flex items-center gap-2 bg-blue-500 hover:bg-blue-600
							text-white font-medium px-6 py-3 text-md rounded-lg transition">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
								fill="none" stroke="currentColor" stroke-width="2"
								stroke-linecap="round" stroke-linejoin="round"
								class="w-4 h-4">
								<path d="M22 2L11 13" />
								<path d="M22 2L15 22L11 13L2 9L22 2Z" />
							</svg>
							Send New SMS Broadcast
						</a>
						<!-- Create New Email Template (Green) -->
						<a href="javascript:void(0)"
							class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700
							text-white font-medium px-6 py-3 text-md rounded-lg transition">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none"
								viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
								class="w-4 h-4">
								<path stroke-linecap="round" stroke-linejoin="round"
									d="M12 4.5v15m7.5-7.5h-15"/>
							</svg>
							Create New Message
						</a>
					</div>
					<!-- ====================== -->
					<!-- 4. EMAIL FUNNEL BUTTONS -->
					<!-- ====================== -->
					<div id="btn-emailfunnel" class="tab-action hidden flex items-center gap-3">
						<!-- Send New Email Broadcast (Dark Blue) -->
						<a href="javascript:void(0)"
							class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-600
							text-white font-medium px-6 py-3 text-md rounded-lg transition">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
								fill="none" stroke="currentColor" stroke-width="2"
								stroke-linecap="round" stroke-linejoin="round"
								class="w-4 h-4">
								<path d="M22 2L11 13" />
								<path d="M22 2L15 22L11 13L2 9L22 2Z" />
							</svg>
							Send New SMS Broadcast
						</a>
						<!-- Create New Email Template (Green) -->
						<a href="javascript:void(0)"
							class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700
							text-white font-medium px-6 py-3 text-md rounded-lg transition">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none"
								viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
								class="w-4 h-4">
								<path stroke-linecap="round" stroke-linejoin="round"
									d="M12 4.5v15m7.5-7.5h-15"/>
							</svg>
							Create New Message
						</a>
					</div>
				</div>
			</div>
		</div>
		<!-- INNER CONTENT SECTIONS -->
		<div id="smsbroadcast" class="tab-content" data-tab-group="inner-msg">
			@include('admin.crm.message_management.partials._tab_sms_broadcast')
			<!-- Add your SMS Broadcast CRUD here -->
		</div>
		<div id="smsfunnel" class="tab-content hidden" data-tab-group="inner-msg">
			<!-- <h3 class="text-lg font-semibold text-gray-900 mb-4">SMS Funnel Content</h3> -->
			<!-- Add your SMS Funnel CRUD here -->
			@include('admin.crm.message_management.partials._tab_sms_funnel_content')
		</div>
		<div id="emailbroadcast" class="tab-content hidden" data-tab-group="inner-msg">
			<!-- <h3 class="text-lg font-semibold text-gray-900 mb-4">Email Broadcast</h3> -->
			@include('admin.crm.message_management.partials._tab_email_broadcast')
			<!-- Add your Email Broadcast CRUD here -->
		</div>
		<div id="emailfunnel" class="tab-content hidden" data-tab-group="inner-msg">
			<!-- <h3 class="text-lg font-semibold text-gray-900 mb-4">Email Funnel Content</h3> -->
			@include('admin.crm.message_management.partials._tab_email_funnel_content')
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
					<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-3">
						<div>
							<h2 class="text-xl font-semibold text-gray-900">Category</h2>
							<p class="text-gray-600">Manage your SMS & Email categories.</p>
						</div>
						<button  id="btn-smscat"
							class="cat-action  gap-2 bg-green-600 hover:bg-green-700
							text-white font-medium px-6 py-3 text-md rounded-lg transition"  onclick="openModal('btn-smscat-modal')">
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
							text-white font-medium px-6 py-3 text-md rounded-lg transition">
							<div class="flex items-center  gap-2" >
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
								font-medium px-6 py-3 text-md rounded-lg transition w-full sm:w-auto"
								>
							SMS Categories
							</button>
							<button onclick="showTab('emailcat', this)"
								class="inner-tab bg-white text-gray-700 border border-gray-300
								font-medium px-6 py-3 text-md rounded-lg transition w-full sm:w-auto"
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
									class="px-6 py-3 text-md rounded-lg font-medium border border-gray-300 text-gray-700 hover:bg-gray-200">
								Cancel
								</button>
								<button type="submit"
									class="px-6 py-3 text-md rounded-lg font-medium bg-blue-600 text-white hover:bg-blue-700">
								Create
								</button>
							</div>
						</form>
					</div>
					<div id="smscat" class="tab-content" data-tab-group="inner-cat">
						<!-- <h3 class="text-lg font-semibold text-gray-900 mb-4">SMS Categories</h3> -->
						@include('admin.crm.message_management.partials._tab_sms_category')
					</div>
					<div id="emailcat" class="tab-content hidden" data-tab-group="inner-cat">
						<!-- <h3 class="text-lg font-semibold text-gray-900 mb-4">Email Categories</h3> -->
						@include('admin.crm.message_management.partials._tab_email_category')
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- ============================ -->
	<!--    created-messages SECTION          -->
	<!-- ============================ -->
	<div id="created-messages" class="tab-content hidden" data-tab-group="main">
		<!-- Inner Tabs + Action Buttons -->
		<div class="w-full bg-white border rounded-xl shadow-sm mb-6 ">
			<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 md:gap-6 border-b p-4" data-tab-group="inner-created-msg">
				<!-- Left: Tabs -->
				<div class="flex flex-wrap gap-3">
					<button onclick="showTab('created-smsbroadcast', this)"
						class="inner-tab inline-flex items-center px-3 pt-3 pb-2
						text-sm font-medium text-blue-600 border-b-2 border-blue-600">
					 SMS List
					</button>
					<!-- <button onclick="showTab('created-smsfunnel', this)"
						class="inner-tab inline-flex items-center px-3 pt-3 pb-2
						text-sm font-medium text-gray-600 hover:text-blue-600">
					SMS Created Funnel Content
					</button> -->
				</div>
				<!-- Right: Action Buttons -->
				<div class="flex items-center gap-3">
					<!-- ====================== -->
					<!-- 1. SMS BROADCAST BUTTONS -->
					<!-- ====================== -->
					<div id="btn-sms-creted" class="tab-action  flex items-center gap-3">
						<!-- Create Message (Green) -->
						<a href="javascript:void(0)"  onclick="openModal('new-smsbroadcast')"
							class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700
							text-white font-medium px-6 py-3 text-md rounded-lg transition">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none"
								viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
								class="w-4 h-4">
								<path stroke-linecap="round" stroke-linejoin="round"
									d="M12 4.5v15m7.5-7.5h-15"/>
							</svg>
							Create New Broadcast
						</a>

                        <!-- Create Message (Green) -->
						<a href="javascript:void(0)" onclick="openModal('new-sms-funnel')"
							class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700
							text-white font-medium px-6 py-3 text-md rounded-lg transition">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none"
								viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
								class="w-4 h-4">
								<path stroke-linecap="round" stroke-linejoin="round"
									d="M12 4.5v15m7.5-7.5h-15"/>
							</svg>
							Create New Funnel
						</a>
					</div>

				</div>
			</div>
		</div>
		<!-- INNER CONTENT SECTIONS -->
		<div id="created-smsbroadcast" class="tab-content"  data-tab-group="inner-created-msg">
			@include('admin.crm.message_management.partials._tab_created_sms')
			<!-- Add your SMS Broadcast CRUD here -->
		</div>

	</div>
</div>




<!-- CREATE NEW Message MODAL -->
<div id="new-smsbroadcast"
     class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">

    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5
                    border border-gray-200 overflow-hidden flex flex-col max-h-full">

            <!-- HEADER -->
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2" >
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <h2 class="text-lg font-medium text-gray-900">Create New Message</h2>
                </div>

                <button type="button" class="text-gray-400 hover:text-gray-700 text-xl"
                        onclick="closeModal('new-smsbroadcast')">×</button>
            </div>

            {{ html()->form()->id('sms_broadcast_form')->attributes([
                'autocomplete' => 'off',
                'data-parsley-validate' => true,
                'class' => 'space-y-8'
            ])->open() }}


            <!-- BODY -->
            <div class="px-6 overflow-y-auto">

                <!-- <form class="space-y-6"> -->


              <input type="hidden" id="sms_broadcast_edit_mode" value="0">
              <input type="hidden" id="sms_broadcast_unique_id" value="">


                    <!-- CATEGORY -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="ContactTags" class="block text-sm font-medium text-gray-700"> Category <span class="text-red-500">*</span></label>
                            <button id="sms-cat-open" type="button" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1" >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Category
                            </button>
                        </div>

                        {!! html()->select('sms_cat_id')
                        ->class([
                            'w-full border rounded-md px-3 py-3 text-sm',
                            'border-red-500' => $errors->has('sms_cat_id'),
                            'border-gray-300' => !$errors->has('sms_cat_id')
                        ])
                        ->placeholder('Select Category')
                        ->id('sms_cat_id')
                        ->required()
                    !!}

                    </div>

                    <!-- CONTENT NAME -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Content Name <span class="text-red-500">*</span>
                        </label>

                               {!! html()->text('name')
                                    ->class([
                                        'w-full border rounded-md px-3 py-3 text-sm',
                                        'border-red-500' => $errors->has('name'),
                                        'border-gray-300' => !$errors->has('name')
                                    ])
                                    ->placeholder('Enter message title...')
                                    ->id('sms_broadcast_name')
                                    ->required()
                                !!}

                    </div>



                    <!-- CONTENT TEXT -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Content <span class="text-red-500">*</span>
                        </label>


                                   {!! html()->textarea('description')
                                ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm char-count-input')
                                ->id('sms_broadcast_description')
                                ->attributes(['data-counter' => 'charCount'])
                                ->rows(4)
                                ->placeholder('Write your SMS content here...')
                                ->required()
                            !!}


                        <!-- CHAR COUNTER -->
                        <p class="text-xs text-gray-500 mt-1">
                        Characters: <span id="charCount">0</span>
                        </p>
                    </div>



            </div>

            <!-- FOOTER -->
            <div class="flex justify-end gap-2 px-6 pb-4">
                <button type="button" onclick="closeModal('new-smsbroadcast')"
                        class="px-6 py-3 text-md rounded-lg font-medium  border border-gray-300 bg-white text-gray-700">
                    Cancel
                </button>

                <button type="submit" id="createSmsBroadcastBtn" class="px-6 py-3 text-md rounded-lg font-medium  bg-blue-600 text-white hover:bg-blue-700">
                    Create Message
                </button>
            </div>

            {{ html()->form()->close() }}
        </div>
    </div>
</div>


<div id="copy-msg"
     class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">

    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">

            <!-- HEADER -->
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-medium text-gray-900">Copy Message</h2>
                </div>

                <button type="button" class="text-gray-400 hover:text-gray-700 text-xl" onclick="closeModal('copy-msg')">×</button>
            </div>

           <div class="px-6 overflow-y-auto">

                <form class="space-y-6">

                    <!-- CATEGORY -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="ContactTags" class="block text-sm font-medium text-gray-700">Content Category <span class="text-red-500">*</span></label>
                            <button type="button" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Category
                            </button>
                        </div>

                        <select class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700">
                            <option value="">Select Category</option>
                            <option>SMS Broadcast</option>
                            <option>SMS Funnel</option>
                            <option>Email Broadcast</option>
                            <option>Email Funnel</option>
                        </select>
                    </div>

                    <!-- CONTENT NAME -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Content Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               placeholder="BOGO Weekend Special (Copy)"
                               class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm"/>
                    </div>

                    <!-- MESSAGE TYPE -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Message Type <span class="text-red-500">*</span>
                        </label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700">
                            <option value="">Select Type</option>
                            <option>SMS Broadcast</option>
                            <option>Email</option>
                        </select>
                    </div>

                    <!-- CONTENT TEXT -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Content <span class="text-red-500">*</span>
                        </label>

                        <textarea id="contentText"
                                  rows="4"
                                  placeholder="Write your message here..."
                                  class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm"></textarea>

                        <!-- CHAR COUNTER -->
                         <div class="text-left text-xs text-gray-500">
                            166 characters
                        </div>

                    </div>

                </form>

            </div>

            <div class="px-6 py-4 flex items-center justify-between gap-3 border-t border-gray-200">

                <!-- SEND BUTTON -->
                <button type="button" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium
                            px-6 py-3 text-md rounded-lg transition">
                    Create Message
                </button>

                <!-- CANCEL -->
                <button type="button" onclick="closeModal('copy-msg')"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium
                        px-6 py-3 text-md rounded-lg transition">
                    Cancel
                </button>

            </div>

        </div>
    </div>
</div>

<!-- EDIT SMS BROADCAST MODAL -->
<div id="edit-smsbroadcast"
     class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">

    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5
                    border border-gray-200 overflow-hidden flex flex-col max-h-full">

            <!-- HEADER -->
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <h2 class="text-lg font-medium text-gray-900">Edit SMS Broadcast</h2>
                </div>

                <button type="button" class="text-gray-400 hover:text-gray-700 text-xl"
                        onclick="closeModal('edit-smsbroadcast')">×</button>
            </div>

            <!-- FORM -->
            <form id="edit_sms_broadcast_form" class="space-y-8 px-6 overflow-y-auto"
                  autocomplete="off" data-parsley-validate>

                <input type="hidden" id="edit_sms_broadcast_id" value="">

                <!-- CATEGORY -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Category <span class="text-red-500">*</span>
                    </label>
                    <select id="edit_sms_cat_id" name="sms_cat_id"
                            class="w-full border rounded-md px-3 py-3 text-sm"
                            required>
                        <option value="">Select Category</option>
                        <!-- Options populated dynamically -->
                    </select>
                </div>

                <!-- CONTENT NAME -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Content Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="edit_sms_broadcast_name" name="name"
                           class="w-full border rounded-md px-3 py-3 text-sm"
                           placeholder="Enter message title..."
                           required>
                </div>

                <!-- CONTENT TEXT -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Content <span class="text-red-500">*</span>
                    </label>
                    <textarea id="edit_sms_broadcast_description" name="description"
                              rows="4"
                              class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm"
                              placeholder="Write your SMS content here..."
                              required></textarea>
                    <!-- CHAR COUNTER -->
                    <p class="text-xs text-gray-500 mt-1">
                        Characters: <span id="editCharCount">0</span>
                    </p>
                </div>

                <!-- FOOTER -->
                <div class="flex justify-end gap-2 pb-4">
                    <button type="button" onclick="closeModal('edit-smsbroadcast')"
                            class="px-6 py-3 text-md rounded-lg font-medium border border-gray-300 bg-white text-gray-700">
                        Cancel
                    </button>
                    <button type="submit" id="updateSmsBroadcastBtn"
                            class="px-6 py-3 text-md rounded-lg font-medium bg-yellow-500 text-white hover:bg-yellow-600">
                        Update Message
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>






<!-- CREATE NEW Message MODAL -->
<div id="new-sms-funnel"
     class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">

    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5
                    border border-gray-200 overflow-hidden flex flex-col max-h-full">

            <!-- HEADER -->
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <h2 class="text-lg font-medium text-gray-900">Create New Message</h2>
                </div>

                <button type="button" class="text-gray-400 hover:text-gray-700 text-xl"
                        onclick="closeModal('new-sms-funnel')">×</button>
            </div>

            {{ html()->form()->id('sms_funnel_form')->attributes([
                'autocomplete' => 'off',
                'data-parsley-validate' => true,
                'class' => 'space-y-8'
            ])->open() }}
            @csrf

            <!-- BODY -->
            <div class="px-6 overflow-y-auto">

                <!-- <form class="space-y-6"> -->

                    <!-- CATEGORY -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Category <span class="text-red-500">*</span>
                        </label>

                           {!! html()->select('sms_funnel_cat_id')
                        ->class([
                            'w-full border rounded-md px-3 py-3 text-sm',
                            'border-gray-300'
                        ])
                        ->placeholder('Select Category')
                        ->id('sms_funnel_cat_id')
                        ->required()
                    !!}



                    </div>

                    <!-- CONTENT NAME -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Content Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name"
                               placeholder="e.g. Welcome Message"
                               class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm"/>

                    </div>



                    <!-- CONTENT TEXT -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Content <span class="text-red-500">*</span>
                        </label>

                        {!! html()->textarea('description')
                        ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm char-count-input')
                        ->attributes(['data-counter' => 'charCountFunnel'])
                        ->rows(4)
                        ->placeholder('Write your message...')
                        ->id('sms_funnel_description')
                        ->required()
                    !!}
                    <p class="text-xs text-gray-500 mt-1">
                        Characters: <span id="charCountFunnel">0</span>
                    </p>
                    </div>

                <!-- </form> -->

            </div>

            <!-- FOOTER -->
            <div class="flex justify-end gap-2 px-6 pb-4">
                <button  type="button"  onclick="closeModal('new-sms-funnel')"
                        class="px-6 py-3 text-md rounded-lg font-medium border border-gray-300 bg-white text-gray-700">
                    Cancel
                </button>

                <button type="submit" id="createSmsFunnelBtn" class="px-6 py-3 text-md rounded-lg font-medium bg-blue-600 text-white hover:bg-blue-700">
                    Create Message
                </button>
            </div>

                {{ html()->form()->close() }}

        </div>
    </div>
</div>

<!-- EDIT SMS FUNNEL MODAL -->
<div id="edit-sms-funnel"
     class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">

    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5
                    border border-gray-200 overflow-hidden flex flex-col max-h-full">

            <!-- HEADER -->
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3 3h18v18H3z" />
                    </svg>
                    <h2 class="text-lg font-medium text-gray-900">Edit SMS Funnel</h2>
                </div>

                <button type="button" class="text-gray-400 hover:text-gray-700 text-xl"
                        onclick="closeModal('edit-sms-funnel')">×</button>
            </div>

            <!-- FORM -->
            <form id="edit_sms_funnel_form" class="space-y-8 px-6 overflow-y-auto"
                  autocomplete="off" data-parsley-validate>

                <input type="hidden" id="edit_sms_funnel_id" name="id">

                <!-- CATEGORY -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Category <span class="text-red-500">*</span>
                    </label>
                    <select id="edit_sms_funnel_cat_id" name="sms_funnel_cat_id"
                            class="w-full border rounded-md px-3 py-3 text-sm"
                            required>
                        <option value="">Select Category</option>
                    </select>
                </div>

                <!-- FUNNEL NAME -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Funnel Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="edit_sms_funnel_name" name="name"
                           class="w-full border rounded-md px-3 py-3 text-sm"
                           placeholder="Enter funnel title..."
                           required>
                </div>

                <!-- FUNNEL DESCRIPTION -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Funnel Content <span class="text-red-500">*</span>
                    </label>
                    <!-- <textarea id="edit_sms_funnel_description" name="description"
                              rows="4"
                              class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm"
                              placeholder="Write SMS funnel message..."
                              required></textarea> -->

                               {!! html()->textarea('description')
                        ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm char-count-input')
                        ->attributes(['data-counter' => 'editFunnelCharCount'])
                        ->rows(4)
                        ->placeholder('Write SMS funnel message...')
                        ->id('edit_sms_funnel_description')
                        ->required()
                    !!}

                    <!-- CHAR COUNTER -->
                    <p class="text-xs text-gray-500 mt-1">
                        Characters: <span id="editFunnelCharCount">0</span>
                    </p>
                </div>


                <!-- FOOTER -->
                <div class="flex justify-end gap-2 pb-4">
                    <button type="button" onclick="closeModal('edit-sms-funnel')"
                            class="px-6 py-3 text-md rounded-lg font-medium border border-gray-300 bg-white text-gray-700">
                        Cancel
                    </button>
                    <button type="submit" id="updateSmsFunnelBtn"
                            class="px-6 py-3 text-md rounded-lg font-medium bg-yellow-500 text-white hover:bg-yellow-600">
                        Update Funnel
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>


<div id="copy-msg-funnel"
     class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">

    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">

            <!-- HEADER -->
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-medium text-gray-900">Copy Message</h2>
                </div>

                <button type="button" class="text-gray-400 hover:text-gray-700 text-xl" onclick="closeModal('copy-msg-funnel')">×</button>
            </div>

           <div class="px-6 overflow-y-auto">

                <form class="space-y-6">

                    <!-- CATEGORY -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                          Content Category <span class="text-red-500">*</span>
                        </label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700">
                            <option value="">Select Category</option>
                            <option>SMS Broadcast</option>
                            <option>SMS Funnel</option>
                            <option>Email Broadcast</option>
                            <option>Email Funnel</option>
                        </select>


                    </div>

                    <!-- CONTENT NAME -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Content Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               placeholder="BOGO Weekend Special (Copy)"
                               class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm"/>
                    </div>

                    <!-- CONTENT TEXT -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Content <span class="text-red-500">*</span>
                        </label>

                        <textarea id="contentText"
                                  rows="4"
                                  placeholder="Write your message here..."
                                  class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm"></textarea>

                        <!-- CHAR COUNTER -->
                         <div class="text-left text-xs text-gray-500">
                            166 characters
                        </div>

                    </div>

                </form>

            </div>

            <div class="px-6 py-4 flex items-center justify-between gap-3 border-t border-gray-200">

                <!-- SEND BUTTON -->
                <button type="button" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium
                            px-6 py-3 rounded-lg text-md transition">
                    Create Message
                </button>

                <!-- CANCEL -->
                <button type="button" onclick="closeModal('copy-msg-funnel')"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium
                        px-6 py-3 rounded-lg text-md transition">
                    Cancel
                </button>

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

    //   console.log('tabGroup:', group);

      // Hide tab content
        document.querySelectorAll(`.tab-content[data-tab-group="${group}"]`)
        .forEach(content => {
            // console.log('Hiding content:', content.id);
            content.classList.add('hidden');
        });


        // Show selected content
        const targetTab = document.getElementById(tabId);
        // console.log('Target tab element:', targetTab);

        if (!targetTab) {
            console.error('❌ Tab NOT FOUND:', tabId);
            return;
        }

        targetTab.classList.remove('hidden');
        // console.log('Showing content:', tabId);


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
        const categoryActions = { 'smscat': 'btn-smscat', 'emailcat': 'btn-emailcat'  };
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
          'emailfunnel': 'btn-emailfunnel',

          'created-smsbroadcast': 'btn-sms-creted'

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




        if (tabId === 'created-messages') {

            document.getElementById('btn-sms-creted').classList.remove('hidden');
                return;
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

<script>
// Global character counter for any .char-count-input
document.addEventListener('input', function (e) {
    if (!e.target.classList.contains('char-count-input')) return;

    const textarea = e.target;
    const counterId = textarea.getAttribute('data-counter');
    const counterEl = document.getElementById(counterId);

    if (!counterEl) return;

    counterEl.textContent = textarea.value.length;
});
</script>


@if(session('active_sub_tab'))
<script>
document.addEventListener("DOMContentLoaded", function () {
    const tabId = "{{ session('active_sub_tab') }}"; // e.g. "smsfunnel"

    const targetBtn = document.querySelector(`[onclick="showTab('${tabId}', this)"]`);

    if (targetBtn) {
        targetBtn.click(); // Activate the correct tab
    }
});
</script>
@endif

<script>

window.refreshCreatedBroadcastAndFunnelSelects = function () {

    const broadcastSelect = document.getElementById('broadcast_select');
    const funnelSelect = document.getElementById('funnel_select');

    if (!broadcastSelect && !funnelSelect) return;

    if (broadcastSelect) {
        broadcastSelect.innerHTML = `<option disabled selected>Loading...</option>`;
    }
    if (funnelSelect) {
        funnelSelect.innerHTML = `<option disabled selected>Loading...</option>`;
    }

      fetch("{{ route('admin.crm.message-management.sms-created-broadcast.get-select') }}")
        .then(res => res.json())
        .then(items => {

            if (broadcastSelect) broadcastSelect.innerHTML = '';
            if (funnelSelect) funnelSelect.innerHTML = '';

            let hasBroadcast = false;
            let hasFunnel = false;

            items.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.dataset.name = item.name;
                option.dataset.category = item.category;
                option.dataset.description = item.description;
                option.textContent = `${item.name} (${item.category})`;

                if (item.type === 'broadcast' && broadcastSelect) {
                    broadcastSelect.appendChild(option);
                    hasBroadcast = true;
                }

                if (item.type === 'funnel' && funnelSelect) {
                    funnelSelect.appendChild(option);
                    hasFunnel = true;
                }
            });

            if (broadcastSelect && !hasBroadcast) {
                broadcastSelect.innerHTML = `<option disabled>No broadcasts created</option>`;
            }

            if (funnelSelect && !hasFunnel) {
                funnelSelect.innerHTML = `<option disabled>No funnels created</option>`;
            }
        })
        .catch(() => {
            if (broadcastSelect) broadcastSelect.innerHTML = `<option disabled>Error</option>`;
            if (funnelSelect) funnelSelect.innerHTML = `<option disabled>Error</option>`;
        });
}

</script>


<!-- gobally can edit the broadcast code  -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    // ---------------------------------------
    // 5) EDIT BROADCAST MODAL (DELEGATED)
    // ---------------------------------------
    const routes = {
        showBroadcast: `{{ route('admin.crm.message-management.sms-broadcast.show', ':id') }}`,
        updateBroadcast: `{{ route('admin.crm.message-management.sms-broadcast.update', ':id') }}`
    };

    // Delegated event listener for dynamic buttons
    document.addEventListener("click", e => {
        const btn = e.target.closest(".edit-smsbrod-btn");
        if (!btn) return;

        const broadcastId = btn.dataset.id;
        console.log("Clicked broadcast ID:", broadcastId);

        const showUrl = routes.showBroadcast.replace(':id', broadcastId);
        console.log("Fetch URL:", showUrl);

        fetch(showUrl)
        .then(res => res.json())
        .then(data => {
            console.log("Fetched data:", data);
            const broadcast = data.broadcast;
            const categories = data.categories || [];

            // Fill modal
            document.getElementById("edit_sms_broadcast_id").value = broadcast.id;
            document.getElementById("edit_sms_broadcast_name").value = broadcast.name;
            document.getElementById("edit_sms_broadcast_description").value = broadcast.description;
            document.getElementById("editCharCount").textContent = broadcast.description.length;

            // Populate categories
            const catSelect = document.getElementById("edit_sms_cat_id");
            catSelect.innerHTML = `<option value="">Select Category</option>`;
            categories.forEach(cat => {
                const opt = document.createElement("option");
                opt.value = cat.id;
                opt.textContent = cat.name;
                if (cat.id === broadcast.category_id) opt.selected = true;
                catSelect.appendChild(opt);
            });

            openModal('edit-smsbroadcast');
        })
        .catch(err => { console.error("Fetch error:", err); notyf.error("Server error. Try again."); });
    });

    document.getElementById("edit_sms_broadcast_form").addEventListener("submit", function(e) {
        e.preventDefault();
        const btn = document.getElementById("updateSmsBroadcastBtn");
        btn.disabled = true;
        btn.textContent = "Updating...";

        const broadcastId = document.getElementById("edit_sms_broadcast_id").value;
        const formData = new FormData(this);
        formData.append('_method', 'PUT'); // Laravel PUT

        const updateUrl = routes.updateBroadcast.replace(':id', broadcastId);

        fetch(updateUrl, {
            method: "POST", // POST + _method=PUT
            headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === "success") {
                notyf.success("SMS Broadcast updated!");
                closeModal('edit-smsbroadcast');

                // refresh table

                fetchBroadcasts();

                fetchCreatedBroadcasts();

                 refreshCreatedBroadcastAndFunnelSelects();
            } else {
                notyf.error(data.message || "Update failed.");
            }
        })
        .catch(err => { console.error(err); notyf.error("Server error"); })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = "Update Message";
        });
    });

});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delegate to handle dynamic buttons
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.delete-smsbroadcast-btn');
        if (!btn) return;

        const broadcastId = btn.dataset.id;
        const broadcastName = btn.dataset.name || 'this message';

        window.showConfirm(
            `Delete "${broadcastName}"? This action cannot be undone!`,
            'Delete SMS Broadcast'
        ).then((result) => {
            if (result.isConfirmed) {
                // Send AJAX delete request
                fetch(`{{ route('admin.crm.message-management.sms-broadcast.delete', ':id') }}`.replace(':id', broadcastId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        notyf.success(data.message);
                        // Optionally remove the row from the table
                        btn.closest('tr').remove();
                    } else {
                        notyf.error(data.message || 'Delete failed.');
                    }

                     refreshCreatedBroadcastAndFunnelSelects();
                })
                .catch(err => {
                    console.error(err);
                    notyf.error('Server error. Try again.');
                });
            }
        });
    });
});
</script>

<!-- gobally can edit the broadcast code  -->



<!-- gobally can edit the funnal code  -->


<script>
document.addEventListener('DOMContentLoaded', function() {
    // ---------------------------------------
    //  EDIT FUNNEL MODAL (DELEGATED)
    // ---------------------------------------
    const routes = {
        showFUNNEL: "{{ route('admin.crm.message-management.sms-funnel.show', ['id' => '__ID__']) }}",
        updateFUNNEL: "{{ route('admin.crm.message-management.sms-funnel.update', ['id' => '__ID__']) }}"
    };

    document.addEventListener("click", e => {
        const btn = e.target.closest(".edit-sms-funnel-btn");
        if (!btn) return;

        const id = btn.dataset.id;
        const showUrl = routes.showFUNNEL.replace('__ID__', id);

        fetch(showUrl)
            .then(res => res.json())
            .then(data => {
                const funnel = data.funnel;
                const categories = data.categories;

                document.getElementById("edit_sms_funnel_id").value = funnel.id;
                document.getElementById("edit_sms_funnel_name").value = funnel.name;
                document.getElementById("edit_sms_funnel_description").value = funnel.description;

                const catSelect = document.getElementById("edit_sms_funnel_cat_id");
                catSelect.innerHTML = `<option value="">Select Category</option>`;
                categories.forEach(c => {
                    catSelect.innerHTML += `
                        <option value="${c.id}" ${c.id == funnel.sms_cat_id ? "selected" : ""}>
                            ${c.name}
                        </option>
                    `;
                });

                openModal('edit-sms-funnel');
            })
            .catch(() => notyf.error("Error loading data"));
    });

    // ---------------------------------------
    //  UPDATE FUNNEL
    // ---------------------------------------
    document.getElementById("edit_sms_funnel_form").addEventListener("submit", function(e) {
        e.preventDefault();

        const btn = document.getElementById("updateSmsFunnelBtn");
        btn.disabled = true;
        btn.textContent = "Updating...";

        const id = document.getElementById("edit_sms_funnel_id").value;
        const formData = new FormData(this);
        formData.append('_method', 'PUT');

        const updateUrl = routes.updateFUNNEL.replace('__ID__', id);

        fetch(updateUrl, {
            method: "POST",
            headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === "success") {
                notyf.success("SMS Funnel updated!");

                closeModal('edit-sms-funnel');
                fetchSmsfunnels(1); // Refresh table

                 fetchCreatedBroadcasts();

                  refreshCreatedBroadcastAndFunnelSelects();

            } else {
                notyf.error(data.message || "Update failed.");
            }
        })
        .catch(err => {
            console.error(err);
            notyf.error("Server error");
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = "Update Funnel";
        });
    });

});

</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delegate to handle dynamic buttons
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.delete-sms-funnel-btn');
        if (!btn) return;

        const funnelId = btn.dataset.id;
        const funnelName = btn.dataset.name || 'this message';

        window.showConfirm(
            `Delete "${funnelName}"? This action cannot be undone!`,
            'Delete SMS Broadcast'
        ).then((result) => {
            if (result.isConfirmed) {
                // Send AJAX delete request
                fetch(`{{ route('admin.crm.message-management.sms-funnel.delete', ':id') }}`.replace(':id', funnelId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        notyf.success(data.message);
                        // Optionally remove the row from the table
                        btn.closest('tr').remove();
                    } else {
                        notyf.error(data.message || 'Delete failed.');
                    }

                     refreshCreatedBroadcastAndFunnelSelects();
                })
                .catch(err => {
                    console.error(err);
                    notyf.error('Server error. Try again.');
                });
            }
        });
    });
});
</script>

<!-- gobally can edit the funnal code  -->

@endpush
