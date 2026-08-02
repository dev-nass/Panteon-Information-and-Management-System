<script setup>
import { onMounted, onBeforeUnmount, watch, computed } from "vue";
import { useBulkCreatePlotLot } from "@/composables/lot_management/create/useBulkCreatePlotLot";
import "leaflet/dist/leaflet.css";
import "leaflet-draw/dist/leaflet.draw.css";

const props = defineProps({
    clusterId: { type: [Number, String], default: null },
    phases: { type: Array, default: () => [] },
    row: { type: [String, Number], default: "" },
    startColumn: { type: [String, Number], default: "" },
    endColumn: { type: [String, Number], default: "" },
});

const emit = defineEmits(["bulkSave", "close"]);

const {
    lots,
    currentIndex,
    currentLot,
    isComplete,
    canSave,
    initializeMap,
    loadCluster,
    removeLot,
    reset,
    cleanupMap,
} = useBulkCreatePlotLot({
    row: props.row,
    startColumn: props.startColumn,
    endColumn: props.endColumn,
});

const plottedCount = computed(
    () => lots.value.filter((lot) => lot.coordinates !== null).length,
);

const lastLot = computed(() => lots.value[lots.value.length - 1] || null);

onMounted(() => {
    initializeMap("bulk-lot-plotting-map");

    if (props.clusterId) {
        loadCluster(props.clusterId, props.phases);
    }
});

onBeforeUnmount(() => {
    cleanupMap();
});

watch(
    () => [props.row, props.startColumn, props.endColumn],
    ([row, startColumn, endColumn]) => {
        reset(row, startColumn, endColumn);
    },
);

watch(
    () => props.clusterId,
    (newClusterId) => {
        if (newClusterId) {
            loadCluster(newClusterId, props.phases);
        }
    },
);

const lotLabel = (lot) => `${lot.column}${lot.row}`;

const lotStatusClass = (index) => {
    const lot = lots.value[index];

    if (lot && lot.coordinates) {
        return "border-green-500/50 bg-green-500/5 text-green-700 dark:text-green-400";
    }

    if (index === currentIndex.value) {
        return "border-blue-500 bg-blue-500/10 text-blue-600 dark:text-blue-400";
    }

    return "border-gray-200 dark:border-neutral-700 bg-gray-50 dark:bg-neutral-800 text-gray-500 dark:text-gray-400";
};

const formatCoords = (coordinates) => {
    if (!coordinates) return "";

    return `${coordinates.coordinates[1].toFixed(5)}, ${coordinates.coordinates[0].toFixed(5)}`;
};

const saveAll = () => {
    if (!canSave.value) return;

    emit(
        "bulkSave",
        lots.value.map(({ column, row, coordinates }) => ({
            column,
            row,
            coordinates,
        })),
    );
};

const closeModal = () => {
    emit("close");
};
</script>

<template>
    <div
        id="bulk-lot-plotting-modal"
        class="hs-overlay size-full fixed top-0 start-0 z-[2000] overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm"
        role="dialog"
        tabindex="-1"
    >
        <div
            class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-100 ease-out transition-all sm:max-w-6xl sm:w-full m-3 sm:mx-auto"
        >
            <div
                class="flex flex-col bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-700 shadow-lg rounded-xl"
            >
                <!-- Header -->
                <div
                    class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-neutral-700"
                >
                    <h3 class="font-bold text-gray-800 dark:text-white">
                        Plot Lots ({{ lots.length }})
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
                <div class="flex flex-col sm:flex-row">
                    <!-- Sidebar: lot list -->
                    <div
                        class="w-full sm:w-72 border-b sm:border-b-0 sm:border-r border-gray-200 dark:border-neutral-700"
                    >
                        <div
                            class="max-h-60 sm:max-h-[480px] overflow-y-auto p-3 space-y-2"
                        >
                            <div
                                v-for="(lot, index) in lots"
                                :key="lot.column + lot.row"
                                class="flex items-center justify-between px-3 py-2 rounded-lg border transition"
                                :class="lotStatusClass(index)"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <span v-if="lot.coordinates">✓</span>
                                    <span v-else>○</span>
                                    <span class="font-medium">
                                        {{ lotLabel(lot) }}
                                    </span>
                                    <span
                                        v-if="
                                            index === currentIndex &&
                                            !lot.coordinates
                                        "
                                        class="shrink-0 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-blue-500 text-white"
                                    >
                                        Plotting
                                    </span>
                                    <span
                                        v-if="lot.coordinates"
                                        class="text-xs text-gray-500 dark:text-gray-400 truncate"
                                    >
                                        {{ formatCoords(lot.coordinates) }}
                                    </span>
                                </div>
                                <button
                                    v-if="lot.coordinates"
                                    type="button"
                                    @click="removeLot(index)"
                                    class="shrink-0 size-6 inline-flex justify-center items-center rounded-full text-gray-500 dark:text-gray-400 hover:bg-red-500/10 hover:text-red-500 transition"
                                    title="Un-plot lot"
                                >
                                    <svg
                                        class="shrink-0 size-3"
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
                        </div>
                    </div>

                    <!-- Map -->
                    <div class="flex-1 p-4">
                        <div
                            id="bulk-lot-plotting-map"
                            class="w-full h-[480px] rounded-lg border border-gray-300 dark:border-neutral-600"
                        ></div>
                        <p
                            class="mt-3 text-sm text-gray-600 dark:text-gray-400"
                        >
                            📍 Select the marker tool, then click on the map to
                            plot the active lot. It will automatically advance
                            to the next lot.
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex justify-between items-center gap-x-2 py-3 px-4 border-t border-gray-200 dark:border-neutral-700"
                >
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <template v-if="currentLot && lastLot">
                            Plotting: {{ lotLabel(currentLot) }} of
                            {{ lotLabel(lastLot) }}
                        </template>
                        <template v-else> All lots plotted ✓ </template>
                    </div>
                    <div class="flex items-center gap-x-2">
                        <button
                            type="button"
                            @click="closeModal"
                            class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-700"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="saveAll"
                            :disabled="!canSave"
                            class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Save All Lots ({{ plottedCount }}/{{ lots.length }})
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
