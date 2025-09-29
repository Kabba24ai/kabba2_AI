<header x-data="{ menuToggle: false }"
    class="no-print sticky top-0 z-99999 flex w-full border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 lg:border-b">
    <div class="flex grow flex-col items-center justify-between lg:flex-row lg:px-6">
        <div
            class="flex w-full items-center justify-between gap-2 border-b border-gray-200 px-3 py-3 dark:border-gray-800 sm:gap-4 lg:justify-normal lg:border-b-0 lg:px-0 lg:py-4">
            <!-- Hamburger Toggle BTN -->
            <button
                :class="sidebarToggle ? 'lg:bg-transparent dark:lg:bg-transparent bg-gray-100 dark:bg-gray-800' : ''"
                class="z-99999 flex h-10 w-10 items-center justify-center rounded-lg border-gray-200 text-gray-500 dark:border-gray-800 dark:text-gray-400 lg:h-11 lg:w-11 lg:border"
                @click.stop="sidebarToggle = !sidebarToggle">
                {{-- Desktop Sidebar Toggle (hamburger icon) --}}
                <x-heroicon-c-bars-3-center-left class="hidden w-5 h-5 lg:block" />

                {{-- Mobile Menu Icon (show when sidebar is closed) --}}
                <span :class="sidebarToggle ? 'hidden' : 'block lg:hidden'" class="w-6 h-6">
                    <x-heroicon-c-bars-3-center-left class="w-6 h-6" />
                </span>

                {{-- Mobile Close Icon (show when sidebar is open) --}}
                <span :class="sidebarToggle ? 'block lg:hidden' : 'hidden'" class="w-6 h-6">
                    <x-heroicon-o-x-mark class="w-6 h-6" />
                </span>

            </button>

            <!-- Hamburger Toggle BTN -->

            <a href="index.html" class="lg:hidden">
                <img class="dark:hidden w-10"
                    src="{{ asset('storage/admin/images/logo/rent-n-king-logo-outro.png') }}" alt="Logo" />
                <img class="hidden dark:block w-10"
                    src="{{ asset('storage/admin/images/logo/rent-n-king-logo-outro.png') }}" alt="Logo" />
            </a>

            <!-- Application nav menu button -->
            <button
                class="z-99999 flex h-10 w-10 items-center justify-center rounded-lg text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 lg:hidden"
                :class="menuToggle ? 'bg-gray-100 dark:bg-gray-800' : ''" @click.stop="menuToggle = !menuToggle">
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M5.99902 10.4951C6.82745 10.4951 7.49902 11.1667 7.49902 11.9951V12.0051C7.49902 12.8335 6.82745 13.5051 5.99902 13.5051C5.1706 13.5051 4.49902 12.8335 4.49902 12.0051V11.9951C4.49902 11.1667 5.1706 10.4951 5.99902 10.4951ZM17.999 10.4951C18.8275 10.4951 19.499 11.1667 19.499 11.9951V12.0051C19.499 12.8335 18.8275 13.5051 17.999 13.5051C17.1706 13.5051 16.499 12.8335 16.499 12.0051V11.9951C16.499 11.1667 17.1706 10.4951 17.999 10.4951ZM13.499 11.9951C13.499 11.1667 12.8275 10.4951 11.999 10.4951C11.1706 10.4951 10.499 11.1667 10.499 11.9951V12.0051C10.499 12.8335 11.1706 13.5051 11.999 13.5051C12.8275 13.5051 13.499 12.8335 13.499 12.0051V11.9951Z"
                        fill="" />
                </svg>
            </button>
            <!-- Application nav menu button -->

            {{-- <div class="hidden lg:block">
                <form>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2">
                            <svg class="fill-gray-500 dark:fill-gray-400" width="20" height="20"
                                viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M3.04175 9.37363C3.04175 5.87693 5.87711 3.04199 9.37508 3.04199C12.8731 3.04199 15.7084 5.87693 15.7084 9.37363C15.7084 12.8703 12.8731 15.7053 9.37508 15.7053C5.87711 15.7053 3.04175 12.8703 3.04175 9.37363ZM9.37508 1.54199C5.04902 1.54199 1.54175 5.04817 1.54175 9.37363C1.54175 13.6991 5.04902 17.2053 9.37508 17.2053C11.2674 17.2053 13.003 16.5344 14.357 15.4176L17.177 18.238C17.4699 18.5309 17.9448 18.5309 18.2377 18.238C18.5306 17.9451 18.5306 17.4703 18.2377 17.1774L15.418 14.3573C16.5365 13.0033 17.2084 11.2669 17.2084 9.37363C17.2084 5.04817 13.7011 1.54199 9.37508 1.54199Z"
                                    fill="" />
                            </svg>
                        </span>
                        <input id="search-input" type="text" placeholder="Search or type command..."
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-200 bg-transparent py-2.5 pl-12 pr-14 text-sm text-gray-800 shadow-brand-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-800 dark:bg-gray-900 dark:bg-white/[0.03] dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800 xl:w-[430px]" />

                        <button id="search-button"
                            class="absolute right-2.5 top-1/2 inline-flex -translate-y-1/2 items-center gap-0.5 rounded-lg border border-gray-200 bg-gray-50 px-[7px] py-[4.5px] text-xs -tracking-[0.2px] text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                            <span> ⌘ </span>
                            <span> K </span>
                        </button>
                    </div>
                </form>
            </div> --}}
        </div>

        <div :class="menuToggle ? 'flex' : 'hidden'"
            class="w-full items-center justify-between gap-4 px-5 py-4 shadow-brand-md lg:flex lg:justify-end lg:px-0 lg:shadow-none">
            <div class="flex items-center gap-2 2xsm:gap-3">
                <!-- Dark Mode Toggler -->
                {{-- <button @click.prevent="darkMode = !darkMode"
                    class="hover:text-dark-900 relative flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white">
                    <x-heroicon-s-sun class="w-5 h-5 block dark:hidden" />

                    <x-heroicon-s-moon class="w-5 h-5 hidden dark:block" />
                </button> --}}
                <!-- Dark Mode Toggler -->

                <!-- Notification Menu Area -->
                {{-- <div class="relative" x-data="{ dropdownOpen: false, notifying: true }" @click.outside="dropdownOpen = false">
                    <button
                        class="hover:text-dark-900 relative flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                        @click.prevent="dropdownOpen = ! dropdownOpen; notifying = false">
                        <span :class="!notifying ? 'hidden' : 'flex'"
                            class="absolute right-0 top-0.5 z-1 h-2 w-2 rounded-full bg-orange-400">
                            <span
                                class="absolute -z-1 inline-flex h-full w-full animate-ping rounded-full bg-orange-400 opacity-75"></span>
                        </span>
                        <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z"
                                fill="" />
                        </svg>
                    </button>

                    <!-- Dropdown Start -->
                    <div x-show="dropdownOpen"
                        class="absolute -right-[240px] mt-[17px] flex h-[480px] w-[350px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-brand-lg dark:border-gray-800 dark:bg-gray-dark sm:w-[361px] lg:right-0">
                        <div
                            class="mb-3 flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                            <h5 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                Notification
                            </h5>

                            <button @click="dropdownOpen = false" class="text-gray-500 dark:text-gray-400">
                                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"
                                        fill="" />
                                </svg>
                            </button>
                        </div>

                        <ul class="custom-scrollbar flex h-auto flex-col overflow-y-auto">
                            <li>
                                <a class="flex gap-3 rounded-lg border-b border-gray-100 p-3 px-4.5 py-3 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5"
                                    href="#">
                                    <span class="relative z-1 block h-10 w-full max-w-10 rounded-full">
                                        <img src="./images/user/user-02.jpg" alt="User"
                                            class="overflow-hidden rounded-full" />
                                        <span
                                            class="absolute bottom-0 right-0 z-10 h-2.5 w-full max-w-2.5 rounded-full border-[1.5px] border-white bg-success-500 dark:border-gray-900"></span>
                                    </span>

                                    <span class="block">
                                        <span class="mb-1.5 block text-brand-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-medium text-gray-800 dark:text-white/90">Terry
                                                Franci</span>
                                            requests permission to change
                                            <span class="font-medium text-gray-800 dark:text-white/90">Project -
                                                Nganter App</span>
                                        </span>

                                        <span
                                            class="flex items-center gap-2 text-brand-xs text-gray-500 dark:text-gray-400">
                                            <span>Project</span>
                                            <span class="h-1 w-1 rounded-full bg-gray-400"></span>
                                            <span>5 min ago</span>
                                        </span>
                                    </span>
                                </a>
                            </li>

                            <li>
                                <a class="flex gap-3 rounded-lg border-b border-gray-100 p-3 px-4.5 py-3 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5"
                                    href="#">
                                    <span class="relative z-1 block h-10 w-full max-w-10 rounded-full">
                                        <img src="./images/user/user-03.jpg" alt="User"
                                            class="overflow-hidden rounded-full" />
                                        <span
                                            class="absolute bottom-0 right-0 z-10 h-2.5 w-full max-w-2.5 rounded-full border-[1.5px] border-white bg-success-500 dark:border-gray-900"></span>
                                    </span>

                                    <span class="block">
                                        <span class="mb-1.5 block text-brand-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-medium text-gray-800 dark:text-white/90">Alena
                                                Franci</span>
                                            requests permission to change
                                            <span class="font-medium text-gray-800 dark:text-white/90">Project -
                                                Nganter App</span>
                                        </span>

                                        <span
                                            class="flex items-center gap-2 text-brand-xs text-gray-500 dark:text-gray-400">
                                            <span>Project</span>
                                            <span class="h-1 w-1 rounded-full bg-gray-400"></span>
                                            <span>8 min ago</span>
                                        </span>
                                    </span>
                                </a>
                            </li>

                            <li>
                                <a class="flex gap-3 rounded-lg border-b border-gray-100 p-3 px-4.5 py-3 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5"
                                    href="#">
                                    <span class="relative z-1 block h-10 w-full max-w-10 rounded-full">
                                        <img src="./images/user/user-04.jpg" alt="User"
                                            class="overflow-hidden rounded-full" />
                                        <span
                                            class="absolute bottom-0 right-0 z-10 h-2.5 w-full max-w-2.5 rounded-full border-[1.5px] border-white bg-success-500 dark:border-gray-900"></span>
                                    </span>

                                    <span class="block">
                                        <span class="mb-1.5 block text-brand-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-medium text-gray-800 dark:text-white/90">Jocelyn
                                                Kenter</span>
                                            requests permission to change
                                            <span class="font-medium text-gray-800 dark:text-white/90">Project -
                                                Nganter App</span>
                                        </span>

                                        <span
                                            class="flex items-center gap-2 text-brand-xs text-gray-500 dark:text-gray-400">
                                            <span>Project</span>
                                            <span class="h-1 w-1 rounded-full bg-gray-400"></span>
                                            <span>15 min ago</span>
                                        </span>
                                    </span>
                                </a>
                            </li>

                            <li>
                                <a class="flex gap-3 rounded-lg border-b border-gray-100 p-3 px-4.5 py-3 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5"
                                    href="#">
                                    <span class="relative z-1 block h-10 w-full max-w-10 rounded-full">
                                        <img src="./images/user/user-05.jpg" alt="User"
                                            class="overflow-hidden rounded-full" />
                                        <span
                                            class="absolute bottom-0 right-0 z-10 h-2.5 w-full max-w-2.5 rounded-full border-[1.5px] border-white bg-error-500 dark:border-gray-900"></span>
                                    </span>

                                    <span class="block">
                                        <span class="mb-1.5 block text-brand-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-medium text-gray-800 dark:text-white/90">Brandon
                                                Philips</span>
                                            requests permission to change
                                            <span class="font-medium text-gray-800 dark:text-white/90">Project -
                                                Nganter App</span>
                                        </span>

                                        <span
                                            class="flex items-center gap-2 text-brand-xs text-gray-500 dark:text-gray-400">
                                            <span>Project</span>
                                            <span class="h-1 w-1 rounded-full bg-gray-400"></span>
                                            <span>1 hr ago</span>
                                        </span>
                                    </span>
                                </a>
                            </li>

                            <li>
                                <a class="flex gap-3 rounded-lg border-b border-gray-100 p-3 px-4.5 py-3 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5"
                                    href="#">
                                    <span class="relative z-1 block h-10 w-full max-w-10 rounded-full">
                                        <img src="./images/user/user-02.jpg" alt="User"
                                            class="overflow-hidden rounded-full" />
                                        <span
                                            class="absolute bottom-0 right-0 z-10 h-2.5 w-full max-w-2.5 rounded-full border-[1.5px] border-white bg-success-500 dark:border-gray-900"></span>
                                    </span>

                                    <span class="block">
                                        <span class="mb-1.5 block text-brand-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-medium text-gray-800 dark:text-white/90">Terry
                                                Franci</span>
                                            requests permission to change
                                            <span class="font-medium text-gray-800 dark:text-white/90">Project -
                                                Nganter App</span>
                                        </span>

                                        <span
                                            class="flex items-center gap-2 text-brand-xs text-gray-500 dark:text-gray-400">
                                            <span>Project</span>
                                            <span class="h-1 w-1 rounded-full bg-gray-400"></span>
                                            <span>5 min ago</span>
                                        </span>
                                    </span>
                                </a>
                            </li>

                            <li>
                                <a class="flex gap-3 rounded-lg border-b border-gray-100 p-3 px-4.5 py-3 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5"
                                    href="#">
                                    <span class="relative z-1 block h-10 w-full max-w-10 rounded-full">
                                        <img src="./images/user/user-03.jpg" alt="User"
                                            class="overflow-hidden rounded-full" />
                                        <span
                                            class="absolute bottom-0 right-0 z-10 h-2.5 w-full max-w-2.5 rounded-full border-[1.5px] border-white bg-success-500 dark:border-gray-900"></span>
                                    </span>

                                    <span class="block">
                                        <span class="mb-1.5 block text-brand-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-medium text-gray-800 dark:text-white/90">Alena
                                                Franci</span>
                                            requests permission to change
                                            <span class="font-medium text-gray-800 dark:text-white/90">Project -
                                                Nganter App</span>
                                        </span>

                                        <span
                                            class="flex items-center gap-2 text-brand-xs text-gray-500 dark:text-gray-400">
                                            <span>Project</span>
                                            <span class="h-1 w-1 rounded-full bg-gray-400"></span>
                                            <span>8 min ago</span>
                                        </span>
                                    </span>
                                </a>
                            </li>

                            <li>
                                <a class="flex gap-3 rounded-lg border-b border-gray-100 p-3 px-4.5 py-3 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5"
                                    href="#">
                                    <span class="relative z-1 block h-10 w-full max-w-10 rounded-full">
                                        <img src="./images/user/user-04.jpg" alt="User"
                                            class="overflow-hidden rounded-full" />
                                        <span
                                            class="absolute bottom-0 right-0 z-10 h-2.5 w-full max-w-2.5 rounded-full border-[1.5px] border-white bg-success-500 dark:border-gray-900"></span>
                                    </span>

                                    <span class="block">
                                        <span class="mb-1.5 block text-brand-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-medium text-gray-800 dark:text-white/90">Jocelyn
                                                Kenter</span>
                                            requests permission to change
                                            <span class="font-medium text-gray-800 dark:text-white/90">Project -
                                                Nganter App</span>
                                        </span>

                                        <span
                                            class="flex items-center gap-2 text-brand-xs text-gray-500 dark:text-gray-400">
                                            <span>Project</span>
                                            <span class="h-1 w-1 rounded-full bg-gray-400"></span>
                                            <span>15 min ago</span>
                                        </span>
                                    </span>
                                </a>
                            </li>

                            <li>
                                <a class="flex gap-3 rounded-lg border-b border-gray-100 p-3 px-4.5 py-3 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5"
                                    href="#">
                                    <span class="relative z-1 block h-10 w-full max-w-10 rounded-full">
                                        <img src="./images/user/user-05.jpg" alt="User"
                                            class="overflow-hidden rounded-full" />
                                        <span
                                            class="absolute bottom-0 right-0 z-10 h-2.5 w-full max-w-2.5 rounded-full border-[1.5px] border-white bg-error-500 dark:border-gray-900"></span>
                                    </span>

                                    <span class="block">
                                        <span class="mb-1.5 block text-brand-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-medium text-gray-800 dark:text-white/90">Brandon
                                                Philips</span>
                                            requests permission to change
                                            <span class="font-medium text-gray-800 dark:text-white/90">Project -
                                                Nganter App</span>
                                        </span>

                                        <span
                                            class="flex items-center gap-2 text-brand-xs text-gray-500 dark:text-gray-400">
                                            <span>Project</span>
                                            <span class="h-1 w-1 rounded-full bg-gray-400"></span>
                                            <span>1 hr ago</span>
                                        </span>
                                    </span>
                                </a>
                            </li>
                        </ul>

                        <a href="#"
                            class="mt-3 flex justify-center rounded-lg border border-gray-300 bg-white p-3 text-brand-sm font-medium text-gray-700 shadow-brand-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                            View All Notification
                        </a>
                    </div>
                    <!-- Dropdown End -->
                </div> --}}
                <!-- Notification Menu Area -->
            </div>

            <!-- User Area -->
            <div class="relative" x-data="{ dropdownOpen: false }" @click.outside="dropdownOpen = false">
                <a class="flex items-center text-gray-700 dark:text-gray-400" href="#"
                    @click.prevent="dropdownOpen = ! dropdownOpen">
                    <span class="mr-3 h-11 w-11 overflow-hidden rounded-full">
                        <img src="{{ asset('storage/admin/images/logo/rent-n-king-logo-outro.png') }}"
                            class="w-10" alt="User" />
                    </span>

                    <span class="mr-1 block text-brand-sm font-medium"> {{ auth()->user()->first_name }} </span>

                    <span :class="{ 'rotate-180': dropdownOpen }" class="transition-transform">
                        <x-heroicon-o-chevron-down class="w-5 h-5 stroke-gray-500 dark:stroke-gray-400" />
                    </span>
                </a>

                <!-- Dropdown Start -->
                <div x-show="dropdownOpen"
                    class="absolute right-0 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-brand-lg dark:border-gray-800 dark:bg-gray-dark">
                    <div>
                        <span class="block text-brand-sm font-medium text-gray-700 dark:text-gray-400">
                            {{ auth()->user()->full_name }}
                        </span>
                        <span class="mt-0.5 block text-brand-xs text-gray-500 dark:text-gray-400">
                            {{ auth()->user()->email }}
                        </span>
                    </div>

                    {{-- <ul class="flex flex-col gap-1 border-b border-gray-200 pb-3 pt-4 dark:border-gray-800">
                        <li>
                            <a href="profile.html"
                                class="group flex items-center gap-3 rounded-lg px-3 py-2 text-brand-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                                <x-heroicon-o-user-circle
                                    class="w-6 h-6 text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300" />

                                Edit profile
                            </a>
                        </li>
                        <li>
                            <a href="chat.html"
                                class="group flex items-center gap-3 rounded-lg px-3 py-2 text-brand-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                                <x-heroicon-o-cog-6-tooth
                                    class="w-6 h-6 text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300" />
                                Account settings
                            </a>
                        </li>
                        <li>
                            <a href="profile.html"
                                class="group flex items-center gap-3 rounded-lg px-3 py-2 text-brand-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                                <x-heroicon-o-question-mark-circle
                                    class="w-6 h-6 text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300" />

                                Support
                            </a>
                        </li>
                    </ul> --}}
                    <a href="{{ route('admin.auth.logout') }}"
                        class="group mt-3 flex items-center gap-3 rounded-lg px-3 py-2 text-brand-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                        <x-heroicon-o-arrow-left-on-rectangle
                            class="w-6 h-6 text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300" />
                        Sign out
                    </a>
                </div>
                <!-- Dropdown End -->
            </div>
            <!-- User Area -->
        </div>
    </div>
</header>
