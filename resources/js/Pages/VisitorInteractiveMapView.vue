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

const { initializeMap, cleanupMap } = useVisitorMap();
const { fetchSuggestions, fetchClusterByBurialId, clearSearch } = useSearch();
const { search, suggestions, isOnSearchMode } = useMapSearchStates();

const mapContainer = ref(null);
const phaseModalFeature = ref(null);
const clusterModalFeature = ref(null);
const lotModalFeature = ref(null);
const clusterIdForModal = ref(null);
const featureForModal = ref(null);

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
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
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
                        (suggestion) => fetchClusterByBurialId(suggestion.burial_id)
                    "
                    @clear-search="clearSearch"
                />
            </div>
        </div>
    </section>
</template>
