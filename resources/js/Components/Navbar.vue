<template>
    <!-- ========== HEADER ========== -->
    <header
        class="sticky top-4 inset-x-0 before:absolute before:inset-0 before:max-w-5xl before:mx-2 lg:before:mx-auto before:rounded-4xl before:border before:border-white/20 dark:before:border-white/10 before:backdrop-blur-xl before:bg-white/70 dark:before:bg-neutral-900/70 before:shadow-lg before:shadow-gray-200/50 dark:before:shadow-black/50 after:absolute after:inset-0 after:-z-1 after:max-w-5xl after:mx-2 lg:after:mx-auto after:rounded-4xl flex flex-wrap md:justify-start md:flex-nowrap z-50 w-full"
    >
        <nav
            class="relative max-w-5xl w-full md:flex md:items-center md:justify-between md:gap-3 ps-5 pe-2 mx-2 lg:mx-auto py-2"
        >
            <!-- Logo w/ Collapse Button -->
            <div class="flex items-center justify-between">
                <a
                    class="flex-none font-semibold text-xl text-gray-800 dark:text-neutral-200 focus:outline-hidden focus:opacity-80"
                    href="#"
                    aria-label="Brand"
                >
                    <img
                        class="h-10"
                        src="/public/images/dasmarinas-logo.png"
                        alt="Dasmariñas Logo"
                    />
                </a>

                <!-- Collapse Button -->
                <div class="md:hidden">
                    <button
                        type="button"
                        class="hs-collapse-toggle relative size-9 flex justify-center items-center text-sm font-semibold rounded-full bg-white/60 dark:bg-neutral-800/60 backdrop-blur-md border border-white/30 dark:border-white/10 text-gray-800 dark:text-white hover:bg-white/80 dark:hover:bg-neutral-700/80 focus:outline-hidden focus:bg-white/80 dark:focus:bg-neutral-700/80 disabled:opacity-50 disabled:pointer-events-none shadow-lg"
                        id="hs-header-classic-collapse"
                        aria-expanded="false"
                        aria-controls="hs-header-classic"
                        aria-label="Toggle navigation"
                        data-hs-collapse="#hs-header-classic"
                    >
                        <svg
                            class="hs-collapse-open:hidden size-4"
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <line x1="3" x2="21" y1="6" y2="6" />
                            <line x1="3" x2="21" y1="12" y2="12" />
                            <line x1="3" x2="21" y1="18" y2="18" />
                        </svg>
                        <svg
                            class="hs-collapse-open:block shrink-0 hidden size-4"
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                        <span class="sr-only">Toggle navigation</span>
                    </button>
                </div>
                <!-- End Collapse Button -->
            </div>
            <!-- End Logo w/ Collapse Button -->

            <!-- Collapse -->
            <div
                id="hs-header-classic"
                class="hs-collapse hidden overflow-hidden transition-all duration-300 basis-full grow md:block"
                aria-labelledby="hs-header-classic-collapse"
                role="region"
            >
                <div
                    class="overflow-hidden overflow-y-auto max-h-[75vh] [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100/50 dark:[&::-webkit-scrollbar-track]:bg-neutral-700/50 [&::-webkit-scrollbar-thumb]:bg-gray-300/50 dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500/50"
                >
                    <div
                        class="py-2 md:py-0 flex flex-col md:grid md:grid-cols-3 md:items-center"
                    >
                        <!-- Link groups -->
                        <div
                            class="flex flex-col md:flex-row md:items-center md:justify-center whitespace-nowrap gap-0.5 md:gap-4 md:col-span-1 md:col-start-2"
                        >
                            <a
                                class="p-2 flex items-center text-sm font-medium text-green-600 dark:text-green-400 focus:outline-hidden hover:bg-white/30 dark:hover:bg-white/10 rounded-lg transition-colors"
                                href="#"
                                aria-current="page"
                            >
                                <svg
                                    class="shrink-0 size-4 me-3 md:me-2 block md:hidden"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"
                                    />
                                    <path
                                        d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"
                                    />
                                </svg>
                                Home
                            </a>

                            <a
                                class="p-2 flex items-center text-sm font-medium text-gray-700 dark:text-neutral-200 hover:text-gray-900 dark:hover:text-white hover:bg-white/30 dark:hover:bg-white/10 rounded-lg transition-colors focus:outline-hidden"
                                href="#"
                            >
                                <svg
                                    class="shrink-0 size-4 me-3 md:me-2 block md:hidden"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"
                                    />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                About Us
                            </a>

                            <a
                                class="p-2 flex items-center text-sm font-medium text-gray-700 dark:text-neutral-200 hover:text-gray-900 dark:hover:text-white hover:bg-white/30 dark:hover:bg-white/10 rounded-lg transition-colors focus:outline-hidden"
                                href="#"
                            >
                                <svg
                                    class="shrink-0 size-4 me-3 md:me-2 block md:hidden"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M12 12h.01" />
                                    <path
                                        d="M16 6V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"
                                    />
                                    <path d="M22 13a18.15 18.15 0 0 1-20 0" />
                                    <rect
                                        width="20"
                                        height="14"
                                        x="2"
                                        y="6"
                                        rx="2"
                                    />
                                </svg>
                                Gallery
                            </a>

                            <a
                                class="p-2 flex items-center text-sm font-medium text-gray-700 dark:text-neutral-200 hover:text-gray-900 dark:hover:text-white hover:bg-white/30 dark:hover:bg-white/10 rounded-lg transition-colors focus:outline-hidden"
                                href="#"
                            >
                                <svg
                                    class="shrink-0 size-4 me-3 md:me-2 block md:hidden"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"
                                    />
                                    <path d="M18 14h-8" />
                                    <path d="M15 18h-5" />
                                    <path d="M10 6h8v4h-8V6Z" />
                                </svg>
                                Map
                            </a>

                            <!-- Dropdown -->
                            <div
                                class="hs-dropdown [--strategy:static] md:[--strategy:fixed] [--adaptive:none] md:[--adaptive:adaptive] [--is-collapse:true] md:[--is-collapse:false]"
                            >
                                <button
                                    id="hs-header-classic-dropdown"
                                    type="button"
                                    class="hs-dropdown-toggle w-full p-2 flex items-center text-sm font-medium text-gray-700 dark:text-neutral-200 hover:text-gray-900 dark:hover:text-white hover:bg-white/30 dark:hover:bg-white/10 rounded-lg transition-colors focus:outline-hidden"
                                    aria-haspopup="menu"
                                    aria-expanded="false"
                                    aria-label="Dropdown"
                                >
                                    <svg
                                        class="shrink-0 size-4 me-3 md:me-2 block md:hidden"
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="m3 10 2.5-2.5L3 5" />
                                        <path d="m3 19 2.5-2.5L3 14" />
                                        <path d="M10 6h11" />
                                        <path d="M10 12h11" />
                                        <path d="M10 18h11" />
                                    </svg>
                                    Contact Us
                                    <svg
                                        class="hs-dropdown-open:-rotate-180 md:hs-dropdown-open:rotate-0 duration-300 shrink-0 size-4 ms-auto md:ms-1"
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="m6 9 6 6 6-6" />
                                    </svg>
                                </button>

                                <div
                                    class="hs-dropdown-menu transition-[opacity,margin] duration-[0.1ms] md:duration-[150ms] hs-dropdown-open:opacity-100 opacity-0 relative w-full md:w-52 hidden z-10 top-full ps-7 md:ps-0 md:bg-white/80 dark:md:bg-neutral-900/80 md:backdrop-blur-xl md:border md:border-white/20 dark:md:border-white/10 md:rounded-lg md:shadow-xl md:shadow-gray-200/50 dark:md:shadow-black/50 before:absolute before:-top-4 before:start-0 before:w-full before:h-5 md:after:hidden after:absolute after:top-1 after:start-4.5 after:h-[calc(100%-4px)] after:border-s after:border-gray-300/50 dark:after:border-neutral-600/50"
                                    role="menu"
                                    aria-orientation="vertical"
                                    aria-labelledby="hs-header-classic-dropdown"
                                >
                                    <div class="py-1 md:px-1 space-y-0.5">
                                        <a
                                            class="py-1.5 px-2 flex items-center text-sm text-gray-700 dark:text-neutral-200 hover:text-gray-900 dark:hover:text-white hover:bg-white/40 dark:hover:bg-white/10 rounded-md transition-colors focus:outline-hidden"
                                            href="#"
                                        >
                                            About
                                        </a>

                                        <div
                                            class="hs-dropdown [--strategy:static] md:[--strategy:absolute] [--adaptive:none] md:[--trigger:hover] [--is-collapse:true] md:[--is-collapse:false] relative"
                                        >
                                            <button
                                                id="hs-header-classic-dropdown-sub"
                                                type="button"
                                                class="hs-dropdown-toggle w-full py-1.5 px-2 flex items-center text-sm text-gray-700 dark:text-neutral-200 hover:text-gray-900 dark:hover:text-white hover:bg-white/40 dark:hover:bg-white/10 rounded-md transition-colors focus:outline-hidden"
                                            >
                                                Sub Menu
                                                <svg
                                                    class="hs-dropdown-open:-rotate-180 md:hs-dropdown-open:-rotate-90 md:-rotate-90 ms-auto shrink-0 size-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    width="24"
                                                    height="24"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                >
                                                    <path d="m6 9 6 6 6-6" />
                                                </svg>
                                            </button>

                                            <div
                                                class="hs-dropdown-menu transition-[opacity,margin] duration-[0.1ms] md:duration-[150ms] hs-dropdown-open:opacity-100 opacity-0 relative md:w-48 hidden z-10 md:mt-2 md:mx-2.5! md:top-0 md:end-full ps-7 md:ps-0 md:bg-white/80 dark:md:bg-neutral-900/80 md:backdrop-blur-xl md:border md:border-white/20 dark:md:border-white/10 md:rounded-lg md:shadow-xl before:hidden md:before:block before:absolute before:-end-5 before:top-0 before:h-full before:w-5 md:after:hidden after:absolute after:top-1 after:start-4.5 after:h-[calc(100%-4px)] after:border-s after:border-gray-300/50 dark:after:border-neutral-600/50"
                                                role="menu"
                                                aria-orientation="vertical"
                                                aria-labelledby="hs-header-classic-dropdown-sub"
                                            >
                                                <div
                                                    class="p-1 space-y-0.5 md:space-y-1"
                                                >
                                                    <a
                                                        class="py-1.5 px-2 flex items-center text-sm text-gray-700 dark:text-neutral-200 hover:text-gray-900 dark:hover:text-white hover:bg-white/40 dark:hover:bg-white/10 rounded-md transition-colors focus:outline-hidden"
                                                        href="#"
                                                    >
                                                        About
                                                    </a>

                                                    <a
                                                        class="py-1.5 px-2 flex items-center text-sm text-gray-700 dark:text-neutral-200 hover:text-gray-900 dark:hover:text-white hover:bg-white/40 dark:hover:bg-white/10 rounded-md transition-colors focus:outline-hidden"
                                                        href="#"
                                                    >
                                                        Downloads
                                                    </a>

                                                    <a
                                                        class="py-1.5 px-2 flex items-center text-sm text-gray-700 dark:text-neutral-200 hover:text-gray-900 dark:hover:text-white hover:bg-white/40 dark:hover:bg-white/10 rounded-md transition-colors focus:outline-hidden"
                                                        href="#"
                                                    >
                                                        Team Account
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <a
                                            class="py-1.5 px-2 flex items-center text-sm text-gray-700 dark:text-neutral-200 hover:text-gray-900 dark:hover:text-white hover:bg-white/40 dark:hover:bg-white/10 rounded-md transition-colors focus:outline-hidden"
                                            href="#"
                                        >
                                            Downloads
                                        </a>

                                        <a
                                            class="py-1.5 px-2 flex items-center text-sm text-gray-700 dark:text-neutral-200 hover:text-gray-900 dark:hover:text-white hover:bg-white/40 dark:hover:bg-white/10 rounded-md transition-colors focus:outline-hidden"
                                            href="#"
                                        >
                                            Team Account
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- End Dropdown -->
                        </div>

                        <!-- Button Group -->
                        <div
                            class="flex items-center justify-center md:justify-end gap-x-1.5 md:col-span-1 md:col-start-3 md:ps-2.5 md:ms-1.5 before:block before:absolute before:top-1/2 before:-start-px before:w-px before:h-4 before:bg-gray-300/50 dark:before:bg-neutral-600/50 before:-translate-y-1/2"
                        >
                            <a
                                class="p-2 w-full flex items-center text-sm font-medium text-gray-700 dark:text-neutral-200 hover:text-gray-900 dark:hover:text-white hover:bg-white/30 dark:hover:bg-white/10 rounded-lg transition-colors focus:outline-hidden md:w-21"
                                href="#"
                            >
                                <svg
                                    class="shrink-0 size-4 me-3 md:me-2"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"
                                    />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                Log in
                            </a>
                            <!-- Dark Mode / Light Mode -->
                            <div>
                                <button
                                    type="button"
                                    class="hs-dark-mode hs-dark-mode-active:hidden flex items-center gap-x-2 py-2 px-3 rounded-full text-sm bg-gray-900/10 backdrop-blur-sm text-gray-900 hover:bg-gray-900/20 focus:outline-hidden shadow-md transition-all"
                                    data-hs-theme-click-value="dark"
                                >
                                    <svg
                                        class="shrink-0 size-4"
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path
                                            d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"
                                        ></path>
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    class="hs-dark-mode hs-dark-mode-active:inline-flex hidden items-center gap-x-2 py-2 px-3 bg-white/20 backdrop-blur-sm rounded-full text-sm text-white hover:bg-white/30 focus:outline-hidden shadow-md transition-all"
                                    data-hs-theme-click-value="light"
                                >
                                    <svg
                                        class="shrink-0 size-4"
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <circle cx="12" cy="12" r="4"></circle>
                                        <path d="M12 2v2"></path>
                                        <path d="M12 20v2"></path>
                                        <path d="m4.93 4.93 1.41 1.41"></path>
                                        <path d="m17.66 17.66 1.41 1.41"></path>
                                        <path d="M2 12h2"></path>
                                        <path d="M20 12h2"></path>
                                        <path d="m6.34 17.66-1.41 1.41"></path>
                                        <path d="m19.07 4.93-1.41 1.41"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <!-- End Button Group -->
                    </div>
                </div>
            </div>
            <!-- End Collapse -->
        </nav>
    </header>
    <!-- ========== END HEADER ========== -->
</template>
