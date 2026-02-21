<script setup>
import { useMap } from "@/composables/clerk/useMap";
import { ref, onMounted, onBeforeMount, onBeforeUnmount } from "vue";

import Dashboard from "@/Layouts/Dashboard.vue";

const { initializeMap, cleanupMap } = useMap();

const mapContainer = ref(null);

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
        <div ref="mapContainer" id="map" class="h-full w-full"></div>

        <div class="absolute top-4 left-4 z-999">
            <div
                class="flex items-center gap-2 w-full px-3 py-2.5 rounded-lg bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 focus-within:border-green-500 focus-within:ring-2 focus-within:ring-green-500 transition"
            >
                <!-- Icon -->
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="19"
                    height="19"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="lucide lucide-search-icon lucide-search text-gray-400 dark:text-gray-500"
                >
                    <path d="m21 21-4.34-4.34" />
                    <circle cx="11" cy="11" r="8" />
                </svg>

                <!-- Input -->
                <input
                    type="text"
                    placeholder="Full name"
                    class="flex-1 bg-transparent text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none"
                />
            </div>
        </div>
    </section>
</template>
