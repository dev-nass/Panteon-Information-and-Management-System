<script setup>
import { useMap } from "@/composables/useMap";
import { ref, onMounted, onBeforeMount, onBeforeUnmount } from "vue";

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
window.openApartmentModal = function (feature, layerId) {
    const modalBody = document.querySelector(
        "#hs-scroll-inside-body-modal .p-4",
    );

    const burialCards = (feature.properties.burials || [])
        .map(
            (burial) => `
            <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                <div class="font-semibold text-green-600 dark:text-green-400">
                    ${burial.deceased?.full_name ?? "Unknown"}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Burial Date: ${burial.burial_date ?? "N/A"}
                </div>
            </div>
        `,
        )
        .join("");

    modalBody.innerHTML = `
        <div class="space-y-4">

            <div class="text-sm text-gray-700 dark:text-gray-300">
                <strong>Lot:</strong> ${feature.properties.lot_id} <br>
                <strong>Type:</strong> ${feature.properties.lot_type} <br>
                <strong>Status:</strong> ${feature.properties.status}
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                ${burialCards || `<div class="text-gray-400">No burial records</div>`}
            </div>

        </div>
    `;

    HSOverlay.open("#hs-scroll-inside-body-modal");
};

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
        <div
            id="hs-scroll-inside-body-modal"
            class="hs-overlay hidden fixed inset-0 z-[2000] overflow-y-auto overflow-x-hidden pointer-events-none bg-black/40 dark:bg-black/60"
            role="dialog"
            tabindex="-1"
            aria-labelledby="hs-scroll-inside-body-modal-label"
        >
            <div
                class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-2xl sm:w-full m-3 h-[calc(100%-56px)] sm:mx-auto"
            >
                <div
                    class="max-h-full flex flex-col overflow-hidden rounded-xl shadow-xl bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-700 pointer-events-auto"
                >
                    <!-- Header -->
                    <div
                        class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-neutral-700"
                    >
                        <h3
                            id="hs-scroll-inside-body-modal-label"
                            class="font-semibold text-gray-900 dark:text-gray-100"
                        >
                            Lot Information
                        </h3>

                        <button
                            type="button"
                            aria-label="Close"
                            data-hs-overlay="#hs-scroll-inside-body-modal"
                            class="size-8 inline-flex justify-center items-center rounded-full border border-gray-200 dark:border-neutral-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-neutral-800"
                        >
                            <span class="sr-only">Close</span>

                            <svg
                                class="size-4"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M18 6 6 18"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m6 6 12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div
                        class="p-4 overflow-y-auto space-y-4 text-gray-700 dark:text-gray-300"
                    >
                        <div>
                            <p><strong>Lot:</strong> 272</p>
                            <p><strong>Section:</strong> undefined</p>
                            <p><strong>Type:</strong> apartment</p>
                            <p><strong>Status:</strong> occupied</p>
                            <p><strong>Fullname:</strong> N/A</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="flex justify-end items-center gap-2 py-3 px-4 border-t border-gray-200 dark:border-neutral-700"
                    >
                        <button
                            type="button"
                            data-hs-overlay="#hs-scroll-inside-body-modal"
                            class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                            Close
                        </button>

                        <button
                            type="button"
                            class="px-3 py-2 text-sm font-medium rounded-lg bg-green-500 hover:bg-green-600 text-white"
                        >
                            Save changes
                        </button>
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
