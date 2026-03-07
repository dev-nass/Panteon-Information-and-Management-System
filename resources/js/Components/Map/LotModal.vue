<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
    feature: { type: Object, default: null },
});

const searchTerm = ref("");
const selectedBurial = ref(null);
// Pagination state
const ITEMS_PER_PAGE = 25;
const currentPage = ref(1);

// Reset state whenever feature changes (new lot clicked)
watch(
    () => props.feature,
    () => {
        searchTerm.value = "";
        selectedBurial.value = null;
    },
);

const filteredBurials = computed(() => {
    if (!props.feature?.properties?.burials) return [];
    const term = searchTerm.value.toLowerCase().trim();
    if (!term) return props.feature.properties.burials;

    return props.feature.properties.burials.filter((burial) => {
        return (
            burial.deceased?.full_name?.toLowerCase().includes(term) ||
            burial.burial_date?.toLowerCase().includes(term)
        );
    });
});

// Pagination
// Reset page when search term or feature changes
watch([searchTerm, () => props.feature], () => {
    currentPage.value = 1;
});

const totalPages = computed(() =>
    Math.ceil(filteredBurials.value.length / ITEMS_PER_PAGE),
);

const paginatedBurials = computed(() => {
    const start = (currentPage.value - 1) * ITEMS_PER_PAGE;
    return filteredBurials.value.slice(start, start + ITEMS_PER_PAGE);
});
</script>

<template>
    <div
        id="hs-scroll-inside-body-modal"
        class="hs-overlay hidden size-full fixed top-0 start-0 z-2000 overflow-x-hidden overflow-y-auto pointer-events-none"
        role="dialog"
        tabindex="-1"
        aria-labelledby="hs-scroll-inside-body-modal-label"
    >
        <div
            class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-2xl sm:w-full m-3 sm:mx-auto h-[calc(100%-3.5rem)] min-h-[calc(100%-3.5rem)] flex items-center"
        >
            <div
                class="max-h-full overflow-hidden flex flex-col bg-white border shadow-sm rounded-xl pointer-events-auto dark:bg-neutral-800 dark:border-neutral-700 w-full"
            >
                <!-- Header -->
                <div
                    class="flex justify-between items-center py-3 px-4 border-b dark:border-neutral-700"
                >
                    <h3 class="font-bold text-gray-800 dark:text-white">
                        Lot Details
                    </h3>
                    <button
                        type="button"
                        class="size-8 inline-flex justify-center items-center rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400"
                        data-hs-overlay="#hs-scroll-inside-body-modal"
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
                    </button>
                </div>

                <!-- YOUR EXISTING BODY CONTENT (moved inside here) -->
                <div
                    class="p-4 overflow-y-auto space-y-4 text-gray-700 dark:text-gray-300"
                >
                    <!-- LIST VIEW -->
                    <template v-if="feature && !selectedBurial">
                        <!-- Search input -->
                        <input
                            v-model="searchTerm"
                            type="search"
                            placeholder="Search by name or burial date..."
                            class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-neutral-900 focus:outline-none focus:ring-2 focus:ring-green-500"
                        />
                        <div class="text-sm space-y-1">
                            <p>
                                <strong>Lot:</strong>
                                {{ feature.properties.lot_id }}
                            </p>
                            <p>
                                <strong>Type:</strong>
                                {{ feature.properties.lot_type }}
                            </p>
                            <p>
                                <strong>Occupants:</strong>
                                {{ feature.properties.burials?.length ?? 0 }} /
                                {{ feature.properties.total_capacity }}
                            </p>
                            <p>
                                <strong>Status:</strong>
                                {{ feature.properties.status }}
                            </p>
                        </div>

                        <!-- BURIAL GRID -->
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-3">
                            <button
                                v-for="burial in paginatedBurials"
                                :key="burial.id"
                                @click.stop="selectedBurial = burial"
                                class="flex items-center gap-3 text-left p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-neutral-800 shadow-sm hover:border-green-500 hover:shadow-md transition w-full cursor-pointer"
                            >
                                <!-- SVG AVATAR -->
                                <div
                                    class="flex items-center justify-center size-10 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="size-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M16 14a4 4 0 10-8 0m8 0v1a4 4 0 01-8 0v-1m8 0H8"
                                        />
                                    </svg>
                                </div>

                                <div>
                                    <div
                                        class="font-semibold text-green-600 dark:text-green-400 text-sm"
                                    >
                                        {{
                                            burial.deceased?.full_name ??
                                            "Unknown"
                                        }}
                                    </div>

                                    <div
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        Burial:
                                        {{ burial.burial_date ?? "N/A" }}
                                    </div>
                                </div>
                            </button>
                        </div>

                        <!-- PAGINATION -->
                        <div
                            v-if="totalPages > 1"
                            class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-neutral-700"
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
                                    class="px-3 py-1 text-sm rounded-lg border border-gray-200 dark:border-neutral-700 disabled:opacity-40 disabled:cursor-not-allowed hover:border-green-500 transition"
                                >
                                    Prev
                                </button>
                                <button
                                    @click="currentPage++"
                                    :disabled="currentPage === totalPages"
                                    class="px-3 py-1 text-sm rounded-lg border border-gray-200 dark:border-neutral-700 disabled:opacity-40 disabled:cursor-not-allowed hover:border-green-500 transition"
                                >
                                    Next
                                </button>
                            </div>
                        </div>

                        <!-- No results message -->
                        <p
                            v-if="filteredBurials.length === 0"
                            class="text-sm text-gray-400 text-center py-4"
                        >
                            No burials found.
                        </p>
                    </template>

                    <!-- DETAIL VIEW -->
                    <template v-if="selectedBurial">
                        <!-- BACK BUTTON -->
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

                        <!-- PROFILE CARD -->
                        <div
                            class="p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-neutral-800"
                        >
                            <div class="flex items-center gap-4">
                                <!-- LARGE AVATAR -->
                                <div
                                    class="flex items-center justify-center size-14 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="size-7"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M16 14a4 4 0 10-8 0m8 0v1a4 4 0 01-8 0v-1m8 0H8"
                                        />
                                    </svg>
                                </div>

                                <div>
                                    <h3
                                        class="text-lg font-semibold text-green-600 dark:text-green-400"
                                    >
                                        {{
                                            selectedBurial.deceased
                                                ?.full_name ?? "Unknown"
                                        }}
                                    </h3>

                                    <p
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        Burial Record #{{ selectedBurial.id }}
                                    </p>
                                </div>
                            </div>

                            <!-- DETAILS -->
                            <div class="mt-5 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Burial Date
                                    </span>
                                    <div class="font-medium">
                                        {{
                                            selectedBurial.burial_date ?? "N/A"
                                        }}
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Date of Death
                                    </span>
                                    <div class="font-medium">
                                        {{
                                            selectedBurial.deceased
                                                ?.deceased_date ?? "N/A"
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
                                        {{ selectedBurial.id }}
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
