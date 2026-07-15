<script setup>
import { Link, usePage, router } from "@inertiajs/vue3";
import { computed } from "vue";
import { route } from "ziggy-js";

const page = usePage();
const user = computed(() => page.props.auth.user);

const handleLogout = () => {
    router.post(route("logout"));
};
</script>

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
                        id="hs-header-profile"
                        type="button"
                        class="p-0.5 inline-flex shrink-0 items-center gap-x-2 text-start text-sm text-gray-800 dark:text-neutral-200 rounded-full hover:bg-gray-100 dark:hover:bg-neutral-700 focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-700"
                        aria-haspopup="menu"
                        aria-expanded="false"
                        aria-label="Dropdown"
                    >
                        <!-- SVG Avatar -->
                        <div class="shrink-0 size-8 rounded-full overflow-hidden bg-gray-200 dark:bg-neutral-700">
                            <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                                <defs>
                                    <linearGradient id="hdrAvatarGrad" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#d1d5db" />
                                        <stop offset="100%" stop-color="#9ca3af" />
                                    </linearGradient>
                                </defs>
                                <rect width="80" height="80" fill="url(#hdrAvatarGrad)" />
                                <ellipse cx="40" cy="72" rx="24" ry="14" fill="#6b7280" opacity="0.5" />
                                <circle cx="40" cy="32" r="17" fill="#f3f4f6" />
                                <ellipse cx="40" cy="19" rx="17" ry="10" fill="#374151" />
                                <circle cx="34" cy="32" r="2.2" fill="#374151" />
                                <circle cx="46" cy="32" r="2.2" fill="#374151" />
                                <path d="M35 39 Q40 44 45 39" stroke="#374151" stroke-width="1.5" fill="none" stroke-linecap="round" />
                            </svg>
                        </div>
                    </button>

                    <!-- Account Dropdown -->
                    <div
                        class="hs-dropdown-menu hs-dropdown-open:opacity-100 w-60 transition-[opacity,margin] duration opacity-0 hidden z-20 bg-white dark:bg-neutral-900 border border-transparent rounded-xl shadow-xl"
                        role="menu"
                        aria-orientation="vertical"
                        aria-labelledby="hs-header-profile"
                    >
                        <div class="py-2 px-3.5">
                            <span class="font-medium text-gray-800 dark:text-neutral-200">
                                {{ user.first_name }} {{ user.last_name }}
                            </span>
                            <p class="text-sm text-gray-500 dark:text-neutral-400">
                                {{ user.email }}
                            </p>
                        </div>
                        <div class="px-4 py-2 border-t border-gray-200 dark:border-neutral-800">
                            <!-- Theme Switch -->
                            <div class="flex flex-wrap justify-between items-center gap-2">
                                <span class="flex-1 cursor-pointer text-sm text-gray-800 dark:text-neutral-200">Theme</span>
                                <div class="p-0.5 inline-flex cursor-pointer bg-gray-100 dark:bg-neutral-700 rounded-full">
                                    <button type="button" class="size-7 flex justify-center items-center bg-white dark:bg-neutral-800 shadow-sm text-gray-800 dark:text-white rounded-full hs-auto-mode-active:bg-transparent hs-auto-mode-active:shadow-none hs-dark-mode-active:bg-transparent hs-dark-mode-active:shadow-none" data-hs-theme-click-value="default">
                                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="4" /><path d="M12 3v1" /><path d="M12 20v1" /><path d="M3 12h1" /><path d="M20 12h1" /><path d="m18.364 5.636-.707.707" /><path d="m6.343 17.657-.707.707" /><path d="m5.636 5.636.707.707" /><path d="m17.657 17.657.707.707" />
                                        </svg>
                                        <span class="sr-only">Default (Light)</span>
                                    </button>
                                    <button type="button" class="size-7 flex justify-center items-center text-gray-800 dark:text-white rounded-full hs-dark-mode-active:bg-gray-800 dark:hs-dark-mode-active:bg-neutral-100 hs-dark-mode-active:text-white dark:hs-dark-mode-active:text-neutral-800 hs-dark-mode-active:shadow-sm" data-hs-theme-click-value="dark">
                                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
                                        </svg>
                                        <span class="sr-only">Dark</span>
                                    </button>
                                    <button type="button" class="size-7 flex justify-center items-center text-gray-800 dark:text-white rounded-full hs-auto-light-mode-active:bg-white dark:hs-auto-light-mode-active:bg-neutral-800 hs-auto-mode-active:shadow-sm" data-hs-theme-click-value="auto">
                                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect width="20" height="14" x="2" y="3" rx="2" /><line x1="8" x2="16" y1="21" y2="21" /><line x1="12" x2="12" y1="17" y2="21" />
                                        </svg>
                                        <span class="sr-only">Auto (System)</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="p-1 border-t border-gray-200 dark:border-neutral-800">
                            <Link
                                class="flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 dark:text-neutral-200 hover:bg-gray-100 dark:hover:bg-neutral-800 focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-800"
                                :href="route('settings.profile.index')"
                            >
                                <svg class="shrink-0 mt-0.5 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                Profile
                            </Link>
                            <button
                                @click="handleLogout"
                                class="w-full flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 focus:outline-hidden focus:bg-red-50 dark:focus:bg-red-900/20"
                            >
                                <svg class="shrink-0 mt-0.5 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m16 17 5-5-5-5" />
                                    <path d="M21 12H9" />
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                </svg>
                                Log out
                            </button>
                        </div>
                    </div>
                    <!-- End Account Dropdown -->
                </div>
            </div>
            <!-- End Button Group -->
        </nav>
    </header>
    <!-- ========== END HEADER ========== -->
</template>
