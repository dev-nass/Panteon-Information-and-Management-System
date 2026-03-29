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
        id="hs-cluster-modal"
        class="hs-overlay hidden size-full fixed top-0 start-0 z-[2000] overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm pointer-events-none"
        role="dialog"
        tabindex="-1"
        aria-labelledby="hs-cluster-modal-label"
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
                        id="hs-cluster-modal-label"
                        class="font-bold text-gray-800 dark:text-white"
                    >
                        Cluster Details
                    </h3>

                    <button
                        type="button"
                        class="size-8 inline-flex justify-center items-center rounded-full bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md border border-white/20 dark:border-white/10 text-gray-700 dark:text-neutral-200 hover:bg-white/60 dark:hover:bg-neutral-700/60 transition"
                        data-hs-overlay="#hs-cluster-modal"
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
                                        class="flex items-center justify-center size-14 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400"
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
                                                width="18"
                                                height="18"
                                                rx="2"
                                            />
                                            <path d="M3 9h18" />
                                            <path d="M9 21V9" />
                                        </svg>
                                    </div>

                                    <div>
                                        <h3
                                            class="text-lg font-semibold text-blue-600 dark:text-blue-400"
                                        >
                                            {{
                                                feature.properties?.name ||
                                                "Unknown"
                                            }}
                                        </h3>

                                        <p
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            Cluster ID #{{
                                                feature.properties?.cluster_id
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        @click="
                                            emit(
                                                'viewOnTable',
                                                feature.properties?.cluster_id
                                            )
                                        "
                                        class="px-3 py-1.5 text-sm font-medium rounded-lg transition bg-blue-500/10 text-blue-400 border-transparent hover:bg-blue-500/20 hover:border-blue-500/40 hover:text-blue-600 dark:hover:text-blue-300"
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
                                        Phase
                                    </span>
                                    <div class="font-medium">
                                        {{ feature.properties.phase }}
                                    </div>
                                </div>
                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Cluster Name
                                    </span>
                                    <div class="font-medium">
                                        {{ feature.properties?.name || "N/A" }}
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Type
                                    </span>
                                    <div class="font-medium">
                                        {{ feature.properties?.type || "N/A" }}
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Occupants
                                    </span>
                                    <div class="font-medium">
                                        {{
                                            feature.properties?.occupied_lots ||
                                            0
                                        }}
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Total Capacity
                                    </span>
                                    <div class="font-medium">
                                        {{
                                            feature.properties?.total_lots || 0
                                        }}
                                        lots
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
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
