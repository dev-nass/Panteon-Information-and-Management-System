<script setup>
import { useVisitorMap } from "@/composables/useVisitorMap";
import { useSearchFeatureProcessing } from "@/composables/map/search/useSearchFeatureProcessing";
import { useMapSearchStates } from "@/stores/useMapSearchStates";
import { ref, onMounted, onBeforeUnmount } from "vue";
import { Link } from "@inertiajs/vue3";
import Input from "@/Components/Form/Input.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    burial_records: Array,
    filters: Object,
});

const { initializeMap, cleanupMap, searchBurialRecord, clearSearch } = useVisitorMap();
const { normalizeCoordinates, markBurialRecordClusterPolygon, markBurialRecordLotPoint } = useSearchFeatureProcessing();
const { isOnSearchMode } = useMapSearchStates();
const mapContainer = ref(null);
const search = ref(props.filters.search || "");

const handleSearch = () => {
    if (isOnSearchMode.value) {
        // Clear search
        search.value = "";
        clearSearch();
    } else {
        // Perform search
        if (search.value) {
            isOnSearchMode.value = true;
            searchBurialRecord(search.value, normalizeCoordinates, markBurialRecordClusterPolygon, markBurialRecordLotPoint);
        }
    }
};

onMounted(() => {
    initializeMap(mapContainer.value);
});

onBeforeUnmount(() => {
    cleanupMap();
});
</script>

<template>
    <section class="relative w-full" style="height: 100vh">
        <div class="h-full w-full">
            <div
                ref="mapContainer"
                id="map"
                class="h-full w-full focus:outline-none"
            ></div>
        </div>

        <div class="absolute top-4 left-4 right-4 z-888">
            <div class="flex items-center gap-2 max-w-2xl">
                <Link
                    :href="route('visitor.index')"
                    class="flex items-center justify-center p-3 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg shadow-lg hover:bg-gray-50 dark:hover:bg-neutral-800 transition"
                >
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
                        class="text-green-500 dark:text-green-600"
                    >
                        <path
                            d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"
                        />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg>
                </Link>

                <div class="flex-1">
                    <Input
                        v-model="search"
                        placeholder="firstname-lastname+date (YYYY-MM-DD)"
                        @keyup.enter="handleSearch"
                        class="shadow-lg !max-w-none"
                    />
                </div>

                <button
                    type="button"
                    @click="handleSearch"
                    class="flex items-center justify-center p-3 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg shadow-lg hover:bg-gray-50 dark:hover:bg-neutral-800 transition"
                >
                    <svg
                        v-if="!isOnSearchMode"
                        xmlns="http://www.w3.org/2000/svg"
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="text-green-500 dark:text-green-600"
                    >
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    <svg
                        v-else
                        xmlns="http://www.w3.org/2000/svg"
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="text-red-500 dark:text-red-600"
                    >
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>

                <button
                    type="button"
                    class="flex items-center justify-center p-3 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg shadow-lg hover:bg-gray-50 dark:hover:bg-neutral-800 transition"
                    aria-haspopup="dialog"
                    aria-expanded="false"
                    aria-controls="hs-search-instructions"
                    data-hs-overlay="#hs-search-instructions"
                >
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
                        class="text-green-500 dark:text-green-600"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 16v-4" />
                        <path d="M12 8h.01" />
                    </svg>
                </button>
            </div>
        </div>

        <Teleport to="body">
            <Modal id="hs-search-instructions">
                <template v-slot:header>
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
                        class="text-green-500 dark:text-green-600"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                        <path d="M12 17h.01" />
                    </svg>
                </template>

                <template v-slot:main>
                    <h3
                        class="text-2xl font-bold text-green-600 dark:text-green-400 mb-4"
                    >
                        Search Instructions
                    </h3>

                    <div class="space-y-4 text-gray-600 dark:text-neutral-300">
                        <p>
                            Use the combined search format to find burial
                            records:
                        </p>

                        <div
                            class="bg-gray-50 dark:bg-neutral-800 p-4 rounded-lg border border-gray-200 dark:border-neutral-700"
                        >
                            <p class="font-mono text-sm mb-2">
                                <span class="text-green-600 dark:text-green-400"
                                    >firstname-lastname+burial_date</span
                                >
                            </p>
                        </div>

                        <div class="space-y-2">
                            <p
                                class="font-semibold text-gray-800 dark:text-neutral-200"
                            >
                                Examples:
                            </p>
                            <ul class="list-disc list-inside space-y-1 ml-2">
                                <li>
                                    <code
                                        class="text-sm bg-gray-100 dark:bg-neutral-700 px-2 py-1 rounded"
                                        >john-doe+2024-01-15</code
                                    >
                                </li>
                                <li>
                                    <code
                                        class="text-sm bg-gray-100 dark:bg-neutral-700 px-2 py-1 rounded"
                                        >maria-santos+2023-12-25</code
                                    >
                                </li>
                            </ul>
                        </div>

                        <div
                            class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 p-3 rounded-lg"
                        >
                            <p
                                class="text-sm text-yellow-800 dark:text-yellow-400"
                            >
                                <strong>Note:</strong> Use hyphens (-) between
                                first and last name, and plus (+) before the
                                date. Date format must be YYYY-MM-DD.
                            </p>
                        </div>
                    </div>
                </template>

                <template v-slot:footer>
                    <button
                        type="button"
                        class="w-full py-3 text-sm font-semibold text-green-600 dark:text-green-400 hover:bg-green-500/10 transition rounded-lg"
                        data-hs-overlay="#hs-search-instructions"
                    >
                        Got it
                    </button>
                </template>
            </Modal>
        </Teleport>
    </section>
</template>
