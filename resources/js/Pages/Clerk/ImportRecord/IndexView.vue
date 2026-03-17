<script setup>
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";

defineOptions({
    layout: Dashboard,
});
</script>

<template>
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-6 mx-auto">
        <div class="flex flex-col items-center" data-aos="zoom-out">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-[650px] inline-block align-middle">
                    <div
                        class="flex flex-col gap-y-6 bg-white/60 dark:bg-neutral-900/60 backdrop-blur-xl px-6 py-6 border border-white/20 dark:border-neutral-700 rounded-xl shadow-lg overflow-hidden"
                    >
                        <!-- Header -->
                        <div class="flex gap-x-4">
                            <div
                                class="flex items-center justify-center size-12 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
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
                                    class="lucide lucide-download-icon lucide-download"
                                >
                                    <path d="M12 15V3" />
                                    <path
                                        d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"
                                    />
                                    <path d="m7 10 5 5 5-5" />
                                </svg>
                            </div>

                            <article>
                                <h1
                                    class="text-2xl font-bold text-green-600 dark:text-green-400"
                                >
                                    Import Records
                                </h1>

                                <p
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Upload CSV files to import burial and
                                    deceased records into the system.
                                </p>
                            </article>
                        </div>

                        <!-- File Upload -->
                        <div
                            data-hs-file-upload='{
"url": "/upload"
}'
                        >
                            <!-- Drop Area -->
                            <div
                                class="cursor-pointer p-[4rem] flex justify-center bg-white/50 dark:bg-neutral-900/40 border border-dashed border-gray-300 dark:border-neutral-700 rounded-xl hover:border-green-500 transition"
                                data-hs-file-upload-trigger
                            >
                                <div
                                    class="text-center flex flex-col items-center"
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
                                        class="lucide lucide-import-icon lucide-import text-green-500"
                                    >
                                        <path d="M12 3v12" />
                                        <path d="m8 11 4 4 4-4" />
                                        <path
                                            d="M8 5H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-4"
                                        />
                                    </svg>

                                    <div
                                        class="mt-4 text-sm text-gray-600 dark:text-gray-300"
                                    >
                                        <span
                                            class="font-medium text-gray-800 dark:text-gray-200"
                                        >
                                            Drop your CSV file here
                                        </span>
                                        or
                                        <span
                                            class="text-green-600 font-semibold hover:underline"
                                        >
                                            browse
                                        </span>
                                    </div>

                                    <p class="mt-1 text-xs text-gray-500">
                                        Supported format: CSV • Max size 2MB
                                    </p>
                                </div>
                            </div>

                            <!-- File Preview -->
                            <div
                                class="mt-4 space-y-2 empty:mt-0"
                                data-hs-file-upload-previews
                            ></div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-between items-center pt-2">
                            <Button data-hs-overlay="#import-logs-modal">
                                View Import Logs
                            </Button>

                            <Button :highlighted="true"> Start Import </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Logs Modal -->

    <div
        id="import-logs-modal"
        class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto"
    >
        <div
            class="hs-overlay-open:mt-7 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto"
        >
            <div
                class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-700 rounded-xl shadow-lg"
            >
                <div
                    class="flex justify-between items-center px-4 py-3 border-b dark:border-neutral-700"
                >
                    <h3 class="font-bold text-gray-800 dark:text-white">
                        Import Logs
                    </h3>

                    <button
                        type="button"
                        class="size-8 flex justify-center items-center rounded-full hover:bg-gray-100 dark:hover:bg-neutral-700"
                        data-hs-overlay="#import-logs-modal"
                    >
                        ✕
                    </button>
                </div>

                <div
                    class="p-4 space-y-3 text-sm text-gray-600 dark:text-gray-300 max-h-[300px] overflow-y-auto"
                >
                    <p>✔ Records imported successfully</p>
                    <p>⚠ Row 24 skipped (invalid date)</p>
                    <p>✔ 120 records inserted</p>
                </div>
            </div>
        </div>
    </div>
</template>
