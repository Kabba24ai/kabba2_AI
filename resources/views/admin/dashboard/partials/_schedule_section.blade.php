 <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Block 1: Schedule -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-300">
                    <div class="flex items-center mb-4">

                        <svg class="w-6 h-6 text-blue-600 mr-3" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C178.6 576 64 461.4 64 320C64 178.6 178.6 64 320 64zM296 184L296 320C296 328 300 335.5 306.7 340L402.7 404C413.7 411.4 428.6 408.4 436 397.3C443.4 386.2 440.4 371.4 429.3 364L344 307.2L344 184C344 170.7 333.3 160 320 160C306.7 160 296 170.7 296 184z"/></svg>
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-1">Schedule</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        @php
                            $due       = $scheduleStats['deliveries_truck']['due_today'] ?? 0;
                            $completed = $scheduleStats['deliveries_truck']['completed_today'] ?? 0;

                            $total = $due + $completed;

                            $progress = $total > 0
                                ? round(($completed / $total) * 100)
                                : 0;

                            $allDone = $total > 0 && $completed === $total;
                        @endphp

                        <!-- Deliveries - Truck -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 cursor-pointer hover:shadow-lg hover:border-blue-400 transition-all">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 group-hover:scale-110 transition-transform duration-200">

                                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class=" w-6 h-6 text-white"  viewBox="0 0 640 640"><path d="M32 160C32 124.7 60.7 96 96 96L384 96C419.3 96 448 124.7 448 160L448 192L498.7 192C515.7 192 532 198.7 544 210.7L589.3 256C601.3 268 608 284.3 608 301.3L608 448C608 483.3 579.3 512 544 512L540.7 512C530.3 548.9 496.3 576 456 576C415.7 576 381.8 548.9 371.3 512L268.7 512C258.3 548.9 224.3 576 184 576C143.7 576 109.8 548.9 99.3 512L96 512C60.7 512 32 483.3 32 448L32 160zM544 352L544 301.3L498.7 256L448 256L448 352L544 352zM224 488C224 465.9 206.1 448 184 448C161.9 448 144 465.9 144 488C144 510.1 161.9 528 184 528C206.1 528 224 510.1 224 488zM456 528C478.1 528 496 510.1 496 488C496 465.9 478.1 448 456 448C433.9 448 416 465.9 416 488C416 510.1 433.9 528 456 528z"/></svg>
                                </div>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 leading-tight">Deliveries - Truck</h3>
                            <div class="flex items-center">
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-gray-400 mb-1"> {{ $scheduleStats['deliveries_truck']['due_today'] ?? 0 }} </div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">DUE</div>
                                </div>
                                <div class="h-12 w-px bg-gray-300"></div>
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-green-600 mb-1"> {{ $scheduleStats['deliveries_truck']['completed_today'] ?? 0 }} </div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">COMPLETED</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-300">
                                   {{-- IF ALL DONE → SHOW TROPHY --}}
                            @if ($allDone)
                                <div class="flex items-center justify-center">
                                    <div class="flex items-center bg-gradient-to-r from-yellow-400 to-amber-500 px-3 py-1.5 rounded-full shadow-sm">

                                         <svg fill="currentColor" class="w-6 h-6 text-white mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M208.3 64L432.3 64C458.8 64 480.4 85.8 479.4 112.2C479.2 117.5 479 122.8 478.7 128L528.3 128C554.4 128 577.4 149.6 575.4 177.8C567.9 281.5 514.9 338.5 457.4 368.3C441.6 376.5 425.5 382.6 410.2 387.1C390 415.7 369 430.8 352.3 438.9L352.3 512L416.3 512C434 512 448.3 526.3 448.3 544C448.3 561.7 434 576 416.3 576L224.3 576C206.6 576 192.3 561.7 192.3 544C192.3 526.3 206.6 512 224.3 512L288.3 512L288.3 438.9C272.3 431.2 252.4 416.9 233 390.6C214.6 385.8 194.6 378.5 175.1 367.5C121 337.2 72.2 280.1 65.2 177.6C63.3 149.5 86.2 127.9 112.3 127.9L161.9 127.9C161.6 122.7 161.4 117.5 161.2 112.1C160.2 85.6 181.8 63.9 208.3 63.9zM165.5 176L113.1 176C119.3 260.7 158.2 303.1 198.3 325.6C183.9 288.3 172 239.6 165.5 176zM444 320.8C484.5 297 521.1 254.7 527.3 176L475 176C468.8 236.9 457.6 284.2 444 320.8z"/></svg>
                                        <span class="text-white font-semibold text-xs tracking-wide">ALL DONE!</span>
                                    </div>
                                </div>
                                 {{-- OTHERWISE → SHOW PROGRESS BAR --}}
                            @else
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Progress</span>
                                    <span>{{ $progress }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-gradient-to-r from-green-400 to-green-500 transition-all duration-500" style="width: {{ $progress }}%"></div>
                                </div>
                             @endif
                            </div>
                        </div>

                        @php
                            $dueStore       = $scheduleStats['deliveries_store']['due_today'] ?? 0;
                            $completedStore = $scheduleStats['deliveries_store']['completed_today'] ?? 0;

                            $totalStore = $dueStore + $completedStore;

                            $progressStore = $totalStore > 0
                                ? round(($completedStore / $totalStore) * 100)
                                : 0;

                            $allDoneStore = $totalStore > 0 && $completedStore === $totalStore;
                        @endphp


                        <!-- Deliveries - In Store -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 cursor-pointer hover:shadow-lg hover:border-blue-400 transition-all">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-green-500 to-green-600 group-hover:scale-110 transition-transform duration-200">

                                     <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-6 h-6 text-white" viewBox="0 0 640 640"><path d="M94.7 136.3C101.6 112.4 123.5 96 148.4 96L492.4 96C517.3 96 539.2 112.4 546.2 136.3L569.6 216.5C582.4 260.2 549.5 304 504 304C477.7 304 454.6 289.1 443.2 266.9C431.6 288.8 408.6 304 381.8 304C355.2 304 332.1 289 320.5 267C308.9 289 285.8 304 259.2 304C232.4 304 209.4 288.9 197.8 266.9C186.4 289 163.3 304 137 304C91.4 304 58.6 260.3 71.4 216.5L94.7 136.3zM160.4 416L480.4 416L480.4 349.6C488 351.2 495.9 352 503.9 352C518.2 352 531.9 349.4 544.4 344.8L544.4 496C544.4 522.5 522.9 544 496.4 544L144.4 544C117.9 544 96.4 522.5 96.4 496L96.4 344.8C108.9 349.4 122.5 352 136.9 352C145 352 152.8 351.2 160.4 349.6L160.4 416z"/></svg>
                                </div>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 leading-tight">Deliveries - In Store</h3>
                            <div class="flex items-center">
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-red-600 mb-1">{{ $scheduleStats['deliveries_store']['due_today'] ?? 0 }} </div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">DUE</div>
                                </div>
                                <div class="h-12 w-px bg-gray-300"></div>
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-green-600 mb-1">{{ $scheduleStats['deliveries_store']['completed_today'] ?? 0 }} </div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">COMPLETED</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-300">


                             {{-- IF ALL DONE → SHOW TROPHY --}}
                            @if ($allDoneStore)
                                <div class="flex items-center justify-center">
                                    <div class="flex items-center bg-gradient-to-r from-yellow-400 to-amber-500 px-3 py-1.5 rounded-full shadow-sm">

                                         <svg fill="currentColor" class="w-6 h-6 text-white mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M208.3 64L432.3 64C458.8 64 480.4 85.8 479.4 112.2C479.2 117.5 479 122.8 478.7 128L528.3 128C554.4 128 577.4 149.6 575.4 177.8C567.9 281.5 514.9 338.5 457.4 368.3C441.6 376.5 425.5 382.6 410.2 387.1C390 415.7 369 430.8 352.3 438.9L352.3 512L416.3 512C434 512 448.3 526.3 448.3 544C448.3 561.7 434 576 416.3 576L224.3 576C206.6 576 192.3 561.7 192.3 544C192.3 526.3 206.6 512 224.3 512L288.3 512L288.3 438.9C272.3 431.2 252.4 416.9 233 390.6C214.6 385.8 194.6 378.5 175.1 367.5C121 337.2 72.2 280.1 65.2 177.6C63.3 149.5 86.2 127.9 112.3 127.9L161.9 127.9C161.6 122.7 161.4 117.5 161.2 112.1C160.2 85.6 181.8 63.9 208.3 63.9zM165.5 176L113.1 176C119.3 260.7 158.2 303.1 198.3 325.6C183.9 288.3 172 239.6 165.5 176zM444 320.8C484.5 297 521.1 254.7 527.3 176L475 176C468.8 236.9 457.6 284.2 444 320.8z"/></svg>
                                        <span class="text-white font-semibold text-xs tracking-wide">ALL DONE!</span>
                                    </div>
                                </div>
                              {{-- OTHERWISE → SHOW PROGRESS BAR --}}
                            @else
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Progress</span>
                                     <span>{{ $progressStore }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-gradient-to-r from-green-400 to-green-500 transition-all duration-500" style="width: {{ $progressStore }}%"></div>
                                </div>
                            @endif

                            </div>
                        </div>


                        @php
                            $dueReturnTruck       = $scheduleStats['returns_truck']['due_today'] ?? 0;
                            $completedReturnTruck = $scheduleStats['returns_truck']['completed_today'] ?? 0;

                            $totalReturnTruck = $dueReturnTruck + $completedReturnTruck;

                            $progressReturnTruck = $totalReturnTruck > 0
                                ? round(($completedReturnTruck / $totalReturnTruck) * 100)
                                : 0;

                            $allDoneReturnTruck = $totalReturnTruck > 0 && $completedReturnTruck === $totalReturnTruck;
                        @endphp


                        <!-- Returns - Truck -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 cursor-pointer hover:shadow-lg hover:border-blue-400 transition-all">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 group-hover:scale-110 transition-transform duration-200">

                                     <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class=" w-6 h-6 text-white"  viewBox="0 0 640 640"><path d="M32 160C32 124.7 60.7 96 96 96L384 96C419.3 96 448 124.7 448 160L448 192L498.7 192C515.7 192 532 198.7 544 210.7L589.3 256C601.3 268 608 284.3 608 301.3L608 448C608 483.3 579.3 512 544 512L540.7 512C530.3 548.9 496.3 576 456 576C415.7 576 381.8 548.9 371.3 512L268.7 512C258.3 548.9 224.3 576 184 576C143.7 576 109.8 548.9 99.3 512L96 512C60.7 512 32 483.3 32 448L32 160zM544 352L544 301.3L498.7 256L448 256L448 352L544 352zM224 488C224 465.9 206.1 448 184 448C161.9 448 144 465.9 144 488C144 510.1 161.9 528 184 528C206.1 528 224 510.1 224 488zM456 528C478.1 528 496 510.1 496 488C496 465.9 478.1 448 456 448C433.9 448 416 465.9 416 488C416 510.1 433.9 528 456 528z"/></svg>
                                </div>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 leading-tight">Returns - Truck</h3>
                            <div class="flex items-center">
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-red-600 mb-1">{{ $scheduleStats['returns_truck']['due_today'] ?? 0 }}</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">DUE</div>
                                </div>
                                <div class="h-12 w-px bg-gray-300"></div>
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-green-600 mb-1">{{ $scheduleStats['returns_truck']['completed_today'] ?? 0 }} </div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">COMPLETED</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-300">
                                  {{-- IF ALL DONE → SHOW TROPHY --}}
                                 @if ($allDoneReturnTruck)
                                <div class="flex items-center justify-center">
                                    <div class="flex items-center bg-gradient-to-r from-yellow-400 to-amber-500 px-3 py-1.5 rounded-full shadow-sm">

                                         <svg fill="currentColor" class="w-6 h-6 text-white mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M208.3 64L432.3 64C458.8 64 480.4 85.8 479.4 112.2C479.2 117.5 479 122.8 478.7 128L528.3 128C554.4 128 577.4 149.6 575.4 177.8C567.9 281.5 514.9 338.5 457.4 368.3C441.6 376.5 425.5 382.6 410.2 387.1C390 415.7 369 430.8 352.3 438.9L352.3 512L416.3 512C434 512 448.3 526.3 448.3 544C448.3 561.7 434 576 416.3 576L224.3 576C206.6 576 192.3 561.7 192.3 544C192.3 526.3 206.6 512 224.3 512L288.3 512L288.3 438.9C272.3 431.2 252.4 416.9 233 390.6C214.6 385.8 194.6 378.5 175.1 367.5C121 337.2 72.2 280.1 65.2 177.6C63.3 149.5 86.2 127.9 112.3 127.9L161.9 127.9C161.6 122.7 161.4 117.5 161.2 112.1C160.2 85.6 181.8 63.9 208.3 63.9zM165.5 176L113.1 176C119.3 260.7 158.2 303.1 198.3 325.6C183.9 288.3 172 239.6 165.5 176zM444 320.8C484.5 297 521.1 254.7 527.3 176L475 176C468.8 236.9 457.6 284.2 444 320.8z"/></svg>
                                        <span class="text-white font-semibold text-xs tracking-wide">ALL DONE!</span>
                                    </div>
                                </div>
                                  {{-- OTHERWISE → SHOW PROGRESS --}}
                                @else
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Progress</span>
                                    <span>{{ $progressReturnTruck }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-gradient-to-r from-purple-400 to-purple-500 transition-all duration-500" style="width: {{ $progressReturnTruck }}%"></div>
                                </div>
                                @endif


                            </div>
                        </div>

                        @php
                            $dueReturnStore       = $scheduleStats['returns_store']['due_today'] ?? 0;
                            $completedReturnStore = $scheduleStats['returns_store']['completed_today'] ?? 0;

                            $totalReturnStore = $dueReturnStore + $completedReturnStore;

                            $progressReturnStore = $totalReturnStore > 0
                                ? round(($completedReturnStore / $totalReturnStore) * 100)
                                : 0;

                            $allDoneReturnStore = $totalReturnStore > 0 && $completedReturnStore === $totalReturnStore;
                        @endphp


                        <!-- Returns - In Store -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 cursor-pointer hover:shadow-lg hover:border-blue-400 transition-all">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 group-hover:scale-110 transition-transform duration-200">

                                     <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-6 h-6 text-white" viewBox="0 0 640 640"><path d="M94.7 136.3C101.6 112.4 123.5 96 148.4 96L492.4 96C517.3 96 539.2 112.4 546.2 136.3L569.6 216.5C582.4 260.2 549.5 304 504 304C477.7 304 454.6 289.1 443.2 266.9C431.6 288.8 408.6 304 381.8 304C355.2 304 332.1 289 320.5 267C308.9 289 285.8 304 259.2 304C232.4 304 209.4 288.9 197.8 266.9C186.4 289 163.3 304 137 304C91.4 304 58.6 260.3 71.4 216.5L94.7 136.3zM160.4 416L480.4 416L480.4 349.6C488 351.2 495.9 352 503.9 352C518.2 352 531.9 349.4 544.4 344.8L544.4 496C544.4 522.5 522.9 544 496.4 544L144.4 544C117.9 544 96.4 522.5 96.4 496L96.4 344.8C108.9 349.4 122.5 352 136.9 352C145 352 152.8 351.2 160.4 349.6L160.4 416z"/></svg>
                                </div>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 leading-tight">Returns - In Store</h3>
                            <div class="flex items-center">
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-red-600 mb-1">{{ $scheduleStats['returns_store']['due_today'] ?? 0 }} </div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">DUE</div>
                                </div>
                                <div class="h-12 w-px bg-gray-300"></div>
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-green-600 mb-1"> {{ $scheduleStats['returns_store']['completed_today'] ?? 0 }}</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">COMPLETED</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-300">

                                {{-- IF ALL DONE → SHOW TROPHY --}}
                                @if ($allDoneReturnStore)
                                <div class="flex items-center justify-center">
                                    <div class="flex items-center bg-gradient-to-r from-yellow-400 to-amber-500 px-3 py-1.5 rounded-full shadow-sm">

                                         <svg fill="currentColor" class="w-6 h-6 text-white mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M208.3 64L432.3 64C458.8 64 480.4 85.8 479.4 112.2C479.2 117.5 479 122.8 478.7 128L528.3 128C554.4 128 577.4 149.6 575.4 177.8C567.9 281.5 514.9 338.5 457.4 368.3C441.6 376.5 425.5 382.6 410.2 387.1C390 415.7 369 430.8 352.3 438.9L352.3 512L416.3 512C434 512 448.3 526.3 448.3 544C448.3 561.7 434 576 416.3 576L224.3 576C206.6 576 192.3 561.7 192.3 544C192.3 526.3 206.6 512 224.3 512L288.3 512L288.3 438.9C272.3 431.2 252.4 416.9 233 390.6C214.6 385.8 194.6 378.5 175.1 367.5C121 337.2 72.2 280.1 65.2 177.6C63.3 149.5 86.2 127.9 112.3 127.9L161.9 127.9C161.6 122.7 161.4 117.5 161.2 112.1C160.2 85.6 181.8 63.9 208.3 63.9zM165.5 176L113.1 176C119.3 260.7 158.2 303.1 198.3 325.6C183.9 288.3 172 239.6 165.5 176zM444 320.8C484.5 297 521.1 254.7 527.3 176L475 176C468.8 236.9 457.6 284.2 444 320.8z"/></svg>
                                        <span class="text-white font-semibold text-xs tracking-wide">ALL DONE!</span>
                                    </div>
                                </div>
                                
                                {{-- OTHERWISE → SHOW PROGRESS --}}
                                @else

                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Progress</span>
                                    <span>{{ $progressReturnStore }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-gradient-to-r from-indigo-400 to-indigo-500 transition-all duration-500" style="width:  {{ $progressReturnStore }}%"></div>
                                </div>

                                 @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Block 2: Operations -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-300">
                    <div class="flex items-center mb-4">


                        <svg fill="currentColor" class="w-6 h-6 text-orange-600 mr-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M541.4 162.6C549 155 561.7 156.9 565.5 166.9C572.3 184.6 576 203.9 576 224C576 312.4 504.4 384 416 384C398.5 384 381.6 381.2 365.8 376L178.9 562.9C150.8 591 105.2 591 77.1 562.9C49 534.8 49 489.2 77.1 461.1L264 274.2C258.8 258.4 256 241.6 256 224C256 135.6 327.6 64 416 64C436.1 64 455.4 67.7 473.1 74.5C483.1 78.3 484.9 91 477.4 98.6L388.7 187.3C385.7 190.3 384 194.4 384 198.6L384 240C384 248.8 391.2 256 400 256L441.4 256C445.6 256 449.7 254.3 452.7 251.3L541.4 162.6z"/></svg>

                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-1">Operations</h3>
                    </div>

                    @php
    $dueMaintenance       = $equipmentStats['maintenance']['due_today'] ?? 0;
    $completedMaintenance = $equipmentStats['maintenance']['completed_today'] ?? 0;

    $totalMaintenance = $dueMaintenance + $completedMaintenance;

    $progressMaintenance = $totalMaintenance > 0
        ? round(($completedMaintenance / $totalMaintenance) * 100)
        : 0;

    $allDoneMaintenance = $totalMaintenance > 0
        && $completedMaintenance === $totalMaintenance;
@endphp


                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Maintenance Hold -->
                        <div onclick="goToEquipment('Maint. Hold')" class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 cursor-pointer hover:shadow-lg hover:border-blue-400 transition-all">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 group-hover:scale-110 transition-transform duration-200">

                                    <svg fill="currentColor" class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M541.4 162.6C549 155 561.7 156.9 565.5 166.9C572.3 184.6 576 203.9 576 224C576 312.4 504.4 384 416 384C398.5 384 381.6 381.2 365.8 376L178.9 562.9C150.8 591 105.2 591 77.1 562.9C49 534.8 49 489.2 77.1 461.1L264 274.2C258.8 258.4 256 241.6 256 224C256 135.6 327.6 64 416 64C436.1 64 455.4 67.7 473.1 74.5C483.1 78.3 484.9 91 477.4 98.6L388.7 187.3C385.7 190.3 384 194.4 384 198.6L384 240C384 248.8 391.2 256 400 256L441.4 256C445.6 256 449.7 254.3 452.7 251.3L541.4 162.6z"/></svg>
                                </div>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 leading-tight">Maintenance Hold</h3>
                            <div class="flex items-center">
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-red-600 mb-1">{{ $equipmentStats['maintenance']['due_today'] ?? 0; }}</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">DUE</div>
                                </div>
                                <div class="h-12 w-px bg-gray-300"></div>
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-green-600 mb-1">    {{ $completedMaintenance }}</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">COMPLETED</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-300">

                             @if ($allDoneMaintenance)
                                <div class="flex items-center justify-center">
                                    <div class="flex items-center bg-gradient-to-r from-yellow-400 to-amber-500 px-3 py-1.5 rounded-full shadow-sm">

                                         <svg fill="currentColor" class="w-6 h-6 text-white mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M208.3 64L432.3 64C458.8 64 480.4 85.8 479.4 112.2C479.2 117.5 479 122.8 478.7 128L528.3 128C554.4 128 577.4 149.6 575.4 177.8C567.9 281.5 514.9 338.5 457.4 368.3C441.6 376.5 425.5 382.6 410.2 387.1C390 415.7 369 430.8 352.3 438.9L352.3 512L416.3 512C434 512 448.3 526.3 448.3 544C448.3 561.7 434 576 416.3 576L224.3 576C206.6 576 192.3 561.7 192.3 544C192.3 526.3 206.6 512 224.3 512L288.3 512L288.3 438.9C272.3 431.2 252.4 416.9 233 390.6C214.6 385.8 194.6 378.5 175.1 367.5C121 337.2 72.2 280.1 65.2 177.6C63.3 149.5 86.2 127.9 112.3 127.9L161.9 127.9C161.6 122.7 161.4 117.5 161.2 112.1C160.2 85.6 181.8 63.9 208.3 63.9zM165.5 176L113.1 176C119.3 260.7 158.2 303.1 198.3 325.6C183.9 288.3 172 239.6 165.5 176zM444 320.8C484.5 297 521.1 254.7 527.3 176L475 176C468.8 236.9 457.6 284.2 444 320.8z"/></svg>
                                        <span class="text-white font-semibold text-xs tracking-wide">ALL DONE!</span>
                                    </div>
                                </div>
                               @else

                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Progress</span>
                                    <span>{{ $progressMaintenance }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-gradient-to-r from-orange-400 to-orange-500 transition-all duration-500" style="width: {{ $progressMaintenance }}%"></div>
                                </div>

                                  @endif
                            </div>
                        </div>

                        <!-- Damaged Items -->
                        <div onclick="goToEquipment('Damaged')" class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 cursor-pointer hover:shadow-lg hover:border-blue-400 transition-all">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-red-500 to-red-600 group-hover:scale-110 transition-transform duration-200">

                                    <svg fill="currentColor" class="w-6 h-6  text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M320 64C334.7 64 348.2 72.1 355.2 85L571.2 485C577.9 497.4 577.6 512.4 570.4 524.5C563.2 536.6 550.1 544 536 544L104 544C89.9 544 76.8 536.6 69.6 524.5C62.4 512.4 62.1 497.4 68.8 485L284.8 85C291.8 72.1 305.3 64 320 64zM320 416C302.3 416 288 430.3 288 448C288 465.7 302.3 480 320 480C337.7 480 352 465.7 352 448C352 430.3 337.7 416 320 416zM320 224C301.8 224 287.3 239.5 288.6 257.7L296 361.7C296.9 374.2 307.4 384 319.9 384C332.5 384 342.9 374.3 343.8 361.7L351.2 257.7C352.5 239.5 338.1 224 319.8 224z"/></svg>
                                </div>
                            </div>

                            @php
                                $dueDamaged       = $equipmentStats['damaged']['due_today'] ?? 0;
                                $completedDamaged = $equipmentStats['damaged']['completed_today'] ?? 0;

                                $totalDamaged = $dueDamaged + $completedDamaged;

                                $progressDamaged = $totalDamaged > 0
                                    ? round(($completedDamaged / $totalDamaged) * 100)
                                    : 0;

                                $allDoneDamaged = $totalDamaged > 0
                                    && $completedDamaged === $totalDamaged;
                            @endphp


                            <h3 class="text-sm font-semibold text-gray-800 mb-4 leading-tight">Damaged Items</h3>
                            <div class="flex items-center">
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-red-600 mb-1">{{ $equipmentStats['damaged']['due_today'] ?? 0; }}</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">DUE</div>
                                </div>
                                <div class="h-12 w-px bg-gray-300"></div>
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-green-600 mb-1">{{ $completedDamaged }}</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">COMPLETED</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-300">

                            @if ($allDoneDamaged)
                                <div class="flex items-center justify-center">
                                    <div class="flex items-center bg-gradient-to-r from-yellow-400 to-amber-500 px-3 py-1.5 rounded-full shadow-sm">

                                         <svg fill="currentColor" class="w-6 h-6 text-white mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M208.3 64L432.3 64C458.8 64 480.4 85.8 479.4 112.2C479.2 117.5 479 122.8 478.7 128L528.3 128C554.4 128 577.4 149.6 575.4 177.8C567.9 281.5 514.9 338.5 457.4 368.3C441.6 376.5 425.5 382.6 410.2 387.1C390 415.7 369 430.8 352.3 438.9L352.3 512L416.3 512C434 512 448.3 526.3 448.3 544C448.3 561.7 434 576 416.3 576L224.3 576C206.6 576 192.3 561.7 192.3 544C192.3 526.3 206.6 512 224.3 512L288.3 512L288.3 438.9C272.3 431.2 252.4 416.9 233 390.6C214.6 385.8 194.6 378.5 175.1 367.5C121 337.2 72.2 280.1 65.2 177.6C63.3 149.5 86.2 127.9 112.3 127.9L161.9 127.9C161.6 122.7 161.4 117.5 161.2 112.1C160.2 85.6 181.8 63.9 208.3 63.9zM165.5 176L113.1 176C119.3 260.7 158.2 303.1 198.3 325.6C183.9 288.3 172 239.6 165.5 176zM444 320.8C484.5 297 521.1 254.7 527.3 176L475 176C468.8 236.9 457.6 284.2 444 320.8z"/></svg>
                                        <span class="text-white font-semibold text-xs tracking-wide">ALL DONE!</span>
                                    </div>
                                </div>
                               @else
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Progress</span>
                                    <span> {{ $progressDamaged }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-gradient-to-r from-red-400 to-red-500 transition-all duration-500" style="width:  {{ $progressDamaged }}%"></div>
                                </div>
                                 @endif
                            </div>
                        </div>

                        <!-- Service Due -->
                        <a href="{{ route('admin.maintenance-management.equipment-service.index') }}" 
                           class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 cursor-pointer hover:shadow-lg hover:border-blue-400 transition-all block">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600 group-hover:scale-110 transition-transform duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-white">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 leading-tight">Service Due</h3>
                            <div class="flex items-center">
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-yellow-600 mb-1">{{ $pendingCount ?? 0 }}</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">{{ ($pendingCount ?? 0) > 1 ? 'EQUIPMENTS' : 'EQUIPMENT' }}</div>
                                </div>
                            </div>
                        </a>

                        <!-- Service Overdue -->
                        <a href="{{ route('admin.maintenance-management.equipment-service.index') }}" 
                           class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 cursor-pointer hover:shadow-lg hover:border-blue-400 transition-all block">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-red-500 to-red-600 group-hover:scale-110 transition-transform duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-white">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                    </svg>
                                </div>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 leading-tight">Service OverDue</h3>
                            <div class="flex items-center">
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-red-600 mb-1">{{ $overdueCount ?? 0 }}</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">{{ ($overdueCount ?? 0) > 1 ? 'EQUIPMENTS' : 'EQUIPMENT' }}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
