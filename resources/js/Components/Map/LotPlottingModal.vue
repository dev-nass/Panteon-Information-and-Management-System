<script setup>
import { onMounted, onBeforeUnmount, watch } from "vue";
import { useCreatePlotLot } from "@/composables/lot_management/create/useCreatePlotLot";
import "leaflet/dist/leaflet.css";
import "leaflet-draw/dist/leaflet.draw.css";

const props = defineProps({
    clusterId: { type: [Number, String], default: null },
    phases: { type: Array, default: () => [] },
});

const emit = defineEmits(["coordinatesSet", "close"]);

const { coordinates, initializeMap, loadCluster, cleanupMap, getCoordinates } =
    useCreatePlotLot();

onMounted(() => {
    initializeMap("lot-plotting-map");

    if (props.clusterId) {
        loadCluster(props.clusterId, props.phases);
    }
});

onBeforeUnmount(() => {
    cleanupMap();
});

const saveCoordinates = () => {
    const coords = getCoordinates();
    if (!coords) {
        alert("Please place a marker on the map first");
        return;
    }
    emit("coordinatesSet", coords);
    closeModal();
};

const closeModal = () => {
    emit("close");
};

// Watch for cluster changes
watch(
    () => props.clusterId,
    (newClusterId) => {
        if (newClusterId) {
            loadCluster(newClusterId, props.phases);
        }
    }
);
</script>

<template>
    <div
        id="lot-plotting-modal"
        class="hs-overlay size-full fixed top-0 start-0 z-[2000] overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm"
        role="dialog"
        tabindex="-1"
    >
        <div
            class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-100 ease-out transition-all sm:max-w-4xl sm:w-full m-3 sm:mx-auto"
        >
            <div
                class="flex flex-col bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-700 shadow-lg rounded-xl"
            >
                <!-- Header -->
                <div
                    class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-neutral-700"
                >
                    <h3 class="font-bold text-gray-800 dark:text-white">
                        Plot Lot Location
                    </h3>
                    <button
                        type="button"
                        @click="closeModal"
                        class="size-8 inline-flex justify-center items-center rounded-full bg-gray-100 dark:bg-neutral-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-neutral-600 transition"
                    >
                        <svg
                            class="shrink-0 size-4"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-4">
                    <div class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                        <p>
                            📍 Click the marker tool and place it on the map to
                            set the lot location.
                        </p>
                    </div>

                    <!-- Map Container -->
                    <div
                        id="lot-plotting-map"
                        class="w-full h-[500px] rounded-lg border border-gray-300 dark:border-neutral-600"
                    ></div>

                    <div
                        v-if="coordinates"
                        class="mt-3 text-sm text-green-600 dark:text-green-400"
                    >
                        ✓ Coordinates set:
                        {{ coordinates.coordinates[1].toFixed(6) }},
                        {{ coordinates.coordinates[0].toFixed(6) }}
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200 dark:border-neutral-700"
                >
                    <button
                        type="button"
                        @click="closeModal"
                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-700"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="saveCoordinates"
                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-green-600 text-white hover:bg-green-700"
                    >
                        Save Coordinates
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
