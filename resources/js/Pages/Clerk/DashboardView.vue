<script setup>
import Dashboard from "@/Layouts/Dashboard.vue";

defineOptions({
    layout: Dashboard,
});
</script>
<template>
    This is the dashboard map page

    <button
        type="button"
        class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-700 dark:focus:bg-neutral-700"
        aria-haspopup="dialog"
        aria-expanded="false"
        aria-controls="hs-basic-modal"
        data-hs-overlay="#hs-basic-modal"
    >
        Open modal
    </button>

    <div
        id="hs-basic-modal"
        class="hs-overlay hidden size-full fixed top-0 start-0 z-60 overflow-x-hidden overflow-y-auto pointer-events-none"
        role="dialog"
        tabindex="-1"
        aria-labelledby="hs-basic-modal-label"
    >
        <div
            class="hs-overlay-open:opacity-100 hs-overlay-open:duration-500 opacity-0 transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto"
        >
            <div
                class="flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl pointer-events-auto dark:bg-neutral-800 dark:border-neutral-700 dark:shadow-neutral-700/70"
            >
                <div
                    class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-neutral-700"
                >
                    <h3
                        id="hs-basic-modal-label"
                        class="font-bold text-gray-800 dark:text-white"
                    >
                        Modal title
                    </h3>
                    <button
                        type="button"
                        class="flex justify-center items-center size-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-neutral-700 dark:focus:bg-neutral-700"
                        aria-label="Close"
                        data-hs-overlay="#hs-basic-modal"
                    >
                        <span class="sr-only">Close</span>
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
                            <path d="M18 6 6 18"></path>
                            <path d="m6 6 12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-4 overflow-y-auto">
                    <p class="mt-1 text-gray-800 dark:text-neutral-400">
                        This is a wider card with supporting text below as a
                        natural lead-in to additional content.
                    </p>
                </div>
                <div
                    class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200 dark:border-neutral-700"
                >
                    <button
                        type="button"
                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-700 dark:focus:bg-neutral-700"
                        data-hs-overlay="#hs-basic-modal"
                    >
                        Close
                    </button>
                    <a
                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                        href="#"
                    >
                        Save changes
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
