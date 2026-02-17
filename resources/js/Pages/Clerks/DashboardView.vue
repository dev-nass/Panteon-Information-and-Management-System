<template>
    <!-- Navigation Toggle -->
    <div class="lg:hidden flex justify-start text-center border">
        <button
            type="button"
            class="py-2 px-3 inline-flex justify-center items-center gap-x-2 text-start bg-gray-900 dark:bg-white border border-transparent text-white dark:text-neutral-800 text-sm font-medium rounded-lg shadow-2xs hover:bg-gray-800 dark:hover:bg-neutral-100 focus:outline-hidden focus:bg-gray-800 dark:focus:bg-neutral-100"
            aria-haspopup="dialog"
            aria-expanded="false"
            aria-controls="hs-sidebar-content-push-to-mini-sidebar"
            aria-label="Toggle navigation"
            data-hs-overlay="#hs-sidebar-content-push-to-mini-sidebar"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="lucide lucide-text-align-start-icon lucide-text-align-start"
            >
                <path d="M21 5H3" />
                <path d="M15 12H3" />
                <path d="M17 19H3" />
            </svg>
        </button>
    </div>
    <!-- End Navigation Toggle -->

    <!-- Sidebar -->
    <div
        id="hs-sidebar-content-push-to-mini-sidebar"
        class="group hs-overlay [--auto-close:lg] hs-overlay-minified:w-13 lg:block lg:translate-x-0 lg:end-auto lg:bottom-0 w-64 hs-overlay-open:translate-x-0 -translate-x-full transition-all duration-300 transform h-full hidden overflow-x-hidden fixed top-0 start-0 bottom-0 z-60 bg-white dark:bg-neutral-800 border-e border-gray-200 dark:border-neutral-700"
        role="dialog"
        tabindex="-1"
        aria-label="Sidebar"
    >
        <div class="relative flex flex-col h-full max-h-full">
            <!-- Header -->
            <header class="py-4 px-2 flex justify-between items-center gap-x-2">
                <a
                    class="flex-none font-semibold text-xl text-gray-800 dark:text-white focus:outline-hidden focus:opacity-80 hs-overlay-minified:hidden"
                    href="#"
                    aria-label="Brand"
                    >Brand</a
                >

                <div class="lg:hidden">
                    <!-- Close Button -->
                    <button
                        type="button"
                        class="flex justify-center items-center gap-x-3 size-6 bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 text-sm text-gray-600 dark:text-neutral-300 hover:bg-gray-50 dark:hover:bg-neutral-700 rounded-full disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-50 dark:focus:bg-neutral-700"
                        data-hs-overlay="#hs-sidebar-content-push-to-mini-sidebar"
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
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                        <span class="sr-only">Close</span>
                    </button>
                    <!-- End Close Button -->
                </div>
                <div class="hidden lg:block">
                    <!-- Toggle Button -->
                    <button
                        type="button"
                        class="flex justify-center items-center flex-none gap-x-3 size-9 text-sm text-gray-600 dark:text-neutral-300 hover:bg-gray-100 dark:hover:bg-neutral-700 rounded-full disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-700"
                        aria-haspopup="dialog"
                        aria-expanded="false"
                        aria-controls="hs-sidebar-content-push-to-mini-sidebar"
                        aria-label="Minify navigation"
                        data-hs-overlay-minifier="#hs-sidebar-content-push-to-mini-sidebar"
                    >
                        <svg
                            class="hidden hs-overlay-minified:block shrink-0 size-4"
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
                            <rect width="18" height="18" x="3" y="3" rx="2" />
                            <path d="M15 3v18" />
                            <path d="m8 9 3 3-3 3" />
                        </svg>
                        <svg
                            class="hs-overlay-minified:hidden shrink-0 size-4"
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
                            <rect width="18" height="18" x="3" y="3" rx="2" />
                            <path d="M15 3v18" />
                            <path d="m10 15-3-3 3-3" />
                        </svg>
                        <span class="sr-only">Navigation Toggle</span>
                    </button>
                    <!-- End Toggle Button -->
                </div>
            </header>
            <!-- End Header -->

            <!-- Body -->
            <nav
                class="overflow-hidden h-full overflow-y-auto [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-none [&::-webkit-scrollbar-track]:bg-gray-100 dark:[&::-webkit-scrollbar-track]:bg-neutral-700 [&::-webkit-scrollbar-thumb]:bg-gray-300 dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500"
            >
                <div class="pb-0 px-2 w-full flex flex-col flex-wrap">
                    <ul class="space-y-1">
                        <li>
                            <a
                                class="min-h-[36px] flex items-center gap-x-3.5 py-2 px-2.5 bg-gray-100 dark:bg-neutral-700 text-sm text-gray-800 dark:text-neutral-200 rounded-lg hover:bg-gray-100 dark:hover:bg-neutral-700 focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-700"
                                href="#"
                            >
                                <svg
                                    class="size-4"
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
                                        d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"
                                    />
                                    <polyline points="9 22 9 12 15 12 15 22" />
                                </svg>
                                <span class="hs-overlay-minified:hidden"
                                    >Dashboard</span
                                >
                            </a>
                        </li>

                        <li>
                            <a
                                class="min-h-[36px] w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 dark:text-neutral-200 rounded-lg hover:bg-gray-100 dark:hover:bg-neutral-700 focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-700"
                                href="#"
                            >
                                <svg
                                    class="size-4"
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
                                    <rect
                                        width="18"
                                        height="18"
                                        x="3"
                                        y="4"
                                        rx="2"
                                        ry="2"
                                    />
                                    <line x1="16" x2="16" y1="2" y2="6" />
                                    <line x1="8" x2="8" y1="2" y2="6" />
                                    <line x1="3" x2="21" y1="10" y2="10" />
                                    <path d="M8 14h.01" />
                                    <path d="M12 14h.01" />
                                    <path d="M16 14h.01" />
                                    <path d="M8 18h.01" />
                                    <path d="M12 18h.01" />
                                    <path d="M16 18h.01" />
                                </svg>
                                <span
                                    class="text-nowrap hs-overlay-minified:hidden"
                                    >Calendar
                                    <span
                                        class="ms-auto py-0.5 px-1.5 inline-flex items-center gap-x-1.5 text-xs bg-gray-200 dark:bg-neutral-600 text-gray-800 dark:text-neutral-200 rounded-full"
                                        >New</span
                                    ></span
                                >
                            </a>
                        </li>
                        <li>
                            <a
                                class="min-h-[36px] w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 dark:text-neutral-200 rounded-lg hover:bg-gray-100 dark:hover:bg-neutral-700 focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-700"
                                href="#"
                            >
                                <svg
                                    class="size-4"
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
                                        d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"
                                    />
                                    <path
                                        d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"
                                    />
                                </svg>
                                <span class="hs-overlay-minified:hidden"
                                    >Documentation</span
                                >
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            <!-- End Body -->

            <!-- Footer -->
            <footer class="mt-auto p-2 border-t border-sidebar-divider">
                <!-- Account Dropdown -->
                <div
                    class="hs-dropdown [--strategy:absolute] [--auto-close:inside] relative w-full inline-flex"
                >
                    <button
                        id="hs-sidebar-footer-example-with-dropdown"
                        type="button"
                        class="w-full inline-flex shrink-0 items-center gap-x-2 p-2 text-start text-sm text-foreground rounded-md hover:bg-muted-hover focus:outline-hidden focus:bg-muted-focus"
                        aria-haspopup="menu"
                        aria-expanded="false"
                        aria-label="Dropdown"
                    >
                        <img
                            class="shrink-0 size-5 rounded-full"
                            src="https://images.unsplash.com/photo-1734122415415-88cb1d7d5dc0?q=80&w=320&h=320&auto=format&fit=facearea&facepad=3&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
                            alt="Avatar"
                        />
                        <div
                            class="hs-overlay-minified:hidden border text-nowrap flex items-center justify-center w-full"
                        >
                            Mia Hudson
                            <svg
                                class="shrink-0 size-3.5 ms-auto"
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
                                <path d="m7 15 5 5 5-5" />
                                <path d="m7 9 5-5 5 5" />
                            </svg>
                        </div>
                    </button>

                    <div
                        class="hs-overlay-minified:hidden hs-dropdown-menu hs-dropdown-open:opacity-100 w-60 transition-[opacity,margin] duration opacity-0 hidden z-20 bg-dropdown border border-dropdown-line rounded-lg shadow-lg"
                        role="menu"
                        aria-orientation="vertical"
                        aria-labelledby="hs-sidebar-footer-example-with-dropdown"
                    >
                        <div class="p-1">
                            <a
                                class="flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-dropdown-item-foreground hover:bg-dropdown-item-hover focus:outline-hidden focus:bg-dropdown-item-focus"
                                href="#"
                            >
                                My account
                            </a>
                            <a
                                class="flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-dropdown-item-foreground hover:bg-dropdown-item-hover focus:outline-hidden focus:bg-dropdown-item-focus"
                                href="#"
                            >
                                Settings
                            </a>
                            <a
                                class="flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-dropdown-item-foreground hover:bg-dropdown-item-hover focus:outline-hidden focus:bg-dropdown-item-focus"
                                href="#"
                            >
                                Billing
                            </a>
                            <a
                                class="flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-dropdown-item-foreground hover:bg-dropdown-item-hover focus:outline-hidden focus:bg-dropdown-item-focus"
                                href="#"
                            >
                                Sign out
                            </a>
                        </div>
                    </div>
                </div>
                <!-- End Account Dropdown -->
            </footer>
            <!-- End Footer -->
        </div>
    </div>
    <!-- End Sidebar -->
</template>
