<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import { route } from "ziggy-js";

const props = defineProps({
    feature: { type: Object, default: null },
});

const emit = defineEmits(["viewOnTable"]);
</script>

<template>
    <div
        id="hs-lot-modal"
        class="hs-overlay hidden size-full fixed top-0 start-0 z-[2000] overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm pointer-events-none"
        role="dialog"
        tabindex="-1"
        aria-labelledby="hs-lot-modal-label"
    >
        <div
            class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto h-[calc(100%-3.5rem)] min-h-[calc(100%-3.5rem)] flex items-center"
        >
            <div
                class="max-h-full overflow-hidden flex flex-col bg-white/70 dark:bg-neutral-900/70 backdrop-blur-xl border border-white/20 dark:border-white/10 shadow-lg shadow-gray-200/50 dark:shadow-black/50 rounded-2xl pointer-events-auto w-full"
            >
                <!-- Header -->
                <div
                    class="flex justify-between items-center py-3 px-4 border-b border-white/20 dark:border-white/10 bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md"
                >
                    <h3
                        id="hs-lot-modal-label"
                        class="font-bold text-gray-800 dark:text-white"
                    >
                        Lot Details
                    </h3>

                    <button
                        type="button"
                        class="size-8 inline-flex justify-center items-center rounded-full bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md border border-white/20 dark:border-white/10 text-gray-700 dark:text-neutral-200 hover:bg-white/60 dark:hover:bg-neutral-700/60 transition"
                        data-hs-overlay="#hs-lot-modal"
                    >
                        <svg
                            class="shrink-0 size-4"
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

                <!-- BODY -->
                <div
                    class="p-4 overflow-y-auto space-y-4 text-gray-700 dark:text-gray-300"
                >
                    <template v-if="feature">
                        <div
                            class="p-5 rounded-xl border border-white/30 dark:border-white/10 bg-white/60 dark:bg-neutral-800/60 backdrop-blur-md"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="flex items-center justify-center size-14 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="22"
                                            height="22"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <rect
                                                x="3"
                                                y="3"
                                                width="7"
                                                height="7"
                                            />
                                            <rect
                                                x="14"
                                                y="3"
                                                width="7"
                                                height="7"
                                            />
                                            <rect
                                                x="14"
                                                y="14"
                                                width="7"
                                                height="7"
                                            />
                                            <rect
                                                x="3"
                                                y="14"
                                                width="7"
                                                height="7"
                                            />
                                        </svg>
                                    </div>

                                    <div>
                                        <h3
                                            class="text-lg font-semibold text-amber-600 dark:text-amber-400"
                                        >
                                            Lot {{
                                                feature.properties?.column
                                            }}{{ feature.properties?.row }}
                                        </h3>

                                        <p
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            Lot ID #{{
                                                feature.properties?.lot_id
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        @click="
                                            emit(
                                                'viewOnTable',
                                                feature.properties?.lot_id
                                            )
                                        "
                                        class="px-3 py-1.5 text-sm font-medium rounded-lg transition bg-amber-500/10 text-amber-400 border-transparent hover:bg-amber-500/20 hover:border-amber-500/40 hover:text-amber-600 dark:hover:text-amber-300"
                                    >
                                        View on Table
                                    </button>
                                </div>
                            </div>

                            <div class="mt-5 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Location
                                    </span>
                                    <div class="font-medium">
                                        {{
                                            feature.properties?.column +
                                            feature.properties?.row
                                        }}
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Status
                                    </span>
                                    <div class="font-medium capitalize">
                                        {{
                                            feature.properties?.status || "N/A"
                                        }}
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Burial Records
                                    </span>
                                    <div class="font-medium">
                                        {{
                                            feature.properties?.burial_count ||
                                            0
                                        }}
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Column
                                    </span>
                                    <div class="font-medium">
                                        {{
                                            feature.properties?.column || "N/A"
                                        }}
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Row
                                    </span>
                                    <div class="font-medium">
                                        {{ feature.properties?.row || "N/A" }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
