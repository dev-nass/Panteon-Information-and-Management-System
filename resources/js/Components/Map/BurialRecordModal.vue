<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import { route } from "ziggy-js";

const props = defineProps({
    clusterId: { type: Number, default: null },
    feature: { type: Object, default: null },
});

const fetchedFeature = ref(null);
const isLoading = ref(false);
const searchTerm = ref("");
const selectedBurial = ref(null);
// Pagination state
const ITEMS_PER_PAGE = 25;
const currentPage = ref(1);

const emit = defineEmits(["viewPath"]);

// Use feature prop if provided (search mode), otherwise fetch by clusterId
const activeFeature = computed(() => props.feature || fetchedFeature.value);

// Fetch cluster data when clusterId changes (only if feature is not provided)
watch(
    () => props.clusterId,
    async (newClusterId) => {
        // Don't fetch if feature is already provided (search mode)
        if (props.feature) {
            return;
        }

        if (!newClusterId) {
            fetchedFeature.value = null;
            return;
        }

        searchTerm.value = "";
        selectedBurial.value = null;
        isLoading.value = true;

        try {
            const response = await fetch(
                route("api.map.cluster.burials", { clusterId: newClusterId })
            );
            const data = await response.json();
            fetchedFeature.value = data.data;
        } catch (error) {
            console.error("Error fetching cluster burial records:", error);
        } finally {
            isLoading.value = false;
        }
    },
    { immediate: true }
);

// Reset state when feature prop changes
watch(
    () => props.feature,
    () => {
        searchTerm.value = "";
        selectedBurial.value = null;
    }
);

const filteredBurials = computed(() => {
    if (!activeFeature.value?.lots) return [];

    const allBurials = activeFeature.value.lots.flatMap((lot) =>
        (lot.burial_records || []).map((burial) => ({
            ...burial,
            lot: lot.lot,
        }))
    );

    const term = searchTerm.value.toLowerCase().trim();
    if (!term) return allBurials;

    return allBurials.filter((burial) => {
        return (
            burial.deceased?.first_name?.toLowerCase().includes(term) ||
            burial.deceased?.last_name?.toLowerCase().includes(term) ||
            burial.burial?.date?.toLowerCase().includes(term)
        );
    });
});

// Pagination
// Reset page when search term or feature changes
watch([searchTerm, activeFeature], () => {
    currentPage.value = 1;
});

const totalPages = computed(() =>
    Math.ceil(filteredBurials.value.length / ITEMS_PER_PAGE)
);

const paginatedBurials = computed(() => {
    const start = (currentPage.value - 1) * ITEMS_PER_PAGE;
    return filteredBurials.value.slice(start, start + ITEMS_PER_PAGE);
});
</script>

<template>
    <div
        id="hs-scroll-inside-body-modal"
        class="hs-overlay hidden size-full fixed top-0 start-0 z-[2000] overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm pointer-events-none"
        role="dialog"
        tabindex="-1"
        aria-labelledby="hs-scroll-inside-body-modal-label"
    >
        <div
            class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-3xl sm:w-full m-3 sm:mx-auto h-[calc(100%-3.5rem)] min-h-[calc(100%-3.5rem)] flex items-center"
        >
            <div
                class="max-h-full overflow-hidden flex flex-col bg-white/70 dark:bg-neutral-900/70 backdrop-blur-xl border border-white/20 dark:border-white/10 shadow-lg shadow-gray-200/50 dark:shadow-black/50 rounded-2xl pointer-events-auto w-full"
            >
                <!-- Header -->
                <div
                    class="flex justify-between items-center py-3 px-4 border-b border-white/20 dark:border-white/10 bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md"
                >
                    <h3
                        id="hs-scroll-inside-body-modal-label"
                        class="font-bold text-gray-800 dark:text-white"
                    >
                        Cluster Details
                    </h3>

                    <button
                        type="button"
                        class="size-8 inline-flex justify-center items-center rounded-full bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md border border-white/20 dark:border-white/10 text-gray-700 dark:text-neutral-200 hover:bg-white/60 dark:hover:bg-neutral-700/60 transition"
                        data-hs-overlay="#hs-scroll-inside-body-modal"
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
                    <!-- LOADING SKELETON -->
                    <template v-if="isLoading">
                        <div class="animate-pulse space-y-4">
                            <!-- Skeleton Search -->
                            <div
                                class="h-10 bg-gray-300/50 dark:bg-gray-700/50 rounded-lg"
                            ></div>

                            <!-- Skeleton Info -->
                            <div class="space-y-2">
                                <div
                                    class="h-4 bg-gray-300/50 dark:bg-gray-700/50 rounded w-3/4"
                                ></div>
                                <div
                                    class="h-4 bg-gray-300/50 dark:bg-gray-700/50 rounded w-1/2"
                                ></div>
                                <div
                                    class="h-4 bg-gray-300/50 dark:bg-gray-700/50 rounded w-2/3"
                                ></div>
                                <div
                                    class="h-4 bg-gray-300/50 dark:bg-gray-700/50 rounded w-1/2"
                                ></div>
                            </div>

                            <!-- Skeleton Grid -->
                            <div
                                class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-3"
                            >
                                <div
                                    v-for="i in 6"
                                    :key="i"
                                    class="h-20 bg-gray-300/50 dark:bg-gray-700/50 rounded-xl"
                                ></div>
                            </div>
                        </div>
                    </template>

                    <!-- LIST VIEW -->
                    <template v-else-if="activeFeature && !selectedBurial">
                        <!-- Search -->
                        <input
                            v-model="searchTerm"
                            type="search"
                            placeholder="Search by name or burial date..."
                            class="w-full px-3 py-2 text-sm rounded-lg border border-white/30 dark:border-white/10 bg-white/60 dark:bg-neutral-900/60 backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-green-500"
                        />

                        <div class="text-sm space-y-1">
                            <p>
                                <strong>Cluster:</strong>
                                {{ activeFeature.cluster?.properties?.name }}
                            </p>
                            <p>
                                <strong>Type:</strong>
                                {{ activeFeature.cluster?.properties?.type }}
                            </p>
                            <p>
                                <strong>Occupants:</strong>
                                {{
                                    activeFeature.cluster?.properties
                                        ?.occupied_lots
                                }}
                                /
                                {{
                                    activeFeature.cluster?.properties
                                        ?.total_lots
                                }}
                            </p>
                            <p>
                                <strong>Status:</strong>
                                {{ activeFeature.cluster?.properties?.status }}
                            </p>
                        </div>

                        <!-- BURIAL GRID -->
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-3">
                            <button
                                v-for="burial in paginatedBurials"
                                :key="burial.id"
                                @click.stop="selectedBurial = burial"
                                class="flex items-center gap-3 text-left p-3 rounded-xl border border-white/30 dark:border-white/10 bg-white/60 dark:bg-neutral-800/60 backdrop-blur-md shadow-sm hover:border-green-500 hover:shadow-md transition w-full cursor-pointer"
                            >
                                <div
                                    class="flex items-center justify-center size-10 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="20"
                                        height="20"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <circle cx="12" cy="8" r="5" />
                                        <path d="M20 21a8 8 0 0 0-16 0" />
                                    </svg>
                                </div>

                                <div>
                                    <div
                                        class="font-semibold text-green-600 dark:text-green-400 text-sm"
                                    >
                                        {{
                                            burial.deceased?.first_name +
                                                " " +
                                                burial.deceased?.last_name ??
                                            "Unknown"
                                        }}
                                    </div>

                                    <div
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        Lot: {{ burial.lot?.properties?.column
                                        }}{{ burial.lot?.properties?.row }}
                                    </div>
                                </div>
                            </button>
                        </div>

                        <!-- PAGINATION -->
                        <div
                            v-if="totalPages > 1"
                            class="flex items-center justify-between pt-2 border-t border-white/20 dark:border-white/10"
                        >
                            <span
                                class="text-xs text-gray-500 dark:text-gray-400"
                            >
                                Page {{ currentPage }} of {{ totalPages }}
                            </span>

                            <div class="flex gap-2">
                                <button
                                    @click="currentPage--"
                                    :disabled="currentPage === 1"
                                    class="px-3 py-1 text-sm rounded-lg border border-white/30 dark:border-white/10 bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md disabled:opacity-40 disabled:cursor-not-allowed hover:border-green-500 transition"
                                >
                                    Prev
                                </button>

                                <button
                                    @click="currentPage++"
                                    :disabled="currentPage === totalPages"
                                    class="px-3 py-1 text-sm rounded-lg border border-white/30 dark:border-white/10 bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md disabled:opacity-40 disabled:cursor-not-allowed hover:border-green-500 transition"
                                >
                                    Next
                                </button>
                            </div>
                        </div>

                        <p
                            v-if="filteredBurials.length === 0"
                            class="text-sm text-gray-400 text-center py-4"
                        >
                            No burials found.
                        </p>
                    </template>

                    <!-- DETAIL VIEW -->
                    <template v-if="selectedBurial">
                        <button
                            @click="selectedBurial = null"
                            class="flex items-center gap-1 text-sm text-green-600 dark:text-green-400 hover:underline"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="size-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 19l-7-7 7-7"
                                />
                            </svg>
                            Back to occupants
                        </button>

                        <div
                            class="p-5 rounded-xl border border-white/30 dark:border-white/10 bg-white/60 dark:bg-neutral-800/60 backdrop-blur-md"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="flex items-center justify-center size-14 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
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
                                            <circle cx="12" cy="8" r="5" />
                                            <path d="M20 21a8 8 0 0 0-16 0" />
                                        </svg>
                                    </div>

                                    <div>
                                        <h3
                                            class="text-lg font-semibold text-green-600 dark:text-green-400"
                                        >
                                            {{
                                                selectedBurial.deceased
                                                    ?.first_name +
                                                    " " +
                                                    selectedBurial.deceased
                                                        ?.last_name ?? "Unknown"
                                            }}
                                        </h3>

                                        <p
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            Burial Record #{{
                                                selectedBurial.burial?.id
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <!-- Primary -->
                                    <Link
                                        :href="
                                            route(
                                                'clerk.burial_records.show',
                                                selectedBurial.burial?.id
                                            )
                                        "
                                        class="px-3 py-1.5 text-sm font-medium rounded-lg transition bg-green-500/10 text-green-400 border-transparent hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-600 dark:hover:text-green-300"
                                    >
                                        View More
                                    </Link>

                                    <!-- Secondary -->
                                    <button
                                        @click="
                                            emit(
                                                'viewPath',
                                                selectedBurial.burial?.id
                                            )
                                        "
                                        class="px-3 py-1.5 text-sm font-medium rounded-lg text-green-600 dark:text-green-400 hover:underline transition"
                                    >
                                        View Path
                                    </button>
                                </div>
                            </div>

                            <div class="mt-5 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Burial Date
                                    </span>
                                    <div class="font-medium">
                                        {{
                                            selectedBurial.burial?.date ?? "N/A"
                                        }}
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Lot Location
                                    </span>
                                    <div class="font-medium">
                                        {{
                                            selectedBurial.lot?.properties
                                                ?.column +
                                                selectedBurial.lot?.properties
                                                    ?.row ?? "N/A"
                                        }}
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Imported By
                                    </span>
                                    <div class="font-medium">
                                        {{
                                            selectedBurial.imported_by?.name ??
                                            "N/A"
                                        }}
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Burial ID
                                    </span>
                                    <div class="font-medium">
                                        {{ selectedBurial.burial?.id }}
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
