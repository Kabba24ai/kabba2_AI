<div id="send-new-sms-funnel"
     class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">
    
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 
                    border border-gray-200 overflow-hidden flex flex-col max-h-full">

            <!-- HEADER -->
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-medium text-gray-900">Send New SMS Broadcast</h2>
                </div>

                <button class="text-gray-400 hover:text-gray-700 text-xl"
                        onclick="closeModal('send-new-sms-funnel')">×</button>
            </div>

            <!-- BODY -->
            <div class="px-6 overflow-y-auto">

                <form class="space-y-6">

                    <!-- CATEGORY -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Select SMS Broadcast Message
                        </label>
                        <select 
                            class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm 
                                focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option>BOGO Weekend Special (Copy) (Promotion) - Not Sent</option>
                            <option>Holiday Sale Blast - Not Sent</option>
                            <option>Welcome Message (New Users)</option>
                        </select>
                    </div>

                     <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                        <div class="mb-2">
                            <label>Message Preview</label> 
                        </div>
                        
                        <div class="flex flex-wrap items-center justify-between text-sm mb-3 gap-2">
                            <div>
                                <span class="font-medium text-gray-700">Category:</span>
                                <span class="text-gray-900">Promotion</span>
                            </div>

                            <div>
                                <span class="font-medium text-gray-700">Name:</span>
                                <span class="text-gray-900">BOGO Weekend Special (Copy)</span>
                            </div>
                        </div>

                        <div class="text-gray-800 text-sm leading-relaxed border border-gray-300 rounded-md p-3">
                            Weekend Special: Buy One Get One FREE on all items this Saturday and Sunday!
                            Stock up on your favorites. Visit example.com/bogo to start shopping.
                            Limited time offer!
                        </div>

                        <!-- CHARACTER COUNT -->
                        <div class="text-left text-xs text-gray-500 mt-3">
                            166 characters
                        </div>

                    </div>

                </form>

            </div>

            <!-- FOOTER -->
            <div class="px-6 py-4 flex items-center justify-between gap-3 border-t border-gray-200">

                <!-- SEND BUTTON -->
                <button class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium 
                            py-2.5 rounded-lg text-sm transition">
                    Send SMS Broadcast
                </button>

                <!-- CANCEL -->
                <button onclick="closeModal('send-new-sms-funnel')"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium
                        py-2.5 rounded-lg text-sm transition">
                    Cancel
                </button>

            </div>
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

                <button class="text-gray-400 hover:text-gray-700 text-xl" onclick="closeModal('copy-msg-funnel')">×</button>
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
                <button class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium 
                            py-2.5 rounded-lg text-sm transition">
                    Create Message
                </button>

                <!-- CANCEL -->
                <button onclick="closeModal('copy-msg-funnel')"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium
                        py-2.5 rounded-lg text-sm transition">
                    Cancel
                </button>

            </div>

        </div>
    </div>
</div>

<!-- CREATE NEW MESSENGER MODAL -->
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

                <button class="text-gray-400 hover:text-gray-700 text-xl"
                        onclick="closeModal('new-sms-funnel')">×</button>
            </div>

            <!-- BODY -->
            <div class="px-6 overflow-y-auto">

                <form class="space-y-6">

                    <!-- CATEGORY -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Category <span class="text-red-500">*</span>
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
                               placeholder="e.g. Welcome Message"
                               class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm"/>
                    </div>

                    <!-- MESSAGE TYPE -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Message Type <span class="text-red-500">*</span>
                        </label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700">
                            <option value="">Select Type</option>
                            <option>SMS</option>
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
                        <p class="text-xs text-gray-500 mt-1">
                            Characters: <span id="charCount">0</span>
                        </p>
                    </div>

                </form>

            </div>

            <!-- FOOTER -->
            <div class="flex justify-end gap-2 px-6 pb-4">
                <button onclick="closeModal('new-sms-funnel')"
                        class="px-6 py-3 text-md rounded-lg border border-gray-300 bg-white text-gray-700">
                    Cancel
                </button>

                <button class="px-6 py-3 text-md rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Create Message
                </button>
            </div>

        </div>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-xl p-4 space-y-4 shadow-sm mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Category</label>
            <select class="w-full text-sm px-3 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option>All Categories</option>
                <option value="15"> aaaaaaa </option>
                <option value="8"> Aerator - Walk Behind </option>
                <option value="7"> Boom Lift </option>
                <option value="14"> booootttt </option>
                <option value="13"> hyhyh both </option>
                <option value="12"> kabba </option>
            </select>
        </div>

        <!-- Search -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Search by Content Name</label>
            <input type="text" placeholder="Search by content name..." class="w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        
    </div>
</div>

<!-- TABLE -->
<div class="overflow-x-auto max-w-full rounded-2xl shadow border border-gray-200 bg-white">

<table class="min-w-full divide-y divide-gray-200 text-sm">

        <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
            <tr>
                <th class="py-4 px-6 text-left">Content Cat</th>
                <th class="py-4 px-6 text-left">Content Name</th>
                <th class="py-4 px-6 text-left">Content</th>
                <th class="py-4 px-6 text-left whitespace-nowrap">Created</th>
                <th class="py-4 px-6 text-left whitespace-nowrap">Sales Funnels</th>
                <th class="py-4 px-6 text-left whitespace-nowrap">Actions</th>
            </tr>
        </thead>

        <tbody class="bg-white divide-y divide-gray-200">


            <!-- ========================== -->
            <!-- ROW 1 — SMS BROADCAST -->
            <!-- ========================== -->
            <tr class="hover:bg-gray-50 transition">
                <td class="py-4 px-6 whitespace-nowrap text-gray-900">Customer Care</td>

                <td class="py-4 px-6 whitespace-nowrap font-medium text-gray-900">
                    Delivery Confirmation
                </td>

                <td class="py-4 px-6 whitespace-nowrap max-w-sm truncate text-gray-700">
                  Delivered! Your order #{ORDER_ID} was delivered today. How did we do? Rate your experience at example.com/review and ear…
                </td>

                <td class="py-4 px-6 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">Oct 29, 2025</div>
                    <div class="text-sm text-gray-500 mt-1">08:23 AM</div>
                </td>

                <td class="py-4 px-6 whitespace-nowrap">
                    <div class="text-sm font-bold text-orange-500">Not assigned</div>
                </td>

                <!-- ACTION BUTTONS -->
                <td class="py-4 px-6 whitespace-nowrap">
                    <div class="flex items-center space-x-3">

                      

                        <!-- CREDIT ICON (Gray Button) -->
                        <button class="text-grey-600 flex items-center text-xs" title="Credits">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-5 h-5 ">
                                <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                <line x1="2" x2="22" y1="10" y2="10"></line>
                            </svg>
                        </button>

                        <!-- COPY (Green Button) -->
                        <button class="text-green-600 flex items-center text-xs" title="Copy Message" onclick="openModal('copy-msg-funnel')">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z"></path>
                            </svg>
                        </button>

                        <!-- DELETE (Red Button) -->
                        <button class="text-red-600 flex items-center text-xs" title="Delete">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                            </svg>
                        </button>

                    </div>
                </td>
            </tr>



            <!-- ========================== -->
            <!-- ROW 2 — SMS FUNNEL -->
            <!-- ========================== -->
            <tr class="hover:bg-gray-50 transition">
                <td class="py-4 px-6 whitespace-nowrap text-gray-900">Customer Care</td>

                <td class="py-4 px-6 whitespace-nowrap font-medium text-gray-900">Delivery Confirmation (Copy)</td>

                <td class="py-4 px-6 whitespace-nowrap max-w-sm truncate text-gray-700">
                   Delivered! Your order #{ORDER_ID} was delivered today. How did we do? Rate your experience at example.com/review and ear…
                </td>

                <td class="py-4 px-6 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">Oct 30, 2025</div>
                    <div class="text-sm text-gray-500 mt-1">04:58 AM</div>
                </td>

                <td class="py-4 px-6 whitespace-nowrap">
                    <div class="text-sm font-bold text-orange-500">Not assigned</div>
                </td>

                <!-- ACTION BUTTONS -->
                <td class="py-4 px-6 whitespace-nowrap">
                    <div class="flex items-center space-x-3">

                       

                        <!-- CREDIT ICON (Gray Button) -->
                        <button class="text-grey-600 flex items-center text-xs" title="Credits">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-5 h-5 ">
                                <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                <line x1="2" x2="22" y1="10" y2="10"></line>
                            </svg>
                        </button>

                        <!-- COPY (Green Button) -->
                        <button class="text-green-600 flex items-center text-xs" title="Copy Message">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z"></path>
                            </svg>
                        </button>

                        <!-- DELETE (Red Button) -->
                        <button class="text-red-600 flex items-center text-xs" title="Delete">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                            </svg>
                        </button>

                    </div>
                </td>
            </tr>



            <!-- ========================== -->
            <!-- ROW 3 — EMAIL BROADCAST -->
            <!-- ========================== -->
            <tr class="hover:bg-gray-50 transition">
                <td class="py-4 px-6 whitespace-nowrap text-gray-900">Customer Care</td>

                <td class="py-4 px-6 whitespace-nowrap font-medium text-gray-900">
                    Order Confirmation
                </td>

                <td class="py-4 px-6 whitespace-nowrap max-w-sm truncate text-gray-700">
                  Order Confirmed! Your order #{ORDER_ID} has been received and is being processed. You will receive a shipping notificati…
                </td>

                <td class="py-4 px-6 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">Oct 29, 2025</div>
                    <div class="text-sm text-gray-500 mt-1">08:23 AM</div>
                </td>

               <td class="py-4 px-6 whitespace-nowrap">
                <div class="text-sm font-bold text-orange-500">Not assigned</div>
            </td>

                <!-- ACTION BUTTONS -->
                <td class="py-4 px-6 whitespace-nowrap">
                    <div class="flex items-center space-x-3">

                      

                        <!-- CREDIT ICON (Gray Button) -->
                        <button class="text-grey-600 flex items-center text-xs" title="Credits">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-5 h-5 ">
                                <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                <line x1="2" x2="22" y1="10" y2="10"></line>
                            </svg>
                        </button>

                        <!-- COPY (Green Button) -->
                        <button class="text-green-600 flex items-center text-xs" title="Copy Message">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z"></path>
                            </svg>
                        </button>

                        <!-- DELETE (Red Button) -->
                        <button class="text-red-600 flex items-center text-xs" title="Delete">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                            </svg>
                        </button>

                    </div>
                </td>
            </tr>

            <!-- ========================== -->
    <!-- ROW 4 — SMS BROADCAST -->
    <!-- ========================== -->
        <tr class="hover:bg-gray-50 transition">
            <td class="py-4 px-6 whitespace-nowrap text-gray-900">Customer Care</td>

            <td class="py-4 px-6 whitespace-nowrap font-medium text-gray-900">
                Shipping Update
            </td>

            <td class="py-4 px-6 whitespace-nowrap max-w-sm truncate text-gray-700">
                Great news! Your order #{ORDER_ID} has shipped via {CARRIER}. Expected delivery: {DATE}. Track your package at example.c…
            </td>

            <td class="py-4 px-6 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">Oct 29, 2025</div>
                <div class="text-sm text-gray-500 mt-1">08:23 AM</div>
            </td>

            <td class="py-4 px-6 whitespace-nowrap">
                <div class="text-sm font-bold text-orange-500">Not assigned</div>
            </td>


            <!-- ACTION BUTTONS -->
            <td class="py-4 px-6 whitespace-nowrap">
                <div class="flex items-center space-x-3">

                      

                        <!-- CREDIT ICON (Gray Button) -->
                        <button class="text-grey-600 flex items-center text-xs" title="Credits">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-5 h-5 ">
                                <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                <line x1="2" x2="22" y1="10" y2="10"></line>
                            </svg>
                        </button>

                        <!-- COPY (Green Button) -->
                        <button class="text-green-600 flex items-center text-xs" title="Copy Message">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z"></path>
                            </svg>
                        </button>

                        <!-- DELETE (Red Button) -->
                        <button class="text-red-600 flex items-center text-xs" title="Delete">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                            </svg>
                        </button>

                    </div>
            </td>
        </tr>





    </tbody>

</table>

</div>



@push('js')

<script>

// LIVE CHARACTER COUNTER
document.addEventListener("input", function() {
    const textarea = document.getElementById("contentText");
    if (textarea) {
        document.getElementById("charCount").textContent = textarea.value.length;
    }
});


</script>

@endpush