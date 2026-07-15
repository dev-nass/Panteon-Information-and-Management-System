<script setup>
import { useVisitorMap } from "@/composables/useVisitorMap";
import { useSearch } from "@/composables/map/search/useSearch";
import { useMapSearchStates } from "@/stores/useMapSearchStates";
import { ref, onMounted, onBeforeUnmount } from "vue";
import { Link } from "@inertiajs/vue3";

import Search from "@/Components/Map/Search.vue";
import BurialRecordModal from "@/Components/Map/BurialRecordModal.vue";
import PhaseModal from "@/Components/Map/PhaseModal.vue";
import ClusterModal from "@/Components/Map/ClusterModal.vue";
import LotModal from "@/Components/Map/LotModal.vue";
import Modal from "@/Components/Modal.vue";
import Button from "@/Components/Form/Button.vue";
import Switch from "@/Components/Switch.vue";

import { useMapStates } from "@/stores/useMapStates";
import { useMap } from "@/composables/useMap";
import JunctionLandMarkModal from "@/Components/Map/JunctionLandMarkModal.vue";

const { initializeMap, cleanupMap } = useVisitorMap();
const { toggleMapFeatures, togglePhaseVisibility } = useMap();
const { phaseVisibility, clusterVisibility, uniqueTypes } = useMapStates();
const { fetchSuggestions, fetchClusterByBurialId, clearSearch } = useSearch();
// clearSearch from useSearch handles both searchResultLayer + isOnSearchMode reset
const { search, suggestions, isOnSearchMode } = useMapSearchStates();

const mapContainer = ref(null);
const phaseModalFeature = ref(null);
const clusterModalFeature = ref(null);
const lotModalFeature = ref(null);
const clusterIdForModal = ref(null);
const featureForModal = ref(null);

// Junction modal state
const junctionModalData = ref({
    junctionId: null,
    junctionNumber: null,
    junctionType: null,
});

/**
 * Description: Global function to open junction modal
 */
window.openJunctionLandMarkModal = function (
    junctionId,
    junctionNumber,
    junctionType,
) {
    junctionModalData.value = {
        junctionId,
        junctionNumber,
        junctionType,
    };
    HSOverlay.open("#junction-modal");
};

window.openPhaseModal = function (feature) {
    phaseModalFeature.value = feature;
    HSOverlay.open("#hs-phase-modal");
};

window.openClusterModal = function (feature) {
    clusterModalFeature.value = feature;
    HSOverlay.open("#hs-cluster-modal");
};

window.openLotDetailsModal = function (feature) {
    lotModalFeature.value = feature;
    HSOverlay.open("#hs-lot-modal");
};

window.openBurialRecordModal = function (clusterIdOrFeature) {
    if (typeof clusterIdOrFeature === "number") {
        clusterIdForModal.value = clusterIdOrFeature;
        featureForModal.value = null;
    } else {
        featureForModal.value = clusterIdOrFeature;
        clusterIdForModal.value = null;
    }
    HSOverlay.open("#hs-scroll-inside-body-modal");
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
        <BurialRecordModal
            :cluster-id="clusterIdForModal"
            :feature="featureForModal"
            @view-path="(burialId) => fetchClusterByBurialId(burialId)"
        />
        <PhaseModal :feature="phaseModalFeature" />
        <ClusterModal :feature="clusterModalFeature" />
        <LotModal :feature="lotModalFeature" />
        <JunctionLandMarkModal
            :junction-id="junctionModalData.junctionId"
            :junction-number="junctionModalData.junctionNumber"
            :junction-type="junctionModalData.junctionType"
        />

        <div class="h-full w-full">
            <div
                ref="mapContainer"
                id="map"
                class="h-full w-full focus:outline-none"
            ></div>
        </div>

        <div class="absolute top-4 left-4 right-4 z-888">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 flex-1 max-w-2xl">
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

                    <Search
                        v-model="search"
                        :suggestions="suggestions"
                        :isOnSearch="isOnSearchMode"
                        placeholder="Search deceased name..."
                        @input="fetchSuggestions"
                        @select-suggestion="
                            (suggestion) =>
                                fetchClusterByBurialId(suggestion.burial_id)
                        "
                        @clear-search="clearSearch"
                    />
                </div>

                <Button
                    aria-haspopup="dialog"
                    aria-expanded="false"
                    aria-controls="hs-visitor-filter"
                    data-hs-overlay="#hs-visitor-filter"
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
                        class="lucide lucide-funnel-plus-icon lucide-funnel-plus text-green-500 dark:text-green-600"
                    >
                        <path
                            d="M13.354 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14v6a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341l1.218-1.348"
                        />
                        <path d="M16 6h6" />
                        <path d="M19 3v6" />
                    </svg>
                </Button>

                <Teleport to="body">
                    <Modal id="hs-visitor-filter">
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
                                class="lucide lucide-sliders-horizontal-icon lucide-sliders-horizontal"
                            >
                                <path d="M10 5H3" />
                                <path d="M12 19H3" />
                                <path d="M14 3v4" />
                                <path d="M16 17v4" />
                                <path d="M21 12h-9" />
                                <path d="M21 19h-5" />
                                <path d="M21 5h-7" />
                                <path d="M8 10v4" />
                                <path d="M8 12H3" />
                            </svg>
                        </template>
                        <template v-slot:main>
                            <div
                                class="grid grid-cols-2 gap-y-3 max-h-96 overflow-y-auto scrollbar-hide space-y-3 py-2 mt-2"
                            >
                                <Switch
                                    :model-value="phaseVisibility"
                                    @update:model-value="togglePhaseVisibility"
                                    label="Phases"
                                    size="sm"
                                />

                                <Switch
                                    v-if="uniqueTypes.length > 0"
                                    v-for="type in uniqueTypes"
                                    :key="type"
                                    :model-value="clusterVisibility.get(type)"
                                    @update:model-value="
                                        toggleMapFeatures(type)
                                    "
                                    :label="type"
                                    size="sm"
                                />
                            </div>
                        </template>
                    </Modal>
                </Teleport>
            </div>
        </div>
    </section>
</template>
