<template>
    <!-- ========== HEADER ========== -->
    <header
        class="md:ms-65 xl:hs-overlay-layout-open:me-96 md:hs-overlay-minified:ms-13 transition-all duration-300 fixed top-0 inset-x-0 flex flex-wrap md:justify-start md:flex-nowrap z-48 md:z-61 bg-white dark:bg-neutral-800"
    >
        <nav
            class="px-4 sm:px-5.5 flex basis-full justify-between items-center w-full mx-auto"
        >
            <!-- Button Group -->
            <div class="flex items-center sm:gap-x-1.5 truncate">
                <!-- Sidebar Toggle (Small screen) -->
                <button
                    type="button"
                    class="md:hidden flex justify-center items-center flex-none gap-x-3 size-9 text-sm text-gray-500 dark:text-neutral-400 rounded-lg hover:bg-gray-100 dark:hover:bg-neutral-700 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-700"
                    aria-haspopup="dialog"
                    aria-expanded="true"
                    aria-controls="hs-pro-sidebar"
                    aria-label="Minify navigation"
                    data-hs-overlay="#hs-pro-sidebar"
                >
                    <!-- Left panel svg -->
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="lucide lucide-panel-left-icon lucide-panel-left"
                    >
                        <rect width="18" height="18" x="3" y="3" rx="2" />
                        <path d="M9 3v18" />
                    </svg>
                    <span class="sr-only">Sidebar Toggle</span>
                </button>
                <!-- End Sidebar Toggle -->
            </div>
            <!-- End Button Group -->

            <!-- Button Group -->
            <div class="flex items-center md:hidden sm:gap-x-1.5">
                <!-- Profile small screen Account Dropdown -->
                <div
                    class="hs-dropdown inline-flex [--strategy:absolute] [--auto-close:inside] [--placement:bottom-right] relative text-start"
                >
                    <button
                        id="hs-dnad"
                        type="button"
                        class="p-0.5 inline-flex shrink-0 items-center gap-x-3 text-start text-sm text-gray-800 dark:text-neutral-200 rounded-full hover:bg-gray-100 dark:hover:bg-neutral-700 focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-700"
                        aria-haspopup="menu"
                        aria-expanded="false"
                        aria-label="Dropdown"
                    >
                        <img
                            class="shrink-0 size-8 rounded-full"
                            src="https://images.unsplash.com/photo-1659482633369-9fe69af50bfb?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=facearea&facepad=3&w=320&h=320&q=80"
                            alt="Avatar"
                        />
                    </button>

                    <!-- Account Dropdown -->
                    <div
                        class="hs-dropdown-menu hs-dropdown-open:opacity-100 w-60 transition-[opacity,margin] duration opacity-0 hidden z-20 bg-white dark:bg-neutral-900 border border-transparent rounded-xl shadow-xl"
                        role="menu"
                        aria-orientation="vertical"
                        aria-labelledby="hs-dnad"
                    >
                        <div class="py-2 px-3.5">
                            <span
                                class="font-medium text-gray-800 dark:text-neutral-200"
                            >
                                James Collison
                            </span>
                            <p
                                class="text-sm text-gray-500 dark:text-neutral-400"
                            >
                                jamescollison@site.com
                            </p>
                            <div class="mt-1.5">
                                <a
                                    class="flex justify-center items-center gap-x-1.5 py-2 px-2.5 font-medium text-[13px] bg-blue-600 dark:bg-blue-500 border border-transparent text-white rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 focus:outline-hidden focus:bg-blue-700 dark:focus:bg-blue-600 disabled:opacity-50 disabled:pointer-events-none"
                                    href="#"
                                >
                                    Upgrade plan
                                </a>
                            </div>
                        </div>
                        <div
                            class="px-4 py-2 border-t border-gray-200 dark:border-neutral-800"
                        >
                            <!-- Switch/Toggle -->
                            <div
                                class="flex flex-wrap justify-between items-center gap-2"
                            >
                                <span
                                    class="flex-1 cursor-pointer text-sm text-gray-800 dark:text-neutral-200"
                                    >Theme</span
                                >
                                <div
                                    class="p-0.5 inline-flex cursor-pointer bg-gray-100 dark:bg-neutral-700 rounded-full"
                                >
                                    <button
                                        type="button"
                                        class="size-7 flex justify-center items-center bg-white dark:bg-neutral-800 shadow-sm text-gray-800 dark:text-white rounded-full hs-auto-mode-active:bg-transparent hs-auto-mode-active:shadow-none hs-dark-mode-active:bg-transparent hs-dark-mode-active:shadow-none"
                                        data-hs-theme-click-value="default"
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
                                            <circle cx="12" cy="12" r="4" />
                                            <path d="M12 3v1" />
                                            <path d="M12 20v1" />
                                            <path d="M3 12h1" />
                                            <path d="M20 12h1" />
                                            <path d="m18.364 5.636-.707.707" />
                                            <path d="m6.343 17.657-.707.707" />
                                            <path d="m5.636 5.636.707.707" />
                                            <path d="m17.657 17.657.707.707" />
                                        </svg>
                                        <span class="sr-only"
                                            >Default (Light)</span
                                        >
                                    </button>
                                    <button
                                        type="button"
                                        class="size-7 flex justify-center items-center text-gray-800 dark:text-white rounded-full hs-dark-mode-active:bg-gray-800 dark:hs-dark-mode-active:bg-neutral-100 hs-dark-mode-active:text-white dark:hs-dark-mode-active:text-neutral-800 hs-dark-mode-active:shadow-sm"
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
                                            />
                                        </svg>
                                        <span class="sr-only">Dark</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="size-7 flex justify-center items-center text-gray-800 dark:text-white rounded-full hs-auto-light-mode-active:bg-white dark:hs-auto-light-mode-active:bg-neutral-800 hs-auto-mode-active:shadow-sm"
                                        data-hs-theme-click-value="auto"
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
                                            <rect
                                                width="20"
                                                height="14"
                                                x="2"
                                                y="3"
                                                rx="2"
                                            />
                                            <line
                                                x1="8"
                                                x2="16"
                                                y1="21"
                                                y2="21"
                                            />
                                            <line
                                                x1="12"
                                                x2="12"
                                                y1="17"
                                                y2="21"
                                            />
                                        </svg>
                                        <span class="sr-only"
                                            >Auto (System)</span
                                        >
                                    </button>
                                </div>
                            </div>
                            <!-- End Switch/Toggle -->
                        </div>
                        <div
                            class="p-1 border-t border-gray-200 dark:border-neutral-800"
                        >
                            <a
                                class="flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 dark:text-neutral-200 hover:bg-gray-100 dark:hover:bg-neutral-800 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-800"
                                href="#"
                            >
                                <svg
                                    class="shrink-0 mt-0.5 size-4"
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
                                Profile
                            </a>
                            <a
                                class="flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 dark:text-neutral-200 hover:bg-gray-100 dark:hover:bg-neutral-800 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-800"
                                href="#"
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
                                        d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"
                                    />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                Settings
                            </a>
                            <a
                                class="flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 dark:text-neutral-200 hover:bg-gray-100 dark:hover:bg-neutral-800 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-800"
                                href="#"
                            >
                                <svg
                                    class="shrink-0 mt-0.5 size-4"
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
                                    <path d="m16 17 5-5-5-5" />
                                    <path d="M21 12H9" />
                                    <path
                                        d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"
                                    />
                                </svg>
                                Log out
                            </a>
                        </div>
                    </div>
                    <!-- End Account Dropdown -->
                </div>
                <!-- End Account Dropdown -->
            </div>
            <!-- End Button Group -->
        </nav>
    </header>
    <!-- ========== END HEADER ========== -->
</template>
