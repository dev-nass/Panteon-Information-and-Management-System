<script setup>
import { useMap } from "@/composables/clerk/useMap";
import { ref, onMounted, onBeforeMount, onBeforeUnmount } from "vue";

import Dashboard from "@/Layouts/Dashboard.vue";
import DeceasedRecordTable from "@/Pages/Clerk/DeceasedRecords/IndexView.vue";
import Input from "@/Components/Form/Input.vue";

const { initializeMap, cleanupMap } = useMap();

const mapContainer = ref(null);
const toggleMap = ref(true);

// NOTE: toggle map and table view
const toggleMapEvent = () => {
    toggleMap.value = !toggleMap.value;
    if (toggleMap.value) {
        cleanupMap();
        setTimeout(() => initializeMap(mapContainer.value), 0);
    } else {
        // Switched to table view - need to reinitialize Preline
        setTimeout(() => {
            if (window.HSStaticMethods) {
                window.HSStaticMethods.autoInit();
            }
        }, 0);
    }
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
    <section id="map-wrapper" class="relative" style="height: 98vh">
        <div v-if="toggleMap" key="map" class="h-full w-full">
            <!-- Map container -->
            <div ref="mapContainer" id="map" class="h-full w-full"></div>
        </div>

        <DeceasedRecordTable
            v-else
            key="table"
            @toggleTable="toggleMapEvent"
            data-aos="zoom-out"
        />

        <div
            v-if="toggleMap"
            class="absolute top-2 inset-x-0 flex justify-between z-999 px-4"
        >
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
                <button
                    @click="toggleMapEvent"
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
                </button>
            </div>
        </div>

        <div
            v-if="toggleMap"
            class="absolute bottom-5 inset-x-0 flex justify-end z-999 px-4"
        >
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
