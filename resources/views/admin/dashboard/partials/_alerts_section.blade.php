
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Fuel Charge Alerts -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="p-2.5 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 mr-3">


                                <svg fill="currentColor" class="w-6 h-6  text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M96 128C96 92.7 124.7 64 160 64L320 64C355.3 64 384 92.7 384 128L384 320L392 320C440.6 320 480 359.4 480 408L480 440C480 453.3 490.7 464 504 464C517.3 464 528 453.3 528 440L528 286C500.4 278.9 480 253.8 480 224L480 164.5L454.2 136.2C445.3 126.4 446 111.2 455.8 102.3C465.6 93.4 480.8 94.1 489.7 103.9L561.4 182.7C570.8 193 576 206.4 576 220.4L576 440C576 479.8 543.8 512 504 512C464.2 512 432 479.8 432 440L432 408C432 385.9 414.1 368 392 368L384 368L384 529.4C393.3 532.7 400 541.6 400 552C400 565.3 389.3 576 376 576L104 576C90.7 576 80 565.3 80 552C80 541.5 86.7 532.7 96 529.4L96 128zM160 144L160 240C160 248.8 167.2 256 176 256L304 256C312.8 256 320 248.8 320 240L320 144C320 135.2 312.8 128 304 128L176 128C167.2 128 160 135.2 160 144z"/></svg>

                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-1">Fuel Charge Alerts</h3>
                                <p class="text-sm text-gray-600"> <span id="fuel-alert-count"></span>
                                    pending alert<span id="fuel-alert-s"></span></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span id="fuel-alert-badge" class="px-3 py-1 rounded-full text-sm font-medium"></span>
                            <button type="button" onclick="openDashFuelModal()"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold transition-colors"
                                title="Add New Fuel Charge">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add
                            </button>
                        </div>
                    </div>

                    <div id="fuel-alert-list-wrapper" class="space-y-3 max-h-[420px] overflow-y-auto overflow-x-auto"></div>

                    <!-- Hidden template (used by JS to clone items) -->
                    <template id="fuel-alert-item-template">
                        <div class="alert-item bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors">
                            <div class="flex items-center p-3 gap-2">

                                {{-- Col 1: Fixed-width customer name (144px). Long names truncate with ellipsis. --}}
                                <div class="w-36 flex-shrink-0 overflow-hidden">
                                    <h4 class="text-sm font-semibold text-gray-800 truncate" data-customer></h4>
                                </div>

                                {{-- Col 2: Order ID / CRM badge · icons · amount (fills remaining space) --}}
                                <div class="flex-1 min-w-0 flex items-center">

                                    <div class="flex-shrink-0 min-w-[95px]">
                                        <button data-order-btn class="flex items-center text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                            <span data-order-id></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" class="w-4 h-4 ml-1 text-xs"><path d="M354.4 83.8C359.4 71.8 371.1 64 384 64L544 64C561.7 64 576 78.3 576 96L576 256C576 268.9 568.2 280.6 556.2 285.6C544.2 290.6 530.5 287.8 521.3 278.7L464 221.3L310.6 374.6C298.1 387.1 277.8 387.1 265.3 374.6C252.8 362.1 252.8 341.8 265.3 329.3L418.7 176L361.4 118.6C352.2 109.4 349.5 95.7 354.5 83.7zM64 240C64 195.8 99.8 160 144 160L224 160C241.7 160 256 174.3 256 192C256 209.7 241.7 224 224 224L144 224C135.2 224 128 231.2 128 240L128 496C128 504.8 135.2 512 144 512L400 512C408.8 512 416 504.8 416 496L416 416C416 398.3 430.3 384 448 384C465.7 384 480 398.3 480 416L480 496C480 540.2 444.2 576 400 576L144 576C99.8 576 64 540.2 64 496L64 240z"/></svg>
                                        </button>
                                    </div>

                                    <div class="flex items-center gap-1">
                                        {{-- Make Payment: green --}}
                                        <button data-pay-btn title="Make a Payment" aria-label="Make a Payment"
                                            class="w-6 h-6 rounded flex items-center justify-center text-green-600 hover:bg-green-100 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                        </button>
                                        {{-- Mark Resolved: blue (hidden for CRM via JS) --}}
                                        <button data-resolve-btn title="Mark as Resolved" aria-label="Mark as Resolved"
                                            class="w-6 h-6 rounded flex items-center justify-center text-blue-600 hover:bg-blue-100 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                        </button>
                                        {{-- Mark Uncollectible: red (hidden for CRM via JS) --}}
                                        <button data-uncollectible-btn title="Mark as Uncollectible" aria-label="Mark as Uncollectible"
                                            class="w-6 h-6 rounded flex items-center justify-center text-red-500 hover:bg-red-100 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                        </button>
                                        {{-- Add Note: gray (always visible) --}}
                                        <button data-note-btn title="Add Note" aria-label="Add Note"
                                            class="w-6 h-6 rounded flex items-center justify-center text-gray-500 hover:bg-gray-100 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                        </button>
                                        {{-- Adjust: amber (hidden for CRM via JS) --}}
                                        <button data-adjust-btn title="Adjust Fuel Charge" aria-label="Adjust Fuel Charge"
                                            class="w-6 h-6 rounded flex items-center justify-center text-amber-500 hover:bg-amber-100 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                                            </svg>
                                        </button>
                                        <span data-notes-count class="hidden text-xs font-semibold text-blue-600 hover:text-blue-800 cursor-pointer px-0.5" title="View notes"></span>
                                    </div>

                                    <div class="ml-auto flex-shrink-0 text-sm text-right min-w-[80px]">
                                        <span data-amount></span>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </template>


                </div>

                <!-- New Damage Alerts -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="p-2.5 rounded-lg bg-gradient-to-br from-red-500 to-red-600 mr-3">

                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" class="w-6 h-6 text-white"><path d="M320 64C334.7 64 348.2 72.1 355.2 85L571.2 485C577.9 497.4 577.6 512.4 570.4 524.5C563.2 536.6 550.1 544 536 544L104 544C89.9 544 76.8 536.6 69.6 524.5C62.4 512.4 62.1 497.4 68.8 485L284.8 85C291.8 72.1 305.3 64 320 64zM320 416C302.3 416 288 430.3 288 448C288 465.7 302.3 480 320 480C337.7 480 352 465.7 352 448C352 430.3 337.7 416 320 416zM320 224C301.8 224 287.3 239.5 288.6 257.7L296 361.7C296.9 374.2 307.4 384 319.9 384C332.5 384 342.9 374.3 343.8 361.7L351.2 257.7C352.5 239.5 338.1 224 319.8 224z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-1">New Damage Alerts</h3>
                                <p class="text-sm text-gray-600">
                                    <span id="damage-alert-count"></span>
                                    pending alert<span id="damage-alert-s"></span></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span id="damage-alert-badge"
                                class="px-3 py-1 rounded-full text-sm font-medium">
                            </span>
                            <button type="button" onclick="openDashDamageModal()"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-red-500 hover:bg-red-600 text-white text-xs font-semibold transition-colors"
                                title="Add New Damage Alert">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add
                            </button>
                        </div>
                    </div>


                    <div id="damage-alert-list-wrapper" class="space-y-3 max-h-[420px] overflow-y-auto overflow-x-auto"></div>

                    <!-- Hidden VanillaJS Template -->
                    <template id="damage-alert-item-template">
                        <div class="alert-item bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors">

                            <div class="flex items-center justify-between p-3">
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-gray-800 truncate" data-customer></h4>
                                </div>

                                <div class="flex-1 min-w-0 px-2 truncate flex justify-center">
                                    <button data-order-btn class="flex items-center text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors ">
                                        <span data-order-id></span>
                                     <svg fill="currentColor" class="w-4 h-4 ml-1 text-xs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M354.4 83.8C359.4 71.8 371.1 64 384 64L544 64C561.7 64 576 78.3 576 96L576 256C576 268.9 568.2 280.6 556.2 285.6C544.2 290.6 530.5 287.8 521.3 278.7L464 221.3L310.6 374.6C298.1 387.1 277.8 387.1 265.3 374.6C252.8 362.1 252.8 341.8 265.3 329.3L418.7 176L361.4 118.6C352.2 109.4 349.5 95.7 354.5 83.7zM64 240C64 195.8 99.8 160 144 160L224 160C241.7 160 256 174.3 256 192C256 209.7 241.7 224 224 224L144 224C135.2 224 128 231.2 128 240L128 496C128 504.8 135.2 512 144 512L400 512C408.8 512 416 504.8 416 496L416 416C416 398.3 430.3 384 448 384C465.7 384 480 398.3 480 416L480 496C480 540.2 444.2 576 400 576L144 576C99.8 576 64 540.2 64 496L64 240z"/></svg>
                                    </button>
                                </div>

                                <div class="px-1">
                                    <button data-edit-amount class="p-1.5 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-5 h-5 " viewBox="0 0 640 640"><path d="M296 88C296 74.7 306.7 64 320 64C333.3 64 344 74.7 344 88L344 128L400 128C417.7 128 432 142.3 432 160C432 177.7 417.7 192 400 192L285.1 192C260.2 192 240 212.2 240 237.1C240 259.6 256.5 278.6 278.7 281.8L370.3 294.9C424.1 302.6 464 348.6 464 402.9C464 463.2 415.1 512 354.9 512L344 512L344 552C344 565.3 333.3 576 320 576C306.7 576 296 565.3 296 552L296 512L224 512C206.3 512 192 497.7 192 480C192 462.3 206.3 448 224 448L354.9 448C379.8 448 400 427.8 400 402.9C400 380.4 383.5 361.4 361.3 358.2L269.7 345.1C215.9 337.5 176 291.4 176 237.1C176 176.9 224.9 128 285.1 128L296 128L296 88z"/></svg>
                                    </button>
                                </div>

                                <div class="px-1">
                                    <button
                                        data-view-charges
                                        class="p-1.5 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-100 rounded transition-colors"
                                        title="View Charges"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-5 h-5" viewBox="0 0 640 640">
                                            <path d="M160 64C124.7 64 96 92.7 96 128L96 576L160 544L224 576L288 544L352 576L416 544L480 576L544 544L544 128C544 92.7 515.3 64 480 64L160 64zM208 176C208 158.3 222.3 144 240 144L400 144C417.7 144 432 158.3 432 176C432 193.7 417.7 208 400 208L240 208C222.3 208 208 193.7 208 176zM208 272C208 254.3 222.3 240 240 240L400 240C417.7 240 432 254.3 432 272C432 289.7 417.7 304 400 304L240 304C222.3 304 208 289.7 208 272zM208 368C208 350.3 222.3 336 240 336L336 336C353.7 336 368 350.3 368 368C368 385.7 353.7 400 336 400L240 400C222.3 400 208 385.7 208 368z"/>
                                        </svg>
                                    </button>
                                </div>



                                <div class="px-1 relative">
                                    <button data-status-btn class="p-1.5 text-sm text-green-600 hover:text-green-800 hover:bg-green-100 rounded">
                                      <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-5 h-5 " viewBox="0 0 640 640"><path d="M297.4 470.6C309.9 483.1 330.2 483.1 342.7 470.6L534.7 278.6C547.2 266.1 547.2 245.8 534.7 233.3C522.2 220.8 501.9 220.8 489.4 233.3L320 402.7L150.6 233.4C138.1 220.9 117.8 220.9 105.3 233.4C92.8 245.9 92.8 266.2 105.3 278.7L297.3 470.7z"/></svg>
                                    </button>
                                    <div data-status-dropdown class="hidden absolute right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-[160px]">
                                        <button data-paid class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">Make a Payment</button>
                                        <button data-resolved class="w-full px-3 py-2 text-left text-sm text-green-700 hover:bg-green-50">Mark as Resolved</button>
                                        <button data-uncollectible class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">Mark as Uncollectible</button>
                                    </div>
                                </div>

                                <div class="px-1 flex items-center gap-0.5">
                                    <button data-edit-notes class="p-1.5 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded" title="Add Note">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.966 8.966 0 0 0-6 2.292m0-14.25v14.25" />
                                        </svg>
                                    </button>
                                    <span data-notes-count class="hidden text-xs font-semibold text-blue-600 hover:text-blue-800 cursor-pointer px-0.5" title="View notes"></span>
                                </div>

                                <div class="flex-shrink-0 text-sm text-right min-w-[80px]">
                                    <span data-amount></span>
                                </div>
                            </div>


                        </div>
                    </template>

                </div>
            </div>





<!-- Resolved Modal -->
<div id="resolved-modal" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">
    <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg border border-gray-200 overflow-hidden flex flex-col max-h-full">

        <div class="flex items-center gap-3 px-6 pt-5 pb-4 border-b border-gray-100">
            <div class="flex items-center justify-center w-9 h-9 rounded-full bg-green-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <h3 id="resolved-modal-title" class="text-lg font-semibold text-gray-900">Mark as Resolved</h3>
        </div>

        <div class="px-6 py-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Resolution Note <span class="text-red-500">*</span>
                </label>

                <select id="resolved-note-select"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">-- Select a resolution reason --</option>
                    @foreach($resolutionPresets as $preset)
                        <option value="{{ $preset->id }}" data-label="{{ $preset->label }}">{{ $preset->label }}</option>
                    @endforeach
                    <option value="other">Other</option>
                </select>

                <div id="resolved-other-wrapper" class="hidden mt-2">
                    <textarea id="resolved-note-input" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                        placeholder="Describe how this was resolved..."></textarea>
                </div>

                <div class="mt-2">
                    <button type="button" id="toggleManagePresets"
                        class="text-xs text-blue-600 hover:text-blue-800 hover:underline">
                        + Manage options
                    </button>
                    <div id="managePresetsPanel" class="hidden mt-2 border border-gray-200 rounded-md p-3 bg-gray-50 space-y-2">
                        <div class="flex gap-2">
                            <input id="newPresetInput" type="text" maxlength="100"
                                placeholder="New option label..."
                                class="flex-1 border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <button type="button" onclick="dashboardApp.addPreset()"
                                class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 whitespace-nowrap">
                                Add
                            </button>
                        </div>
                        <ul id="presetsList" class="space-y-1 max-h-36 overflow-y-auto">
                            @foreach($resolutionPresets as $preset)
                                <li class="flex items-center justify-between text-sm py-0.5" data-preset-id="{{ $preset->id }}">
                                    <span class="text-gray-700">{{ $preset->label }}</span>
                                    <button type="button"
                                        onclick="dashboardApp.deletePreset({{ $preset->id }}, this)"
                                        class="text-red-500 hover:text-red-700 text-base leading-none px-1">×</button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Resolved By <span class="text-red-500">*</span>
                </label>
                <select id="resolved-by-select"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">-- Select person responsible --</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex justify-end gap-2 px-6 pb-5 border-t border-gray-100 pt-4">
            <button onclick="dashboardApp.closeResolvedModal()"
                class="px-5 py-2.5 text-sm rounded-lg font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                Cancel
            </button>
            <button id="resolved-save-btn" onclick="dashboardApp.saveResolved()"
                class="px-5 py-2.5 text-sm rounded-lg font-medium bg-green-600 text-white hover:bg-green-700">
                Mark as Resolved
            </button>
        </div>
    </div>
</div>

<!-- Notes Modal -->
<div id="notes-modal" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">
    <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg border border-gray-200 overflow-hidden flex flex-col max-h-full">

        <div class="px-6 pt-5 pb-3 border-b border-gray-100">
            <h3 id="notes-modal-title" class="text-lg font-semibold text-gray-800">Add Note</h3>
        </div>

        <div class="px-6 py-4 space-y-4">

            {{-- Pre-canned note selector --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select a note</label>
                <select id="notes-select"
                    onchange="dashboardApp.onNoteSelectChange()"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">— choose a note —</option>
                    @foreach($fuelNotePresets as $preset)
                        <option value="{{ $preset->label }}">{{ $preset->label }}</option>
                    @endforeach
                    <option value="other">Other (type below)</option>
                </select>
            </div>

            {{-- Freeform text (visible only when "Other" is selected) --}}
            <div id="notes-other-section" class="hidden space-y-2">
                <label class="block text-sm font-medium text-gray-700">Custom note</label>
                <textarea id="notes-input" rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Type your note here..."></textarea>
                <p class="text-xs text-gray-400">Use <strong>Save and Add to List</strong> to save this note for future use.</p>
            </div>

        </div>

        <div class="flex justify-between items-center gap-2 px-6 pb-5">
            <button id="notes-save-list-btn"
                onclick="dashboardApp.saveNotesAndAddToList()"
                class="hidden px-4 py-2 text-sm rounded-lg font-medium bg-green-600 text-white hover:bg-green-700">
                Save and Add to List
            </button>

            <div class="flex gap-2 ml-auto">
                <button onclick="dashboardApp.closeNotesModal()"
                    class="px-5 py-2 text-sm rounded-lg font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button id="notes-save-btn"
                    onclick="dashboardApp.saveNotes()"
                    class="px-5 py-2 text-sm rounded-lg font-medium bg-blue-600 text-white hover:bg-blue-700">
                    Save Note
                </button>
            </div>
        </div>

    </div>
</div>


<!-- View Notes Modal -->
<div id="view-notes-modal" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">
    <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg border border-gray-200 overflow-hidden flex flex-col max-h-[80vh]">

        <div class="flex items-center justify-between px-6 pt-5 pb-3 border-b border-gray-100">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Notes</h3>
                <p id="view-notes-customer" class="text-xs text-gray-500 mt-0.5"></p>
            </div>
            <button onclick="dashboardApp.closeViewNotesModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>

        <div id="view-notes-list" class="px-6 py-4 space-y-3 overflow-y-auto flex-1 min-h-[80px]"></div>

        <div class="flex justify-between items-center px-6 pb-5 pt-3 border-t border-gray-100">
            <button onclick="dashboardApp.openNoteFromViewer()"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm rounded-lg font-medium bg-blue-600 text-white hover:bg-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add Note
            </button>
            <button onclick="dashboardApp.closeViewNotesModal()"
                class="px-4 py-2 text-sm rounded-lg font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                Close
            </button>
        </div>

    </div>
</div>


<!-- Amount Modal -->
<div id="amount-modal"
     class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">
    <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg
                border border-gray-200 overflow-hidden flex flex-col">

        <!-- Header -->
        <div class="px-6 pt-4 border-b">
            <h3 id="amount-modal-title"
                class="text-lg font-semibold text-gray-800">
                Adjust Damage Charge
            </h3>
            <p class="text-xs text-gray-500 mt-1">
                Add or subtract an adjustment from the original damage amount
            </p>
        </div>

        <!-- Amount Summary -->
        <div class="px-6 py-4 bg-gray-50 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">Base Damage Amount</span>
                <span class="font-medium text-gray-900"
                      id="base-damage-amount">$0.00</span>
            </div>

            <div class="flex justify-between">
                <span class="text-gray-600">Current Total (after adjustments)</span>
                <span class="font-semibold text-gray-900"
                      id="current-damage-amount">$0.00</span>
            </div>
        </div>

        <!-- Adjustment Input -->
        <div class="px-6 py-4 space-y-3">
            <label class="block text-sm font-medium text-gray-700">
                Adjustment Amount
            </label>

            <div class="relative">
                <!-- Dollar sign -->
                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                    $
                </span>

                <!-- Input -->
                <input
                    id="amount-input"
                    type="number"
                    step="0.01"
                    class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md
                        focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="e.g. 100 or -50"
                >
            </div>

            <p class="text-xs text-gray-500">
                Use a <strong>positive</strong> value to increase, or
                <strong>negative</strong> value to reduce the charge.
            </p>
        </div>


        <!-- Preview -->
        <div class="px-6 py-3 bg-blue-50 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-700 font-medium">New Total After Adjustment</span>
                <span class="font-bold text-blue-700"
                      id="preview-damage-amount">$0.00</span>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-2 px-6 py-4 border-t">
            <button onclick="dashboardApp.closeAmountModal()"
                    class="px-6 py-2 rounded-lg border border-gray-300
                           bg-white text-gray-700">
                Cancel
            </button>

            <button onclick="dashboardApp.saveAmount()"
                    id="save-btn-amount"
                    class="px-6 py-2 rounded-lg bg-blue-600
                           text-white hover:bg-blue-700">
                Save Adjustment
            </button>
        </div>
    </div>
</div>




   <!-- Record Payment Wrapper -->
   <div id="PaymentModal" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
       <div class="modal-scrollable w-full mx-auto">
           <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
               <div class="flex justify-between items-center px-6 pt-4">
                   <div class="flex items-center gap-2">
                       <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-5 h-5 text-green-600">
                           <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                           <line x1="2" x2="22" y1="10" y2="10"></line>
                       </svg>
                       <h2 class="text-lg font-medium text-gray-900">Record Payment</h2>
                   </div>
                   <button id="closeModalBtn" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
               </div>
               <div class=" px-6 overflow-y-auto">

                   {{ html()->form('POST', route('admin.dashboard.paymentstore'))->id('recordpayment')->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->open() }}

                    <input type="hidden" name="customer_id" id="customer_id" value="">
                    <input type="hidden" name="order_id" id="order_id" value="">
                    <input type="hidden" name="order_product_id" id="order_product_id" value="">
                    <input type="hidden" name="type" id="type" value="">
                    <input type="hidden" name="source" id="payment_source" value="order">
                    <input type="hidden" name="customer_account_id" id="payment_customer_account_id" value="">

                   <!-- Payment Amount -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Payment Amount </label>
                       <div class="relative">
                           <span class="absolute inset-y-0 left-0 h-[36px] pl-3 flex items-center text-gray-500">$</span>


                           <!-- <input type="number" placeholder="0.00"
                                class="pl-7 pr-3 py-3 w-full border border-gray-300 rounded-md text-sm"/> -->

                           {!! html()->text('amount', old('amount'))->attributes([
                           'placeholder' => '0',
                           'autocomplete' => 'off',
                           'data-parsley-min' => '0.01',
                           'min' => '0.01',
                           'data-digit-input' => 'true',
                           'data-parsley-maxlength' => 8,
                           'maxlength' => 8,

                           ])->class('pl-7 pr-3 py-3 w-full border border-gray-300 rounded-md text-sm')
                           ->placeholder('0.00')->required()
                           !!}



                       </div>
                   </div>
                   <!-- Payment Method -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Payment Method </label>
                       {!! html()->select(
                       'payment_type',
                       \App\Enums\Customers\PaymentMethod::options(),

                       )
                       ->id('payment_type')
                       ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')
                       ->required() !!}


                   </div>

                   <!-- Cheque Number (hidden by default) -->
                   <div id="chequeNumberField" class="mb-4 hidden">
                       <label for="cheque_number" class="block text-sm font-medium text-gray-700 mb-1 ">
                           Check Number
                       </label>
                       <input
                           type="text"
                           id="cheque_number"
                           name="cheque_number"
                           class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700"
                           placeholder="Enter Check number" />
                   </div>


                   <!-- Card Options -->
                   <div id="creditCardOptions" class="mb-4 hidden">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Card Options</label>
                       <select id="cardOption" name="card_option"
                           class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700">
                           <option value="NewCard" selected>New Card</option>
                           <option value="CardOnFile">Card on File</option>

                       </select>
                   </div>

                   <!-- New Card Fields -->
                   <div id="newCardFields" class="mb-4 hidden">
                       <div class="grid md:grid-cols-2 gap-4">
                           <div class="md:col-span-1">
                               <input type="text" placeholder="First name" id="firstName" name="firstName"
                                   class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                           </div>
                           <div class="md:col-span-1">
                               <input type="text" placeholder="Last name" id="lastName" name="lastName"
                                   class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                           </div>
                           <div class="md:col-span-2">
                               <input type="text" placeholder="Card number" maxlength="19" id="cardNumber" name="cardNumber"
                                   class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                           </div>
                           <div class="md:col-span-1">
                               <input type="text" placeholder="MM/YY" maxlength="5" id="expiry" name="expiry"
                                   class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                           </div>
                           <div class="md:col-span-1">
                               <input type="text" placeholder="CVC" maxlength="4" id="cvc" name="cvc"
                                   class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                           </div>
                       </div>
                       <input type="hidden" name="opaqueDataValue" id="opaqueDataValue" />
                       <input type="hidden" name="opaqueDataDescriptor" id="opaqueDataDescriptor" />
                   </div>

                   <!-- Card on File Dropdown -->

                   <div id="cardOnFileDropdown" class="mb-4 hidden">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Select Existing Card </label>
                       <select name="existing_card_id" id="existing_card_id"
                           class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700">
                           <option value="">-- Select a saved card --</option>
                       </select>
                   </div>


                   <!-- Person Responsible -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1 required">Person Responsible </label>



                       {!! html()
                       ->select('responsible_person',
                       $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select person responsible', '')->toArray(),
                       old('responsible_person')
                       )
                       ->id('responsible_person')
                       ->class([
                       'w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700',
                       'border-red-500' => $errors->has('responsible_person'),
                       ])
                       ->required()
                       !!}


                   </div>

                   <!-- Notes -->
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                       <!-- <textarea rows="3" placeholder="Enter any additional notes..."
                            class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700"></textarea> -->

                       {!! html()->textarea('notes', old('notes'))
                       ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')
                       ->rows(3)
                       ->placeholder('Enter any additional notes...') !!}


                   </div>

                   <div class="flex justify-end gap-2 pb-4">
                       <button type="button" id="canceltempBtn" class="px-6 py-3 text-md rounded-lg border border-gray-300 bg-white text-gray-700">
                           Cancel
                       </button>
                       <button type="submit" id="submitTemplatesBtn" class="relative px-6 py-3 text-md rounded-lg bg-teal-600 text-white flex items-center justify-center gap-2 hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
                           <span id="btnText">Record Payment</span>
                           <svg id="btnSpinner" xmlns="http://www.w3.org/2000/svg" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                               <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                               <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                           </svg>
                       </button>

                   </div>

                   {{ html()->form()->close() }}

               </div>

           </div>
       </div>
   </div>

<div id="ChargesModal"
     class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">

  <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-xl
              border border-gray-200 overflow-hidden flex flex-col max-h-full">

    <!-- Header -->
    <div class="px-6 pt-4 border-b flex justify-between items-center">
      <div>
        <h3 class="text-lg font-semibold text-gray-800">Charges</h3>
        <p id="chargesModalHeader" class="text-xs text-gray-500 mt-1"></p>
      </div>
      <button onclick="dashboardApp.closeChargesModal()"
              class="p-2 hover:bg-gray-100 rounded">✕</button>
    </div>

    <!-- Body -->
    <div class="px-6 py-4 overflow-auto">

      <div id="chargesModalLoading"
           class="hidden text-sm text-gray-600 py-4">
        Loading...
      </div>

      <div id="chargesModalBody">
        <table class="min-w-full divide-y divide-gray-200 text-sm whitespace-nowrap">
          <thead class="bg-gray-100 text-gray-600">
            <tr class="text-left border-b">
              <th class="py-2 px-2">Checklist Item</th>
              <th class="py-2 px-2">Delivered</th>
              <th class="py-2 px-2">Returned</th>
              <th class="py-2 px-2 text-right">Customer Owes</th>
            </tr>
          </thead>
          <tbody id="chargesTableBody" class="text-gray-800"></tbody>
        </table>
      </div>

      <div id="chargesModalEmpty"
           class="hidden text-sm text-gray-500 text-center py-4">
        No charges found.
      </div>

    </div>
  </div>
</div>



@push('js')


   @if ($paymentSetting['payment_test_mode'] ?? false)
   <script src="https://jstest.authorize.net/v1/Accept.js"></script>
   @else
   <script src="https://js.authorize.net/v1/Accept.js"></script>
   @endif

   <script>
       document.addEventListener('DOMContentLoaded', function() {





    const closeBtn = document.getElementById('closeModalBtn');
    const cancelBtn = document.getElementById('canceltempBtn');

    closeBtn?.addEventListener('click', () => {
            resetCreditCardState();

        window.dashboardApp.closePaymentModal();
    });

    cancelBtn?.addEventListener('click', () => {
            resetCreditCardState();

        window.dashboardApp.closePaymentModal();
    });




           const form = document.getElementById('recordpayment');
           const paymentType = document.getElementById('payment_type');
           const creditCardOptions = document.getElementById('creditCardOptions');
           const cardOption = document.getElementById('cardOption');
           const newCardFields = document.getElementById('newCardFields');
           const cardOnFileDropdown = document.getElementById('cardOnFileDropdown');
           const cardNumberInput = document.getElementById('cardNumber');
           const expiryInput = document.getElementById('expiry');
           const cvcInput = document.getElementById('cvc');
           const submitBtn = document.getElementById('submitTemplatesBtn');
           const btnText = document.getElementById('btnText');
           const btnSpinner = document.getElementById('btnSpinner');
           const chequeNumberField = document.getElementById('chequeNumberField');


           // ===== Show/hide card sections =====
           paymentType.addEventListener('change', function() {
               if (this.value === 'CreditCard') {
                   creditCardOptions.classList.remove('hidden');
                   cardOption.dispatchEvent(new Event('change'));
                   chequeNumberField.classList.add('hidden');
               }else if (this.value === 'Cheque') {
                   chequeNumberField.classList.remove('hidden');
                   creditCardOptions.classList.add('hidden');
               }else {
                   creditCardOptions.classList.add('hidden');
                   chequeNumberField.classList.add('hidden');

                   newCardFields.classList.add('hidden');
                   cardOnFileDropdown.classList.add('hidden');
               }
           });

           cardOption.addEventListener('change', function() {
            //    console.log("Card option changed:", this.value);
               if (this.value === 'NewCard') {
                   newCardFields.classList.remove('hidden');
                   cardOnFileDropdown.classList.add('hidden');
               } else {
                   newCardFields.classList.add('hidden');
                   cardOnFileDropdown.classList.remove('hidden');
               }
           });

           // ===== Input formatting =====
           cardNumberInput.addEventListener('input', function() {
               this.value = this.value.replace(/\D/g, '').substring(0, 16).replace(/(.{4})/g, '$1 ').trim();
           });

           expiryInput.addEventListener('input', function() {
               let val = this.value.replace(/[^0-9]/g, '').substring(0, 4);
               if (val.length >= 3) val = val.substring(0, 2) + '/' + val.substring(2);
               this.value = val;
           });

           cvcInput.addEventListener('input', function() {
               this.value = this.value.replace(/\D/g, '').substring(0, 4);
           });

           // ===== Submit handler =====
           form.addEventListener('submit', function(e) {
               e.preventDefault();
            //    console.log(" Form submit triggered");

               const payType = paymentType.value;
               const cardOpt = cardOption.value;

               if (payType !== 'CreditCard' || cardOpt === 'CardOnFile') {
                //    console.log("ℹ Non-credit card or card on file — submitting normally");
                   if ($(form).parsley().isValid()) {

                       // Show loader immediately
                       submitBtn.disabled = true;
                       btnText.textContent = 'Processing...';
                       btnSpinner.classList.remove('hidden');

                       form.submit();
                   } else {
                       notyf.error("Please fix the form errors before submitting.");
                   }
                   return;
               }

               // Show loader immediately
               submitBtn.disabled = true;
               btnText.textContent = 'Processing...';
               btnSpinner.classList.remove('hidden');
            //    console.log(" Loader shown, starting tokenization");

               try {
                   // Parse expiry
                   let [expMonth, expYearShort] = expiryInput.value.split('/');
                   expMonth = expMonth?.trim();
                   expYearShort = expYearShort?.trim();
                   let expYear = '';
                   if (expYearShort?.length === 2) expYear = '20' + expYearShort;
                   else if (expYearShort?.length === 4) expYear = expYearShort;

                //    console.log(" Expiry parsed:", expMonth, expYear);

                   // Tokenize
                   Accept.dispatchData({
                       authData: {
                           clientKey: "{{ safe_decrypt($paymentSetting['payment_api_public_key']) }}",
                           apiLoginID: "{{ safe_decrypt($paymentSetting['payment_api_key']) }}"
                       },
                       cardData: {
                           cardNumber: cardNumberInput.value.replace(/\s/g, ''),
                           month: expMonth,
                           year: expYear,
                           cardCode: cvcInput.value,
                       }
                   }, function(response) {
                    //    console.log(" Tokenization response:", response);

                       if (response.messages.resultCode === 'Error') {
                           let errorMsg = response.messages.message?.[0]?.text || "Tokenization failed.";
                        //    console.error(" Tokenization error:", errorMsg);
                           notyf.error(errorMsg);

                           // Reset UI
                           submitBtn.disabled = false;
                           btnText.textContent = 'Record Payment';
                           btnSpinner.classList.add('hidden');
                           return; // Stop submit
                       }

                    //    console.log(" Tokenization success — Opaque Data:", response.opaqueData);
                       notyf.success("Payment details validated successfully!");

                       document.getElementById('opaqueDataValue').value = response.opaqueData.dataValue;
                       document.getElementById('opaqueDataDescriptor').value = response.opaqueData.dataDescriptor;

                       //  Now check Parsley validation before final submit
                       if ($(form).parsley().isValid()) {
                        //    console.log(" Form validation passed — submitting now");
                           form.submit();
                       } else {
                        //    console.warn(" Form validation failed after tokenization");
                           notyf.error("Please fix the form errors before submitting.");

                           submitBtn.disabled = false;
                           btnText.textContent = 'Record Payment';
                           btnSpinner.classList.add('hidden');
                       }

                   });

               } catch (error) {
                //    console.error(" Tokenization JS error:", error);
                   notyf.error("Something went wrong during payment processing.");

                   // Reset UI
                   submitBtn.disabled = false;
                   btnText.textContent = 'Record Payment';
                   btnSpinner.classList.add('hidden');

                   return; // Stop submit
               }

           });


           window.resetCreditCardState = function () {
                // console.log('resetCreditCardState');

                // Reset select
                cardOption.value = 'NewCard';


                // Hide sections
                creditCardOptions.classList.add('hidden');
                newCardFields.classList.add('hidden');
                cardOnFileDropdown.classList.add('hidden');
                chequeNumberField.classList.add('hidden');


                // Clear inputs
                cardNumberInput.value = '';
                expiryInput.value = '';
                cvcInput.value = '';

                // Clear tokens
                document.getElementById('opaqueDataValue').value = '';
                document.getElementById('opaqueDataDescriptor').value = '';

                // Clear saved card
                const savedCard = document.getElementById('existing_card_id');
                if (savedCard) savedCard.value = '';
            }


       });
   </script>


@endpush

{{-- ===== Add New Fuel Charge Modal ===== --}}
<div id="dashFuelModal" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg border border-gray-200 overflow-hidden flex flex-col max-h-[90vh]">

        {{-- Header --}}
        <div class="flex justify-between items-center px-6 pt-4 pb-3 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <div class="p-1.5 rounded-md bg-orange-100">
                    <svg fill="currentColor" class="w-4 h-4 text-orange-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M96 128C96 92.7 124.7 64 160 64L320 64C355.3 64 384 92.7 384 128L384 320L392 320C440.6 320 480 359.4 480 408L480 440C480 453.3 490.7 464 504 464C517.3 464 528 453.3 528 440L528 286C500.4 278.9 480 253.8 480 224L480 164.5L454.2 136.2C445.3 126.4 446 111.2 455.8 102.3C465.6 93.4 480.8 94.1 489.7 103.9L561.4 182.7C570.8 193 576 206.4 576 220.4L576 440C576 479.8 543.8 512 504 512C464.2 512 432 479.8 432 440L432 408C432 385.9 414.1 368 392 368L384 368L384 529.4C393.3 532.7 400 541.6 400 552C400 565.3 389.3 576 376 576L104 576C90.7 576 80 565.3 80 552C80 541.5 86.7 532.7 96 529.4L96 128zM160 144L160 240C160 248.8 167.2 256 176 256L304 256C312.8 256 320 248.8 320 240L320 144C320 135.2 312.8 128 304 128L176 128C167.2 128 160 135.2 160 144z"/></svg>
                </div>
                <h2 class="text-lg font-medium text-gray-900">Add New Fuel Charge</h2>
            </div>
            <button type="button" onclick="closeDashFuelModal()" class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
        </div>

        <div class="px-6 overflow-y-auto space-y-5 py-5">

            {{-- Customer --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Customer <span class="text-red-500">*</span></label>
                <select id="dashFuelCustomerSelect"
                    class="choices-select-fuel w-full rounded-md py-3 px-3 border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    <option value="">Select Customer</option>
                    @foreach ($customers as $customer)
                        @php
                            $fullName = trim((string) $customer->full_name);
                            $phone    = trim((string) $customer->phone);
                            $email    = trim((string) $customer->email);
                        @endphp
                        @if ($fullName || $phone)
                            <option value="{{ $customer->id }}">
                                {{ $fullName }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $phone ? \App\Helpers\CustomHelper::formatPhone($phone) : '' }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $email }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            {{-- Charge Amount --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Charge Amount <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-500 text-sm pointer-events-none">$</span>
                    <input id="dashFuelAmount" type="number" step="0.01" min="0.01" placeholder="0.00"
                        class="w-full pl-7 pr-3 py-3 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            {{-- Sales Tax Treatment --}}
            @php $dashTaxPct = \App\Helpers\CustomHelper::displayPercentage($sales_tax); @endphp
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sales Tax Treatment</label>
                <div class="space-y-2 text-sm text-gray-700">
                    <label class="flex items-start gap-2">
                        <input type="radio" name="dashFuelSalesTax" value="add" checked class="mt-1.5 text-blue-600 focus:ring-blue-500">
                        <div>
                            <p class="font-medium">Add Sales Tax</p>
                            <p class="text-gray-500">Add {{ $dashTaxPct }}% sales tax to the entered amount</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-2">
                        <input type="radio" name="dashFuelSalesTax" value="free" class="mt-1.5 text-blue-600 focus:ring-blue-500">
                        <div>
                            <p class="font-medium">Tax Free</p>
                            <p class="text-gray-500">No sales tax applied to this charge</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-2">
                        <input type="radio" name="dashFuelSalesTax" value="reverse" class="mt-1.5 text-blue-600 focus:ring-blue-500">
                        <div>
                            <p class="font-medium">Reverse Sales Tax</p>
                            <p class="text-gray-500">Split entered amount proportionally between base amount and tax</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Charge Reason (static) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Charge Reason</label>
                <p class="text-base font-semibold text-red-500">Fuel Charge</p>
            </div>

            {{-- Person Responsible --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible <span class="text-red-500">*</span></label>
                <select id="dashFuelPerson" class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select person responsible</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                <textarea id="dashFuelNotes" rows="3"
                    class="w-full px-3 py-3 border border-gray-300 rounded-md text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter any additional notes about this charge..."></textarea>
            </div>

            {{-- Buttons --}}
            <div class="flex justify-end gap-2 pb-2">
                <button type="button" onclick="closeDashFuelModal()"
                    class="px-6 py-3 text-sm rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">Cancel</button>
                <button type="button" id="dashFuelSubmitBtn"
                    class="px-6 py-3 text-sm rounded-lg bg-teal-600 text-white hover:bg-teal-700 flex items-center gap-2">
                    <span id="dashFuelBtnText">Add Charge</span>
                    <svg id="dashFuelSpinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ===== Add New Damage Alert Modal ===== --}}
<div id="dashDamageModal" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg border border-gray-200 overflow-hidden flex flex-col max-h-[90vh]">

        {{-- Header --}}
        <div class="flex justify-between items-center px-6 pt-4 pb-3 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <div class="p-1.5 rounded-md bg-red-100">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" class="w-4 h-4 text-red-600"><path d="M320 64C334.7 64 348.2 72.1 355.2 85L571.2 485C577.9 497.4 577.6 512.4 570.4 524.5C563.2 536.6 550.1 544 536 544L104 544C89.9 544 76.8 536.6 69.6 524.5C62.4 512.4 62.1 497.4 68.8 485L284.8 85C291.8 72.1 305.3 64 320 64zM320 416C302.3 416 288 430.3 288 448C288 465.7 302.3 480 320 480C337.7 480 352 465.7 352 448C352 430.3 337.7 416 320 416zM320 224C301.8 224 287.3 239.5 288.6 257.7L296 361.7C296.9 374.2 307.4 384 319.9 384C332.5 384 342.9 374.3 343.8 361.7L351.2 257.7C352.5 239.5 338.1 224 319.8 224z"/></svg>
                </div>
                <h2 class="text-lg font-medium text-gray-900">Add New Damage Alert</h2>
            </div>
            <button type="button" onclick="closeDashDamageModal()" class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
        </div>

        <div class="px-6 overflow-y-auto space-y-5 py-5">

            {{-- Customer --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Customer <span class="text-red-500">*</span></label>
                <select id="dashDamageCustomerSelect"
                    class="choices-select-damage w-full rounded-md py-3 px-3 border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    <option value="">Select Customer</option>
                    @foreach ($customers as $customer)
                        @php
                            $fullName = trim((string) $customer->full_name);
                            $phone    = trim((string) $customer->phone);
                            $email    = trim((string) $customer->email);
                        @endphp
                        @if ($fullName || $phone)
                            <option value="{{ $customer->id }}">
                                {{ $fullName }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $phone ? \App\Helpers\CustomHelper::formatPhone($phone) : '' }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $email }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            {{-- Charge Amount --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Charge Amount <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-500 text-sm pointer-events-none">$</span>
                    <input id="dashDamageAmount" type="number" step="0.01" min="0.01" placeholder="0.00"
                        class="w-full pl-7 pr-3 py-3 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            {{-- Sales Tax Treatment --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sales Tax Treatment</label>
                <div class="space-y-2 text-sm text-gray-700">
                    <label class="flex items-start gap-2">
                        <input type="radio" name="dashDamageSalesTax" value="add" checked class="mt-1.5 text-blue-600 focus:ring-blue-500">
                        <div>
                            <p class="font-medium">Add Sales Tax</p>
                            <p class="text-gray-500">Add {{ $dashTaxPct }}% sales tax to the entered amount</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-2">
                        <input type="radio" name="dashDamageSalesTax" value="free" class="mt-1.5 text-blue-600 focus:ring-blue-500">
                        <div>
                            <p class="font-medium">Tax Free</p>
                            <p class="text-gray-500">No sales tax applied to this charge</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-2">
                        <input type="radio" name="dashDamageSalesTax" value="reverse" class="mt-1.5 text-blue-600 focus:ring-blue-500">
                        <div>
                            <p class="font-medium">Reverse Sales Tax</p>
                            <p class="text-gray-500">Split entered amount proportionally between base amount and tax</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Charge Reason (static) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Charge Reason</label>
                <p class="text-base font-semibold text-red-500">Damages</p>
            </div>

            {{-- Person Responsible --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible <span class="text-red-500">*</span></label>
                <select id="dashDamagePerson" class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select person responsible</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                <textarea id="dashDamageNotes" rows="3"
                    class="w-full px-3 py-3 border border-gray-300 rounded-md text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter any additional notes about this damage..."></textarea>
            </div>

            {{-- Buttons --}}
            <div class="flex justify-end gap-2 pb-2">
                <button type="button" onclick="closeDashDamageModal()"
                    class="px-6 py-3 text-sm rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">Cancel</button>
                <button type="button" id="dashDamageSubmitBtn"
                    class="px-6 py-3 text-sm rounded-lg bg-teal-600 text-white hover:bg-teal-700 flex items-center gap-2">
                    <span id="dashDamageBtnText">Add Charge</span>
                    <svg id="dashDamageSpinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </button>
            </div>

        </div>
    </div>
</div>

@push('js')
<script>
(function () {
    const dashDamageStoreUrl = "{{ route('admin.dashboard.damage-charge.store') }}";
    const csrfTokenDmg       = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function openDashDamageModal() {
        const modal = document.getElementById('dashDamageModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        if (!window.dashDamageCustomerChoices) {
            window.dashDamageCustomerChoices = new Choices('#dashDamageCustomerSelect', {
                searchEnabled: true,
                searchPlaceholderValue: 'Search customer...',
                itemSelectText: '',
                shouldSort: false,
            });
        }
    }
    window.openDashDamageModal = openDashDamageModal;

    function closeDashDamageModal() {
        const modal = document.getElementById('dashDamageModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        resetDashDamageModal();
    }
    window.closeDashDamageModal = closeDashDamageModal;

    function resetDashDamageModal() {
        document.getElementById('dashDamageAmount').value = '';
        document.getElementById('dashDamageNotes').value  = '';
        document.getElementById('dashDamagePerson').value = '';

        const addRadio = document.querySelector('input[name="dashDamageSalesTax"][value="add"]');
        if (addRadio) addRadio.checked = true;

        if (window.dashDamageCustomerChoices) {
            window.dashDamageCustomerChoices.removeActiveItems();
        }
    }

    document.getElementById('dashDamageSubmitBtn').addEventListener('click', function () {
        const customerId = window.dashDamageCustomerChoices
            ? window.dashDamageCustomerChoices.getValue(true)
            : document.getElementById('dashDamageCustomerSelect').value;

        const amount  = document.getElementById('dashDamageAmount').value.trim();
        const person  = document.getElementById('dashDamagePerson').value;
        const notes   = document.getElementById('dashDamageNotes').value.trim();
        const taxRadio = document.querySelector('input[name="dashDamageSalesTax"]:checked');
        const salesTaxType = taxRadio ? taxRadio.value : 'free';

        if (!customerId) { notyf.error('Please select a customer.'); return; }
        if (!amount || parseFloat(amount) <= 0) { notyf.error('Please enter a valid amount.'); return; }
        if (!person) { notyf.error('Please select a person responsible.'); return; }

        const btn     = document.getElementById('dashDamageSubmitBtn');
        const btnText = document.getElementById('dashDamageBtnText');
        const spinner = document.getElementById('dashDamageSpinner');
        btn.disabled = true;
        btnText.textContent = 'Saving...';
        spinner.classList.remove('hidden');

        fetch(dashDamageStoreUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfTokenDmg },
            body: JSON.stringify({
                customer_id: customerId,
                amount,
                responsible_person: person,
                notes,
                sales_tax_type: salesTaxType,
            }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                notyf.success(data.message);
                closeDashDamageModal();
                window.location.reload();
            } else {
                notyf.error(data.message || 'Something went wrong.');
            }
        })
        .catch(err => { console.error('Dash damage charge error:', err); notyf.error('Request failed. Please try again.'); })
        .finally(() => {
            btn.disabled = false;
            btnText.textContent = 'Add Charge';
            spinner.classList.add('hidden');
        });
    });
})();
</script>
@endpush

@push('js')
<script>
(function () {
    const dashFuelStoreUrl = "{{ route('admin.dashboard.fuel-charge.store') }}";
    const csrfToken        = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function openDashFuelModal() {
        const modal = document.getElementById('dashFuelModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        if (!window.dashFuelCustomerChoices) {
            window.dashFuelCustomerChoices = new Choices('#dashFuelCustomerSelect', {
                searchEnabled: true,
                searchPlaceholderValue: 'Search customer...',
                itemSelectText: '',
                shouldSort: false,
            });
        }
    }
    window.openDashFuelModal = openDashFuelModal;

    function closeDashFuelModal() {
        const modal = document.getElementById('dashFuelModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        resetDashFuelModal();
    }
    window.closeDashFuelModal = closeDashFuelModal;

    function resetDashFuelModal() {
        document.getElementById('dashFuelAmount').value = '';
        document.getElementById('dashFuelNotes').value  = '';
        document.getElementById('dashFuelPerson').value = '';

        const addRadio = document.querySelector('input[name="dashFuelSalesTax"][value="add"]');
        if (addRadio) addRadio.checked = true;

        if (window.dashFuelCustomerChoices) {
            window.dashFuelCustomerChoices.removeActiveItems();
        }
    }

    document.getElementById('dashFuelSubmitBtn').addEventListener('click', function () {
        const customerId = window.dashFuelCustomerChoices
            ? window.dashFuelCustomerChoices.getValue(true)
            : document.getElementById('dashFuelCustomerSelect').value;

        const amount  = document.getElementById('dashFuelAmount').value.trim();
        const person  = document.getElementById('dashFuelPerson').value;
        const notes   = document.getElementById('dashFuelNotes').value.trim();
        const taxRadio = document.querySelector('input[name="dashFuelSalesTax"]:checked');
        const salesTaxType = taxRadio ? taxRadio.value : 'free';

        if (!customerId) { notyf.error('Please select a customer.'); return; }
        if (!amount || parseFloat(amount) <= 0) { notyf.error('Please enter a valid amount.'); return; }
        if (!person) { notyf.error('Please select a person responsible.'); return; }

        const btn     = document.getElementById('dashFuelSubmitBtn');
        const btnText = document.getElementById('dashFuelBtnText');
        const spinner = document.getElementById('dashFuelSpinner');
        btn.disabled = true;
        btnText.textContent = 'Saving...';
        spinner.classList.remove('hidden');

        fetch(dashFuelStoreUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                customer_id: customerId,
                amount,
                responsible_person: person,
                notes,
                sales_tax_type: salesTaxType,
            }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                notyf.success(data.message);
                closeDashFuelModal();
                // Refresh the fuel alerts list so the new charge appears immediately
                if (typeof window.fuelAlerts !== 'undefined') {
                    window.location.reload();
                }
            } else {
                notyf.error(data.message || 'Something went wrong.');
            }
        })
        .catch(err => { console.error('Dash fuel charge error:', err); notyf.error('Request failed. Please try again.'); })
        .finally(() => {
            btn.disabled = false;
            btnText.textContent = 'Add Charge';
            spinner.classList.add('hidden');
        });
    });
})();
</script>
@endpush
