<script setup>
import { Link, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { computed } from "vue";

import SidebarLink from "@/Components/Dashboard/SidebarLink.vue";

const page = usePage();
const user = computed(() => page.props.auth.user);

const handleLogout = () => {
    router.post(route("logout"));
};
</script>

<template>
    <div class="relative flex flex-col h-full max-h-full">
        <!-- Header -->
        <header class="py-3 px-4 flex justify-between items-center gap-x-2">
            <div class="-ms-2 flex items-center gap-x-1">
                <div class="md:hs-overlay-minified:hidden">
                    <Link
                        class="flex gap-x-2 justify-center font-semibold text-lg text-gray-800 dark:text-white focus:outline-hidden focus:opacity-80 hs-overlay-minified:hidden"
                        href="#"
                        aria-label="Panteon-de-Dasmariñas"
                    >
                        <img
                            class="h-9"
                            src="/public/images/dasmarinas-logo.png"
                            alt="Dasmariñas Logo"
                        />
                        <p class="mt-[2px]">panteon.</p>
                    </Link>
                </div>

                <!-- Sidebar Toggle -->
                <button
                    type="button"
                    class="hidden md:hs-overlay-minified:flex justify-center items-center flex-none gap-x-3 size-9 text-sm text-gray-500 dark:text-neutral-400 rounded-lg hover:bg-gray-100 dark:hover:bg-neutral-700 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-700"
                    aria-haspopup="dialog"
                    aria-expanded="true"
                    aria-controls="hs-pro-sidebar"
                    aria-label="Minify navigation"
                    data-hs-overlay-minifier="#hs-pro-sidebar"
                >
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

                <div
                    class="hidden sm:block md:hs-overlay-minified:hidden"
                ></div>
            </div>

            <!-- Sidebar Toggle -->
            <button
                type="button"
                class="hidden md:hs-overlay-minified:hidden md:flex justify-center items-center flex-none gap-x-3 size-9 text-sm text-gray-500 dark:text-neutral-400 rounded-lg hover:bg-gray-100 dark:hover:bg-neutral-700 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-700"
                aria-haspopup="dialog"
                aria-expanded="true"
                aria-controls="hs-pro-sidebar"
                aria-label="Minify navigation"
                data-hs-overlay-minifier="#hs-pro-sidebar"
            >
                <!--- Left Panel SVG -->
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="22"
                    height="22"
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

            <!-- Sidebar Toggle -->
            <button
                type="button"
                class="flex md:hidden justify-center items-center gap-x-3 size-6 bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 text-sm text-gray-500 dark:text-neutral-400 rounded-full hover:bg-gray-100 dark:hover:bg-neutral-700 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-100 dark:focus:bg-neutral-700"
                data-hs-overlay="#hs-pro-sidebar"
                aria-expanded="true"
            >
                <svg
                    class="shrink-0 size-3.5"
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
            <!-- End Sidebar Toggle -->
        </header>
        <!-- End Header -->

        <div class="mb-5 px-2 flex flex-col gap-y-5">
            <!-- List -->
            <ul class="flex flex-col gap-y-0.5">
                <li class="mb-3">
                    <!-- Profile big screen Account Dropdown -->
                    <div
                        class="hs-dropdown w-full inline-flex [--strategy:fixed] [--auto-close:inside] [--placement:bottom-right] relative text-start"
                    >
                        <button
                            id="hs-dnad"
                            type="button"
                            class="group relative w-full flex items-center justify-between gap-1 py-2 px-2.5 text-sm rounded-lg before:absolute before:inset-y-0 before:-start-2 before:rounded-e-full before:w-1 before:h-full text-gray-800 dark:text-neutral-200 bg-gray-100/70 dark:bg-neutral-700/70 focus:bg-gray-100/70 dark:focus:bg-neutral-700/70"
                            aria-haspopup="menu"
                            aria-expanded="false"
                            aria-label="Dropdown"
                        >
                            <div class="flex items-center gap-2">
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
                                    class="lucide lucide-user-cog-icon lucide-user-cog"
                                >
                                    <path d="M10 15H6a4 4 0 0 0-4 4v2" />
                                    <path d="m14.305 16.53.923-.382" />
                                    <path d="m15.228 13.852-.923-.383" />
                                    <path d="m16.852 12.228-.383-.923" />
                                    <path d="m16.852 17.772-.383.924" />
                                    <path d="m19.148 12.228.383-.923" />
                                    <path d="m19.53 18.696-.382-.924" />
                                    <path d="m20.772 13.852.924-.383" />
                                    <path d="m20.772 16.148.924.383" />
                                    <circle cx="18" cy="15" r="3" />
                                    <circle cx="9" cy="7" r="4" />
                                </svg>
                                <span
                                    class="truncate hs-overlay-minified:opacity-0 transition-opacity duration-300"
                                    >{{ user.first_name }}
                                    {{ user.last_name }}</span
                                >
                            </div>

                            <!--- Up down arrow svg -->
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="17"
                                height="17"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="lucide lucide-chevrons-up-down-icon lucide-chevrons-up-down"
                            >
                                <path d="m7 15 5 5 5-5" />
                                <path d="m7 9 5-5 5 5" />
                            </svg>
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
                                    {{ user.first_name }} {{ user.last_name }}
                                </span>
                                <p
                                    class="text-sm text-gray-500 dark:text-neutral-400"
                                >
                                    {{ user.email }}
                                </p>
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
                                                <path
                                                    d="m18.364 5.636-.707.707"
                                                />
                                                <path
                                                    d="m6.343 17.657-.707.707"
                                                />
                                                <path
                                                    d="m5.636 5.636.707.707"
                                                />
                                                <path
                                                    d="m17.657 17.657.707.707"
                                                />
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
                                <button
                                    @click="handleLogout"
                                    class="w-full flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-red-50 dark:focus:bg-red-900/20"
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
                                </button>
                            </div>
                        </div>
                        <!-- End Account Dropdown -->
                    </div>
                    <!-- End Account Dropdown -->
                </li>

                <span
                    class="truncate hs-overlay-minified:opacity-0 transition-opacity duration-300 block ps-2.5 mb-1 text-sm text-gray-400 dark:text-neutral-500"
                    >Main</span
                >
                <li>
                    <SidebarLink
                        :href="route('clerk.dashboard')"
                        :isActive="page.component === 'Clerk/DashboardView'"
                    >
                        <template v-slot:icon>
                            <span
                                class="-ms-[5px] flex shrink-0 justify-center items-center size-6"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="lucide lucide-gauge-icon lucide-gauge"
                                >
                                    <path d="m12 14 4-4" />
                                    <path d="M3.34 19a10 10 0 1 1 17.32 0" />
                                </svg>
                            </span>
                        </template>
                        <template v-slot:text> Dashboard </template>
                    </SidebarLink>
                </li>

                <li>
                    <SidebarLink
                        :href="route('clerk.generate_report.index')"
                        :isActive="
                            page.component === 'Clerk/GenerateReport/IndexView'
                        "
                    >
                        <template v-slot:icon>
                            <span
                                class="-ms-[5px] flex shrink-0 justify-center items-center size-6"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="lucide lucide-chart-pie-icon lucide-chart-pie"
                                >
                                    <path
                                        d="M21 12c.552 0 1.005-.449.95-.998a10 10 0 0 0-8.953-8.951c-.55-.055-.998.398-.998.95v8a1 1 0 0 0 1 1z"
                                    />
                                    <path d="M21.21 15.89A10 10 0 1 1 8 2.83" />
                                </svg>
                            </span>
                        </template>
                        <template v-slot:text> Generate Report </template>
                    </SidebarLink>
                </li>

                <li>
                    <SidebarLink
                        :href="route('clerk.map.index')"
                        :isActive="page.component === 'Clerk/Map/IndexView'"
                    >
                        <template v-slot:icon>
                            <span
                                class="-ms-[5px] flex shrink-0 justify-center items-center size-6"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="lucide lucide-map-pin-house-icon lucide-map-pin-house"
                                >
                                    <path
                                        d="M15 22a1 1 0 0 1-1-1v-4a1 1 0 0 1 .445-.832l3-2a1 1 0 0 1 1.11 0l3 2A1 1 0 0 1 22 17v4a1 1 0 0 1-1 1z"
                                    />
                                    <path
                                        d="M18 10a8 8 0 0 0-16 0c0 4.993 5.539 10.193 7.399 11.799a1 1 0 0 0 .601.2"
                                    />
                                    <path d="M18 22v-3" />
                                    <circle cx="10" cy="10" r="3" />
                                </svg>
                            </span>
                        </template>
                        <template v-slot:text> Intertactive Map </template>
                    </SidebarLink>
                </li>

                <li>
                    <SidebarLink
                        :href="route('clerk.lot_management.index')"
                        :isActive="
                            page.component === 'Clerk/LotManagement/IndexView'
                        "
                    >
                        <template v-slot:icon>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="lucide lucide-grid3x2-icon lucide-grid-3x2"
                            >
                                <path d="M15 3v18" />
                                <path d="M3 12h18" />
                                <path d="M9 3v18" />
                                <rect
                                    x="3"
                                    y="3"
                                    width="18"
                                    height="18"
                                    rx="2"
                                />
                            </svg>
                        </template>

                        <template v-slot:text> Lot Management </template>
                    </SidebarLink>
                </li>

                <li>
                    <SidebarLink
                        :href="route('clerk.burial_records.index')"
                        :isActive="
                            page.component === 'Clerk/BurialRecords/IndexView'
                        "
                    >
                        <template v-slot:icon>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="lucide lucide-table-icon lucide-table"
                            >
                                <path d="M12 3v18" />
                                <rect
                                    width="18"
                                    height="18"
                                    x="3"
                                    y="3"
                                    rx="2"
                                />
                                <path d="M3 9h18" />
                                <path d="M3 15h18" />
                            </svg>
                        </template>
                        <template v-slot:text> Burial Records </template>
                    </SidebarLink>
                </li>

                <li>
                    <SidebarLink
                        :href="route('clerk.import.index')"
                        :isActive="
                            page.component === 'Clerk/ImportRecord/IndexView'
                        "
                    >
                        <template v-slot:icon>
                            <span
                                class="-ms-[5px] flex shrink-0 justify-center items-center size-6"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="lucide lucide-import-icon lucide-import"
                                >
                                    <path d="M12 3v12" />
                                    <path d="m8 11 4 4 4-4" />
                                    <path
                                        d="M8 5H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-4"
                                    />
                                </svg>
                            </span>
                        </template>
                        <template v-slot:text> Import Records </template>
                    </SidebarLink>
                </li>
            </ul>
            <!-- End List -->
        </div>
    </div>
</template>
