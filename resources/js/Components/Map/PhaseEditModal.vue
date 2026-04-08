<script setup>
import { onMounted, onBeforeUnmount } from "vue";
import { useEditPhasePlot } from "@/composables/lot_management/edit/useEditPhasePlot";
import PlottingModalWrapper from "./PlottingModalWrapper.vue";
import "leaflet/dist/leaflet.css";
import "leaflet-draw/dist/leaflet.draw.css";

const props = defineProps({
    existingCoordinates: { type: [String, Object], default: null },
});

const emit = defineEmits(["coordinatesSet", "close"]);

const { coordinates, initializeMap, cleanupMap, getCoordinates } =
    useEditPhasePlot();

onMounted(() => {
    initializeMap("phase-edit-map", props.existingCoordinates);
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
</script>

<template>
    <PlottingModalWrapper
        title="Edit Phase Boundary"
        instruction="📐 Edit the phase boundary by dragging the vertices or use the polygon tool to redraw."
        map-id="phase-edit-map"
        :coordinates="coordinates"
        :coordinate-label="
            coordinates
                ? `Phase boundary set (${
                      coordinates.coordinates[0].length - 1
                  } points)`
                : null
        "
        @save="saveCoordinates"
        @close="emit('close')"
    />
</template>
