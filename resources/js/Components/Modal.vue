<script setup></script>

<template>
    <div
        id="hs-cookies"
        class="hs-overlay hidden size-full fixed top-0 start-0 z-[2000] overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm"
        role="dialog"
        tabindex="-1"
        aria-labelledby="hs-cookies-label"
    >
        <div
            class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto"
        >
            <div
                class="relative w-full max-h-full flex flex-col bg-white/70 dark:bg-neutral-900/70 backdrop-blur-xl border border-white/20 dark:border-white/10 rounded-2xl shadow-lg shadow-gray-200/50 dark:shadow-black/50"
            >
                <!-- Close button -->
                <div class="absolute top-3 end-3">
                    <button
                        type="button"
                        class="size-8 inline-flex justify-center items-center rounded-full bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md border border-white/20 dark:border-white/10 text-gray-700 dark:text-neutral-200 hover:bg-white/60 dark:hover:bg-neutral-700/60 transition"
                        data-hs-overlay="#hs-cookies"
                    >
                        <svg
                            class="size-4"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div
                    class="p-10 flex flex-col items-center gap-y-4 text-center"
                >
                    <div
                        class="flex items-center justify-center size-14 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
                    >
                        <slot name="header" />
                    </div>

                    <slot name="main" />

                    <!-- SAMPLE  -->
                    <!-- <h3 -->
                    <!--     id="hs-cookies-label" -->
                    <!--     class="-mt-2 text-2xl font-bold text-green-600 dark:text-green-400" -->
                    <!-- > -->
                    <!--     Unsaved Changes -->
                    <!-- </h3> -->
                    <!---->
                    <!-- <p class="text-gray-600 dark:text-neutral-300 max-w-sm"> -->
                    <!--     Are you sure you want to discard your changes? This -->
                    <!--     action cannot be undone. -->
                    <!-- </p> -->
                </div>

                <!-- Buttons -->
                <div class="flex border-t border-white/20 dark:border-white/10">
                    <slot name="footer" />

                    <!-- Sample button used as reference -->
                    <!-- <button -->
                    <!--     type="button" -->
                    <!--     class="w-full py-3 text-sm font-semibold text-green-600 dark:text-green-400 hover:bg-green-500/10 transition" -->
                    <!--     data-hs-overlay="#hs-cookies" -->
                    <!-- > -->
                    <!--     Cancel -->
                    <!-- </button> -->

                    <!-- <button -->
                    <!--     type="button" -->
                    <!--     class="w-full py-3 text-sm font-semibold text-red-500 hover:bg-red-500/10 transition" -->
                    <!--     @click="confirmDiscard" -->
                    <!-- > -->
                    <!--     Discard Changes -->
                    <!-- </button> -->
                </div>
            </div>
        </div>
    </div>
</template>
