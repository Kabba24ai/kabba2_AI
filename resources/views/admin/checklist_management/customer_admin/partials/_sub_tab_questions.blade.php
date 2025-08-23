   <!-- Inner Tab Contents -->
    <div id="questionssub" class="tab-content" data-tab-group="inner">
                    <div class="space-y-6 p-4" id="questionWrapper">
                        <!-- Header -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Customer Questions
</h2>
                                <p class="text-gray-600">Create delivery/return questions with cost calculation (Return Value - Delivery Value = Customer Charge)

</p>
                            </div>
                            <a href="javascript:void(0)" onclick="openQuestionModal()"
    class="inline-flex items-center justify-center whitespace-nowrap rounded-lg bg-purple-600 hover:bg-purple-700 px-6 py-2 text-sm font-medium text-white shadow focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500 mt-3 sm:mt-0">
    + New Customer Question
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
                                    @foreach ($CustomerAdminCategory as $category)
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
                        <!-- Wrapper around all cards -->
                        
                    </div>
    </div>



    
