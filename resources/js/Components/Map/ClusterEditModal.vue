<script setup>
import { onMounted, onBeforeUnmount, watch } from "vue";
import { useEditClusterPlot } from "@/composables/lot_management/edit/useEditClusterPlot";
import PlottingModalWrapper from "./PlottingModalWrapper.vue";
import "leaflet/dist/leaflet.css";
import "leaflet-draw/dist/leaflet.draw.css";

const props = defineProps({
    phaseId: { type: [Number, String], default: null },
    phases: { type: Array, default: () => [] },
    existingCoordinates: { type: [String, Object], default: null },
});

const emit = defineEmits(["coordinatesSet", "close"]);

const { coordinates, initializeMap, loadPhase, cleanupMap, getCoordinates } =
    useEditClusterPlot();

onMounted(() => {
    initializeMap("cluster-edit-map", props.existingCoordinates);

    if (props.phaseId) {
        loadPhase(props.phaseId, props.phases);
    }
});

onBeforeUnmount(() => {
    cleanupMap();
});

const saveCoordinates = () => {
    const coords = getCoordinates();
    if (!coords) {
        alert("Please draw a polygon on the map first");
        return;
    }
    emit("coordinatesSet", coords);
    emit("close");
};

watch(
    () => props.phaseId,
    (newPhaseId) => {
        if (newPhaseId) {
            loadPhase(newPhaseId, props.phases);
        }
    }
);
</script>

<template>
    <PlottingModalWrapper
        title="Edit Cluster Boundary"
        instruction="📐 Edit the cluster boundary by dragging the vertices or use the polygon tool to redraw."
        map-id="cluster-edit-map"
        :coordinates="coordinates"
        :coordinate-label="
            coordinates
                ? `Cluster boundary set (${
                      coordinates.coordinates[0].length - 1
                  } points)`
                : null
        "
        @save="saveCoordinates"
        @close="emit('close')"
    />
</template>
