<script setup>
import { useVisitorMap } from "@/composables/useVisitorMap";
import { useSearchFeatureProcessing } from "@/composables/map/search/useSearchFeatureProcessing";
import { useMapSearchStates } from "@/stores/useMapSearchStates";
import { ref, onMounted, onBeforeUnmount } from "vue";
import { Link } from "@inertiajs/vue3";
import Input from "@/Components/Form/Input.vue";
import Modal from "@/Components/Modal.vue";
import Button from "@/Components/Form/Button.vue";

const props = defineProps({
    burial_records: Array,
    filters: Object,
});

const { initializeMap, cleanupMap, searchBurialRecord, clearSearch } =
    useVisitorMap();
const {
    normalizeCoordinates,
    markBurialRecordClusterPolygon,
    markBurialRecordLotPoint,
} = useSearchFeatureProcessing();
const { isOnSearchMode } = useMapSearchStates();

const mapContainer = ref(null);
const firstName = ref("");
const lastName = ref("");
const burialDate = ref("");
const showSearchModal = ref(false);

const handleClear = () => {
    firstName.value = "";
    lastName.value = "";
    burialDate.value = "";
    clearSearch();
};

const performSearch = () => {
    if (firstName.value && lastName.value && burialDate.value) {
        isOnSearchMode.value = true;
        searchBurialRecord(
            {
                firstName: firstName.value,
                lastName: lastName.value,
                burialDate: burialDate.value,
            },
            normalizeCoordinates,
            markBurialRecordClusterPolygon,
            markBurialRecordLotPoint
        );
        showSearchModal.value = false;
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

                <Button
                    v-if="!isOnSearchMode"
                    aria-haspopup="dialog"
                    aria-expanded="false"
                    aria-controls="hs-search-modal"
                    data-hs-overlay="#hs-search-modal"
                >
                    Search
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
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                </Button>

                <button
                    v-if="isOnSearchMode"
                    type="button"
                    @click="handleClear"
                    class="flex items-center justify-center p-3 bg-white dark:bg-neutral-900 border border-red-300 dark:border-red-700 rounded-lg shadow-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition"
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
                        class="text-red-500 dark:text-red-600"
                    >
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <Teleport to="body">
            <Modal id="hs-search-modal">
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
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                </template>

                <template v-slot:main>
                    <h3
                        class="text-2xl font-bold text-green-600 dark:text-green-400 mb-4"
                    >
                        Search Burial Record
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label
                                for="firstName"
                                class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2 text-left"
                            >
                                First Name
                            </label>
                            <Input
                                id="firstName"
                                v-model="firstName"
                                placeholder="Enter first name"
                                class="!max-w-none"
                            />
                        </div>

                        <div>
                            <label
                                for="lastName"
                                class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2 text-left"
                            >
                                Last Name
                            </label>
                            <Input
                                id="lastName"
                                v-model="lastName"
                                placeholder="Enter last name"
                                class="!max-w-none"
                            />
                        </div>

                        <div>
                            <label
                                for="burialDate"
                                class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2 text-left"
                            >
                                Burial Date
                            </label>
                            <Input
                                id="burialDate"
                                v-model="burialDate"
                                type="date"
                                class="!max-w-none"
                            />
                        </div>
                    </div>
                </template>

                <template v-slot:footer>
                    <button
                        type="button"
                        class="w-full py-3 text-sm font-semibold text-green-600 dark:text-green-400 hover:bg-green-500/10 transition rounded-lg"
                        data-hs-overlay="#hs-search-modal"
                        @click="performSearch"
                    >
                        Search
                    </button>
                </template>
            </Modal>
        </Teleport>
    </section>
</template>
