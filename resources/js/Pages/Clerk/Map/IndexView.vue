<script setup>
import { useMap } from "@/composables/useMap";
import { ref, onMounted, onBeforeMount, onBeforeUnmount, computed } from "vue";

import Dashboard from "@/Layouts/Dashboard.vue";
import DeceasedRecordTable from "@/Pages/Clerk/DeceasedRecords/IndexView.vue";
import Input from "@/Components/Form/Input.vue";
import { Link } from "@inertiajs/vue3";
import { forEach } from "lodash";

const { initializeMap, cleanupMap } = useMap();

const mapContainer = ref(null);
const toggleMap = ref(true);

// NOTE: toggle map and table view (out for now since we ended up separating the MAP and TABLE)
// const toggleMapEvent = () => {
//     toggleMap.value = !toggleMap.value;
//     if (toggleMap.value) {
//         cleanupMap();
//         setTimeout(() => initializeMap(mapContainer.value), 0);
//     } else {
//         // Switched to table view - need to reinitialize Preline
//         setTimeout(() => {
//             if (window.HSStaticMethods) {
//                 window.HSStaticMethods.autoInit();
//             }
//         }, 0);
//     }
// };

const modalFeature = ref(null);
const selectedBurial = ref(null);
const searchTerm = ref("");

// Definition of global function using 'window' API
window.openUndergroundModal = function (feature, layerId) {
    const modalBody = document.querySelector(
        "#hs-scroll-inside-body-modal .p-4",
    );

    modalBody.innerHTML = `
        <strong>Lot: ${feature.properties.lot_id}</strong><br>
        Section: ${feature.properties.section}<br>
        Type: ${feature.properties.lot_type}<br>
        Status: ${feature.properties.status}<br>
        Fullname: ${feature.properties.deceased_record?.full_name ?? "N/A"}
    `;

    HSOverlay.open("#hs-scroll-inside-body-modal");
};

// Definition of global function for apartment and comlubarium lot using 'window' API
window.openApartmentModal = function (feature) {
    modalFeature.value = feature;
    selectedBurial.value = null;

    HSOverlay.open("#hs-scroll-inside-body-modal");
};

const filteredBurials = computed(() => {
    if (!modalFeature.value?.properties?.burials) return [];

    const term = searchTerm.value.toLowerCase().trim();
    if (!term) return modalFeature.value.properties.burials;

    return modalFeature.value.properties.burials.filter((burial) => {
        const fullName = burial.deceased?.full_name?.toLowerCase() ?? "";
        const burialDate = burial.burial_date?.toLowerCase() ?? "";

        return fullName.includes(term) || burialDate.includes(term);
    });
});

defineOptions({
    layout: Dashboard,
});

onMounted(() => {
    initializeMap(mapContainer.value);
});

onBeforeUnmount(() => {
    cleanupMap();
});
</script>

<template>
    <Teleport to="body">
        <!-- FULL PRELINE MODAL SHELL -->
        <div
            id="hs-scroll-inside-body-modal"
            class="hs-overlay hidden size-full fixed top-0 start-0 z-2000 overflow-x-hidden overflow-y-auto pointer-events-none"
            role="dialog"
            tabindex="-1"
            aria-labelledby="hs-scroll-inside-body-modal-label"
        >
            <div
                class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto h-[calc(100%-3.5rem)] min-h-[calc(100%-3.5rem)] flex items-center"
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
                        <template v-if="modalFeature && !selectedBurial">
                            <!-- Search input -->
                            <input
                                v-model="searchTerm"
                                type="search"
                                placeholder="Search by name or burial date..."
                                class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500"
                            />
                            <div class="text-sm space-y-1">
                                <p>
                                    <strong>Lot:</strong>
                                    {{ modalFeature.properties.lot_id }}
                                </p>
                                <p>
                                    <strong>Type:</strong>
                                    {{ modalFeature.properties.lot_type }}
                                </p>
                                <p>
                                    <strong>Occupants:</strong>
                                    {{
                                        modalFeature.properties.burials
                                            ?.length ?? 0
                                    }}
                                </p>
                                <p>
                                    <strong>Status:</strong>
                                    {{ modalFeature.properties.status }}
                                </p>
                            </div>

                            <!-- BURIAL GRID -->
                            <div
                                class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-3"
                            >
                                <button
                                    v-for="burial in filteredBurials"
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
                                            Burial Record #{{
                                                selectedBurial.id
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <!-- DETAILS -->
                                <div
                                    class="mt-5 grid grid-cols-2 gap-4 text-sm"
                                >
                                    <div>
                                        <span
                                            class="text-gray-500 dark:text-gray-400"
                                        >
                                            Burial Date
                                        </span>
                                        <div class="font-medium">
                                            {{
                                                selectedBurial.burial_date ??
                                                "N/A"
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
                                                selectedBurial.imported_by
                                                    ?.name ?? "N/A"
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
    </Teleport>

    <section id="map-wrapper" class="relative w-full" style="height: 98vh">
        <div v-if="toggleMap" key="map" class="h-full w-full">
            <!-- Map container -->
            <div
                ref="mapContainer"
                id="map"
                class="h-full w-full focus:outline-none"
            ></div>
        </div>

        <div class="absolute top-2 inset-x-0 flex justify-between z-888 px-4">
            <Input placeholder="Full name" type="search" />

            <div class="flex gap-x-2">
                <!--- ISSUE: Change this into offcanvas or modal button --->
                <!--- NOTE: Filter button  --->
                <div
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
                        class="lucide lucide-funnel-plus-icon lucide-funnel-plus text-green-500 dark:text-green-600"
                    >
                        <path
                            d="M13.354 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14v6a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341l1.218-1.348"
                        />
                        <path d="M16 6h6" />
                        <path d="M19 3v6" />
                    </svg>
                </div>

                <!--- NOTE: Toggle table view button --->

                <Link
                    :href="route('clerk.deceased_records.index')"
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
            </div>
        </div>

        <div class="absolute bottom-5 inset-x-0 flex justify-end z-999 px-4">
            <div class="flex gap-x-2">
                <!--- ISSUE: Change this a button that on and off polygon, and change the element to be button --->
                <!--- NOTE: Toggle polygon button --->
                <div
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
                        class="lucide lucide-eye-icon lucide-eye text-green-500 dark:text-green-600"
                    >
                        <path
                            d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"
                        />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </div>
            </div>
        </div>
    </section>
</template>
