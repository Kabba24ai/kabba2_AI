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

                        </div>
                    </div>

                    <div id="fuel-alert-list-wrapper" class="space-y-3 h-[360px] overflow-y-auto overflow-x-auto"></div>

                    <!-- Hidden template (used by JS to clone items) -->
                    <template id="fuel-alert-item-template">
                        <div class="alert-item bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors">
                            <div class="flex items-center justify-between p-3">
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-gray-800 truncate" data-customer></h4>
                                </div>

                                <div class="flex-1 min-w-0 px-2 truncate">
                                    <button data-order-btn class="flex items-center text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                        <span data-order-id></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" class="w-4 h-4 ml-1 text-xs"><path d="M354.4 83.8C359.4 71.8 371.1 64 384 64L544 64C561.7 64 576 78.3 576 96L576 256C576 268.9 568.2 280.6 556.2 285.6C544.2 290.6 530.5 287.8 521.3 278.7L464 221.3L310.6 374.6C298.1 387.1 277.8 387.1 265.3 374.6C252.8 362.1 252.8 341.8 265.3 329.3L418.7 176L361.4 118.6C352.2 109.4 349.5 95.7 354.5 83.7zM64 240C64 195.8 99.8 160 144 160L224 160C241.7 160 256 174.3 256 192C256 209.7 241.7 224 224 224L144 224C135.2 224 128 231.2 128 240L128 496C128 504.8 135.2 512 144 512L400 512C408.8 512 416 504.8 416 496L416 416C416 398.3 430.3 384 448 384C465.7 384 480 398.3 480 416L480 496C480 540.2 444.2 576 400 576L144 576C99.8 576 64 540.2 64 496L64 240z"/></svg>

                                    </button>
                                </div>

                                <div class="px-1">
                                    <button data-edit-amount class="p-1.5 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded transition-colors"><svg  fill="currentColor" class="w-5 h-5 " xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M296 88C296 74.7 306.7 64 320 64C333.3 64 344 74.7 344 88L344 128L400 128C417.7 128 432 142.3 432 160C432 177.7 417.7 192 400 192L285.1 192C260.2 192 240 212.2 240 237.1C240 259.6 256.5 278.6 278.7 281.8L370.3 294.9C424.1 302.6 464 348.6 464 402.9C464 463.2 415.1 512 354.9 512L344 512L344 552C344 565.3 333.3 576 320 576C306.7 576 296 565.3 296 552L296 512L224 512C206.3 512 192 497.7 192 480C192 462.3 206.3 448 224 448L354.9 448C379.8 448 400 427.8 400 402.9C400 380.4 383.5 361.4 361.3 358.2L269.7 345.1C215.9 337.5 176 291.4 176 237.1C176 176.9 224.9 128 285.1 128L296 128L296 88z"/></svg> </button>
                                </div>

                                <div class="px-1 relative">
                                    <button data-status-btn class="p-1.5 text-sm text-green-600 hover:text-green-800 hover:bg-green-100 rounded transition-colors"><svg fill="currentColor" class="w-5 h-5 " xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M297.4 470.6C309.9 483.1 330.2 483.1 342.7 470.6L534.7 278.6C547.2 266.1 547.2 245.8 534.7 233.3C522.2 220.8 501.9 220.8 489.4 233.3L320 402.7L150.6 233.4C138.1 220.9 117.8 220.9 105.3 233.4C92.8 245.9 92.8 266.2 105.3 278.7L297.3 470.7z"/></svg> </button>

                                    <div data-status-dropdown class="hidden absolute top-full right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-[140px]">
                                        <button data-paid class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">Mark as Paid</button>
                                        <button data-uncollectible class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">Mark as Uncollectible</button>
                                    </div>
                                </div>

                                <div class="px-1">
                                    <button data-edit-notes class="p-1.5 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded transition-colors"><svg fill="currentColor" class="w-5 h-5 "  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM248 320C234.7 320 224 330.7 224 344C224 357.3 234.7 368 248 368L392 368C405.3 368 416 357.3 416 344C416 330.7 405.3 320 392 320L248 320zM248 416C234.7 416 224 426.7 224 440C224 453.3 234.7 464 248 464L392 464C405.3 464 416 453.3 416 440C416 426.7 405.3 416 392 416L248 416z"/></svg> </button>
                                </div>

                                <div class="flex-shrink-0 text-sm text-right min-w-[80px]">
                                    <span data-amount></span>
                                </div>
                            </div>

                            <div data-notes-wrapper class="hidden px-3 pb-3">
                                <div class="p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-800">
                                    <strong>Notes:</strong> <span data-notes></span>
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

                        </div>
                    </div>


                    <div id="damage-alert-list-wrapper" class="space-y-3 h-[360px] overflow-y-auto overflow-x-auto"></div>

                    <!-- Hidden VanillaJS Template -->
                    <template id="damage-alert-item-template">
                        <div class="alert-item bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors">

                            <div class="flex items-center justify-between p-3">
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-gray-800 truncate" data-customer></h4>
                                </div>

                                <div class="flex-1 min-w-0 px-2 truncate">
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

                                <div class="px-1 relative">
                                    <button data-status-btn class="p-1.5 text-sm text-green-600 hover:text-green-800 hover:bg-green-100 rounded">
                                      <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-5 h-5 " viewBox="0 0 640 640"><path d="M297.4 470.6C309.9 483.1 330.2 483.1 342.7 470.6L534.7 278.6C547.2 266.1 547.2 245.8 534.7 233.3C522.2 220.8 501.9 220.8 489.4 233.3L320 402.7L150.6 233.4C138.1 220.9 117.8 220.9 105.3 233.4C92.8 245.9 92.8 266.2 105.3 278.7L297.3 470.7z"/></svg>
                                    </button>
                                    <div data-status-dropdown class="hidden absolute right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-[140px]">
                                        <button data-paid class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100">Mark as Paid</button>
                                        <button data-uncollectible class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100">Mark as Uncollectible</button>
                                    </div>
                                </div>

                                <div class="px-1">
                                    <button data-edit-notes class="p-1.5 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-5 h-5 " viewBox="0 0 640 640"><path d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM248 320C234.7 320 224 330.7 224 344C224 357.3 234.7 368 248 368L392 368C405.3 368 416 357.3 416 344C416 330.7 405.3 320 392 320L248 320zM248 416C234.7 416 224 426.7 224 440C224 453.3 234.7 464 248 464L392 464C405.3 464 416 453.3 416 440C416 426.7 405.3 416 392 416L248 416z"/></svg>
                                    </button>
                                </div>

                                <div class="flex-shrink-0 text-sm text-right min-w-[80px]">
                                    <span data-amount></span>
                                </div>
                            </div>

                            <div data-notes-wrapper class="hidden px-3 pb-3">
                                <div class="p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-800">
                                    <strong>Notes:</strong> <span data-notes></span>
                                </div>
                            </div>

                        </div>
                    </template>

                </div>
            </div>





   <!-- Notes Modal -->
<div id="notes-modal" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">
    <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">

        <h3 id="notes-modal-title" class="text-lg font-semibold px-6 pt-4"></h3>

        <div class="px-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
            <textarea id="notes-input" rows="4"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Add notes..."></textarea>
        </div>

        <div class="flex justify-end gap-2 px-6 pb-4">
            <button onclick="dashboard.closeNotesModal()"
                class="px-6 py-3 text-md rounded-lg font-medium  border border-gray-300 bg-white text-gray-700">Cancel</button>

            <button onclick="dashboard.saveNotes()"
                class="px-6 py-3 text-md rounded-lg font-medium  bg-blue-600 text-white hover:bg-blue-700">
                Save
            </button>
        </div>

    </div>
</div>

<!-- Amount Modal -->
<div id="amount-modal" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">
    <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">

        <h3 id="amount-modal-title" class="text-lg font-semibold px-6 pt-4"></h3>

        <div class="px-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Amount ($)</label>
            <input id="amount-input" type="number" step="0.01"   data-digit-input = 'true'  data-parsley-maxlength='8'
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="0.00" >
        </div>

        <div class="flex justify-end gap-2 px-6 pb-4">
            <button onclick="dashboard.closeAmountModal()"
                class="px-6 py-3 text-md rounded-lg font-medium  border border-gray-300 bg-white text-gray-700">
                Cancel
            </button>

            <button onclick="dashboard.saveAmount()"
                class="px-6 py-3 text-md rounded-lg font-medium  bg-blue-600 text-white hover:bg-blue-700">
                Save
            </button>
        </div>
    </div>
</div>

