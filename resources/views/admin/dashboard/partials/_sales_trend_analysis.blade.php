<div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-gray-900">Sales Trend Analysis</h2>
                <div class="flex items-center space-x-2">
                    <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">

                        <svg fill="currentColor" class="w-6 h-6 " xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M544.1 256L552 256C565.3 256 576 245.3 576 232L576 88C576 78.3 570.2 69.5 561.2 65.8C552.2 62.1 541.9 64.2 535 71L483.3 122.8C439 86.1 382 64 320 64C191 64 84.3 159.4 66.6 283.5C64.1 301 76.2 317.2 93.7 319.7C111.2 322.2 127.4 310 129.9 292.6C143.2 199.5 223.3 128 320 128C364.4 128 405.2 143 437.7 168.3L391 215C384.1 221.9 382.1 232.2 385.8 241.2C389.5 250.2 398.3 256 408 256L544.1 256zM573.5 356.5C576 339 563.8 322.8 546.4 320.3C529 317.8 512.7 330 510.2 347.4C496.9 440.4 416.8 511.9 320.1 511.9C275.7 511.9 234.9 496.9 202.4 471.6L249 425C255.9 418.1 257.9 407.8 254.2 398.8C250.5 389.8 241.7 384 232 384L88 384C74.7 384 64 394.7 64 408L64 552C64 561.7 69.8 570.5 78.8 574.2C87.8 577.9 98.1 575.8 105 569L156.8 517.2C201 553.9 258 576 320 576C449 576 555.7 480.6 573.4 356.5z"/></svg>
                    </button>
                    <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">

                        <svg fill="currentColor" class="w-6 h-6 " xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M352 96C352 78.3 337.7 64 320 64C302.3 64 288 78.3 288 96L288 306.7L246.6 265.3C234.1 252.8 213.8 252.8 201.3 265.3C188.8 277.8 188.8 298.1 201.3 310.6L297.3 406.6C309.8 419.1 330.1 419.1 342.6 406.6L438.6 310.6C451.1 298.1 451.1 277.8 438.6 265.3C426.1 252.8 405.8 252.8 393.3 265.3L352 306.7L352 96zM160 384C124.7 384 96 412.7 96 448L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 448C544 412.7 515.3 384 480 384L433.1 384L376.5 440.6C345.3 471.8 294.6 471.8 263.4 440.6L206.9 384L160 384zM464 440C477.3 440 488 450.7 488 464C488 477.3 477.3 488 464 488C450.7 488 440 477.3 440 464C440 450.7 450.7 440 464 440z"/></svg>
                    </button>
                </div>
            </div>

            <!-- Sales Metrics Cards -->
            <div class="flex justify-center mb-6">
                <div class="flex flex-wrap justify-center gap-4 max-w-5xl">
                    <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200 min-w-[168px] text-center">
                        <div class="text-xs text-gray-600 font-medium mb-1">Growth Rate</div>

                        <div id="growth-rate-wrapper"
                            class="text-lg font-semibold flex items-center justify-center whitespace-nowrap">

                            <span id="growth-icon" class="mr-1"></span>
                            <span id="growth-rate-value"></span>
                        </div>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-xl border border-blue-200 min-w-[168px] text-center">
                        <div class="text-xs text-gray-600 font-medium mb-1">Total Sales</div>
                        <div id="total-sales" class="text-lg font-semibold text-blue-800"></div>
                    </div>

                    <div class="bg-green-50 p-4 rounded-xl border border-green-200 min-w-[168px] text-center">
                        <div class="text-xs text-gray-600 font-medium mb-1">Previous Period</div>
                        <div id="previous-total" class="text-lg font-semibold text-green-800"></div>
                    </div>

                    <div class="bg-purple-50 p-4 rounded-xl border border-purple-200 min-w-[168px] text-center">
                        <div class="text-xs text-gray-600 font-medium mb-1">Daily Average</div>
                        <div id="daily-average" class="text-lg font-semibold text-purple-800"></div>
                    </div>

                </div>
            </div>

            <!-- Period Selector -->
            <div class="flex space-x-2 mb-6">

                <button id="btn-rolling30"
                    onclick="dashboardApp.changeSalesPeriod('rolling30')"
                    class="px-6 py-3 rounded-lg text-md font-medium transition-colors">
                    Rolling 30 Days
                </button>

                <button id="btn-currentMonth"
                    onclick="dashboardApp.changeSalesPeriod('currentMonth')"
                    class="px-6 py-3 rounded-lg text-md font-medium transition-colors">
                    Current Month
                </button>

                <button id="btn-lastMonth"
                    onclick="dashboardApp.changeSalesPeriod('lastMonth')"
                    class="px-6 py-3 rounded-lg text-md font-medium transition-colors">
                    Last Month
                </button>

            </div>


            <div id="salesChart" class="chart-container" style="height: 400px;"></div>
