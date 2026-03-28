<script setup>
import { useMap } from "@/composables/useMap";
import {
    ref,
    onMounted,
    onBeforeMount,
    onBeforeUnmount,
    computed,
    nextTick,
} from "vue";
import { forEach } from "lodash";

import Dashboard from "@/Layouts/Dashboard.vue";
import Input from "@/Components/Form/Input.vue";
import { Link } from "@inertiajs/vue3";
import ClusterModal from "@/Components/Map/ClusterModal.vue";
import Modal from "@/Components/Modal.vue";
import Button from "@/Components/Form/Button.vue";
import Search from "@/Components/Map/Search.vue";
import Switch from "@/Components/Switch.vue";

import { useMapStates } from "@/stores/useMapStates";
import { useMapSearchStates } from "@/stores/useMapSearchStates";

import { useSearch } from "@/composables/map/useSearch";

// states
const {
    phaseVisibility,
    clusterVisibility,
    uniqueTypes,
    toggleMapFeaturesState,
    mode,
    context,
} = useMapStates();
const { search, suggestions, isOnSearchMode } = useMapSearchStates();

// compsable
const { initializeMap, cleanupMap, toggleMapFeatures, togglePhaseVisibility } =
    useMap();
const {
    fetchSuggestions,
    fetchClusterByBurialId,
    showSearchResult,
    clearSearch,
} = useSearch();

const mapContainer = ref(null);
const modalFeature = ref(null);

// TODO: remove this
// console.log(modalFeature.value);

// Definition of global function using 'window' API

/**
 * Description: Definition of a global function for apartment, comlabrium and search
 * result lot using 'window' API
 */
window.openLotModal = function (feature) {
    modalFeature.value = feature;
    // console.log("Modal feature", modalFeature.value.lots);

    HSOverlay.open("#hs-scroll-inside-body-modal");
};

const setViewMode = () => {
    mode.value = "view";
    context.value = "burial";
};

const setManageMode = (type) => {
    mode.value = "manage";
    context.value = type;
};

/**
 * Styling
 */
const activeBtn = "px-3 py-2 text-sm rounded-lg bg-green-500 text-white";

const inactiveBtn =
    "px-3 py-2 text-sm rounded-lg bg-gray-100 dark:bg-neutral-700 text-gray-500";

defineOptions({
    layout: Dashboard,
});

onMounted(() => {
    initializeMap(mapContainer.value);

    setTimeout(() => {
        document
            .querySelectorAll("#hs-cookies")
            .forEach((el) => HSOverlay.open(el));
    });
});

onBeforeUnmount(() => {
    cleanupMap();
    document
        .querySelectorAll("#hs-scroll-inside-body-modal")
        .forEach((el) => HSOverlay.close(el));
});
</script>

<template>
    <section id="map-wrapper" class="relative w-full" style="height: 98vh">
        <!--- NOTE: Uncomment this later -->
        <!-- <Teleport to="body"> -->
        <ClusterModal
            :feature="modalFeature"
            @view-path="(burialId) => fetchClusterByBurialId(burialId)"
        />
        <!--     <Modal> -->
        <!--         <template v-slot:header> -->
        <!--             <svg -->
        <!--                 xmlns="http://www.w3.org/2000/svg" -->
        <!--                 width="24" -->
        <!--                 height="24" -->
        <!--                 viewBox="0 0 24 24" -->
        <!--                 fill="none" -->
        <!--                 stroke="currentColor" -->
        <!--                 stroke-width="2" -->
        <!--                 stroke-linecap="round" -->
        <!--                 stroke-linejoin="round" -->
        <!--                 class="lucide lucide-info-icon lucide-info" -->
        <!--             > -->
        <!--                 <circle cx="12" cy="12" r="10" /> -->
        <!--                 <path d="M12 16v-4" /> -->
        <!--                 <path d="M12 8h.01" /> -->
        <!--             </svg> -->
        <!--         </template> -->
        <!---->
        <!--         <template v-slot:main> -->
        <!--             <h3 -->
        <!--                 id="hs-cookies-label" -->
        <!--                 class="-mt-2 text-2xl font-bold text-green-600 dark:text-green-400" -->
        <!--             > -->
        <!--                 Notice -->
        <!--             </h3> -->
        <!---->
        <!--             <p class="text-gray-600 dark:text-neutral-300 max-w-sm"> -->
        <!--                 Slowly zoom in the map to see the markings and polygon -->
        <!--             </p> -->
        <!--         </template> -->
        <!--         <template v-slot:footer> -->
        <!--             <button -->
        <!--                 type="button" -->
        <!--                 class="w-full py-3 text-sm font-semibold text-green-600 dark:text-green-400 hover:bg-green-500/10 transition" -->
        <!--                 data-hs-overlay="#hs-cookies" -->
        <!--             > -->
        <!--                 Got it -->
        <!--             </button> -->
        <!--         </template> -->
        <!--     </Modal> -->
        <!-- </Teleport> -->

        <!-- Main Content Start -->
        <div class="h-full w-full">
            <!-- Map container -->
            <div
                ref="mapContainer"
                id="map"
                class="h-full w-full focus:outline-none"
            ></div>
        </div>

        <div class="absolute top-2 inset-x-0 flex justify-between z-888 px-4">
            <!-- TODO: Fix the show search result still missing its implementation -->
            <Search
                v-model="search"
                :suggestions="suggestions"
                :isOnSearch="isOnSearchMode"
                @input="fetchSuggestions"
                @select-suggestion="
                    (suggestion) => fetchClusterByBurialId(suggestion.burial_id)
                "
                @clear-search="clearSearch"
            />

            <div class="flex gap-x-2">
                <!--- ISSUE: Change this into offcanvas or modal button --->
                <!--- NOTE: Filter button  --->
                <Button
                    aria-haspopup="dialog"
                    aria-expanded="false"
                    aria-controls="hs-filter"
                    data-hs-overlay="#hs-filter"
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
                    <Modal id="hs-filter">
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
                            <div class="space-y-6 mt-2">
                                <!-- MODE -->
                                <div>
                                    <p
                                        class="text-xs text-gray-400 mb-2 uppercase"
                                    >
                                        Mode
                                    </p>

                                    <div class="grid grid-cols-2 gap-2">
                                        <button
                                            @click="setViewMode"
                                            :class="
                                                mode === 'view'
                                                    ? activeBtn
                                                    : inactiveBtn
                                            "
                                        >
                                            View
                                        </button>

                                        <button
                                            @click="setManageMode('lot')"
                                            :class="
                                                mode === 'manage'
                                                    ? activeBtn
                                                    : inactiveBtn
                                            "
                                        >
                                            Manage
                                        </button>
                                    </div>
                                </div>

                                <!-- ===================== -->
                                <!-- 👁️ VIEW MODE (FILTERS) -->
                                <!-- ===================== -->
                                <div
                                    v-if="mode === 'view'"
                                    class="grid grid-cols-2 gap-y-3 max-h-96 overflow-y-auto scrollbar-hide space-y-3 py-2"
                                >
                                    <Switch
                                        :model-value="phaseVisibility"
                                        @update:model-value="
                                            togglePhaseVisibility
                                        "
                                        label="Phases"
                                        size="sm"
                                    />

                                    <Switch
                                        v-if="uniqueTypes.length > 0"
                                        v-for="type in uniqueTypes"
                                        :key="type"
                                        :model-value="
                                            clusterVisibility.get(type)
                                        "
                                        @update:model-value="
                                            toggleMapFeatures(type)
                                        "
                                        :label="type"
                                        size="sm"
                                    />
                                </div>

                                <!-- ===================== -->
                                <!-- 🛠️ MANAGE MODE -->
                                <!-- ===================== -->
                                <div v-else class="space-y-4">
                                    <div>
                                        <p
                                            class="text-xs text-gray-400 mb-2 uppercase"
                                        >
                                            Manage Level
                                        </p>

                                        <div class="grid grid-cols-3 gap-2">
                                            <button
                                                @click="setManageMode('phase')"
                                                :class="
                                                    context === 'phase'
                                                        ? activeBtn
                                                        : inactiveBtn
                                                "
                                            >
                                                Phase
                                            </button>

                                            <button
                                                @click="
                                                    setManageMode('cluster')
                                                "
                                                :class="
                                                    context === 'cluster'
                                                        ? activeBtn
                                                        : inactiveBtn
                                                "
                                            >
                                                Cluster
                                            </button>

                                            <button
                                                @click="setManageMode('lot')"
                                                :class="
                                                    context === 'lot'
                                                        ? activeBtn
                                                        : inactiveBtn
                                                "
                                            >
                                                Lot
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- STATUS INDICATOR -->
                                <div
                                    class="text-xs text-gray-500 border-t pt-3"
                                >
                                    <span class="font-medium">Current:</span>
                                    {{ mode }} /
                                    <span v-if="mode === 'view'">burial</span>
                                    <span v-else>{{ context }}</span>
                                </div>
                            </div>
                        </template>
                    </Modal>
                </Teleport>

                <!--- NOTE: Toggle table view button --->
                <Link
                    :href="route('clerk.burial_records.index')"
                    class="flex items-center justify-center py-2 px-3 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg shadow-md transition"
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
                        class="lucide lucide-arrow-right-left-icon lucide-arrow-right-left text-green-500 dark:text-green-600"
                    >
                        <path d="m16 3 4 4-4 4" />
                        <path d="M20 7H4" />
                        <path d="m8 21-4-4 4-4" />
                        <path d="M4 17h16" />
                    </svg>
                </Link>
                <!-- END: Toggle table view -->
            </div>
        </div>

        <div class="absolute bottom-5 inset-x-0 flex justify-end z-999 px-4">
            <div class="flex gap-x-2">
                <!--- ISSUE: Change this a button that on and off polygon, and change the element to be button --->
                <!--- NOTE: Toggle polygon button --->
                <Button
                    v-if="uniqueTypes.length > 0"
                    @click="toggleMapFeatures()"
                >
                    <svg
                        v-if="toggleMapFeaturesState"
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="lucide lucide-eye-icon lucide-eye text-green-500 dark:text-green-400"
                    >
                        <path
                            d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"
                        />
                        <circle cx="12" cy="12" r="3" />
                    </svg>

                    <svg
                        v-else
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="lucide lucide-eye-off-icon lucide-eye-off text-green-500 dark:text-green-600"
                    >
                        <path
                            d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"
                        />
                        <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242" />
                        <path
                            d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"
                        />
                        <path d="m2 2 20 20" />
                    </svg>
                </Button>
            </div>
        </div>
    </section>
</template>
